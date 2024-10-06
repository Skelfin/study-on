<?php

namespace App\Form;

use App\Entity\Course;
use App\Entity\Lesson;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\CallbackTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class LessonType extends AbstractType
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('content')
            ->add('lessonOrder')
            ->add('course', HiddenType::class)
        ;

        $builder->get('course')->addModelTransformer(new CallbackTransformer(
            function ($course) {
                return $course instanceof Course ? $course->getId() : null;
            },
            function ($courseId) {
                return $this->entityManager->getRepository(Course::class)->find($courseId);
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lesson::class,
        ]);
    }
}