<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use AMREU\UserBundle\Model\UserInterface as AMREUserInterface;
use AMREU\UserBundle\Model\User as BaseUser;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Table(name: 'user')]
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User extends BaseUser implements AMREUserInterface, PasswordAuthenticatedUserInterface
{

    final public const ROLE_BOSS = 'ROLE_BOSS';
    final public const ROLE_RRHH = 'ROLE_RRHH';
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Groups(['historic'])]
    protected $username;

    #[ORM\Column(type: 'json')]
    protected $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column(type: 'string')]
    protected $password;

    #[ORM\Column(type: 'string', length: 255)]
    protected $firstName;

    #[ORM\Column(type: 'string', length: 255)]
    protected $email;

    #[ORM\Column(type: 'boolean', options: ['default' => '1'])]
    protected $activated;

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected $lastLogin;

    #[ORM\OneToMany(targetEntity: Historic::class, mappedBy: 'user')]
    private Collection|array $historics;

    #[ORM\OneToMany(targetEntity: Worker::class, mappedBy: 'validatedBy')]
    private Collection|array $workers;

    /**
     * @var Collection<int, Permission>
     */
    #[ORM\OneToMany(mappedBy: 'grantedBy', targetEntity: Permission::class)]
    private Collection $permissions;

    /**
     * @var Collection<int, Permission>
     */
    #[ORM\OneToMany(mappedBy: 'approvedBy', targetEntity: Permission::class)]
    private Collection $approvedPermissions;

    /**
     * @var Collection<int, Application>
     */
    #[ORM\ManyToMany(targetEntity: Application::class, mappedBy: 'appOwners')]
    private Collection $applications;

    public function __construct()
    {
        $this->historics = new ArrayCollection();
        $this->workers = new ArrayCollection();
        $this->permissions = new ArrayCollection();
        $this->approvedPermissions = new ArrayCollection();
        $this->applications = new ArrayCollection();
    }

    /**
     * @return Collection<int, Historic>
     */
    public function getHistorics(): Collection
    {
        return $this->historics;
    }

    public function addHistoric(Historic $historic): self
    {
        if (!$this->historics->contains($historic)) {
            $this->historics[] = $historic;
            $historic->setUser($this);
        }

        return $this;
    }

    public function removeHistoric(Historic $historic): self
    {
        if ($this->historics->removeElement($historic)) {
            // set the owning side to null (unless already changed)
            if ($historic->getUser() === $this) {
                $historic->setUser(null);
            }
        }

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
            $worker->setValidatedBy($this);
        }

        return $this;
    }

    public function removeWorker(Worker $worker): self
    {
        if ($this->workers->removeElement($worker)) {
            // set the owning side to null (unless already changed)
            if ($worker->getValidatedBy() === $this) {
                $worker->setValidatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Permission>
     */
    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    public function addPermission(Permission $permission): static
    {
        if (!$this->permissions->contains($permission)) {
            $this->permissions->add($permission);
            $permission->setGrantedBy($this);
        }

        return $this;
    }

    public function removePermission(Permission $permission): static
    {
        if ($this->permissions->removeElement($permission)) {
            // set the owning side to null (unless already changed)
            if ($permission->getGrantedBy() === $this) {
                $permission->setGrantedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Permission>
     */
    public function getApprovedPermissions(): Collection
    {
        return $this->approvedPermissions;
    }

    public function addApprovedPermission(Permission $approvedPermission): static
    {
        if (!$this->approvedPermissions->contains($approvedPermission)) {
            $this->approvedPermissions->add($approvedPermission);
            $approvedPermission->setApprovedBy($this);
        }

        return $this;
    }

    public function removeApprovedPermission(Permission $approvedPermission): static
    {
        if ($this->approvedPermissions->removeElement($approvedPermission)) {
            // set the owning side to null (unless already changed)
            if ($approvedPermission->getApprovedBy() === $this) {
                $approvedPermission->setApprovedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Application>
     */
    public function getApplications(): Collection
    {
        return $this->applications;
    }

    public function getApplicationIds(): array
    {
        $ids = [];
        foreach ($this->applications as $application) {
            $ids[] = $application->getId();
        }
        return $ids;
    }

    public function addApplication(Application $application): static
    {
        if (!$this->applications->contains($application)) {
            $this->applications->add($application);
            $application->addAppOwner($this);
        }

        return $this;
    }

    public function removeApplication(Application $application): static
    {
        if ($this->applications->removeElement($application)) {
            $application->removeAppOwner($this);
        }

        return $this;
    }
}
