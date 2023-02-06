<?php

namespace App\Controller;

use App\Controller\BaseController;
use App\Entity\Application;
use App\Form\ApplicationType;
use App\Repository\ApplicationRepository;
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
class ApplicationController extends BaseController
{

   private ApplicationRepository $repo;
   private EntityManagerInterface $em;

   public function __construct(ApplicationRepository $repo, EntityManagerInterface $em) {
      $this->repo = $repo;
      $this->em = $em;
   }

     /**
     * Creates or updates an application
     * 
     * @Route("/application/new", name="application_save", methods={"GET","POST"})
     */
    public function createOrSave(Request $request): Response
    {
        $this->loadQueryParameters($request);
        $application = $this->createApplication($request);
        $form = $this->createForm(ApplicationType::class, $application,[
            'readonly' => false,
            'locale' => $request->getLocale(),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Application $data */
            $data = $form->getData();
            if (null !== $data->getId()) {
                $application = $this->repo->find($data->getId());
                $application->fill($data);
            } elseif ($this->checkAlreadyExists($application)) {
                $this->addFlash('error', 'messages.applicationAlreadyExist');
                $template = $this->getAjax() || $request->isXmlHttpRequest() ? '_form.html.twig' : 'edit.html.twig';
                return $this->render('application/' . $template, [
                    'form' => $form->createView(),
                ], new Response(null, 422));        
            }
            $this->em->persist($application);
            $this->em->flush();
            if ($this->getAjax() || $request->isXmlHttpRequest()) {
               return new Response(null, 204);
            }
            return $this->redirectToRoute('application_index');
        }
        $template = $this->getAjax() || $request->isXmlHttpRequest() ? '_form.html.twig' : 'edit.html.twig';
        return $this->render('application/' . $template, [
            'form' => $form->createView(),
        ], new Response(null, $form->isSubmitted() && ( !$form->isValid() )? 422 : 200,));        
   }

      /**
       * Show the application form specified by id.
       * The application can't be changed
       * 
       * @Route("/application/{application}", name="application_show", methods={"GET"})
       */
      public function show(Request $request, Application $application): Response
      {
         $form = $this->createForm(ApplicationType::class, $application, [
               'readonly' => true,
               'locale' => $request->getLocale(),
         ]);
         $template = $this->getAjax() || $request->isXmlHttpRequest() ? '_form.html.twig' : 'show.html.twig';
         return $this->render('application/' . $template, [
               'application' => $application,
               'form' => $form->createView(),
               'readonly' => true,
               'new' => false,
         ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
      }

      /**
       * Renders the application form specified by id to edit it's fields
      * 
      * @Route("/application/{application}/edit", name="application_edit", methods={"GET","POST"})
      */
      public function edit(Request $request, Application $application, EntityManagerInterface $entityManager): Response
      {
         $form = $this->createForm(ApplicationType::class, $application, [
            'readonly' => false,
            'locale' => $request->getLocale(),
         ]);
         $form->handleRequest($request);
         if ($form->isSubmitted() && $form->isValid()) {
            /** @var Application $application */
            $application = $form->getData();
            $entityManager->persist($application);
            $entityManager->flush();
         }

         $template = $this->getAjax() || $request->isXmlHttpRequest() ? '_form.html.twig' : 'edit.html.twig';
         return $this->render('application/' . $template, [
            'application' => $application,
            'form' => $form->createView(),
            'readonly' => false,
            'new' => false,
         ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
      }


    /**
     * @Route("/application/{application}/delete", name="application_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Application $application): Response
    {
        $workers = $application->getWorkers();
        if ( count($workers) > 0 ) {
            $this->addFlash('error', new TranslatableMessage('error.applicationHasWorkers', 
                ['{workers}' => substr(implode(',',$workers->toArray()),0,50).'...'], 'messages'));
            return $this->render('common/_error.html.twig',[], new Response('', 422));
        }
        if ($this->isCsrfTokenValid('delete'.$application->getId(), $request->get('_token'))) {
            $this->em->remove($application);
            $this->em->flush();
            if (!$request->isXmlHttpRequest()) {
                return $this->redirectToRoute('application_index');
            } else {
                return new Response(null, 204);
            }
        } else {
            return new Response('messages.invalidCsrfToken', 422);
        }
    }   

   /**
    * @Route("/application", name="application_index")
    */
    public function index(Request $request): Response
    {
        $this->loadQueryParameters($request);
        $applications = $this->repo->findAll();
        $application = $this->createApplication($request);
        $form = $this->createForm(ApplicationType::class, $application,[
            'readonly' => false,
            'locale' => $request->getLocale(),
        ]);

        $template = !$this->getAjax() ? 'application/index.html.twig' : 'application/_list.html.twig';
        return $this->render($template, [
            'applications' => $applications,
            'form' => $form->createView(),
        ]);        
    }

   private function checkAlreadyExists(Application $application) {
      $result = $this->repo->findApplicationByExample($application);
      return $result !== null ? true : false;
  }    

   private function createApplication(Request $request) {
      $application = new Application();
      if ( $request->get('name') ) {
          $application->setName($request->get('name'));
      }
      return $application;
  }

}
