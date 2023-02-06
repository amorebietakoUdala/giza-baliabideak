<?php

namespace App\Controller;

use App\Entity\Job;
use App\Repository\JobRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api")
 */
class ApiController extends AbstractController
{

   private JobRepository $repo;

   public function __construct(JobRepository $repo) {
      $this->repo = $repo;
   }

   /**
    * @Route("/job", name="api_get_job")
    */
   public function show(Request $request) {
      $job = $this->repo->find($request->get('id'));
      return $this->json($job, 200, [], [
         'groups' => ['show']
      ]);
   }
}
