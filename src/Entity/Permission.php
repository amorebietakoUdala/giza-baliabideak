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

    #[ORM\Column(nullable: true)]
    private ?bool $granted = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $grantedAt = null;

    #[ORM\ManyToOne(inversedBy: 'permissions')]
    private ?User $grantedBy = null;

    #[ORM\ManyToOne(inversedBy: 'approvedPermissions')]
    private ?User $approvedBy = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $approvedAt = null;

    #[ORM\Column(nullable: true)]
    private ?bool $approved = null;

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

    public static function copyPermission(Permission $permission, Worker|null $worker = null) {
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

    public static function createPermissionFromJobPermission(JobPermission $permission, Worker $worker) {
        $newPermission = new Permission();
        $newPermission->setWorker($worker);
        $newPermission->setApplication($permission->getApplication());
        $newPermission->setSubApplication($permission->getSubApplication());
        foreach ($permission->getRoles() as $rol) {
            $newPermission->addRole($rol);
        }
        return $newPermission;
    }

    public function isGranted(): ?bool
    {
        return $this->granted;
    }

    public function setGranted(?bool $granted): static
    {
        $this->granted = $granted;

        return $this;
    }

    public function __toString(): string
    {
        $roles = [];
        foreach ($this->getRoles() as $role) {
            $roles[] = $role->getNameEs();
        }
        $application = $this->getApplication();
        $subApplication = $this->getSubApplication();
        if ( null !== $subApplication ) {
            return sprintf(
                '%s - %s (%s)',
                $application->getName(),
                $subApplication->getNameEs(),
                implode(', ', $roles)
            );
        } else {
            return sprintf(
                '%s (%s)',
                $application->getName(),
                implode(', ', $roles)
            );
        }
    }

    public function getGrantedAt(): ?\DateTimeImmutable
    {
        return $this->grantedAt;
    }

    public function setGrantedAt(?\DateTimeImmutable $grantedAt): static
    {
        $this->grantedAt = $grantedAt;

        return $this;
    }

    public function getGrantedBy(): ?User
    {
        return $this->grantedBy;
    }

    public function setGrantedBy(?User $grantedBy): static
    {
        $this->grantedBy = $grantedBy;

        return $this;
    }

    public function getApprovedBy(): ?User
    {
        return $this->approvedBy;
    }

    public function setApprovedBy(?User $approvedBy): static
    {
        $this->approvedBy = $approvedBy;

        return $this;
    }

    public function getApprovedAt(): ?\DateTimeImmutable
    {
        return $this->approvedAt;
    }

    public function setApprovedAt(?\DateTimeImmutable $approvedAt): static
    {
        $this->approvedAt = $approvedAt;

        return $this;
    }

    public function isApproved(): ?bool
    {
        return $this->approved;
    }

    public function setApproved(?bool $approved): static
    {
        $this->approved = $approved;

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

    public function hasNotes(): bool {
        return mb_strlen(trim($this->notes)) > 0;
    }
}
