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
     * @ORM\OneToMany(targetEntity=Worker::class, mappedBy="job")
     */
    private $workers;

    /**
     * @ORM\ManyToMany(targetEntity=User::class)
     */
    private $bosses;

    /**
     * @ORM\OneToMany(targetEntity=JobPermission::class, mappedBy="job", cascade={"persist"})
     */
    private $permissions;

    public function __construct()
    {
        $this->workers = new ArrayCollection();
        $this->bosses = new ArrayCollection();
        $this->permissions = new ArrayCollection();
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
}
