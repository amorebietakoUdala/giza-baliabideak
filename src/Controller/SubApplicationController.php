<?php

namespace App\Controller;

use App\Controller\BaseController;
use App\Entity\SubApplication;
use App\Form\SubApplicationType;
use App\Repository\SubApplicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[IsGranted('ROLE_ADMIN')]
#[Route(path: '/{_locale}', requirements: ['_locale' => 'es|eu|en'])]
class SubApplicationController extends BaseController
{

   public function __construct(private readonly SubApplicationRepository $repo, private readonly EntityManagerInterface $em)
   {
   }

     /**
     * Creates or updates an department
     */
    #[Route(path: '/sub-application/new', name: 'subApplication_new', methods: ['GET', 'POST'])]
    public function createOrSave(Request $request): Response
    {
        $this->loadQueryParameters($request);
        $subApplication = new SubApplication();
        $form = $this->createForm(SubApplicationType::class, $subApplication,[
            'readonly' => false,
            'locale' => $request->getLocale(),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var SubApplication $data */
            $data = $form->getData();
            if (null !== $data->getId()) {
                $subApplication = $this->repo->find($data->getId());
                $subApplication->fill($data);
            } elseif ($this->checkAlreadyExists($subApplication)) {
                $this->addFlash('error', 'messages.departmentAlreadyExist');
                $template = $this->getAjax() || $request->isXmlHttpRequest() ? '_form.html.twig' : 'edit.html.twig';
                return $this->render('sub-application/' . $template, [
                    'form' => $form,
                ], new Response(null, \Symfony\Component\HttpFoundation\Response::HTTP_UNPROCESSABLE_ENTITY));        
            }
            $this->em->persist($subApplication);
            $this->em->flush();
            if ($this->getAjax() || $request->isXmlHttpRequest()) {
               return new Response(null, \Symfony\Component\HttpFoundation\Response::HTTP_NO_CONTENT);
            }
            return $this->redirectToRoute('department_index');
        }
        $template = $this->getAjax() || $request->isXmlHttpRequest() ? '_form.html.twig' : 'edit.html.twig';
        return $this->render('sub-application/' . $template, [
            'form' => $form,
        ], new Response(null, $form->isSubmitted() && ( !$form->isValid() )? 422 : 200,));        
   }

      /**
       * Show the SubApplication form specified by id.
       * The SubApplication can't be changed
       */
      #[Route(path: '/sub-application/{subApplication}', name: 'subApplication_show', methods: ['GET'])]
      public function show(Request $request, SubApplication $subApplication): Response
      {
         $form = $this->createForm(SubApplicationType::class, $subApplication, [
               'readonly' => true,
               'locale' => $request->getLocale(),
         ]);
         $template = $this->getAjax() || $request->isXmlHttpRequest() ? '_form.html.twig' : 'edit.html.twig';
         return $this->render('sub-application/' . $template, [
               'department' => $subApplication,
               'form' => $form,
               'readonly' => true,
               'new' => false,
         ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
      }

      /**
       * Renders the SubApplication form specified by id to edit it's fields
       */
      #[Route(path: '/sub-application/{subApplication}/edit', name: 'subApplication_edit', methods: ['GET', 'POST'])]
      public function edit(Request $request, SubApplication $subApplication): Response
      {
         $form = $this->createForm(SubApplicationType::class, $subApplication, [
            'readonly' => false,
            'locale' => $request->getLocale(),
         ]);
         $form->handleRequest($request);
         if ($form->isSubmitted() && $form->isValid()) {
            /** @var SubApplication $subApplication */
            $subApplication = $form->getData();
            $this->em->persist($subApplication);
            $this->em->flush();
         }

         $template = $this->getAjax() || $request->isXmlHttpRequest() ? '_form.html.twig' : 'edit.html.twig';
         return $this->render('sub-application/' . $template, [
            'department' => $subApplication,
            'form' => $form,
            'readonly' => false,
            'new' => false,
         ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
      }


    #[Route(path: '/sub-application/{subApplication}/delete', name: 'subApplication_delete', methods: ['DELETE'])]
    public function delete(Request $request, SubApplication $subApplication): Response
    {
        $permissions = $subApplication->getPermissions();
        if ( count($permissions) > 0 ) {
            $this->addFlash('error', new TranslatableMessage('error.subAplicationHasPermissions'));
            return $this->render('common/_error.html.twig',[], new Response('', \Symfony\Component\HttpFoundation\Response::HTTP_UNPROCESSABLE_ENTITY));
        }
        if ($this->isCsrfTokenValid('delete'.$subApplication->getId(), $request->get('_token'))) {
            $this->em->remove($subApplication);
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

   #[Route(path: '/sub-application', name: 'subApplication_index')]
    public function index(Request $request): Response
    {
        $this->loadQueryParameters($request);
        $subApplications = $this->repo->findAll();
        $form = $this->createForm(SubApplicationType::class, new SubApplication(),[
            'readonly' => false,
            'locale' => $request->getLocale(),
        ]);

        $template = !$this->getAjax() ? 'sub-application/index.html.twig' : 'sub-application/_list.html.twig';
        return $this->render($template, [
            'subApplications' => $subApplications,
            'form' => $form,
        ]);        
    }

    private function checkAlreadyExists(SubApplication $subApplication) {
        $result = $this->repo->findOneBy([
            'nameEs' => $subApplication->getNameEs(),
        ]);
        if ( $result !== null ) {
            return $result !== null ? true : false;
        }
        $result = $this->repo->findOneBy([
            'nameEu' => $subApplication->getNameEu(),
        ]);
        if ( $result !== null ) {
            return $result !== null ? true : false;
        }
    }    
}

