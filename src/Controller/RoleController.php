<?php

namespace App\Controller;

use App\Controller\BaseController;
use App\Entity\Role;
use App\Form\RoleType;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[IsGranted('ROLE_ADMIN')]
#[Route(path: '/{_locale}', requirements: ['_locale' => 'es|eu|en'])]
class RoleController extends BaseController
{

   public function __construct(private readonly RoleRepository $repo, private readonly EntityManagerInterface $em)
   {
   }

     /**
     * Creates or updates an role
     */
    #[Route(path: '/role/new', name: 'role_new', methods: ['GET', 'POST'])]
    public function createOrSave(Request $request): Response
    {
        $this->loadQueryParameters($request);
//        $role = $this->createRole($request);
        $role = new Role();
        $form = $this->createForm(RoleType::class, $role,[
            'readonly' => false,
            'locale' => $request->getLocale(),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Role $role */
            $roleData = $form->getData();
            if (null !== $roleData->getId()) {
                $role = $this->repo->find($roleData->getId());
                $role->fill($roleData);
            } elseif ($this->checkAlreadyExists($roleData)) {
                $this->addFlash('error', 'messages.roleAlreadyExist');
                $template = $this->getAjax() || $request->isXmlHttpRequest() ? '_form.html.twig' : 'edit.html.twig';
                return $this->render('role/' . $template, [
                    'form' => $form,
                ], new Response(null, \Symfony\Component\HttpFoundation\Response::HTTP_UNPROCESSABLE_ENTITY));        
            }
            $this->em->persist($role);
            $this->em->flush();
            if ($this->getAjax() || $request->isXmlHttpRequest()) {
               return new Response(null, \Symfony\Component\HttpFoundation\Response::HTTP_NO_CONTENT);
            }
            return $this->redirectToRoute('role_index');
        }
        $template = $this->getAjax() || $request->isXmlHttpRequest() ? '_form.html.twig' : 'edit.html.twig';
        return $this->render('role/' . $template, [
            'form' => $form,
        ], new Response(null, $form->isSubmitted() && ( !$form->isValid() )? 422 : 200,));        
   }

      /**
       * Show the role form specified by id.
       * The Role can't be changed
       */
      #[Route(path: '/role/{role}', name: 'role_show', methods: ['GET'])]
      public function show(Request $request, #[MapEntity(id: 'role')] Role $role): Response
      {
         $form = $this->createForm(RoleType::class, $role, [
               'readonly' => true,
               'locale' => $request->getLocale(),
         ]);
         $template = $this->getAjax() || $request->isXmlHttpRequest() ? '_form.html.twig' : 'edit.html.twig';
         return $this->render('role/' . $template, [
               'role' => $role,
               'form' => $form,
               'readonly' => true,
               'new' => false,
         ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
      }

      /**
       * Renders the Role form specified by id to edit it's fields
       */
      #[Route(path: '/role/{role}/edit', name: 'role_edit', methods: ['GET', 'POST'])]
      public function edit(Request $request, #[MapEntity(id: 'role')]  Role $role): Response
      {
         $form = $this->createForm(RoleType::class, $role, [
            'readonly' => false,
            'locale' => $request->getLocale(),
         ]);
         $form->handleRequest($request);
         if ($form->isSubmitted() && $form->isValid()) {
            /** @var Role $role */
            $role = $form->getData();
            $this->em->persist($role);
            $this->em->flush();
         }

         $template = $this->getAjax() || $request->isXmlHttpRequest() ? '_form.html.twig' : 'edit.html.twig';
         return $this->render('role/' . $template, [
            'role' => $role,
            'form' => $form,
            'readonly' => false,
            'new' => false,
         ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
      }


    #[Route(path: '/role/{role}/delete', name: 'role_delete', methods: ['DELETE'])]
    public function delete(Request $request, #[MapEntity(id: 'role')]  Role $role): Response
    {
        $workers = $role->getApplications();
        if ( count($workers) > 0 ) {
            $this->addFlash('error', new TranslatableMessage('error.roleHasApplications', 
            ['{workers}' => substr(implode(',',$workers->toArray()),0,50).'...'], 'messages'));
            return $this->render('common/_error.html.twig',[], new Response('', \Symfony\Component\HttpFoundation\Response::HTTP_UNPROCESSABLE_ENTITY));
        }
        if ($this->isCsrfTokenValid('delete'.$role->getId(), $request->get('_token'))) {
            $this->em->remove($role);
            $this->em->flush();
            if (!$request->isXmlHttpRequest()) {
                return $this->redirectToRoute('role_index');
            } else {
                return new Response(null, \Symfony\Component\HttpFoundation\Response::HTTP_NO_CONTENT);
            }
        } else {
            return new Response('messages.invalidCsrfToken', \Symfony\Component\HttpFoundation\Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }   

   #[Route(path: '/role', name: 'role_index')]
    public function index(Request $request): Response
    {
        $this->loadQueryParameters($request);
        $roles = $this->repo->findAll();
        $form = $this->createForm(RoleType::class, new Role(),[
            'readonly' => false,
            'locale' => $request->getLocale(),
        ]);

        $template = !$this->getAjax() ? 'role/index.html.twig' : 'role/_list.html.twig';
        return $this->render($template, [
            'roles' => $roles,
            'form' => $form,
        ]);        
    }

    private function checkAlreadyExists(Role $role) {
        $result = $this->repo->findOneBy([
            'nameEs' => $role->getNameEs(),
        ]);
        if ( $result !== null ) {
            return $result !== null ? true : false;
        }
        $result = $this->repo->findOneBy([
            'nameEu' => $role->getNameEu(),
        ]);
        if ( $result !== null ) {
            return $result !== null ? true : false;
        }
    }    
}

