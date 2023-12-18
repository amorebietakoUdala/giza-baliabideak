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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Translation\TranslatableMessage;

#[Route(path: '/{_locale}', requirements: ['_locale' => 'es|eu|en'])]
class PermissionController extends BaseController
{

    public function __construct(private readonly EntityManagerInterface $em, private readonly SerializerInterface $serializer, private readonly MailerInterface $mailer)
    {
    }

    #[Route(path: '/permission/add-to/worker/{worker}', name: 'permission_add_to_worker')]
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
            if ( !$worker->checkIfUserIsAllowedBoss($this->getUser()) ) {
                $this->addFlash('error',new TranslatableMessage('error.notAllowedBoss', 
                    ['{bosses}' => implode(',',$worker->getJob()->getBosses()->toArray())], 'messages'));
                return $this->renderError($form, $template);
            }
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
            /** If status > 2 means it's a change in permissions after IT gives them the proper permissions, so we need to notify app owner 
             *  if status is < 2 no need to notify it will be sent after validation.
            */
            $status = $worker->getStatus();
            if ( $status > 2) {
                $worker->setStatus(Worker::STATUS_IN_PROGRESS);
                $this->em->persist($worker);
                $this->createHistoric("enviado al responsable de la aplicación", $this->serializer->serialize($worker,'json',['groups' => 'historic']));
                $this->sendMessages($permission, 'Langileari honako baimenak emango zaizkio / Se le van a dar los siguientes permisos al empleado');
            }
            $this->em->flush();
            return $this->redirectToRoute('worker_edit', [
                'worker' => $worker->getId(),
            ]);
        }
        return $this->render('permission/' . $template, [
            'readonly' => false,
            'new' => true,
            'form' => $form,
        ], new Response(null, $form->isSubmitted() && ( !$form->isValid() )? 422 : 200,));
    }

    #[Route(path: '/permission/{permission}/delete', name: 'permission_delete')]
    public function deletePermisionToWorker(Request $request, Permission $permission): Response
    {
        if ($this->isCsrfTokenValid('delete'.$permission->getId(), $request->get('_token'))) {
            $this->removePermissionsFromJob($permission);
            $this->createHistoric('eliminar permiso', $this->serializer->serialize([$permission, $permission->getWorker()],'json',['groups' => 'historic']));
            $worker = $permission->getWorker();
            $this->em->remove($permission);
            $status = $worker->getStatus();
            if ( $status > 2) {
                $worker->setStatus(Worker::STATUS_IN_PROGRESS);
                $this->em->persist($worker);
                $this->createHistoric("enviado al responsable de la aplicación", $this->serializer->serialize($worker,'json',['groups' => 'historic']));
                $this->sendMessages($permission, 'Langileari honako baimenak kenduko zaizkio / Se le van a quitar los siguientes permisos al empleado', true);
            }
            $this->em->flush();
            if (!$request->isXmlHttpRequest()) {
                return $this->redirectToRoute('worker_edit',[
                    'worker' => $permission->getWorker()->getId(),
                ]);
            } else {
                return new Response(null, \Symfony\Component\HttpFoundation\Response::HTTP_NO_CONTENT);
            }
        } else {
            return new Response('messages.invalidCsrfToken', \Symfony\Component\HttpFoundation\Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[Route(path: '/permission/list/worker/{worker}', name: 'permission_list')]
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
        ], new Response(null, \Symfony\Component\HttpFoundation\Response::HTTP_UNPROCESSABLE_ENTITY));
    }

    private function createHistoric($operation, $details) {
        $historic = new Historic();
        $historic->fill($this->getUser(),$operation, $details);
        $this->em->persist($historic);
    }

    private function sendMessages(Permission $permission, $message, $remove = false) {
        $worker = $permission->getWorker();
        $this->sendMessageToAppOwners($message, $worker, $permission->getApplication(), $remove);
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
}
