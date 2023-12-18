<?php

namespace App\Entity;

use App\Repository\PermissionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PermissionRepository::class)]
class Permission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Worker::class, inversedBy: 'permissions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Worker $worker = null;

    #[ORM\ManyToOne(targetEntity: Application::class, inversedBy: 'permissions')]
    #[Groups(['historic'])]
    private ?Application $application = null;

    #[ORM\ManyToOne(targetEntity: SubApplication::class, inversedBy: 'permissions')]
    #[Groups(['historic'])]
    private ?SubApplication $subApplication = null;

    #[ORM\ManyToMany(targetEntity: Role::class, inversedBy: 'permissions')]
    #[Groups(['historic'])]
    #[MaxDepth(1)]
    private Collection|array $roles;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWorker(): ?Worker
    {
        return $this->worker;
    }

    public function setWorker(?Worker $worker): self
    {
        $this->worker = $worker;

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

    public static function copyPermission(Permission $permission, Worker $worker = null) {
        $newPermission = new Permission();
        if ( null !== $worker ) {
            $newPermission->setWorker($worker);
        }
        $newPermission->setApplication($permission->getApplication());
        $newPermission->setSubApplication($permission->getSubApplication());
        foreach ($permission->getRoles() as $rol) {
            $newPermission->addRole($rol);
        }
        return $newPermission;
    }
}
