<?php

namespace App\Controller;

use App\Repository\JobRepository;
use App\Controller\BaseController;
use App\Entity\Job;
use App\Entity\WorkerJob;
use App\Form\JobType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_RRHH')]
#[Route(path: '/{_locale}', requirements: ['_locale' => 'es|eu|en'])]
class JobController extends BaseController
{

    public function __construct(private readonly JobRepository $repo, private readonly EntityManagerInterface $em)
    {
    }

    #[Route(path: '/job/permissions', name: 'job_permission_list')]
    public function permissions(Request $request) {
        $id = $request->get('job');
        $job = $this->repo->find($id);
        return $this->json($job);
    }

    #[Route(path: '/job/new', name: 'job_new')]
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
            'form' => $form,
            'readonly' => false,
            'new' => true,
        ]);        
    }

    #[Route(path: '/job/{job}/edit', name: 'job_edit')]
    public function edit(Request $request, #[MapEntity(id: 'job')] Job $job) {
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
            'form' => $form,
            'readonly' => false,
            'new' => false,
        ]);        
    }

    #[Route(path: '/job/{job}', name: 'job_show')]
    public function show(Request $request, #[MapEntity(id: 'job')] Job $job) {
        $this->loadQueryParameters($request);
        $form = $this->createForm(JobType::class, $job,[
            'readonly' => true,
        ]);

        return $this->render('job/edit.html.twig', [
            'form' => $form,
            'readonly' => true,
            'new' => false,
        ]);
    }

    #[Route(path: '/job/{job}/delete', name: 'job_delete', methods: ['GET'])]
    public function delete(Request $request, #[MapEntity(id: 'job')] Job $job)
    {
        $workers = $job->getWorkerJob()->toArray();
        if ( count($workers) > 0 ) {
            $this->addFlash('error', new TranslatableMessage('error.jobHasWorkers', 
            ['{workers}' => substr(implode(',',$this->getWorkerDnisArray($workers)),0,50).'...'], 'messages'));
            return $this->redirectToRoute('job_index');
        }
        $this->loadQueryParameters($request);
        $this->em->remove($job);
        $this->em->flush();
        $this->addFlash('success', 'job.deleted');

        return $this->redirectToRoute('job_index');
    }    


    #[Route(path: '/job', name: 'job_index')]
    public function index(Request $request): Response
    {
        $this->loadQueryParameters($request);
        $jobs = $this->repo->findAll();
        return $this->render('job/index.html.twig', [
            'jobs' => $jobs,
        ]);
    }


    #[Route(path: '/job/{job}/permissions', name: 'job_permission_list')]
    public function jobPermissions(Request $request, job $job): Response
    {
        $this->loadQueryParameters($request);
        $permissions = $job->getPermissions();

        return $this->render('permission/_job_permission_list.html.twig', [
            'permissions' => $permissions,
        ]);        
    }

    private function getWorkerDnisArray(array $workers) {
        $workerDnis = [];
        /** @var WorkerJob $workerJob */
        foreach($workers as $workerJob) {
            $workerDnis[] = $workerJob->getWorker()->getDni();
        }
        return $workerDnis;
    }
}
