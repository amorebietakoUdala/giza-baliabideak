<?php

namespace App\Controller;

use App\Controller\BaseController;
use App\Entity\Application;
use App\Entity\Historic;
use App\Entity\JobPermission;
use App\Entity\Worker;
use App\Form\WorkerType;
use App\Form\WorkerSearchType;
use App\Repository\WorkerRepository;
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

    public function __construct(private readonly WorkerRepository $repo, private readonly EntityManagerInterface $em, private readonly MailerInterface $mailer, private readonly SerializerInterface $serializer)
    {
    }

    #[IsGranted('ROLE_RRHH')]
    #[Route(path: '/worker/new', name: 'worker_new')]
    public function new(Request $request) {
        $this->loadQueryParameters($request);
        $form = $this->createForm(WorkerType::class, new Worker(), [
            'new' => true,
            'locale' => $request->getLocale(),
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
                return $this->renderEdit($form, true, false);
            }
            if ($existingWorker) {
                if ($existingWorker->getStatus() !== Worker::STATUS_DELETED ) {
                    $this->addFlash('error','worker.alreadyExists');
                    return $this->renderEdit($form, true, false);
                } else {
                    $existingWorker->fill($worker);
                    $worker = $existingWorker;
                    $worker->setStatus(Worker::STATUS_REVISION_PENDING);
                    $this->addJobPermissionsToWorker($worker);
                    $this->createHistoric('alta ya existente', $this->serializer->serialize($worker,'json',['groups' => 'historic']));
                    $this->addFlash('warning','worker.alreadyExistsStatusChanged');
                }
            } else {
                $worker->setStatus(Worker::STATUS_REVISION_PENDING);
                $this->addJobPermissionsToWorker($worker);
                $this->createHistoric('alta', $this->serializer->serialize($worker,'json',['groups' => 'historic']));
                $this->addFlash('success', 'worker.sent');
            }
            $this->em->persist($worker);
            $this->em->flush();
            $this->sendMessageToBoss('Langile berriaren baimenak hautatu / Seleccione los permisos del nuevo empleado', $worker);                
            $this->createHistoric("enviado para validar por el responsable", $this->serializer->serialize($worker,'json',['groups' => 'historic']));
            // Send an email to medical revisions responsible
            $this->sendMessage('Langile berria gorde da / Se ha dado de alta un nuevo empleado', [$this->getParameter('mailerMM')], $worker);
            return $this->redirectToRoute('worker_index');
        }

        return $this->renderEdit($form, true, false);
    }

    /**
     * Sends message to boss to choose the authorized applications
     */
    #[Route(path: '/worker/{worker}/send', name: 'worker_send', methods: ['GET'])]
    public function send(Request $request, Worker $worker) {
        $this->loadQueryParameters($request);
        $this->createHistoric("Reenviado para validar por el responsable", $this->serializer->serialize($worker,'json',['groups' => 'historic']));
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
        ]);

        $form->handleRequest($request);
        if ( $form->isSubmitted() && $form->isValid() ) {
            /** @var Worker $worker */
            $worker = $form->getData();
            if ( $worker->getStatus() === Worker::STATUS_IN_PROGRESS ) {
                $this->addFlash('error','error.alreadyValidated');
                return $this->renderEdit($form, false, false, true);
            }
            if ( !$worker->checkIfUserIsAllowedBoss($this->getUser()) ) {
                $this->addFlash('error',new TranslatableMessage('error.notAllowedBoss', 
                    ['{bosses}' => implode(',',$worker->getWorkerJob()->getJob()->getBosses()->toArray())], 'messages'));
                return $this->renderEdit($form, false, false, true);
            }
            $worker->setStatus(Worker::STATUS_IN_PROGRESS);
            $worker->setValidatedBy($this->getUser());
            $this->em->persist($worker);
            $this->createHistoric('validado', $this->serializer->serialize($worker,'json',['groups' => 'historic']));
            $applications = [];
            $permissions = $worker->getPermissions();
            foreach($permissions as $permission) {
                $applications[] = $permission->getApplication(); 
            }
            foreach ($applications as $application) {
                $this->sendMessageToAppOwners('Langile berriari honako baimenak emango zaizkio / Se le van a dar los siguientes permisos al nuevo empleado ', $worker, $application, false);
            }
            $this->sendMessage('Langile berriari informatikak baimenak eman behar zaizkio / Informática tiene que dar los permisos al nuevo empleado', [$this->getParameter('mailerBCC')], $worker);
            $this->em->flush();
            $this->addFlash('success', 'worker.saved');
            return $this->redirectToRoute('worker_index');
        }

        return $this->renderEdit($form, false, false, true);
    }

    #[Route(path: '/worker/{worker}/edit', name: 'worker_edit')]
    public function edit(Request $request, Worker $worker) {
        $this->loadQueryParameters($request);
        $form = $this->createForm(WorkerType::class, $worker, [
            'locale' => $request->getLocale(),
            'roleBossOnly' => $this->isGranted('ROLE_BOSS') && !$this->isGranted('ROLE_RRHH'),
        ]);
        $form->handleRequest($request);
        if ( $form->isSubmitted() && $form->isValid() ) {
            /** @var Worker $worker */
            $worker = $form->getData();
            $error = $this->checkForErrors($worker);
            if ($error) {
                return $this->renderEdit($form, false, false);
            }
            $this->em->persist($worker);
            $this->createHistoric('modificación', $this->serializer->serialize($worker,'json',['groups' => 'historic']));
            $this->em->flush();
            $this->addFlash('success', 'worker.saved');
        }

        return $this->renderEdit($form, false, false);
    }

    #[Route(path: '/worker/{worker}/delete', name: 'worker_delete', methods: ['GET'])]
    public function delete(Request $request, Worker $worker)
    {
        $this->loadQueryParameters($request);
        $worker->setStatus(Worker::STATUS_DELETED);
        $this->createHistoric('baja', $this->serializer->serialize($worker,'json',['groups' => 'historic']));
        $this->sendMessage('Langile hau ezabatu egin da / El siguiente empleado se ha dado de baja', [$this->getParameter('mailerBCC')], $worker, false, true);
        $this->em->flush();
        $this->addFlash('success', 'worker.deleted');

        return $this->redirectToRoute('worker_index');
    }    

    #[Route(path: '/worker/{worker}', name: 'worker_show')]
    public function show(Request $request, Worker $worker) {
        $this->loadQueryParameters($request);
        $form = $this->createForm(WorkerType::class, $worker,[
            'readonly' => true,
            'locale' => $request->getLocale(),
        ]);

        return $this->renderEdit($form);
    }

    #[Route(path: '/worker', name: 'worker_index')]
    public function index(Request $request): Response
    {
        $this->loadQueryParameters($request);
        $worker = $this->fillWorkerFilter($request);
        if ( $this->isGranted('ROLE_BOSS') && !$this->isGranted('ROLE_RRHH') && $request->get('status') === null ) {
            $worker['status'] = Worker::STATUS_REVISION_PENDING;
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

    /** 
     *  This method is used when new Worker is created to assign permissions attached to the Job if there any.
     *  This way a new Worker on the same Job inherits the previous worker job permissions
    */
    private function addJobPermissionsToWorker(Worker $worker) 
    {
        $job = $worker->getWorkerJob()->getJob();
        $permissions = $job->getPermissions();
        foreach($permissions as $permission) {
            $permissionCopy = JobPermission::copyPermission($permission, $worker);
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
        // $existingWorker = $this->repo->findOneBy([
        //     'expedientNumber' => $worker->getExpedientNumber(),
        // ]);
        // if ($existingWorker) {
        //     $this->addFlash('error','worker.expedientNumberAlreadyExists');
        //     $error = true;
        // }
        return $error;
    }
    
    private function renderEdit(FormInterface $form, $new = false, $readonly = true, $validate = false) {
        return $this->render('worker/edit.html.twig', [
            'form' => $form,
            'readonly' => $readonly,
            'new' => $new,
            'validate' => $validate,
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

    private function createHistoric($operation, $details) {
        $historic = new Historic();
        $historic->fill($this->getUser(),$operation, $details);
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
            $this->sendMessage($subject, $emails, $worker, true);
        }
    }

    private function sendMessageToAppOwners($subject, Worker $worker, Application $application, bool $remove) {
        if ($application !== null && $application->getAppOwnersEmails() !== null) {
            $owners = explode(',', $application->getAppOwnersEmails());
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
                    'application' => $application,
                    'remove' => $remove,
                ])
            );
            $this->mailer->send($email);
        }
    }

    private function sendMessage($subject, array $to, Worker $worker, $validate = false, $deleteOperation = false)
    {
        $email = (new Email())
            ->from($this->getParameter('mailer_from'))
            ->to(...$to)
            ->subject($subject)
            ->html($this->renderView('worker/bossRevisionPendingMail.html.twig', [
                'worker' => $worker,
                'validate' => $validate,
                'deleteOperation' => $deleteOperation
            ])
        );
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
