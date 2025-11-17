<?php

namespace App\Entity;

use App\Repository\WorkerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Query\Expr\Func;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

#[ORM\Entity(repositoryClass: WorkerRepository::class)]
class Worker implements \Stringable
{
    use TimestampableEntity;
    
    final public const STATUS_USERNAME_PENDING = 1;
    final public const STATUS_REVISION_PENDING = 2;
    final public const STATUS_APPROVAL_PENDING = 3;
    final public const STATUS_IN_PROGRESS = 4;
    final public const STATUS_REGISTERED = 5;
    final public const STATUS_DELETED = 6;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['historic'])]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['historic'])]
    private ?string $dni = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['historic'])]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['historic'])]
    private ?string $surname1 = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['historic'])]
    private ?string $surname2 = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $expedientNumber = null;

    #[ORM\ManyToOne(targetEntity: Department::class, inversedBy: 'workers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Department $department = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $status = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $noEndDate = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'workers')]
    #[Groups(['historic'])]
    private ?User $validatedBy = null;

    #[ORM\OneToMany(targetEntity: Permission::class, mappedBy: 'worker')]
    #[Groups(['historic'])]
    #[MaxDepth(1)]
    private Collection|array $permissions;

    #[ORM\OneToOne(mappedBy: 'worker', cascade: ['persist', 'remove'])]
    private ?WorkerJob $workerJob = null;

    /**
     * @var Collection<int, Historic>
     */
    #[ORM\OneToMany(mappedBy: 'worker', targetEntity: Historic::class)]
    private Collection $historics;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $username = null;

    public function __construct()
    {
        $this->permissions = new ArrayCollection();
        $this->historics = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDni(): ?string
    {
        return $this->dni;
    }

    public function setDni(string $dni): self
    {
        $this->dni = $dni;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSurname1(): ?string
    {
        return $this->surname1;
    }

    public function setSurname1(string $surname1): self
    {
        $this->surname1 = $surname1;

        return $this;
    }

    public function getSurname2(): ?string
    {
        return $this->surname2;
    }

    public function setSurname2(string $surname2): self
    {
        $this->surname2 = $surname2;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function isNoEndDate(): ?bool
    {
        return $this->noEndDate;
    }

    public function setNoEndDate(?bool $noEndDate): self
    {
        $this->noEndDate = $noEndDate;

        return $this;
    }

    public function getExpedientNumber(): ?string
    {
        return $this->expedientNumber;
    }

    public function setExpedientNumber(?string $expedientNumber): self
    {
        $this->expedientNumber = $expedientNumber;

        return $this;
    }

    public function getDepartment(): ?Department
    {
        return $this->department;
    }

    public function setDepartment(?Department $department): self
    {
        $this->department = $department;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name.' '.$this->surname1.' '.$this->surname2;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function fill(Worker $worker): self {
        $this->dni = $worker->getDni();
        $this->name = $worker->getName();
        $this->surname1 = $worker->getSurname1();
        $this->surname2 = $worker->getSurname2();
        $this->startDate = $worker->getStartDate();
        $this->endDate = $worker->getEndDate();
        $this->expedientNumber = $worker->getExpedientNumber();
        $this->department = $worker->getDepartment();
        $this->workerJob = $this->workerJob ?? new WorkerJob();
        $this->workerJob->setWorker($this);
        $this->workerJob->setJob($worker->getWorkerJob()->getJob());
        $this->workerJob->setCode($worker->getWorkerJob()->getCode());
        if ( null !== $this->getPermissions() ) {
            $this->getPermissions()->clear();
        }
        // foreach ($worker->getPermissions() as $permission) {
        //     $permissionCopy = Permission::copyPermission($permission);
        //     $permissionCopy->setWorker($this);
        //     $this->addPermission($permissionCopy);
        // }
        return $this;
    }

    public function getValidatedBy(): ?User
    {
        return $this->validatedBy;
    }

    public function setValidatedBy(?User $validatedBy): self
    {
        $this->validatedBy = $validatedBy;

        return $this;
    }

    /**
     * @return Collection<int, Permission>
     */
    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    public function addPermission(Permission $permission): self
    {
        if (!$this->permissions->contains($permission)) {
            $this->permissions[] = $permission;
            $permission->setWorker($this);
        }

        return $this;
    }

    public function removePermission(Permission $permission): self
    {
        if ($this->permissions->removeElement($permission)) {
            // set the owning side to null (unless already changed)
            if ($permission->getWorker() === $this) {
                $permission->setWorker(null);
            }
        }

        return $this;
    }

    public function removeAllPermissions(): self
    {
        foreach ($this->getPermissions() as $permission) {
            $this->removePermission($permission);
        }
        return $this;
    }

    public function checkIfUserIsAllowedBoss(User $user) {
        $job = $this->getWorkerJob()->getJob();
        $bosses = $job->getBosses();
        if ($bosses->contains($user)) {
            return true;
        }
        return false;
    }

    public function getWorkerJob(): ?WorkerJob
    {
        return $this->workerJob;
    }

    public function setWorkerJob(WorkerJob $workerJob): static
    {
        // set the owning side of the relation if necessary
        if ($workerJob->getWorker() !== $this) {
            $workerJob->setWorker($this);
        }

        $this->workerJob = $workerJob;

        return $this;
    }

    /**
     * @return Collection<int, Historic>
     */
    public function getHistorics(): Collection
    {
        return $this->historics;
    }

    public function addHistoric(Historic $historic): static
    {
        if (!$this->historics->contains($historic)) {
            $this->historics->add($historic);
            $historic->setWorker($this);
        }

        return $this;
    }

    public function removeHistoric(Historic $historic): static
    {
        if ($this->historics->removeElement($historic)) {
            // set the owning side to null (unless already changed)
            if ($historic->getWorker() === $this) {
                $historic->setWorker(null);
            }
        }

        return $this;
    }

    public function hasAllPermissionsApprovedOrDenied(): bool
    {
        foreach ($this->getPermissions() as $permission) {
            if (null === $permission->isApproved()) {
                return false;
            }
        }
        return true;
    }

    public function hasAllPermissionsGranted(): bool
    {
        foreach ($this->getPermissions() as $permission) {
            if (null === $permission->isGranted()) {
                return false;
            }
        }
        return true;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function hasPendingApprovalPermissionsFrom(User $appOwner): bool
    {   
        foreach ($this->getPermissions() as $permission) {
            $application = $permission->getApplication();
            $applicationOwners = $application->getAppOwners();
            if ($applicationOwners->contains($appOwner) && null === $permission->isApproved()) {
                return true;
            }
        }
        return false;
    }

}

