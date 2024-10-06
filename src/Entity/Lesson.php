<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Lesson
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Course::class, inversedBy: 'lessons')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Course $course = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: "Название не должно быть пустым.")]
    private ?string $name = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: "Содержимое не должно быть пустым.")]
    private ?string $content = null;

    #[ORM\Column(type: 'integer', options: ["unsigned" => true])]
    private ?int $lessonOrder = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): static
    {
        $this->course = $course;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getLessonOrder(): int
    {
        return $this->lessonOrder;
    }

    public function setLessonOrder(int $lessonOrder): static
    {
        if ($lessonOrder < 1 || $lessonOrder > 10000) {
            throw new \InvalidArgumentException('Место должно быть от 1 до 10000.');
        }
        $this->lessonOrder = $lessonOrder;

        return $this;
    }
}