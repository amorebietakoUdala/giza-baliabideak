<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Worker;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Email;

class MailingService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $mailerFrom,
        private readonly bool $sendBCC,
        private readonly string $mailerBCC,
        private readonly string $mailerMM,
    ) {}

    /**
     * Agrupa permisos por criterio (appOwner o userCreator)
     */
    private function groupPermissionsBy(array $permissions, string $criteria): array
    {
        $permissionsMap = [];

        foreach ($permissions as $permission) {
            $application = $permission->getApplication();
            if (!$application) {
                continue;
            }

            $emailsString = match ($criteria) {
                'appOwner' => $application->getAppOwnersEmails(),
                'userCreator' => $application->getUserCreatorEmail(),
                default => null,
            };

            if ($criteria === 'appOwner') {
                foreach ($application->getAppOwners() as $owner) {
                    $permissionsMap[$owner->getEmail()][] = $permission;
                }
            }

            if ($emailsString) {
                foreach (explode(',', $emailsString) as $email) {
                    $permissionsMap[$email][] = $permission;
                }
            }
        }

        return $permissionsMap;
    }

    /**
     * Método genérico para enviar emails con plantilla
     */
    private function sendTemplatedEmail(string $subject, array $recipients, string $template, array $context): void
    {
        $email = (new TemplatedEmail())
            ->from($this->mailerFrom)
            ->to(...$recipients)
            ->subject($subject)
            ->htmlTemplate($template)
            ->context($context);

        $this->mailer->send($email);
    }

    public function sendMessageToUserCreators(string $subject, Worker $worker, ?array $approvedPermissions, bool $remove = false): void
    {
        $permissions = $approvedPermissions ?? $worker->getPermissions()->toArray();
        $userCreatorMap = $this->groupPermissionsBy($permissions, 'userCreator');

        foreach ($userCreatorMap as $creatorEmail => $permissionsGroup) {
            $this->sendTemplatedEmail($subject, [$creatorEmail], 'worker/userCreatorsMail.html.twig', [
                'worker' => $worker,
                'permissions' => $permissionsGroup,
                'remove' => $remove,
            ]);
        }
    }

    public function sendMessageToAppOwners(string $subject, User $user, Worker $worker, ?array $newPermissions, bool $remove = false): void
    {
        $permissions = $newPermissions ?? $worker->getPermissions()->toArray();
        $permissionsMap = $this->groupPermissionsBy($permissions, 'appOwner');

        foreach ($permissionsMap as $ownerEmail => $permissionsGroup) {
            $this->sendTemplatedEmail($subject, [$ownerEmail], 'worker/appOwnersMail.html.twig', [
                'user' => $user,
                'worker' => $worker,
                'permissions' => $permissionsGroup,
                'remove' => $remove,
            ]);
        }
    }

    public function sendMessageToBoss(string $subject, Worker $worker, bool $validate = false, bool $deleteOperation = false): void
    {
        if ($worker->getWorkerJob()->getJob() !== null) {
            $bosses = $worker->getWorkerJob()->getJob()->getBosses();
            $emails = [];
            foreach ($bosses as $boss) {
                if ($boss->getEmail()) {
                    $emails[] = $boss->getEmail();
                }
            }
            if ($emails) {
                $this->sendTemplatedEmail($subject, $emails, 'worker/bossRevisionPendingMail.html.twig', [
                    'worker' => $worker,
                    'validate' => $validate,
                    'deleteOperation' => $deleteOperation,
                ]);
            }
        }
    }

    public function sendMessageToMM(string $subject, Worker $worker, bool $validate = false, bool $deleteOperation = false): void
    {
        $emails = $this->mailerMM ? explode(',', $this->mailerMM) : [];
        if ($emails) {
            $this->sendTemplatedEmail($subject, $emails, 'worker/bossRevisionPendingMail.html.twig', [
                'worker' => $worker,
                'validate' => $validate,
                'deleteOperation' => $deleteOperation,
            ]);
        }
    }

    public function sendMessageToIT(string $subject, Worker $worker, bool $validate = false, bool $deleteOperation = false): void
    {
        $emails = $this->mailerBCC ? explode(',', $this->mailerBCC) : [];
        if ($emails) {
            $this->sendTemplatedEmail($subject, $emails, 'worker/bossRevisionPendingMail.html.twig', [
                'worker' => $worker,
                'validate' => $validate,
                'deleteOperation' => $deleteOperation,
            ]);
        }
    }

    public function sendUsernamePendingMessageToIT(string $subject, Worker $worker): void
    {
        $emails = $this->mailerBCC ? explode(',', $this->mailerBCC) : [];
        if ($emails) {
            $this->sendTemplatedEmail($subject, $emails, 'worker/usernamePendingMail.html.twig', [
                'worker' => $worker,
            ]);
        }
    }

    public function sendMessage(string $subject, array $to, string $html): void
    {
        $email = (new Email())
            ->from($this->mailerFrom)
            ->to(...$to)
            ->subject($subject)
            ->html($html);

        if ($this->sendBCC && $this->mailerBCC) {
            $email->addBcc($this->mailerBCC);
        }

        $this->mailer->send($email);
    }
}