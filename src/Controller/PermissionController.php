<?php

namespace App\Controller;

use App\Entity\Application;
use App\Entity\Department;
use App\Entity\DepartmentPermission;
use App\Entity\Historic;
use App\Entity\Job;
use App\Entity\JobPermission;
use App\Entity\Worker;
use App\Entity\Permission;
use App\Form\DepartmentPermissionType;
use App\Form\JobPermissionType;
use App\Form\PermissionType;
use App\Service\MailingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Translation\TranslatableMessage;

#[Route(path: '/{_locale}', requirements: ['_locale' => 'es|eu|en'])]
class PermissionController extends BaseController
{

    public function __construct(
        private readonly EntityManagerInterface $em, 
        private readonly SerializerInterface $serializer,
        private readonly MailingService $mailingService, 
    )
    {
    }

    #[IsGranted('ROLE_GIZA_BALIABIDEAK')]
    #[Route(path: '/permission/add-to/worker/{worker}', name: 'permission_add_to_worker')]
    public function addPermisionToWorker(Request $request, #[MapEntity(id: 'worker')] ?Worker $worker): Response
    {
        $workerApplication = new Permission();
        $form = $this->createForm(PermissionType::class, $workerApplication, [
            'action' => $this->generateUrl('permission_add_to_worker', [
                'worker' => $worker->getId(),
            ]),
            'readonly' => false,
            'locale' => $request->getLocale(),
            'isAdmin' => $this->isGranted('ROLE_ADMIN'),
        ]);
        $template = $this->getAjax() || $request->isXmlHttpRequest() ? '_form.html.twig' : 'edit.html.twig';
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Permission $permission */
            $permission = $form->getData();
            $permission->setWorker($worker);
            if ( !$worker->checkIfUserIsAllowedBoss($this->getUser()) && !$this->isGranted("ROLE_ADMIN") ) {
                $this->addFlash('error',new TranslatableMessage('error.notAllowedBoss', 
                    ['{bosses}' => implode(',',$worker->getWorkerJob()->getJob()->getBosses()->toArray())], 'messages'));
                return $this->renderError($form, $template);
            }
            if ($this->checkAlreadyAddedPermission($permission)) {
                $this->addFlash('error', 'error.alreadyAddedApplication');
                return $this->renderError($form, $template);
            }
            if ($permission->getApplication()->getId() === Application::Application_GESTIONA && $permission->getSubApplication() === null) {
                $this->addFlash('error', 'error.subAplicationNeeded');
                return $this->renderError($form, $template);            
            }
            $this->em->persist($permission);
            $this->em->flush();
            $this->createHistoric("añadir permiso: $permission", $this->serializer->serialize([$permission, $permission->getWorker()],'json',['groups' => 'historic']), $worker);
            $this->addOrUpdatePermissionToJob($permission);
            /** If status > 2 means it's a change in permissions after IT gives them the proper permissions, so we need to notify app owner 
             *  if status is < 2 no need to notify it will be sent after validation.
            */
            $status = $worker->getStatus();
            if ( $status > Worker::STATUS_REVISION_PENDING) {
                $worker->setStatus(Worker::STATUS_APPROVAL_PENDING);
                $this->em->persist($worker);
                $application = $permission->getApplication();
                $appOwners = $application->getAppOwners();
                $appOwnersString = implode(', ',$appOwners->toArray());
                $this->createHistoric("enviado al responsable $permission para su aprobación ($appOwnersString) para crear el usuario", $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
                $this->mailingService->sendMessageToAppOwners('Langile berriaren baimena onartu / Aprobar el permiso del nuevo empleado', $this->getUser(), $worker, [$permission], false);
                $this->em->flush();
            }
                $referer = $request->headers->get('referer');
                return $this->redirect($referer ?? $this->generateUrl('worker_edit',['worker' => $worker->getId(),]));
        }
        return $this->render('permission/' . $template, [
            'readonly' => false,
            'new' => true,
            'form' => $form,
        ], new Response(null, $form->isSubmitted() && ( !$form->isValid() )? 422 : 200,));
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/permission/add-to/department/{department}', name: 'permission_add_to_department')]
    public function addPermisionToDepartment(Request $request, #[MapEntity(id: 'department')] Department $department): Response
    {
        $departmentPermission = new DepartmentPermission();
        $form = $this->createForm(DepartmentPermissionType::class, $departmentPermission, [
            'action' => $this->generateUrl('permission_add_to_department', [
                'department' => $department->getId(),
            ]),
            'readonly' => false,
            'locale' => $request->getLocale(),
        ]);
        $template = $this->getAjax() || $request->isXmlHttpRequest() ? '_form.html.twig' : 'edit.html.twig';
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var DepartmentPermission $permission */
            $permission = $form->getData();
            $permission->setDepartment($department);
            if ($this->checkAlreadyAddedDepartmentPermission($permission)) {
                $this->addFlash('error', 'error.alreadyAddedApplication');
                return $this->renderError($form, $template);
            }
            if ($permission->getApplication()->getId() === Application::Application_GESTIONA && $permission->getSubApplication() === null) {
                $this->addFlash('error', 'error.subAplicationNeeded');
                return $this->renderError($form, $template);            
            }
            $this->em->persist($permission);
            $this->em->flush();
            /** If status > 2 means it's a change in permissions after IT gives them the proper permissions, so we need to notify app owner 
             *  if status is < 2 no need to notify it will be sent after validation.
            */
            return $this->redirectToRoute('department_edit', [
                'department' => $department->getId(),
            ]);
        }
        return $this->render('permission/' . $template, [
            'readonly' => false,
            'new' => true,
            'form' => $form,
        ], new Response(null, $form->isSubmitted() && ( !$form->isValid() )? 422 : 200,));
    }

    #[IsGranted('ROLE_GIZA_BALIABIDEAK')]
    #[Route(path: '/permission/add-to/job/{job}', name: 'permission_add_to_job')]
    public function addPermisionToJob(Request $request, #[MapEntity(id: 'job')] Job $job): Response
    {
        $form = $this->createForm(JobPermissionType::class, new JobPermission(), [
            'action' => $this->generateUrl('permission_add_to_job', [
                'job' => $job->getId(),
            ]),
            'readonly' => false,
            'locale' => $request->getLocale(),
        ]);
        $template = $this->getAjax() || $request->isXmlHttpRequest() ? '_form.html.twig' : 'edit.html.twig';
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var JobPermission $jobPermission */
            $jobPermission = $form->getData();
            $jobPermission->setJob($job);
            if ($this->checkAlreadyAddedJobPermission($jobPermission)) {
                $this->addFlash('error', 'error.alreadyAddedApplication');
                return $this->renderError($form, $template);
            }
            if ($jobPermission->getApplication()->getId() === Application::Application_GESTIONA && $jobPermission->getSubApplication() === null) {
                $this->addFlash('error', 'error.subAplicationNeeded');
                return $this->renderError($form, $template);            
            }
            $this->em->persist($jobPermission);
            $this->em->flush();
            /** If status > 2 means it's a change in permissions after IT gives them the proper permissions, so we need to notify app owner 
             *  if status is < 2 no need to notify it will be sent after validation.
            */
            return $this->redirectToRoute('job_edit', [
                'job' => $job->getId(),
            ]);
        }
        return $this->render('permission/' . $template, [
            'readonly' => false,
            'new' => true,
            'form' => $form,
        ], new Response(null, $form->isSubmitted() && ( !$form->isValid() )? 422 : 200,));
    }

    #[IsGranted('ROLE_GIZA_BALIABIDEAK')]
    #[Route(path: '/permission/{permission}/delete', name: 'permission_delete')]
    public function deletePermisionToWorker(Request $request, #[MapEntity(id: 'permission')] Permission $permission): Response
    {
        $worker = $permission->getWorker();
        if ($this->isCsrfTokenValid('delete'.$permission->getId(), $request->get('_token'))) {
            $this->removePermissionsFromJob($permission);
            $this->createHistoric("eliminar permiso $permission", $this->serializer->serialize([$permission, $permission->getWorker()],'json',['groups' => 'historic']), $worker);
            $worker = $permission->getWorker();
            $this->em->remove($permission);
            $status = $worker->getStatus();
            if ( $status > 2) {
                $worker->setStatus(Worker::STATUS_IN_PROGRESS);
                $this->em->persist($worker);
                $application = $permission->getApplication();
                $application = $permission->getApplication();
                $appOwners = $application->getAppOwners();
                $appOwnersString = implode(', ',$appOwners->toArray());
                $this->createHistoric("enviado al responsable de la aplicación $application ($appOwnersString) para eliminar el usuario", $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
                $this->mailingService->sendMessageToUserCreators('Langileari honako baimenak kenduko zaizkio / Se le van a quitar los siguientes permisos al empleado', $worker, [$permission], true);
            }
            $this->em->flush();
            if (!$request->isXmlHttpRequest()) {
                $referer = $request->headers->get('referer');
                return $this->redirect($referer ?? $this->generateUrl('worker_edit',['worker' => $worker->getId(),]));
            } else {
                return new Response(null, Response::HTTP_NO_CONTENT);
            }
        } else {
            return new Response('messages.invalidCsrfToken', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/department-permission/{departmentPermission}/delete', name: 'department_permission_delete')]
    public function deleteDepartmentPermision(Request $request, #[MapEntity(id: 'departmentPermission')] DepartmentPermission $departmentPermission): Response
    {
        $departmentId = $departmentPermission->getDepartment()->getId();
        if ($this->isCsrfTokenValid('delete'.$departmentPermission->getId(), $request->get('_token'))) {
            $this->em->remove($departmentPermission);
            $this->em->flush();
            if (!$request->isXmlHttpRequest()) {
                return $this->redirectToRoute('department_edit',[
                    'department' => $departmentId
                ]);
            } else {
                return new Response(null, Response::HTTP_NO_CONTENT);
            }
        } else {
            return new Response('messages.invalidCsrfToken', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('ROLE_GIZA_BALIABIDEAK')]
    #[Route(path: '/job-permission/{jobPermission}/delete', name: 'job_permission_delete')]
    public function deleteJobPermision(Request $request, #[MapEntity(id: 'jobPermission')] JobPermission $jobPermission): Response
    {
        $jobId = $jobPermission->getJob()->getId();
        if ($this->isCsrfTokenValid('delete'.$jobPermission->getId(), $request->get('_token'))) {
            $this->em->remove($jobPermission);
            $this->em->flush();
            if (!$request->isXmlHttpRequest()) {
                return $this->redirectToRoute('job_edit',[
                    'job' => $jobId
                ]);
            } else {
                return new Response(null, Response::HTTP_NO_CONTENT);
            }
        } else {
            return new Response('messages.invalidCsrfToken', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('ROLE_GIZA_BALIABIDEAK')]
    #[Route(path: '/permission/{permission}/granted', name: 'permission_granted', methods: ['GET'])]
    public function markAsGranted(Request $request, #[MapEntity(id: 'permission')] Permission $permission): Response
    {
        if (!$this->isCsrfTokenValid('granted'.$permission->getId(), $request->get('_token'))) {
            $this->addFlash('error', 'messages.invalidCsrfToken');
            return $this->redirectToRoute('worker_edit',[
                'worker' => $permission->getWorker()->getId(),
            ]);
        }
        $permission->setGranted(true);
        $permission->setGrantedAt(new \DateTimeImmutable());
        $permission->setGrantedBy($this->getUser());
        $this->em->persist($permission);
        $worker = $permission->getWorker();
        $this->createHistoric("$permission concedido", $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
        $this->addFlash('success', 'message.permissionGranted');
        if ( $worker->hasAllPermissionsGranted()) {
            $this->em->persist($worker);
            $worker->setStatus(Worker::STATUS_REGISTERED);
            $this->createHistoric("Todos los permisos concedidos. Paso a estado alta.", $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
        }
        $this->em->flush();
        if ($request->isXmlHttpRequest()) {
            return new Response(null, Response::HTTP_NO_CONTENT);
        }
        return $this->redirectToRoute('worker_edit',[
            'worker' => $permission->getWorker()->getId(),
        ]);
    }

    #[IsGranted('ROLE_GIZA_BALIABIDEAK')]
    #[Route(path: '/permission/list/worker/{worker}', name: 'permission_list')]
    public function list(Request $request, #[MapEntity(id: 'worker')] Worker $worker) {
        $removeAllowed = false;
        $referer = $request->headers->get('referer');
        if (strpos($referer,'/edit') || strpos($referer,'/validate')) {
            $removeAllowed = true;
        }
        $permissions = $worker->getPermissions();
        $notGeneral = [];
        if (!$this->isGranted('ROLE_ADMIN')) {
            foreach($permissions as $permission) {
                $application = $permission->getApplication();
                if (!$application->isGeneral()) {
                    $notGeneral[] = $permission;
                }
            }
            $permissions = $notGeneral;
        }
        /** @var User $user */
        $user = $this->getUser();
        return $this->render('permission/_list.html.twig',[
            'permissions' => $permissions,
            'removeAllowed' => $removeAllowed, 
            'applicationIds' => $user->getApplicationIds(),
        ]);
    }

    /**
     * Approves a permission for a worker and sends a message to the user creators to create the user.
     * 
     * @param Request $request
     * @param Permission $permission
     * @return Response
     */
    #[IsGranted('ROLE_APP_OWNER')]
    #[Route(path: '/permission/{permission}/approve', name: 'permission_approve')]
    public function approve(Request $request, #[MapEntity(id: 'permission')] Permission $permission) {
        $worker = $permission->getWorker();
        if ($permission->isApproved()) {
            $this->addFlash('success', 'message.allreadyApproved');
            return $this->redirectToRoute('worker_edit',[
                'worker' => $worker->getId(),
            ]);
        }        
        $permission->setApproved(true);
        $permission->setApprovedAt(new \DateTimeImmutable());
        $permission->setApprovedBy($this->getUser());
        // check if there is no more permissions to approve or deny. If so change status to in progress
        $allApproved = $worker->hasAllPermissionsApprovedOrDenied();
        if ($allApproved) {
            $worker->setStatus(Worker::STATUS_IN_PROGRESS);
            $this->createHistoric("permiso $permission aprobado. No hay más permisos pendientes de aprobación.", $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
        } else {
            $this->createHistoric("permiso $permission aprobado", $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
        }
        $this->mailingService->sendMessageToUserCreators('Langile berriaren erabiltzailea sortu / Cree el usuario del nuevo trabajador', $worker, [$permission]);
        $this->createHistoric("Enviado mensaje a los creadores de usuarios de la aplicación", $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
        $this->em->persist($permission);
        $this->em->flush();
        $this->addFlash('success', 'message.permissionApproved');
        return $this->redirectToRoute('worker_edit', [
            'worker' => $worker->getId(),
        ]);
    }

    /**
     * Denies a permission for a worker and sends a message to the user creators to delete the user.
     * 
     * @param Request $request
     * @param Permission $permission
     * @return Response
     */
    #[IsGranted('ROLE_APP_OWNER')]
    #[Route(path: '/permission/{permission}/deny', name: 'permission_deny')]
    public function deny(Request $request, #[MapEntity(id: 'permission')] Permission $permission) {
        $worker = $permission->getWorker();
        if (!$permission->isApproved() && $permission->isApproved() !== null) {
            $this->addFlash('success', 'message.allreadyDenied');
            return $this->redirectToRoute('worker_edit',[
                'worker' => $worker->getId(),
            ]);
        }        

        $permission->setApproved(false);
        $permission->setApprovedAt(new \DateTimeImmutable());
        $permission->setApprovedBy($this->getUser());
        // check if there is no more permissions to approve or deny. If so change status to in progress
        $allApproved = $worker->hasAllPermissionsApprovedOrDenied();
        if ($allApproved) {
            $worker->setStatus(Worker::STATUS_IN_PROGRESS);
            $this->createHistoric("permiso $permission denegado. No hay más permisos pendientes de aprobación.", $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
        } else {
            $this->createHistoric("permiso $permission denegado", $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
        }
        if ($permission->isGranted()){
            $this->mailingService->sendMessageToUserCreators('Mesedez, langile honen erabiltzailea ezabatu / Por favor, elimine el usuario del siguiente trabajador', $worker, [$permission], true);
            $this->createHistoric("Enviado mensaje a los creadores de usuarios de la aplicación para eliminar el usuario de la aplicación", $this->serializer->serialize($worker,'json',['groups' => 'historic']), $worker);
        }
        $this->em->persist($permission);
        $this->em->flush();
        $this->addFlash('success', 'message.permissionDenied');
        
        return $this->redirectToRoute('worker_edit', [
            'worker' => $worker->getId(),
        ]);
    }

    /**
     * Checks if the permission's worker's job has permission to the application added and adds it or updates it.
     */
    private function addOrUpdatePermissionToJob(Permission $permission) 
    {
        $job = $permission->getWorker()->getWorkerJob()->getJob();
        $permissions = $job->getPermissions();
        if (count($permissions) === 0) {
            $permissionCopy = JobPermission::createJobPermissionFromPermissionAndWorker($permission, $job);
            $job->addPermission($permissionCopy);
            $this->em->persist($permissionCopy);
            return;
        }
        $application = $permission->getApplication();
        $found = false;
        foreach($permissions as $perm) {
            if ($application === $perm->getApplication()) {
                $perm->setSubApplication($perm->getSubApplication());
                $roles = $perm->getRoles();
                $perm->getRoles()->clear();
                foreach ($roles as $rol) {
                    $perm->addRole($rol);
                    $this->em->persist($rol);                    
                }
                $this->em->persist($perm);
                $found = true;
            }
        }
        if (!$found) {
            $permissionCopy = JobPermission::createJobPermissionFromPermissionAndWorker($permission, $job);
            $job->addPermission($permissionCopy);
            $this->em->persist($permissionCopy);
        }
        $this->em->persist($job);
    }

    private function removePermissionsFromJob(Permission $permission) {
        $job = $permission->getWorker()->getWorkerJob()->getJob();
        $permissions = $job->getPermissions();
        foreach($permissions as $perm) {
            if ($permission->getApplication() === $perm->getApplication()) {
                $this->em->remove($perm);
                break;
            }
        }
    }

    private function checkAlreadyAddedPermission(Permission $permission) {
        $worker = $permission->getWorker();
        $apps = $worker->getPermissions();
        foreach ($apps as $app ) {
            if ($permission->getApplication() === $app->getApplication()) {
                return true;
            }
        }
        return false;
    }

    private function checkAlreadyAddedDepartmentPermission(DepartmentPermission $permission) {
        $department = $permission->getDepartment();
        $apps = $department->getPermissions();
        foreach ($apps as $app ) {
            if ($permission->getApplication() === $app->getApplication()) {
                return true;
            }
        }
        return false;
    }

    private function checkAlreadyAddedJobPermission(JobPermission $permission) {
        $job = $permission->getJob();
        $apps = $job->getPermissions();
        foreach ($apps as $app ) {
            if ($permission->getApplication() === $app->getApplication()) {
                return true;
            }
        }
        return false;
    }

    private function renderError($form, $template) {
        return $this->render('permission/' . $template, [
            'readonly' => false,
            'new' => true,
            'form' => $form->createView(),
        ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
    }

    private function createHistoric($operation, $details, Worker $worker) {
        $historic = new Historic();
        $historic->fill($this->getUser(),$operation, $details, $worker);
        $this->em->persist($historic);
    }
}
