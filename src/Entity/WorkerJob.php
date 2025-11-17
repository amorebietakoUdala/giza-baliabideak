<?php

namespace App\Entity;

use App\Repository\WorkerJobRepository;
use App\Entity\Job;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkerJobRepository::class)]
class WorkerJob
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'workerJob', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false, unique: false)]
    private ?Worker $worker = null;

    #[ORM\ManyToOne(targetEntity: Job::class, inversedBy: 'workerJob')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Job $job = null;

    #[ORM\Column(nullable: true)]
    private ?int $code = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWorker(): ?Worker
    {
        return $this->worker;
    }

    public function setWorker(Worker $worker): static
    {
        $this->worker = $worker;

        return $this;
    }

    public function getJob(): ?Job
    {
        return $this->job;
    }

    public function setJob(Job $job): static
    {
        $this->job = $job;

        return $this;
    }

    public function getCode(): ?int
    {
        return $this->code;
    }

    public function setCode(?int $code): static
    {
        $this->code = $code;

        return $this;
    }
}
