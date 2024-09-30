<?php

namespace App\Form;

use App\Entity\Course;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class CourseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('description')
            ->add('price')
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Аренда' => \App\Entity\Course::TYPE_RENT,
                    'Покупка' => \App\Entity\Course::TYPE_BUY,
                    'Бесплатный' => \App\Entity\Course::TYPE_FREE,
                ],
                'label' => 'Тип курса'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Course::class,
        ]);
    }
}