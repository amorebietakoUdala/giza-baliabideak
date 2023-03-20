<?php

namespace App\Entity;

use App\Repository\RoleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RoleRepository::class)
 */
class Role
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
     * @Groups({"show", "historic"})
    */
    private $nameEs;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"show"})
     */
    private $nameEu;

    /**
     * @ORM\ManyToMany(targetEntity=Application::class, mappedBy="roles")
     */
    private $applications;

    /**
     * @ORM\ManyToMany(targetEntity=Permission::class, mappedBy="roles")
     */
    private $permissions;

    public function __construct()
    {
        $this->applications = new ArrayCollection();
        $this->permissions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNameEs(): ?string
    {
        return $this->nameEs;
    }

    public function setNameEs(string $nameEs): self
    {
        $this->nameEs = $nameEs;

        return $this;
    }

    public function getNameEu(): ?string
    {
        return $this->nameEu;
    }

    public function setNameEu(string $nameEu): self
    {
        $this->nameEu = $nameEu;

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
            $application->addRole($this);
        }

        return $this;
    }

    public function removeApplication(Application $application): self
    {
        if ($this->applications->removeElement($application)) {
            $application->removeRole($this);
        }

        return $this;
    }

    public function fill(Role $data): self {
        $this->id= $data->getId();
        $this->nameEs= $data->getNameEs();
        $this->nameEu= $data->getNameEu();
        return $this;
    }

    public function __toString()
    {
        return $this->nameEs;
    }

    /**
     * @return Collection<int, Permission>
     */
    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    public function addPermission(Permission $permissions): self
    {
        if (!$this->permissions->contains($permissions)) {
            $this->permissions[] = $permissions;
            $permissions->addRole($this);
        }

        return $this;
    }

    public function removePermission(Permission $permissions): self
    {
        if ($this->permissions->removeElement($permissions)) {
            $permissions->removeRole($this);
        }

        return $this;
    }
}
