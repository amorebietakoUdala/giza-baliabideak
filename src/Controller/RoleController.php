<?php

namespace App\Controller;

use App\Controller\BaseController;
use App\Entity\Role;
use App\Form\RoleType;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * @Route("/{_locale}", requirements={
 *	    "_locale": "es|eu|en"
 * })
 * @Security("is_granted('ROLE_ADMIN')")
 */
class RoleController extends BaseController
{

   private RoleRepository $repo;
   private EntityManagerInterface $em;

   public function __construct(RoleRepository $repo, EntityManagerInterface $em) {
      $this->repo = $repo;
      $this->em = $em;
   }

     /**
     * Creates or updates an role
     * 
     * @Route("/role/new", name="role_new", methods={"GET","POST"})
     */
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
            $role = $form->getData();
            if (null !== $role->getId()) {
                $role = $this->repo->find($role->getId());
                $role->fill($role);
            } elseif ($this->checkAlreadyExists($role)) {
                $this->addFlash('error', 'messages.roleAlreadyExist');
                $template = $this->getAjax() || $request->isXmlHttpRequest() ? '_form.html.twig' : 'edit.html.twig';
                return $this->render('role/' . $template, [
                    'form' => $form->createView(),
                ], new Response(null, 422));        
            }
            $this->em->persist($role);
            $this->em->flush();
            if ($this->getAjax() || $request->isXmlHttpRequest()) {
               return new Response(null, 204);
            }
            return $this->redirectToRoute('role_index');
        }
        $template = $this->getAjax() || $request->isXmlHttpRequest() ? '_form.html.twig' : 'edit.html.twig';
        return $this->render('role/' . $template, [
            'form' => $form->createView(),
        ], new Response(null, $form->isSubmitted() && ( !$form->isValid() )? 422 : 200,));        
   }

      /**
       * Show the role form specified by id.
       * The Role can't be changed
       * 
       * @Route("/role/{role}", name="role_show", methods={"GET"})
       */
      public function show(Request $request, Role $role): Response
      {
         $form = $this->createForm(RoleType::class, $role, [
               'readonly' => true,
               'locale' => $request->getLocale(),
         ]);
         $template = $this->getAjax() || $request->isXmlHttpRequest() ? '_form.html.twig' : 'edit.html.twig';
         return $this->render('role/' . $template, [
               'role' => $role,
               'form' => $form->createView(),
               'readonly' => true,
               'new' => false,
         ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
      }

      /**
      * Renders the Role form specified by id to edit it's fields
      * 
      * @Route("/role/{role}/edit", name="role_edit", methods={"GET","POST"})
      */
      public function edit(Request $request, Role $role): Response
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
            'form' => $form->createView(),
            'readonly' => false,
            'new' => false,
         ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
      }


    /**
     * @Route("/role/{role}/delete", name="role_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Role $role): Response
    {
        $workers = $role->getApplications();
        if ( count($workers) > 0 ) {
            $this->addFlash('error', new TranslatableMessage('error.roleHasApplications', 
            ['{workers}' => substr(implode(',',$workers->toArray()),0,50).'...'], 'messages'));
            return $this->render('common/_error.html.twig',[], new Response('', 422));
        }
        if ($this->isCsrfTokenValid('delete'.$role->getId(), $request->get('_token'))) {
            $this->em->remove($role);
            $this->em->flush();
            if (!$request->isXmlHttpRequest()) {
                return $this->redirectToRoute('role_index');
            } else {
                return new Response(null, 204);
            }
        } else {
            return new Response('messages.invalidCsrfToken', 422);
        }
    }   

   /**
    * @Route("/role", name="role_index")
    */
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
            'form' => $form->createView(),
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

