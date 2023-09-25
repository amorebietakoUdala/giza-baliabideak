<?php

namespace App\Entity;

use App\Repository\WorkerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

/**
 * @ORM\Entity(repositoryClass=WorkerRepository::class)
 */
class Worker
{
    use TimestampableEntity;
    
    const STATUS_RRHH_NEW = 1;
    const STATUS_REVISION_PENDING = 2;
    const STATUS_IN_PROGRESS = 3;
    const STATUS_REGISTERED = 4;
    const STATUS_DELETED = 5;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"historic"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"historic"})
     */
    private $dni;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"historic"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"historic"})
     */
    private $surname1;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"historic"})
     */
    private $surname2;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $startDate;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $endDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $expedientNumber;

    /**
     * @ORM\ManyToOne(targetEntity=Department::class, inversedBy="workers")
     * @ORM\JoinColumn(nullable=false)
     */
    private $department;

    /**
     * @ORM\ManyToOne(targetEntity=Job::class, inversedBy="workers")
     */
    private $job;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $status;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $noEndDate;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="workers")
     * @Groups({"historic"})
     */
    private $validatedBy;

    /**
     * @ORM\OneToMany(targetEntity=Permission::class, mappedBy="worker")
     * @Groups({"historic"})
     * @MaxDepth(1)
     */
    private $permissions;

    public function __construct()
    {
        $this->permissions = new ArrayCollection();
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

    public function __toString()
    {
        return $this->name.' '.$this->surname1.' '.$this->surname2;
    }

    public function getJob(): ?Job
    {
        return $this->job;
    }

    public function setJob(?Job $job): self
    {
        $this->job = $job;

        return $this;
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

    public function fill(Worker $worker) {
        $this->dni = $worker->getDni();
        $this->name = $worker->getName();
        $this->surname1 = $worker->getSurname1();
        $this->surname2 = $worker->getSurname2();
        $this->startDate = $worker->getStartDate();
        $this->endDate = $worker->getEndDate();
        $this->expedientNumber = $worker->getExpedientNumber();
        $this->department = $worker->getDepartment();
        $this->job = $worker->getJob();
        if ( null !== $this->getPermissions() ) {
            $this->getPermissions()->clear();
        }
        foreach ($worker->getPermissions() as $permission) {
            $permissionCopy = Permission::copyPermission($permission);
            $permissionCopy->setWorker($this);
            $this->addPermission($permissionCopy);
        }
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

    public function checkIfUserIsAllowedBoss(User $user) {
        $job = $this->getJob();
        $bosses = $job->getBosses();
        if ($bosses->contains($user)) {
            return true;
        }
        return false;
    }
}

