<?php

namespace App\Entity;

use App\Repository\ApplicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\BrowserKit\History;

/**
 * @ORM\Entity(repositoryClass=ApplicationRepository::class)
 */
class Application
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"show"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"show"})
     */
    private $name;

    /**
     * @ORM\ManyToMany(targetEntity=Worker::class, mappedBy="applications")
     */
    private $workers;

    /**
     * @ORM\ManyToMany(targetEntity=Job::class, mappedBy="applications")
     */
    private $jobs;

    /**
     * @ORM\ManyToMany(targetEntity=Historic::class, inversedBy="applications")
     */
    private $historics;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $appOwnersEmails;

    public function __construct()
    {
        $this->workers = new ArrayCollection();
        $this->jobs = new ArrayCollection();
        $this->historics = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

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

    /**
     * @return Collection<int, Worker>
     */
    public function getWorkers(): Collection
    {
        return $this->workers;
    }

    public function addWorker(Worker $worker): self
    {
        if (!$this->workers->contains($worker)) {
            $this->workers[] = $worker;
            $worker->addApplication($this);
        }

        return $this;
    }

    public function removeWorker(Worker $worker): self
    {
        if ($this->workers->removeElement($worker)) {
            $worker->removeApplication($this);
        }

        return $this;
    }

    public function __toString()
    {
        return $this->name;
    }

    /**
     * @return Collection<int, Job>
     */
    public function getJobs(): Collection
    {
        return $this->jobs;
    }

    public function addJob(Job $job): self
    {
        if (!$this->jobs->contains($job)) {
            $this->jobs[] = $job;
            $job->addApplication($this);
        }

        return $this;
    }

    public function removeJob(Job $job): self
    {
        if ($this->jobs->removeElement($job)) {
            $job->removeApplication($this);
        }

        return $this;
    }

    public function fill(Application $data): self {
        $this->id= $data->getId();
        $this->name= $data->getName();
        $this->appOwnersEmails = $data->getAppOwnersEmails();
        return $this;
    }

    public function getHistorics(): Collection
    {
        return $this->historics;
    }

    public function addHistoric(Historic $historic): self
    {
        if (!$this->historics->contains($historic)) {
            $this->historics[] = $historic;
            $historic->addApplication($this);
        }

        return $this;
    }

    public function removeHistoric(Historic $historic): self
    {
        if ($this->historics->removeElement($historic)) {
            $historic->removeApplication($this);
        }

        return $this;
    }

    public function getAppOwnersEmails(): ?string
    {
        return $this->appOwnersEmails;
    }

    public function setAppOwnersEmails(?string $appOwnersEmails): self
    {
        $this->appOwnersEmails = $appOwnersEmails;

        return $this;
    }

}
