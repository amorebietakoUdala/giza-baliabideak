<?php

namespace App\Entity;

use App\Repository\SubApplicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubApplicationRepository::class)]
class SubApplication implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    
    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['historic'])]
    private ?string $nameEs = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $nameEu = null;

    #[ORM\ManyToOne(targetEntity: Application::class, inversedBy: 'subApplications')]
    private ?Application $application = null;

    #[ORM\OneToMany(targetEntity: Permission::class, mappedBy: 'subApplication')]
    private Collection|array $permissions;

    public function __construct()
    {
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

    public function setApplication(Application $application):self {
        $this->application = $application;

        return $this;
    }

    public function getApplication(): ?Application {
        return $this->application;
    }

    public function fill(SubApplication $data): self {
        $this->id= $data->getId();
        $this->nameEs= $data->getNameEs();
        $this->nameEu= $data->getNameEu();
        $this->application = $data->getApplication();
        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->nameEs;
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
            $permission->setSubApplication($this);
        }

        return $this;
    }

    public function removePermission(Permission $permission): self
    {
        if ($this->permissions->removeElement($permission)) {
            // set the owning side to null (unless already changed)
            if ($permission->getSubApplication() === $this) {
                $permission->setSubApplication(null);
            }
        }

        return $this;
    }

}

