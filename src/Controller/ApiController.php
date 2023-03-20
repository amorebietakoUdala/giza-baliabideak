<?php

namespace App\Controller;

use App\Repository\ApplicationRepository;
use App\Repository\JobRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api")
 */
class ApiController extends AbstractController
{

   private JobRepository $repo;
   private ApplicationRepository $appRepo;

   public function __construct(JobRepository $repo, ApplicationRepository $appRepo) {
      $this->repo = $repo;
      $this->appRepo = $appRepo;
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

   /**
    * @Route("/application", name="api_get_application")
    */
   public function applicationRoles(Request $request) {
      $id = $request->get('application');
      $application = $this->appRepo->find($id);
      return $this->json($application, 200, [], [
         'groups' => ['show']
      ]);

   }
}
