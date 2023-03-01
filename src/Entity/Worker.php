<?php

namespace App\Entity;

use App\Repository\WorkerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\HttpFoundation\Request;

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
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $dni;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $surname1;

    /**
     * @ORM\Column(type="string", length=255)
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
     * @ORM\ManyToMany(targetEntity=Application::class, inversedBy="workers")
     */
    private $applications;

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
     */
    private $validatedBy;

    public function __construct()
    {
        $this->applications = new ArrayCollection();
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

    /**
     * @return Collection<int, Application>
     */
    public function getApplications(): Collection
    {
        return $this->applications;
    }

    public function addApplication(Application $application): self
    {
        if (!$this->applications->contains($application)) {
            $this->applications[] = $application;
        }

        return $this;
    }

    public function removeApplication(Application $application): self
    {
        $this->applications->removeElement($application);

        return $this;
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
        if ( null !== $this->getApplications() ) {
            $this->getApplications()->clear();
        }
        foreach ($worker->getApplications() as $app) {
            $this->addApplication($app);
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
}

