<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use AMREU\UserBundle\Model\UserInterface as AMREUserInterface;
use AMREU\UserBundle\Model\User as BaseUser;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @ORM\Table(name="user")
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User extends BaseUser implements AMREUserInterface, PasswordAuthenticatedUserInterface
{

    const ROLE_BOSS = 'ROLE_BOSS';
    const ROLE_RRHH = 'ROLE_RRHH';
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    protected $username;

    /**
     * @ORM\Column(type="json")
     */
    protected $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    protected $password;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $firstName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $email;

    /**
     * @ORM\Column(type="boolean", options={"default":"1"})
     */
    protected $activated;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $lastLogin;

    /**
     * @ORM\OneToMany(targetEntity=Historic::class, mappedBy="user")
     */
    private $historics;

    /**
     * @ORM\OneToMany(targetEntity=Worker::class, mappedBy="validatedBy")
     */
    private $workers;

    public function __construct()
    {
        $this->historics = new ArrayCollection();
        $this->workers = new ArrayCollection();
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
}
