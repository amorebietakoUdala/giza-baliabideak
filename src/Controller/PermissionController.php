<?php

namespace App\Controller;

use App\Entity\Application;
use App\Entity\Historic;
use App\Entity\JobPermission;
use App\Entity\Worker;
use App\Entity\Permission;
use App\Form\PermissionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/{_locale}", requirements={
 *	    "_locale": "es|eu|en"
 * })
  */
class PermissionController extends BaseController
{

    private EntityManagerInterface $em;
    private SerializerInterface $serializer;

    public function __construct(EntityManagerInterface $em, SerializerInterface $serializer) 
    {
        $this->em = $em;
        $this->serializer = $serializer;
    }

    /**
     * @Route("/permission/add-to/worker/{worker}", name="permission_add_to_worker")
     */
    public function addPermisionToWorker(Request $request, ?Worker $worker): Response
    {
        $workerApplication = new Permission();
        $form = $this->createForm(PermissionType::class, $workerApplication, [
            'action' => $this->generateUrl('permission_add_to_worker', [
                'worker' => $worker->getId(),
            ]),
            'readonly' => false,
            'locale' => $request->getLocale(),
        ]);
        $template = $this->getAjax() || $request->isXmlHttpRequest() ? '_form.html.twig' : 'edit.html.twig';
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Permission $permission */
            $permission = $form->getData();
            $permission->setWorker($worker);
            if ($this->checkAlreadyAddedPermission($permission)) {
                $this->addFlash('error', 'error.alreadyAddedApplication');
                return $this->renderError($form, $template);
            }
            if ($permission->getApplication()->getId() === Application::Application_AUPAC && $permission->getSubApplication() === null) {
                $this->addFlash('error', 'error.subAplicationNeeded');
                return $this->renderError($form, $template);            
            }
            $this->em->persist($permission);
            $this->createHistoric('añadir permiso', $this->serializer->serialize([$permission, $permission->getWorker()],'json',['groups' => 'historic']));
            $this->addOrUpdatePermissionToJob($permission);
            $this->em->flush();

            return $this->redirectToRoute('worker_edit', [
                'worker' => $worker->getId(),
            ]);
        }
        return $this->render('permission/' . $template, [
            'readonly' => false,
            'new' => true,
            'form' => $form->createView(),
        ], new Response(null, $form->isSubmitted() && ( !$form->isValid() )? 422 : 200,));
    }

    /**
     * @Route("/permission/{permission}/delete", name="permission_delete")
     */
    public function deletePermisionToWorker(Request $request, Permission $permission): Response
    {
        if ($this->isCsrfTokenValid('delete'.$permission->getId(), $request->get('_token'))) {
            $this->removePermissionsFromJob($permission);
            $this->createHistoric('eliminar permiso', $this->serializer->serialize([$permission, $permission->getWorker()],'json',['groups' => 'historic']));
            $this->em->remove($permission);
            $this->em->flush();
            if (!$request->isXmlHttpRequest()) {
                return $this->redirectToRoute('worker_edit',[
                    'worker' => $permission->getWorker()->getId(),
                ]);
            } else {
                return new Response(null, 204);
            }
        } else {
            return new Response('messages.invalidCsrfToken', 422);
        }
    }

    /**
     * @Route("/permission/list/worker/{worker}", name="permission_list")
     */
    public function list(Request $request, Worker $worker) {
        $removeAllowed = false;
        $referer = $request->headers->get('referer');
        if (strpos($referer,'/edit') || strpos($referer,'/validate')) {
            $removeAllowed = true;
        }
        $permissions = $worker->getPermissions();
        return $this->render('permission/_list.html.twig',[
            'permissions' => $permissions,
            'removeAllowed' => $removeAllowed, 
        ]);
    }

    /**
     * Checks if the permission's worker's job has permission to the application added and adds it or updates it.
     */
    private function addOrUpdatePermissionToJob(Permission $permission) 
    {
        $job = $permission->getWorker()->getJob();
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
        $job = $permission->getWorker()->getJob();
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

    private function renderError($form, $template) {
        return $this->render('permission/' . $template, [
            'readonly' => false,
            'new' => true,
            'form' => $form->createView(),
        ], new Response(null, 422));
    }

    private function createHistoric($operation, $details) {
        $historic = new Historic();
        $historic->fill($this->getUser(),$operation, $details);
        $this->em->persist($historic);
    }

}
