<?php

namespace App\Controller;

use App\Controller\BaseController;
use App\Entity\Historic;
use App\Entity\Worker;
use App\Form\WorkerType;
use App\Form\WorkerSearchType;
use App\Repository\WorkerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Route("/{_locale}", requirements={
 *	    "_locale": "es|eu|en"
 * })
  */
class WorkerController extends BaseController
{

    private WorkerRepository $repo;
    private EntityManagerInterface $em;
    private MailerInterface $mailer;
//    private JobRepository $jobRepo;
    
    public function __construct(WorkerRepository $repo, EntityManagerInterface $em, MailerInterface $mailer)
    {
        $this->repo = $repo;
//        $this->jobRepo = $jobRepo;
        $this->em = $em;
        $this->mailer = $mailer;
    }

    /**
     * @Route("/worker/new", name="worker_new")
     * @Security("is_granted('ROLE_RRHH')")
     */
    public function new(Request $request) {
        $this->loadQueryParameters($request);
        $form = $this->createForm(WorkerType::class, new Worker(), [
            'new' => true,
            'locale' => $request->getLocale(),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Worker $data */
            $data = $form->getData();
            $existingWorker = $this->repo->findOneBy([
                'dni' => $data->getDni(),
            ]);
            $error = $this->checkForErrors($data);
            if ($error) {
                return $this->renderEdit($form, true, false);
            }
            if ($existingWorker) {
                if ($existingWorker->getStatus() !== Worker::STATUS_DELETED ) {
                    $this->addFlash('error','worker.alreadyExists');
                    return $this->renderEdit($form, true, false);
                } else {
                    $existingWorker->fill($data);
                    $existingWorker->setStatus(Worker::STATUS_RRHH_NEW);
                    $this->em->persist($existingWorker);
                    $this->updateJobApplications($existingWorker);
                    $this->createHistoric($existingWorker);
                    $this->em->flush();
                    $this->sendMessage('Langile berria gorde da / Se ha dado de alta un nuevo empleado', [$this->getParameter('mailerBCC')], $data);
                    $this->addFlash('warning','worker.alreadyExistsStatusChanged');
                }
            } else {
                $data->setStatus(Worker::STATUS_RRHH_NEW);
                $this->em->persist($data);
                $this->updateJobApplications($data);
                $this->createHistoric($data);
                $this->em->flush();
                $this->sendMessage('Langile berria gorde da / Se ha dado de alta un nuevo empleado', [$this->getParameter('mailerBCC')], $data);
                $this->addFlash('success', 'worker.created');
            }
            return $this->redirectToRoute('worker_edit', ['worker' => $data->getId()]);
        }

        return $this->renderEdit($form, true, false);
    }

    /**
     * @Route("/worker/{worker}/validate", name="worker_validate")
     * @Security("is_granted('ROLE_BOSS')")
     */
    public function validate(Request $request, Worker $worker) {
        $this->loadQueryParameters($request);
        $form = $this->createForm(WorkerType::class, $worker,[
            'locale' => $request->getLocale(),
            'roleBossOnly' => $this->isGranted('ROLE_BOSS') && !$this->isGranted('ROLE_RRHH'),
        ]);

        $form->handleRequest($request);
        if ( $form->isSubmitted() && $form->isValid() ) {
            /** @var Worker $data */
            $data = $form->getData();
            $data->setStatus(Worker::STATUS_IN_PROGRESS);
            $this->em->persist($data);
            $this->updateJobApplications($data);
            $this->createHistoric($data);
            $this->sendMessage('Langile berriari informatikak baimenak eman behar zaizkio / Informática tiene que dar los permisos al nuevo empleado', [$this->getParameter('mailerBCC')], $data);
            $this->em->flush();
            $this->addFlash('success', 'worker.saved');
            $form = $this->createForm(WorkerType::class, $data,[
                'locale' => $request->getLocale(),
            ]);
        }

        return $this->renderEdit($form, false, false);
    }

    /**
     * @Route("/worker/{worker}/send", name="worker_send", methods={"POST"})
     */
    public function send(Request $request, Worker $worker) {
        $this->loadQueryParameters($request);
        $form = $this->createForm(WorkerType::class, $worker,[
            'locale' => $request->getLocale(),
            'roleBossOnly' => $this->isGranted('ROLE_BOSS') && !$this->isGranted('ROLE_RRHH'),
        ]);

        $form->handleRequest($request);
        if ( $form->isSubmitted() && $form->isValid() ) {
            /** @var Worker $data */
            $data = $form->getData();
            $data->setStatus(Worker::STATUS_REVISION_PENDING);
            $this->em->persist($data);
            $this->createHistoric($data);
            $this->sendMessageToBoss('Langile berriaren baimenak hautatu / Seleccione los permisos del nuevo empleado', $data);
            $this->em->flush();
            $this->addFlash('success', 'worker.sent');
            $form = $this->createForm(WorkerType::class, $data,[
                'locale' => $request->getLocale(),
            ]);
        }

        return $this->renderEdit($form, false, false);
    }

    /**
     * @Route("/worker/{worker}/edit", name="worker_edit")
     */
    public function edit(Request $request, Worker $worker) {
        $this->loadQueryParameters($request);
        $form = $this->createForm(WorkerType::class, $worker, [
            'locale' => $request->getLocale(),
            'roleBossOnly' => $this->isGranted('ROLE_BOSS') && !$this->isGranted('ROLE_RRHH'),
        ]);

        $form->handleRequest($request);
        if ( $form->isSubmitted() && $form->isValid() ) {
            /** @var Worker $data */
            $data = $form->getData();
            $error = $this->checkForErrors($data);
            if ($error) {
                return $this->renderEdit($form, false, false);
            }
            $this->em->persist($data);
            $this->updateJobApplications($data);
            $this->createHistoric($data);
            $this->em->flush();
            $this->addFlash('success', 'worker.saved');
        }

        return $this->renderEdit($form, false, false);
    }

    /**
     * @Route("/worker/{worker}", name="worker_show")
     */
    public function show(Request $request, Worker $worker) {
        $this->loadQueryParameters($request);
        $form = $this->createForm(WorkerType::class, $worker,[
            'readonly' => true,
            'locale' => $request->getLocale(),
        ]);

        return $this->renderEdit($form);
    }

    /**
     * @Route("/worker/{worker}/delete", name="worker_delete", methods={"GET"})
     */
    public function delete(Request $request, Worker $worker)
    {
        $this->loadQueryParameters($request);
        $worker->setStatus(Worker::STATUS_DELETED);
        $this->createHistoric($worker);
        $this->sendMessage('Langile hau ezabatu egin da / El siguiente empleado se ha dado de baja', [$this->getParameter('mailerBCC')], $worker);
        $this->em->flush();
        $this->addFlash('success', 'worker.deleted');

        return $this->redirectToRoute('worker_index');
    }    

    /**
     * @Route("/worker", name="worker_index")
     */
    public function index(Request $request): Response
    {
        $this->loadQueryParameters($request);
        $worker = $this->fillWorkerFilter($request);
        if ( $this->isGranted('ROLE_BOSS') && !$this->isGranted('ROLE_RRHH') && $request->get('status') === null ) {
            $worker['status'] = Worker::STATUS_REVISION_PENDING;
        }
        $form = $this->createForm(WorkerSearchType::class, $worker);
        $form->handleRequest($request);
        if ( $form->isSubmitted() && $form->isValid() ) {
            $worker = $form->getData();
            $this->queryParams['page'] = 1;
        }
        $workers = $this->repo->findByExample($worker);
        return $this->render('worker/index.html.twig', [
            'workers' => $workers,
            'form' => $form->createView(),
            'filters' => $this->remove_blank_filters($worker),
        ]);
    }

    private function updateJobApplications(Worker $worker) 
    {
        $job = $worker->getJob();
        $applications = $worker->getApplications();
        if ( null != $job->getApplications() ) {
            $job->getApplications()->clear();
        }
        foreach($applications as $app) {
            $job->addApplication($app);
        }
        $this->em->persist($job);
    }

    private function checkForErrors(Worker $worker) 
    {
        $error = false;
        if ( $worker->getEndDate() === null && !$worker->isNoEndDate() ) {
            $this->addFlash('error','worker.endDateNotSet');
            $error = true;
        }
        if ( $worker->getEndDate() !== null && ( $worker->getEndDate() < $worker->getStartDate()) ) {
            $this->addFlash('error','worker.endDateGreaterThanStartDate');
            $error = true;
        }
        if ( $worker->getEndDate() !== null && $worker->isNoEndDate() ) {
            $this->addFlash('error','worker.cannotsetEndDateAndNoEndDate');
            $error = true;
        }
        // $existingWorker = $this->repo->findOneBy([
        //     'expedientNumber' => $worker->getExpedientNumber(),
        // ]);
        // if ($existingWorker) {
        //     $this->addFlash('error','worker.expedientNumberAlreadyExists');
        //     $error = true;
        // }
        return $error;
    }
    
    private function renderEdit(FormInterface $form, $new = false, $readonly = true) {
        return $this->render('worker/edit.html.twig', [
            'form' => $form->createView(),
            'readonly' => $readonly,
            'new' => $new,
        ]);        
    }

    private function fillWorkerFilter(Request $request): array {
        $worker = [];
        $worker['dni'] = $request->get('dni') ?? null;
        $worker['name'] = $request->get('name') ?? null;
        $worker['surname1'] = $request->get('surname1') ?? null;
        $worker['expedientNumber'] = $request->get('expedientNumber') ?? null;
        $worker['status'] = $request->get('status');
        return $worker;
    }    

    private function createHistoric(Worker $worker) {
        $historic = new Historic();
        $historic->fill($worker, $this->getUser());
        $this->em->persist($historic);
    }

    private function sendMessageToBoss($subject, Worker $worker) {
        if ($worker->getJob() !== null) {
            $bosses = $worker->getJob()->getBosses();
            $emails = [];
            foreach ($bosses as $boss) {
                if ($boss->getEmail()) {
                    $emails[] = $boss->getEmail();
                }
            }
            $this->sendMessage($subject, $emails, $worker, true);
        }
    }

    private function sendMessage($subject, array $to, Worker $worker, $validate = false)
    {
        $email = (new Email())
            ->from($this->getParameter('mailer_from'))
            ->to(...$to)
            ->subject($subject)
            ->html($this->renderView('worker/bossRevisionPendingMail.html.twig', [
                'worker' => $worker,
                'validate' => $validate,
            ])
        );
        if ( $this->getParameter('sendBCC') ) {
            $addresses = [$this->getParameter('mailerBCC')];
            foreach ($addresses as $address) {
                $email->addBcc($address);
            }
        }            
        $this->mailer->send($email);
    }

    private function remove_blank_filters($criteria)
    {
        $new_criteria = [];
        foreach ($criteria as $key => $value) {
            if (!empty($value)) {
                $new_criteria[$key] = $value;
            }
        }

        return $new_criteria;
    }

}
