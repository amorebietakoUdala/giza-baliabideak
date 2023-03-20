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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * @Route("/{_locale}", requirements={
 *	    "_locale": "es|eu|en"
 * })
 * @Security("is_granted('ROLE_ADMIN')")
 */
class SubApplicationController extends BaseController
{

   private SubApplicationRepository $repo;
   private EntityManagerInterface $em;

   public function __construct(SubApplicationRepository $repo, EntityManagerInterface $em) {
      $this->repo = $repo;
      $this->em = $em;
   }

     /**
     * Creates or updates an department
     * 
     * @Route("/sub-application/new", name="subApplication_new", methods={"GET","POST"})
     */
    public function createOrSave(Request $request): Response
    {
        $this->loadQueryParameters($request);
//        $subApplication = $this->createSubApplication($request);
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
                    'form' => $form->createView(),
                ], new Response(null, 422));        
            }
            $this->em->persist($subApplication);
            $this->em->flush();
            if ($this->getAjax() || $request->isXmlHttpRequest()) {
               return new Response(null, 204);
            }
            return $this->redirectToRoute('department_index');
        }
        $template = $this->getAjax() || $request->isXmlHttpRequest() ? '_form.html.twig' : 'edit.html.twig';
        return $this->render('sub-application/' . $template, [
            'form' => $form->createView(),
        ], new Response(null, $form->isSubmitted() && ( !$form->isValid() )? 422 : 200,));        
   }

      /**
       * Show the SubApplication form specified by id.
       * The SubApplication can't be changed
       * 
       * @Route("/sub-application/{subApplication}", name="subApplication_show", methods={"GET"})
       */
      public function show(Request $request, SubApplication $subApplication): Response
      {
         $form = $this->createForm(SubApplicationType::class, $subApplication, [
               'readonly' => true,
               'locale' => $request->getLocale(),
         ]);
         $template = $this->getAjax() || $request->isXmlHttpRequest() ? '_form.html.twig' : 'edit.html.twig';
         return $this->render('sub-application/' . $template, [
               'department' => $subApplication,
               'form' => $form->createView(),
               'readonly' => true,
               'new' => false,
         ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
      }

      /**
      * Renders the SubApplication form specified by id to edit it's fields
      * 
      * @Route("/sub-application/{subApplication}/edit", name="subApplication_edit", methods={"GET","POST"})
      */
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
            'form' => $form->createView(),
            'readonly' => false,
            'new' => false,
         ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200,));
      }


    /**
     * @Route("/sub-application/{subApplication}/delete", name="subApplication_delete", methods={"DELETE"})
     */
    public function delete(Request $request, SubApplication $subApplication): Response
    {
        $permissions = $subApplication->getPermissions();
        if ( count($permissions) > 0 ) {
            $this->addFlash('error', new TranslatableMessage('error.subAplicationHasPermissions'));
            return $this->render('common/_error.html.twig',[], new Response('', 422));
        }
        if ($this->isCsrfTokenValid('delete'.$subApplication->getId(), $request->get('_token'))) {
            $this->em->remove($subApplication);
            $this->em->flush();
            if (!$request->isXmlHttpRequest()) {
                return $this->redirectToRoute('department_index');
            } else {
                return new Response(null, 204);
            }
        } else {
            return new Response('messages.invalidCsrfToken', 422);
        }
    }   

   /**
    * @Route("/sub-application", name="subApplication_index")
    */
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
            'form' => $form->createView(),
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

