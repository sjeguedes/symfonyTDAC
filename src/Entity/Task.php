<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Task
 *
 * Define a Task entity.
 *
 * @ORM\Entity
 * @ORM\Table
 */
class Task
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

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
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank(message="Vous devez saisir un titre.")
     */
    private string $title;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     * @Assert\NotBlank(message="Vous devez saisir du contenu.")
     */
    private string $content;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private bool $isDone;

    /**
     * @var UserInterface|User|null a task corresponding author
     *
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(name="author_id", referencedColumnName="id", nullable=true)
     */
    private ?UserInterface $author;

    /**
     * @var UserInterface|User|null the last corresponding user which edited a task
     *                              which can be different from the author
     *
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(name="last_editor_id", referencedColumnName="id", nullable=true)
     */
    private ?UserInterface $lastEditor;

    /**
     * Task constructor.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->isDone = false;
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
     * @param \DateTimeImmutable $createdAt
     *
     * @return Task
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
     * @return Task
     */
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        if ($this->createdAt >= $updatedAt) {
            throw new \RuntimeException('Update date is not logical: Task cannot be modified before creation!');
        }
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return Task
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * @param string $content
     *
     * @return Task
     */
    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDone(): bool
    {
        return $this->isDone;
    }

    /**
     * @param bool $flag
     *
     * @return Task
     */
    public function toggle(bool $flag): self
    {
        $this->isDone = $flag;

        return $this;
    }

    /**
     * Get the task author.
     *
     * @return UserInterface|User|null
     */
    public function getAuthor(): ?UserInterface
    {
        return $this->author;
    }

    /**
     * Set the task author.
     *
     * @param UserInterface $user
     *
     * @return Task
     */
    public function setAuthor(UserInterface $user): self
    {
        $this->author = $user;

        return $this;
    }

    /**
     * Get the last user who edited a task.
     *
     * @return UserInterface|User|null
     */
    public function getLastEditor(): ?UserInterface
    {
        return $this->lastEditor;
    }

    /**
     * Set the last user who edited a task.
     *
     * @param UserInterface|User $user
     *
     * @return Task
     */
    public function setLastEditor(UserInterface $user): self
    {
        $this->lastEditor = $user;

        return $this;
    }
}
