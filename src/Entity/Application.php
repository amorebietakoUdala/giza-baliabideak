<?php

namespace App\Entity;

use App\Entity\SubApplication;
use App\Repository\ApplicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ApplicationRepository::class)
 */
class Application
{

    const Application_AUPAC=1;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"show"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"show", "historic"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $appOwnersEmails;

    /**
     * @ORM\OneToMany(targetEntity=SubApplication::class, cascade={"persist", "remove"}, mappedBy="application")
     * @Groups({"show"}) 
     */
    private $subApplications;

    /**
     * @ORM\ManyToMany(targetEntity=Role::class, inversedBy="applications")
     * @Groups({"show"})
     */
    private $roles;

    /**
     * @ORM\OneToMany(targetEntity=Permission::class, mappedBy="application")
     */
    private $permissions;

    public function __construct()
    {
        $this->subApplications = new ArrayCollection();
        $this->roles = new ArrayCollection();
        $this->permissions = new ArrayCollection();
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

    public function __toString()
    {
        return $this->name;
    }

    public function fill(Application $data): self {
        $this->id= $data->getId();
        $this->name= $data->getName();
        $this->appOwnersEmails = $data->getAppOwnersEmails();
        $this->subApplications = $data->getSubApplications();
        $this->roles = $data->getRoles();
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

}
