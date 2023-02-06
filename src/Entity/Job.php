<?php

namespace App\Entity;

use App\Repository\JobRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=JobRepository::class)
 */
class Job
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"show"})
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"show"})
     */
    private $code;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"show"})
     */
    private $titleEs;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"show"})
     */
    private $titleEu;

    /**
     * @ORM\ManyToMany(targetEntity=Application::class, inversedBy="jobs")
     * @Groups({"show"})
     */
    private $applications;

    /**
     * @ORM\OneToMany(targetEntity=Worker::class, mappedBy="job")
     */
    private $workers;

    /**
     * @ORM\ManyToMany(targetEntity=User::class)
     */
    private $bosses;

    public function __construct()
    {
        $this->applications = new ArrayCollection();
        $this->workers = new ArrayCollection();
        $this->bosses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?int
    {
        return $this->code;
    }

    public function setCode(int $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getTitleEs(): ?string
    {
        return $this->titleEs;
    }

    public function setTitleEs(string $titleEs): self
    {
        $this->titleEs = $titleEs;

        return $this;
    }

    public function getTitleEu(): ?string
    {
        return $this->titleEu;
    }

    public function setTitleEu(string $titleEu): self
    {
        $this->titleEu = $titleEu;

        return $this;
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
            $worker->setJob($this);
        }

        return $this;
    }

    public function removeWorker(Worker $worker): self
    {
        if ($this->workers->removeElement($worker)) {
            // set the owning side to null (unless already changed)
            if ($worker->getJob() === $this) {
                $worker->setJob(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->code.'-'.$this->titleEs;
    }

    /**
     * @return Collection<int, User>
     */
    public function getBosses(): Collection
    {
        return $this->bosses;
    }

    public function addBoss(User $boss): self
    {
        if (!$this->bosses->contains($boss)) {
            $this->bosses[] = $boss;
        }

        return $this;
    }

    public function removeBoss(User $boss): self
    {
        $this->bosses->removeElement($boss);

        return $this;
    }

}
