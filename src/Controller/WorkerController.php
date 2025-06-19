<?php

namespace App\Controller;

use App\Controller\BaseController;
use App\Entity\DepartmentPermission;
use App\Entity\Historic;
use App\Entity\JobPermission;
use App\Entity\Permission;
use App\Entity\Worker;
use App\Form\WorkerType;
use App\Form\WorkerSearchType;
use App\Repository\WorkerRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Translation\TranslatableMessage;

#[Route(path: '/{_locale}', requirements: ['_locale' => 'es|eu|en'])]
class WorkerController extends BaseController
{

    public function __construct(
        private readonly WorkerRepository $repo, 
        private readonly EntityManagerInterface $em, 
        private readonly MailerInterface $mailer, 
        private readonly SerializerInterface $serializer)
    {
    }
    #[IsGranted('ROLE_RRHH')]
    #[Route(path: '/worker/new', name: 'worker_new')]
    public function new(Request $request) {
        $this->loadQueryParameters($request);
        $form = $this->createForm(WorkerType::class, new Worker(), [
            'new' => true,
            'locale' => $request->getLocale(),
            'isAppOwnerOnly' => $this->isGranted('ROLE_APP_OWNER') && ( !$this->isGranted('ROLE_RRHH') || !$this->isGranted('ROLE_ADMIN') ),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Worker $worker */
            $worker = $form->getData();
            $existingWorker = $this->repo->findOneBy([
                'dni' => $worker->getDni(),
            ]);
            $error = $this->checkForErrors($worker);
            if ($error) {
                return $this->renderEdit($form, null, true, false, false, $this->isGranted('ROLE_APP_OWNER'));
            }
            if ($existingWorker) {
                if ($existingWorker->getStatus() !== Worker::STATUS_DELETED ) {
                    $this->addFlash('error','worker.alreadyExists');
                    return $this->renderEdit($form, null, true, false, false, $this->isGranted('ROLE_APP_OWNER'));
                } else {
                    $existingWorker->fill($worker);
                    $worker = $existingWorker;
                    $worker->setStatus(Worker::STATUS_REVISION_PENDING);
                    $this->addJobPermissionsToWorker($worker);
                    $this->createHistoric('alta ya existente', $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
                    $this->sendMessageToBoss('Langile berriaren baimenak hautatu / Seleccione los permisos del nuevo empleado', $worker);
                    $this->addFlash('warning','worker.alreadyExistsStatusChanged');
                }
            } else {
                if ( $worker->getUsername() === null || $worker->getUsername() === '' ) {
                    $worker->setStatus(Worker::STATUS_USERNAME_PENDING);    
                } else {
                    $worker->setStatus(Worker::STATUS_REVISION_PENDING);
                }
                $this->addJobPermissionsToWorker($worker);
                $this->createHistoric('alta', $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
                $this->addFlash('success', 'worker.sent');
            }
            $this->em->persist($worker);
            $this->em->flush();
            if ( $worker->getStatus() === Worker::STATUS_USERNAME_PENDING ) {
                $this->createHistoric("pendiente de asignación de nombre de usuario", $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
                $emailContent = $this->createUsernamePendingEmailContent($worker);
                $this->sendMessage('Langile berriari ezarri erabiltzaile izena / Asigne un nombre de usuario al nuevo empleado', [$this->getParameter('mailerBCC')], $emailContent);
            } else {
                $this->sendMessageToBoss('Langile berriaren baimenak hautatu / Seleccione los permisos del nuevo empleado', $worker);                
                $this->createHistoric("enviado para validar por el responsable", $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
            }
            // Send an email to medical revisions responsible
            $html = $this->createBossRevisionPendingEmailContent($worker);
            $this->sendMessage('Langile berria gorde da / Se ha dado de alta un nuevo empleado', [$this->getParameter('mailerMM')], $html);
            return $this->redirectToRoute('worker_index');
        }

        return $this->renderEdit($form, null, true, false, false, $this->isGranted('ROLE_APP_OWNER'));
    }

    /**
     * Sends message to boss to choose the authorized applications
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/worker/{worker}/send', name: 'worker_send', methods: ['GET'])]
    public function send(Request $request, Worker $worker) {
        $this->loadQueryParameters($request);
        $this->createHistoric("Reenviado para validar por el responsable", $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
        $this->sendMessageToBoss('Langile berriaren baimenak hautatu / Seleccione los permisos del nuevo empleado', $worker);
        $this->addFlash('success', 'worker.resent');

        return $this->redirectToRoute('worker_index');
    }

    #[IsGranted('ROLE_BOSS')]
    #[Route(path: '/worker/{worker}/validate', name: 'worker_validate')]
    public function validate(Request $request, Worker $worker) {
        $this->loadQueryParameters($request);
        $form = $this->createForm(WorkerType::class, $worker,[
            'locale' => $request->getLocale(),
            'roleBossOnly' => $this->isGranted('ROLE_BOSS') && !$this->isGranted('ROLE_RRHH'),
            'isAppOwnerOnly' => $this->isGranted('ROLE_APP_OWNER') && ( !$this->isGranted('ROLE_RRHH') || !$this->isGranted('ROLE_ADMIN') ),
        ]);
        $historics = $worker->getHistorics();
        $form->handleRequest($request);
        if ( $form->isSubmitted() && $form->isValid() ) {
            /** @var Worker $worker */
            $worker = $form->getData();
            if ( $worker->getStatus() === Worker::STATUS_APPROVAL_PENDING ) {
                $this->addFlash('error','error.alreadyValidated');
                return $this->renderEdit($form, $historics, false, false, true);
            }
            if ( !$worker->checkIfUserIsAllowedBoss($this->getUser()) ) {
                $this->addFlash('error',new TranslatableMessage('error.notAllowedBoss', 
                    ['{bosses}' => implode(',',$worker->getWorkerJob()->getJob()->getBosses()->toArray())], 'messages'));
                return $this->renderEdit($form, $historics, false, false, true, $this->isGranted('ROLE_APP_OWNER'));
            }
            $worker->setStatus(Worker::STATUS_APPROVAL_PENDING);
            $worker->setValidatedBy($this->getUser());
            $this->em->persist($worker);
            $this->createHistoric("validado por el responsable", $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
            $permissions = $worker->getPermissions();
            foreach($permissions as $permission) {
                $this->sendMessageToAppOwners('Langile berriaren baimena onartu / Aprobar el permiso del nuevo empleado ', $worker, $permission, false);
            }
            $this->em->flush();
            $this->addFlash('success', 'worker.saved');
            return $this->redirectToRoute('worker_index');
        }

        return $this->renderEdit($form, $historics, false, false, true, $this->isGranted('ROLE_APP_OWNER'));
    }

    #[IsGranted('ROLE_GIZA_BALIABIDEAK')]
    #[Route(path: '/worker/{worker}/edit', name: 'worker_edit')]
    public function edit(Request $request, Worker $worker) {
        $this->loadQueryParameters($request);
        $form = $this->createForm(WorkerType::class, $worker, [
            'locale' => $request->getLocale(),
            'roleBossOnly' => $this->isGranted('ROLE_BOSS') && !$this->isGranted('ROLE_RRHH'),
            'isAppOwnerOnly' => $this->isGranted('ROLE_APP_OWNER') && ( !$this->isGranted('ROLE_RRHH') || !$this->isGranted('ROLE_ADMIN') ),
        ]);
        $historics = $worker->getHistorics();
        $form->handleRequest($request);
        if ( $form->isSubmitted() && $form->isValid() ) {
            /** @var Worker $worker */
            $worker = $form->getData();
            $error = $this->checkForErrors($worker);
            if ($error) {
                return $this->renderEdit($form, $historics, false, false);
            }
            if ( $worker->getUsername() !== null && $worker->getUsername() !== '' ) {
                $worker->setStatus(Worker::STATUS_REVISION_PENDING);
                $this->sendMessageToBoss('Langile berriaren baimenak hautatu / Seleccione los permisos del nuevo empleado', $worker);
                $this->createHistoric("enviado para validar por el responsable", $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
            } else {
                $this->createHistoric("modificación de datos", $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
            }
            $this->em->persist($worker);
            $this->em->flush();
            $this->addFlash('success', 'worker.saved');
            return $this->redirectToRoute('worker_edit', ['worker' => $worker->getId()]);
        }

        return $this->renderEdit($form, $historics, false, false, false, $this->isGranted('ROLE_ADMIN'));
    }

    #[IsGranted('ROLE_RRHH')]
    #[Route(path: '/worker/{worker}/delete', name: 'worker_delete', methods: ['GET'])]
    public function delete(Request $request, Worker $worker)
    {
        $this->loadQueryParameters($request);
        $worker->setStatus(Worker::STATUS_DELETED);
        $this->createHistoric('baja', $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
        $html = $this->createBossRevisionPendingEmailContent($worker, false, true);
        $this->sendMessage('Langile hau ezabatu egin da / El siguiente empleado se ha dado de baja', [$this->getParameter('mailerBCC')], $html);
        $this->sendMessageToUserCreators('Mesedez, langile honen erabiltzailea ezabatu / Por favor, elimine el usuario del siguiente trabajador', $worker, true);
        $this->em->flush();
        $this->addFlash('success', 'worker.deleted');

        return $this->redirectToRoute('worker_index');
    }    

    #[IsGranted('ROLE_GIZA_BALIABIDEAK')]
    #[Route(path: '/worker/{worker}', name: 'worker_show')]
    public function show(Request $request, Worker $worker) {
        $this->loadQueryParameters($request);
        $form = $this->createForm(WorkerType::class, $worker,[
            'readonly' => true,
            'locale' => $request->getLocale(),
            'isAppOwnerOnly' => $this->isGranted('ROLE_APP_OWNER') && ( !$this->isGranted('ROLE_RRHH') || !$this->isGranted('ROLE_ADMIN') ),
        ]);
        $historics = $worker->getHistorics();
        return $this->renderEdit($form, $historics, false, true, false, $this->isGranted('ROLE_APP_OWNER'));
    }

    #[IsGranted('ROLE_GIZA_BALIABIDEAK')]
    #[Route(path: '/worker', name: 'worker_index')]
    public function index(Request $request): Response
    {
        $this->loadQueryParameters($request);
        $worker = $this->fillWorkerFilter($request);
        if ( $this->isGranted('ROLE_BOSS') && !$this->isGranted('ROLE_RRHH') && $request->get('status') === null ) {
            $worker['status'] = Worker::STATUS_REVISION_PENDING;
        } else if ( $this->isGranted('ROLE_APP_OWNER') && !$this->isGranted('ROLE_ADMIN') ) {
            $worker['status'] = Worker::STATUS_APPROVAL_PENDING;
        }
        $form = $this->createForm(WorkerSearchType::class, $worker);
        $form->handleRequest($request);
        if ( $form->isSubmitted() && $form->isValid() ) {
            $worker = $form->getData();
            $this->queryParams['page'] = 1;
        }
        $workers = $this->repo->findByExample($worker);
        return $this->render('worker/index.html.twig', [
            'workers' => $workers,
            'form' => $form,
            'filters' => $this->remove_blank_filters($worker),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/worker/{worker}/historic', name: 'worker_historic_list',methods: ['GET'])]
    public function list(Worker $worker): Response
    {
        $historics = $worker->getHistorics();
        if (count($historics) === 0) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }
        return $this->render('worker/_historic_row.html.twig', [
            'historics' => $historics,
        ]);
    }   


    /** 
     *  This method is used when new Worker is created to assign permissions attached to the Department and the Job if there any.
     *  This way a new Worker on the same Deparment and Job inherits the previous worker job permissions.
    */
    private function addJobPermissionsToWorker(Worker $worker) 
    {
        // Copy department permissions to worker
        $department = $worker->getDepartment();
        $job = $worker->getWorkerJob()->getJob();
        $departmentPermissions = $department->getPermissions();
        foreach($departmentPermissions as $dp) {
            $dpCopy = DepartmentPermission::copyPermission($dp, $worker);
            $this->em->persist($dpCopy);
            $worker->addPermission($dpCopy);
        }
        // Copy job permissions to worker
        $jobPermissions = $job->getPermissions();
        foreach($jobPermissions as $jp) {
            $permissionCopy = JobPermission::copyPermission($jp, $worker);
            // If the permission is already assigned to the worker, skip it
            $this->em->persist($permissionCopy);
            $worker->addPermission($permissionCopy);
        }
    }

    private function checkForErrors(Worker $worker) 
    {
        $error = false;
        if ( $worker->getEndDate() === null && !$worker->isNoEndDate() ) {
            $this->addFlash('error','worker.endDateNotSet');
            $error = true;
        }
        if ( $worker->getEndDate() !== null && ( $worker->getEndDate() < $worker->getStartDate()) ) {
            $this->addFlash('error','worker.endDateGreaterThanStartDate');
            $error = true;
        }
        if ( $worker->getEndDate() !== null && $worker->isNoEndDate() ) {
            $this->addFlash('error','worker.cannotsetEndDateAndNoEndDate');
            $error = true;
        }
        return $error;
    }
    
    private function renderEdit(FormInterface $form, Collection|null $historics, $new = false, $readonly = true, $validate = false, $isAppOwner = false) {
        return $this->render('worker/edit.html.twig', [
            'form' => $form,
            'historics' => $historics,
            'readonly' => $readonly,
            'new' => $new,
            'validate' => $validate,
            'isAppOwnerOnly' => $isAppOwner,
        ]);        
    }

    private function fillWorkerFilter(Request $request): array {
        $worker = [];
        $worker['dni'] = $request->get('dni') ?? null;
        $worker['name'] = $request->get('name') ?? null;
        $worker['surname1'] = $request->get('surname1') ?? null;
        $worker['expedientNumber'] = $request->get('expedientNumber') ?? null;
        $worker['status'] = $request->get('status');
        return $worker;
    }    

    private function createHistoric($operation, $details, Worker|null $worker) {
        $historic = new Historic();
        $historic->fill($this->getUser(),$operation, $details, $worker);
        $this->em->persist($historic);
    }

    private function sendMessageToBoss($subject, Worker $worker) {
        if ($worker->getWorkerJob()->getJob() !== null) {
            $bosses = $worker->getWorkerJob()->getJob()->getBosses();
            $emails = [];
            foreach ($bosses as $boss) {
                if ($boss->getEmail()) {
                    $emails[] = $boss->getEmail();
                }
            }
            $html = $this->createBossRevisionPendingEmailContent($worker, true);
            $this->sendMessage($subject, $emails, $html);
        }
    }

    private function sendMessageToAppOwners($subject, Worker $worker, Permission $permission, bool $remove) {
        $application = $permission->getApplication();
        if ($application !== null && $application->getAppOwnersEmails() !== null) {
            $owners = explode(',', $application->getAppOwnersEmails());
            $userCreatorEmail = $application->getUserCreatorEmail();
            $emails = [];
            foreach ($owners as $owner) {
                $emails[] = $owner;
            }
            $email = (new Email())
                ->from($this->getParameter('mailer_from'))
                ->to(...$emails)
                ->subject($subject)
                ->html($this->renderView('worker/appOwnersMail.html.twig', [
                    'user' => $this->getUser(),
                    'worker' => $worker,
                    'permission' => $permission,
                    'remove' => $remove,
                    'appOwners' => $application->getAppOwners(),
                    'userCreatorEmail' => $userCreatorEmail,
                ])
            );
            $this->mailer->send($email);
        }
    }

    private function sendMessageToUserCreators($subject, Worker $worker, bool $remove = false) {
        foreach ($worker->getPermissions() as $permission) {
            $application = $permission->getApplication();
            if ($application !== null && $application->getUserCreatorEmail() !== null) {
                $creators = explode(',', $application->getUserCreatorEmail());
                $emails = [];
                foreach ($creators as $creator) {
                    $emails[] = $creator;
                }
                $email = (new Email())
                    ->from($this->getParameter('mailer_from'))
                    ->to(...$emails)
                    ->subject($subject)
                    ->html($this->renderView('worker/userCreatorsMail.html.twig', [
                        'user' => $this->getUser(),
                        'worker' => $worker,
                        'permission' => $permission,
                        'remove' => $remove,
                        'application' => $application,
                    ])
                );
                $this->mailer->send($email);
            }
        }
    }    

    private function createUsernamePendingEmailContent(Worker $worker) :string
    {
        return $this->renderView('worker/usernamePendingMail.html.twig', [
            'worker' => $worker,
        ]);
    }

    private function createBossRevisionPendingEmailContent(Worker $worker, $validate = false, $deleteOperation = false) :string
    {
        return $this->renderView('worker/bossRevisionPendingMail.html.twig', [
            'worker' => $worker,
            'validate' => $validate,
            'deleteOperation' => $deleteOperation
        ]);
    }

//    private function sendMessage($subject, array $to, Worker $worker, $validate = false, $deleteOperation = false)
    private function sendMessage($subject, array $to, string $html)
    {
        $email = (new Email())
            ->from($this->getParameter('mailer_from'))
            ->to(...$to)
            ->subject($subject)
            ->html($html)
        ;
        if ( $this->getParameter('sendBCC') ) {
            $addresses = [$this->getParameter('mailerBCC')];
            foreach ($addresses as $address) {
                $email->addBcc($address);
            }
        }            
        $this->mailer->send($email);
    }

    private function remove_blank_filters($criteria)
    {
        $new_criteria = [];
        foreach ($criteria as $key => $value) {
            if (!empty($value)) {
                $new_criteria[$key] = $value;
            }
        }

        return $new_criteria;
    }

}
