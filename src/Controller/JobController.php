<?php

namespace App\Controller;

use App\Repository\JobRepository;
use App\Controller\BaseController;
use App\Entity\Job;
use App\Form\JobType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Route("/{_locale}", requirements={
 *	    "_locale": "es|eu|en"
 * })
 * @Security("is_granted('ROLE_RRHH')")
 */
class JobController extends BaseController
{

    private JobRepository $repo;
    private EntityManagerInterface $em;

    public function __construct(JobRepository $repo, EntityManagerInterface $em) {
        $this->repo = $repo;
        $this->em = $em;
    }

    /**
     * @Route("/job/permissions", name="job_permission_list")
     */
    public function permissions(Request $request) {
        $id = $request->get('job');
        $job = $this->repo->find($id);
        return $this->json($job);
    }

    /**
     * @Route("/job/new", name="job_new")
     */
     public function new(Request $request) {
        $this->loadQueryParameters($request);
        $form = $this->createForm(JobType::class, new Job());
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Job $job */
            $job = $form->getData();
            $this->em->persist($job);
            $this->em->flush();
            $this->addFlash('success', 'job.created');

            return $this->redirectToRoute('job_index');
        }

        return $this->render('job/edit.html.twig', [
            'form' => $form->createView(),
            'readonly' => false,
            'new' => true,
        ]);        
    }

    /**
     * @Route("/job/{job}/edit", name="job_edit")
     */
    public function edit(Request $request, Job $job) {
        $this->loadQueryParameters($request);
        $form = $this->createForm(JobType::class, $job);

        $form->handleRequest($request);
        if ( $form->isSubmitted() && $form->isValid() ) {
            /** @var Job $job */
            $job = $form->getData();
            $this->em->persist($job);
            $this->em->flush();
            $this->addFlash('success', 'job.saved');
        }

        return $this->render('job/edit.html.twig', [
            'form' => $form->createView(),
            'readonly' => false,
            'new' => false,
        ]);        
    }

    /**
     * @Route("/job/{job}", name="job_show")
     */
    public function show(Request $request, Job $job) {
        $this->loadQueryParameters($request);
        $form = $this->createForm(JobType::class, $job,[
            'readonly' => true,
        ]);

        return $this->render('job/edit.html.twig', [
            'form' => $form->createView(),
            'readonly' => true,
            'new' => false,
        ]);
    }

    /**
     * @Route("/job/{job}/delete", name="job_delete", methods={"GET"})
     */
    public function delete(Request $request, Job $job)
    {
        $workers = $job->getWorkers();
        if ( count($workers) > 0 ) {
            $this->addFlash('error', new TranslatableMessage('error.jobHasWorkers', 
            ['{workers}' => substr(implode(',',$workers->toArray()),0,50).'...'], 'messages'));
            return $this->redirectToRoute('job_index');
        }
        $this->loadQueryParameters($request);
        $this->em->remove($job);
        $this->em->flush();
        $this->addFlash('success', 'job.deleted');

        return $this->redirectToRoute('job_index');
    }    


    /**
     * @Route("/job", name="job_index")
     */
    public function index(Request $request): Response
    {
        $this->loadQueryParameters($request);
        $jobs = $this->repo->findAll();
        return $this->render('job/index.html.twig', [
            'jobs' => $jobs,
        ]);
    }
}
