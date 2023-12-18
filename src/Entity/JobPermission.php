<?php

namespace App\Entity;

use App\Repository\JobPermissionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobPermissionRepository::class)]
class JobPermission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Job::class, inversedBy: 'permissions', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Job $job = null;

    #[ORM\ManyToOne(targetEntity: Application::class, inversedBy: 'permissions')]
    private ?Application $application = null;

    #[ORM\ManyToOne(targetEntity: SubApplication::class, inversedBy: 'permissions')]
    private ?SubApplication $subApplication = null;

    #[ORM\ManyToMany(targetEntity: Role::class, inversedBy: 'permissions', cascade: ['persist'])]
    private Collection|array $roles;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getApplication(): ?Application
    {
        return $this->application;
    }

    public function setApplication(?Application $application): self
    {
        $this->application = $application;

        return $this;
    }

    public function getSubApplication(): ?SubApplication
    {
        return $this->subApplication;
    }

    public function setSubApplication(?SubApplication $subApplication): self
    {
        $this->subApplication = $subApplication;

        return $this;
    }

    /**
     * @return Collection<int, Role>
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    public function addRole(Role $role): self
    {
        if (!$this->roles->contains($role)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    public function removeRole(Role $role): self
    {
        $this->roles->removeElement($role);

        return $this;
    }

    public static function copyPermission(JobPermission $permission, Worker $worker) {
        $newPermission = new Permission();
        $newPermission->setWorker($worker);
        $newPermission->setApplication($permission->getApplication());
        $newPermission->setSubApplication($permission->getSubApplication());
        foreach ($permission->getRoles() as $rol) {
            $newPermission->addRole($rol);
        }
        return $newPermission;
    }

    public static function createJobPermissionFromPermissionAndWorker(Permission $permission, Job $job) {
        $newPermission = new JobPermission();
        $newPermission->setJob($job);
        $newPermission->setApplication($permission->getApplication());
        $newPermission->setSubApplication($permission->getSubApplication());
        foreach ($permission->getRoles() as $rol) {
            $newPermission->addRole($rol);
        }
        return $newPermission;
    }
}
