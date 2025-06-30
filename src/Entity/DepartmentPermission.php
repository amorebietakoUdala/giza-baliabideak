<?php

namespace App\Entity;

use App\Repository\DepartmentPermissionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DepartmentPermissionRepository::class)]
class DepartmentPermission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Department::class, inversedBy: 'permissions', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Department $department = null;

    #[ORM\ManyToOne(targetEntity: Application::class, inversedBy: 'permissions')]
    private ?Application $application = null;

    #[ORM\ManyToOne(targetEntity: SubApplication::class, inversedBy: 'permissions')]
    private ?SubApplication $subApplication = null;

    /**
     * @var Collection<int, Role>
     */
    #[ORM\ManyToMany(targetEntity: Role::class, inversedBy: 'departmentPermissions')]
    private Collection $roles;

    #[ORM\Column(length: 4096, nullable: true)]
    private ?string $notes = null;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function addRole(Role $role): static
    {
        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
        }

        return $this;
    }

    public function removeRole(Role $role): static
    {
        $this->roles->removeElement($role);

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public static function copyPermission(DepartmentPermission $permission, Worker $worker) {
        $newPermission = new Permission();
        $newPermission->setWorker($worker);
        $newPermission->setApplication($permission->getApplication());
        $newPermission->setSubApplication($permission->getSubApplication());
        foreach ($permission->getRoles() as $rol) {
            $newPermission->addRole($rol);
        }
        $newPermission->setNotes($permission->getNotes());
        return $newPermission;
    }

    public static function createJobPermissionFromPermissionAndWorker(Permission $permission, Department $department) {
        $newPermission = new DepartmentPermission();
        $newPermission->setDepartment($department);
        $newPermission->setApplication($permission->getApplication());
        $newPermission->setSubApplication($permission->getSubApplication());
        foreach ($permission->getRoles() as $rol) {
            $newPermission->addRole($rol);
        }
        $newPermission->setNotes($permission->getNotes());
        return $newPermission;
    }
}
