<?php

namespace App\Controller;

use App\Controller\BaseController;
use App\Entity\DepartmentPermission;
use App\Entity\Historic;
use App\Entity\JobPermission;
use App\Entity\User;
use App\Entity\Worker;
use App\Form\WorkerType;
use App\Form\WorkerSearchType;
use App\Repository\WorkerRepository;
use App\Service\MailingService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
        private readonly SerializerInterface $serializer,
        private readonly MailingService $mailingService,
        )
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
                    // Remove all existing permissions, because they will be re-added according to the Department and Job
                    foreach ( $existingWorker->getPermissions() as $permission ) {
                        $this->em->remove($permission);
                    }
                    $existingWorker->fill($worker);
                    $existingWorker->setStatus(Worker::STATUS_REVISION_PENDING);
                    $this->addJobPermissionsToWorker($existingWorker);
                    $this->addFlash('warning','worker.alreadyExistsStatusChanged');
                    $this->em->persist($existingWorker);
                    $worker = $existingWorker;
                    $this->createHistoric('alta ya existente', $this->serializer->serialize($existingWorker,'json',['groups' => 'historic']), $existingWorker);
                }
            } else {
                if ( $worker->getUsername() === null || $worker->getUsername() === '' ) {
                    $worker->setStatus(Worker::STATUS_USERNAME_PENDING);    
                } else {
                    $worker->setStatus(Worker::STATUS_REVISION_PENDING);
                }
                $this->addJobPermissionsToWorker($worker);
                $this->addFlash('success', 'worker.sent');
                $this->em->persist($worker);
                $this->createHistoric('alta', $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
            }
            //dd($worker);
            $this->em->flush();
            if ( $worker->getStatus() === Worker::STATUS_USERNAME_PENDING ) {
                $this->createHistoric("pendiente de asignación de nombre de usuario", $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
                $this->mailingService->sendUsernamePendingMessageToIT('Langile berriari ezarri erabiltzaile izena / Asigne un nombre de usuario al nuevo empleado', $worker);
                $this->createHistoric("enviado mensaje a IT para asignación de nombre de usuario", $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
                $this->em->flush();
            } else {
                $this->mailingService->sendMessageToBoss('Langile berriaren baimenak hautatu / Seleccione los permisos del nuevo empleado', $worker, true);                
                $this->createHistoric("enviado para validar por el responsable", $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
                $this->em->flush();
            }
            // Send an email to medical revisions responsible
            $this->mailingService->sendMessageToMM('Langile berria gorde da / Se ha dado de alta un nuevo empleado', $worker);
            return $this->redirectToRoute('worker_index');
        }

        return $this->renderEdit($form, null, true, false, false, $this->isGranted('ROLE_APP_OWNER'));
    }

    /**
     * Sends message to boss to choose the authorized applications
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/worker/{worker}/send', name: 'worker_send', methods: ['GET'])]
    public function send(Request $request, #[MapEntity(id: 'worker')] Worker $worker) {
        $this->loadQueryParameters($request);
        $this->mailingService->sendMessageToBoss('Langile berriaren baimenak hautatu / Seleccione los permisos del nuevo empleado', $worker);
        $this->createHistoric("Reenviado para validar por el responsable", $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
        $this->em->flush();
        $this->addFlash('success', 'worker.resent');
        return $this->redirectToRoute('worker_index');
    }

    /**
     * Sends message to appOwners to aprove access to the apps
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/worker/{worker}/send-to-appowners', name: 'worker_send_to_app_owners', methods: ['GET'])]
    public function sendToAppOwners(Request $request, #[MapEntity(id: 'worker')] Worker $worker) {
        $this->loadQueryParameters($request);
        $permissions = $worker->getApprovalPendingPermissions();
        if ( count($permissions) === 0 ) {
            $this->addFlash('error','error.allreadyApprovedAllPermissions');
            return $this->redirectToRoute('worker_index');
        }
        $this->mailingService->sendMessageToAppOwners('Langile berriaren baimenak onartu / Aprobar los permisos del nuevo empleado ',$this->getUser(), $worker, $permissions, false);
        $this->createHistoric("Reenviado para a los responsables de las aplicaciones", $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
        $this->em->flush();
        $this->addFlash('success', 'worker.resentAppOwners');
        return $this->redirectToRoute('worker_index');
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/worker/{worker}/send-to-user-creators', name: 'worker_send_to_user-creators', methods: ['GET'])]
    public function sendToUserCreators(Request $request, #[MapEntity(id: 'worker')] Worker $worker) {
        $this->loadQueryParameters($request);
        $permissions = $worker->getUngrantedPermissions();
        if ( count($permissions) === 0 ) {
            $this->addFlash('error','error.allreadyGrantedAllPermissions');
            return $this->redirectToRoute('worker_index');
        }
        $this->mailingService->sendMessageToUserCreators('Langilearen baimenak onartu dira / Permisos del empleado aprobados', $worker, $permissions);
        $this->createHistoric("Reenviado para a los creadores de usuarios", $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
        $this->em->flush();
        $this->addFlash('success', 'worker.resentUserCreators');
        return $this->redirectToRoute('worker_index');
    }

    #[IsGranted('ROLE_BOSS')]
    #[Route(path: '/worker/{worker}/validate', name: 'worker_validate')]
    public function validate(Request $request, #[MapEntity(id: 'worker')] Worker $worker) {
        $this->loadQueryParameters($request);
        $oldPermissions = new ArrayCollection();
        $session = $request->getSession();
        if ( $request->getMethod() !== 'POST' ) {
            $oldPermissions = $worker->getPermissions();
            $session->set('oldPermissions', $oldPermissions);
        }
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
            //$job = $worker->getWorkerJob()->getJob();
            $oldPermissions = $session->get('oldPermissions', new ArrayCollection());
            $newPermissions = $this->determineNewPermissions($worker, $oldPermissions);
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
            $this->mailingService->sendMessageToAppOwners('Langile berriaren baimenak onartu / Aprobar los permisos del nuevo empleado ',$this->getUser(), $worker, $newPermissions, false);
            $this->createHistoric("Enviados mensajes a los responsable de aplicaiones seleccionadas.", $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
            $this->em->flush();
            $this->addFlash('success', 'worker.saved');
            return $this->redirectToRoute('worker_index');
        }

        return $this->renderEdit($form, $historics, false, false, true, $this->isGranted('ROLE_APP_OWNER'));
    }

    #[IsGranted('ROLE_GIZA_BALIABIDEAK')]
    #[Route(path: '/worker/{worker}/edit', name: 'worker_edit')]
    public function edit(Request $request, #[MapEntity(id: 'worker')] Worker $worker) {
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
            if ( $worker->getUsername() !== null && $worker->getUsername() !== '' && $worker->getStatus() === Worker::STATUS_USERNAME_PENDING )
            {
                $worker->setStatus(Worker::STATUS_REVISION_PENDING);
                $this->mailingService->sendMessageToBoss('Langile berriaren baimenak hautatu / Seleccione los permisos del nuevo empleado', $worker, true);
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
    public function delete(Request $request, #[MapEntity(id: 'worker')] Worker $worker)
    {
        $this->loadQueryParameters($request);
        $worker->setStatus(Worker::STATUS_DELETED);
        $this->createHistoric('baja', $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
        $this->mailingService->sendMessageToIT('Langile hau ezabatu egin da / El siguiente empleado se ha dado de baja', $worker, false, true);
        $this->createHistoric("Enviado mensaje a IT para eliminar el usuario.", $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
        $this->mailingService->sendMessageToUserCreators('Mesedez, langile honen erabiltzailea ezabatu / Por favor, elimine el usuario del siguiente trabajador', $worker, null, true);
        $this->createHistoric("Enviados mensajes a los creadores de usuarios para eliminar el usuario de esas aplicaciones", $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
        $this->em->flush();
        $this->addFlash('success', 'worker.deleted');

        return $this->redirectToRoute('worker_index');
    }    

    #[IsGranted('ROLE_GIZA_BALIABIDEAK')]
    #[Route(path: '/worker/{worker}', name: 'worker_show')]
    public function show(Request $request, #[MapEntity(id: 'worker')] Worker $worker) {
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
    public function list(#[MapEntity(id: 'worker')] Worker $worker): Response
    {
        $historics = $worker->getHistorics();
        if (count($historics) === 0) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }
        return $this->render('worker/_historic_row.html.twig', [
            'historics' => $historics,
        ]);
    }   

    #[IsGranted('ROLE_APP_OWNER')]
    #[Route(path: '/worker/{worker}/approve-all-pending', name: 'worker_approve_all_pending')]
    public function approveAllPending(#[MapEntity(id: 'worker')] Worker $worker) {
        /** @var User $user */
        $user = $this->getUser();
        $applications = $user->getApplications();
        $approvedPermissions = [];
        foreach ( $applications as $application ) {
            foreach ( $worker->getPermissions() as $permission ) {
                if ( $permission->isApproved() === null && $permission->getApplication() === $application ) {
                    $permission->setApproved(true);
                    $permission->setApprovedBy($user);
                    $permission->setApprovedAt(new \DateTimeImmutable());
                    $this->em->persist($permission);
                    $this->createHistoric("permiso $permission aprobado", $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
                    $approvedPermissions[] = $permission;
                }
            }
        }
        if ($worker->hasAllPermissionsApprovedOrDenied()) {
            $worker->setStatus(Worker::STATUS_IN_PROGRESS);
            $this->createHistoric("No hay aprobaciones pendientes. Cambio de estado a 'En proceso alta informática'", $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
            $this->em->persist($worker);
        }
        $this->em->flush();
        $this->mailingService->sendMessageToUserCreators('Langilearen baimenak onartu dira / Permisos del empleado aprobados', $worker, $approvedPermissions);
        $this->createHistoric("Enviados mensajes a los creadores de usuarios para crear el usuario de esas aplicaciones", $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
        $this->em->flush();
        return $this->redirectToRoute('worker_edit', ['worker' => $worker->getId()]);
    }

    #[IsGranted('ROLE_APP_OWNER')]
    #[Route(path: '/worker/{worker}/deny-all-pending', name: 'worker_deny_all_pending')]
    public function denyAllPending(#[MapEntity(id: 'worker')] Worker $worker) {
        /** @var User $user */
        $user = $this->getUser();
        $applications = $user->getApplications();
        foreach ( $applications as $application ) {
            foreach ( $worker->getPermissions() as $permission ) {
                if ( $permission->isApproved() === null && $permission->getApplication() === $application ) {
                    $permission->setApproved(false);
                    $permission->setApprovedBy($user);
                    $permission->setApprovedAt(new \DateTimeImmutable());
                    $this->em->persist($permission);
                    $this->createHistoric("permiso $permission denegado", $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
                }
            }
        }
        if ($worker->hasAllPermissionsApprovedOrDenied()) {
            $worker->setStatus(Worker::STATUS_IN_PROGRESS);
            $this->createHistoric("No hay aprobaciones pendientes. Cambio de estado a 'En proceso alta informática'", $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
            $this->em->persist($worker);
        }
        $this->em->flush();
        return $this->redirectToRoute('worker_edit', ['worker' => $worker->getId()]);
    }

    private function determineNewPermissions(Worker $worker, Collection $oldPermissions): array
    {
        $oldPermissionIds = array_map(
            fn($perm) => $perm->getId(),
            $oldPermissions->toArray()
        );

        $newPermissions = [];
        foreach ($worker->getPermissions() as $permission) {
            if (!in_array($permission->getId(), $oldPermissionIds, true)) {
                $newPermissions[] = $permission;
            }
        }

        return $newPermissions;
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

    private function remove_blank_filters(array $criteria)
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
