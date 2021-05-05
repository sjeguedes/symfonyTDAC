<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints as DoctrineAssert;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class User
 *
 * Define a User entity.
 *
 * @DoctrineAssert\UniqueEntity("username", message="Un utilisateur enregistré utilise déjà ce nom.")
 * @DoctrineAssert\UniqueEntity("email", message="Un utilisateur enregistré utilise déjà cette adresse.")
 *
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\Table("users")
 */
class User implements UserInterface
{
    /**
     * Define roles representation.
     *
     * IMPORTANT: pay attention on space after comma "," due to data transformation logic!
     */
    public const ROLES = [
        'admin' => 'ROLE_ADMIN, ROLE_USER',
        'user'  => 'ROLE_USER'
    ];

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @var \DateTimeImmutable
     *
     * @ORM\Column(type="datetime_immutable")
     */
    private \DateTimeImmutable $createdAt;

    /**
     * @var \DateTimeImmutable
     *
     * @ORM\Column(type="datetime_immutable")
     */
    private \DateTimeImmutable $updatedAt;

    /**
     * @var string|null
     *
     * @Assert\NotBlank(message="Vous devez saisir un nom d'utilisateur.")
     *
     * @ORM\Column(type="string", length=25, unique=true)
     */
    private ?string $username = null;

    /**
     * @var string|null
     *
     * @Assert\NotBlank(message="Vous devez saisir un mot de passe.")
     * @Assert\Regex(
     *     groups={"user_creation", "user_update"},
     *     pattern="/^(?!.*\s)(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W]).{8,20}$/",
     *     message="Le format attendu n'est pas respecté. (voir aide)"
     * )
     *
     * @ORM\Column(type="string")
     */
    private ?string $password = null;

    /**
     * @var string|null
     *
     * @Assert\NotBlank(message="Vous devez saisir une adresse email.")
     * @Assert\Email(message="Le format de l'adresse n'est pas correct.")
     *
     * @ORM\Column(type="string", unique=true)
     */
    private ?string $email = null;

    /**
     * @var string|null
     */
    private ?string $salt = null;

    /**
     * @var array<string>
     *
     * @ORM\Column(type="simple_array")
     */
    private array $roles;

    /**
     * User constructor.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
        // Define at least "ROLE_USER" default user role
        $this->roles = ['ROLE_USER'];
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * Please note this setter is optional since data is set in constructor.
     * This allows to keep control on date of creation.
     *
     * @codeCoverageIgnore
     *
     * @param \DateTimeImmutable $createdAt
     *
     * @return User
     */
    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTimeImmutable $updatedAt
     *
     * @return User
     *
     * @throws \Exception
     */
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        if ($this->createdAt > $updatedAt) {
            throw new \LogicException('Update date is not logical: User cannot be modified before creation!');
        }
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @param string $username
     *
     * @return User
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSalt(): ?string
    {
        return $this->salt;
    }

    /**
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return User
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return User
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return array<string>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @param array $roles
     *
     * @return User
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     *
     * @return void
     */
    public function eraseCredentials(): void
    {
        // This is not used since no temporary sensitive data need(s) to be erased!
    }
}
