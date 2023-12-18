<?php

namespace App\Controller;

use App\Controller\BaseController;
use App\Entity\Department;
use App\Form\DepartmentType;
use App\Repository\DepartmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[IsGranted('ROLE_ADMIN')]
#[Route(path: '/{_locale}', requirements: ['_locale' => 'es|eu|en'])]
class DepartmentController extends BaseController
{

   public function __construct(private readonly DepartmentRepository $repo, private readonly EntityManagerInterface $em)
   {
   }

     /**
     * Creates or updates an department
     */
    #[Route(path: '/department/new', name: 'department_new', methods: ['GET', 'POST'])]
    public function createOrSave(Request $request): Response
    {
        $this->loadQueryParameters($request);
        $department = $this->createDepartment($request);
        $form = $this->createForm(DepartmentType::class, $department,[
            'readonly' => false,
            'locale' => $request->getLocale(),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Department $permission */
            $permission = $form->getData();
            if (null !== $permission->getId()) {
                $department = $this->repo->find($permission->getId());
                $department->fill($permission);
            } elseif ($this->checkAlreadyExists($department)) {
                $this->addFlash('error', 'messages.departmentAlreadyExist');
                $template = $this->getAjax() || $request->isXmlHttpRequest() ? '_form.html.twig' : 'edit.html.twig';
                return $this->render('department/' . $template, [
                    'form' => $form,
                ], new Response(null, \Symfony\Component\HttpFoundation\Response::HTTP_UNPROCESSABLE_ENTITY));        
            }
            $this->em->persist($department);
            $this->em->flush();
            if ($this->getAjax() || $request->isXmlHttpRequest()) {
               return new Response(null, \Symfony\Component\HttpFoundation\Response::HTTP_NO_CONTENT);
            }
            return $this->redirectToRoute('department_index');
        }
        $template = $this->getAjax() || $request->isXmlHttpRequest() ? '_form.html.twig' : 'edit.html.twig';
        return $this->render('department/' . $template, [
            'form' => $form,
        ], new Response(null, $form->isSubmitted() && ( !$form->isValid() )? 422 : 200,));        
   }

      /**
       * Show the Department form specified by id.
       * The Department can't be changed
       */
      #[Route(path: '/department/{department}', name: 'department_show', methods: ['GET'])]
      public function show(Request $request, Department $department): Response
      {
         $form = $this->createForm(DepartmentType::class, $department, [
               'readonly' => true,
               'locale' => $request->getLocale(),
         ]);
         $template = $this->getAjax() || $request->isXmlHttpRequest() ? '_form.html.twig' : 'edit.html.twig';
         return $this->render('department/' . $template, [
               'department' => $department,
               'form' => $form,
               'readonly' => true,
               'new' => false,
         ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
      }

      /**
       * Renders the Department form specified by id to edit it's fields
       */
      #[Route(path: '/department/{department}/edit', name: 'department_edit', methods: ['GET', 'POST'])]
      public function edit(Request $request, Department $department): Response
      {
         $form = $this->createForm(DepartmentType::class, $department, [
            'readonly' => false,
            'locale' => $request->getLocale(),
         ]);
         $form->handleRequest($request);
         if ($form->isSubmitted() && $form->isValid()) {
            /** @var Department $department */
            $department = $form->getData();
            $this->em->persist($department);
            $this->em->flush();
         }

         $template = $this->getAjax() || $request->isXmlHttpRequest() ? '_form.html.twig' : 'edit.html.twig';
         return $this->render('department/' . $template, [
            'department' => $department,
            'form' => $form,
            'readonly' => false,
            'new' => false,
         ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
      }


    #[Route(path: '/department/{department}/delete', name: 'department_delete', methods: ['DELETE'])]
    public function delete(Request $request, Department $department): Response
    {
        $workers = $department->getWorkers();
        if ( count($workers) > 0 ) {
            $this->addFlash('error', new TranslatableMessage('error.departmentHasWorkers', 
            ['{workers}' => substr(implode(',',$workers->toArray()),0,50).'...'], 'messages'));
            return $this->render('common/_error.html.twig',[], new Response('', \Symfony\Component\HttpFoundation\Response::HTTP_UNPROCESSABLE_ENTITY));
        }
        if ($this->isCsrfTokenValid('delete'.$department->getId(), $request->get('_token'))) {
            $this->em->remove($department);
            $this->em->flush();
            if (!$request->isXmlHttpRequest()) {
                return $this->redirectToRoute('department_index');
            } else {
                return new Response(null, \Symfony\Component\HttpFoundation\Response::HTTP_NO_CONTENT);
            }
        } else {
            return new Response('messages.invalidCsrfToken', \Symfony\Component\HttpFoundation\Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }   

   #[Route(path: '/department', name: 'department_index')]
    public function index(Request $request): Response
    {
        $this->loadQueryParameters($request);
        $departments = $this->repo->findAll();
        $department = $this->createDepartment($request);
        $form = $this->createForm(DepartmentType::class, $department,[
            'readonly' => false,
            'locale' => $request->getLocale(),
        ]);

        $template = !$this->getAjax() ? 'department/index.html.twig' : 'department/_list.html.twig';
        return $this->render($template, [
            'departments' => $departments,
            'form' => $form,
        ]);        
    }

   private function checkAlreadyExists(Department $department) {
      $result = $this->repo->findDepartmentByExample($department);
      return $result !== null ? true : false;
  }    

   private function createDepartment(Request $request) {
      $department = new Department();
      if ( $request->get('name') ) {
          $department->setNameEs($request->get('name'));
      }
      return $department;
  }

}

