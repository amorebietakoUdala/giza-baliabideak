<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_GIZA_BALIABIDEAK')]
class DefaultController extends AbstractController
{
    #[Route(path: '/', name: 'app_home')]
    public function index(): Response
    {
        return $this->redirectToRoute('worker_index');
    }
}
