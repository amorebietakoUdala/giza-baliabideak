<?php

namespace App\Controller;

use App\Repository\ApplicationRepository;
use App\Repository\JobRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_GIZA_BALIABIDEAK')]
#[Route(path: '/api')]
class ApiController extends AbstractController
{

   public function __construct(private readonly JobRepository $repo, private readonly ApplicationRepository $appRepo)
   {
   }

   #[Route(path: '/job', name: 'api_get_job')]
   public function show(Request $request) {
      $job = $this->repo->find($request->get('id'));
      return $this->json($job, 200, [], [
         'groups' => ['show']
      ]);
   }

   #[Route(path: '/application', name: 'api_get_application')]
   public function applicationRoles(Request $request) {
      $id = $request->get('application');
      $application = $this->appRepo->find($id);
      return $this->json($application, 200, [], [
         'groups' => ['show']
      ]);

   }
}
