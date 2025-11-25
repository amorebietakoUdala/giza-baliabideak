<?php

namespace App\Entity;

use App\Repository\JobRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobRepository::class)]
class Job implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['show'])]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['show'])]
    private ?string $titleEs = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['show'])]
    private ?string $titleEu = null;

    #[ORM\ManyToMany(targetEntity: User::class)]
    private Collection|array $bosses;

    #[ORM\OneToMany(targetEntity: JobPermission::class, mappedBy: 'job', cascade: ['persist'])]
    private Collection|array $permissions;

    #[ORM\OneToMany(targetEntity: WorkerJob::class, mappedBy: 'job', cascade: ['persist', 'remove'])]
    private Collection|array $workerJob;

    public function __construct()
    {
        $this->bosses = new ArrayCollection();
        $this->permissions = new ArrayCollection();
        $this->workerJob = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTitleBilingual(): ?string
    {
        return $this->titleEs.' / '.$this->titleEu;
    }

    public function __toString(): string
    {
        return $this->titleEs;
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

    /**
     * @return Collection<int, JobPermission>
     */
    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    public function addPermission(JobPermission $permission): self
    {
        if (!$this->permissions->contains($permission)) {
            $this->permissions[] = $permission;
            $permission->setJob($this);
        }

        return $this;
    }

    public function removePermission(JobPermission $permission): self
    {
        if ($this->permissions->removeElement($permission)) {
            // set the owning side to null (unless already changed)
            if ($permission->getJob() === $this) {
                $permission->setJob(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, WorkerJob>
     */
    public function getWorkerJob(): Collection
    {
        return $this->workerJob;
    }

    public function addWorkerJob(WorkerJob $workerJob): self
    {
        if (!$this->workerJob->contains($workerJob)) {
            $this->workerJob[] = $workerJob;
            $workerJob->setJob($this);
        }

        return $this;
    }

    public function removeWorkerJob(JobPermission $workerJob): self
    {
        if ($this->workerJob->removeElement($workerJob)) {
            // set the owning side to null (unless already changed)
            if ($workerJob->getJob() === $this) {
                $workerJob->setJob(null);
            }
        }

        return $this;
    }

}
