<?php

namespace App\Command;

use App\Entity\Worker;
use App\Repository\WorkerRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

#[\Symfony\Component\Console\Attribute\AsCommand('app:about-to-expire', 'Search for contracts about to expire in the specified days')]
class AboutToExpireContractsCommand extends Command
{
    public function __construct(private readonly MailerInterface $mailer, private readonly WorkerRepository $repo, private readonly ParameterBagInterface $params, private readonly Environment $twig)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('days', InputArgument::OPTIONAL, 'Number of days about to expire contracts')
//            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $days = $input->getArgument('days') ?? 15;
        $workers = $this->repo->findEndsInNextDays($days);
        if ( $workers ) {
            $this->sendMessage('Amaitzear dauden kontratuak / Contratos a punto de terminar', [$this->params->get('mailerHHRR')], $workers);
        }
        return Command::SUCCESS;
    }

    private function sendMessage($subject, array $to, $workers)
    {
        $email = (new Email())
            ->from($this->params->get('mailer_from'))
            ->to(...$to)
            ->subject($subject)
            ->html($this->twig->render('worker/workerListMail.html.twig', [
                'workers' => $workers,
            ])
        );
        if ( $this->params->get('sendBCC') ) {
            $addresses = [$this->params->get('mailerBCC')];
            foreach ($addresses as $address) {
                $email->addBcc($address);
            }
        }            
        $this->mailer->send($email);
    }
}
