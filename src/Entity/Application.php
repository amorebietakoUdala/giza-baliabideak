<?php

namespace App\Entity;

use App\Entity\SubApplication;
use App\Repository\ApplicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApplicationRepository::class)]
class Application implements \Stringable
{
    final public const Application_GESTIONA=1;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['show'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['show', 'historic'])]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $appOwnersEmails = null;

    #[ORM\OneToMany(targetEntity: SubApplication::class, cascade: ['persist', 'remove'], mappedBy: 'application')]
    #[Groups(['show'])]
    private Collection|array $subApplications;

    #[ORM\ManyToMany(targetEntity: Role::class, inversedBy: 'applications')]
    #[Groups(['show'])]
    private Collection|array $roles;

    #[ORM\OneToMany(targetEntity: Permission::class, mappedBy: 'application')]
    private Collection|array $permissions;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $userCreatorEmail = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'applications')]
    private Collection $appOwners;

    #[ORM\Column(nullable: true)]
    private ?bool $general = false;

    public function __construct()
    {
        $this->subApplications = new ArrayCollection();
        $this->roles = new ArrayCollection();
        $this->permissions = new ArrayCollection();
        $this->appOwners = new ArrayCollection();
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

    public function __toString(): string
    {
        return (string) $this->name;
    }

    public function fill(Application $data): self {
        $this->id= $data->getId();
        $this->name= $data->getName();
        $this->appOwnersEmails = $data->getAppOwnersEmails();
        $this->subApplications = $data->getSubApplications();
        $this->roles = $data->getRoles();
        $this->appOwners = $data->getAppOwners();
        $this->userCreatorEmail = $data->getUserCreatorEmail();
        $this->general = $data->isGeneral();
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

    public function getSubApplications(): Collection
    {
        return $this->subApplications;
    }

    public function addSubApplication(SubApplication $subApplication): self
    {
        if (!$this->subApplications->contains($subApplication)) {
            $this->subApplications[] = $subApplication;
        }

        return $this;
    }

    public function removeSubApplication(SubApplication $subApplication): self
    {
        $this->subApplications->removeElement($subApplication);

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

    /**
     * @return Collection<int, Permission>
     */
    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    public function addPermission(Permission $permission): self
    {
        if (!$this->permissions->contains($permission)) {
            $this->permissions[] = $permission;
            $permission->setApplication($this);
        }

        return $this;
    }

    public function removePermission(Permission $permission): self
    {
        if ($this->permissions->removeElement($permission)) {
            // set the owning side to null (unless already changed)
            if ($permission->getApplication() === $this) {
                $permission->setApplication(null);
            }
        }

        return $this;
    }

    public function getUserCreatorEmail(): ?string
    {
        return $this->userCreatorEmail;
    }

    public function setUserCreatorEmail(?string $userCreatorEmail): static
    {
        $this->userCreatorEmail = $userCreatorEmail;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getAppOwners(): Collection
    {
        return $this->appOwners;
    }

    public function addAppOwner(User $appOwner): static
    {
        if (!$this->appOwners->contains($appOwner)) {
            $this->appOwners->add($appOwner);
        }

        return $this;
    }

    public function removeAppOwner(User $appOwner): static
    {
        $this->appOwners->removeElement($appOwner);

        return $this;
    }

    public function isGeneral(): ?bool
    {
        return $this->general;
    }

    public function setGeneral(?bool $general): static
    {
        $this->general = $general;

        return $this;
    }

}
