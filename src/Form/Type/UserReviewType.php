<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class UserReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('quality_of_work', ChoiceType::class, [
            'choices'  => [5 => 5, 4 => 4, 3 => 3, 2 => 2, 1 => 1],
            'required' => true,
        ]);
        $builder->add('communication', ChoiceType::class, [
            'choices'  => [5 => 5, 4 => 4, 3 => 3, 2 => 2, 1 => 1],
            'required' => true,
        ]);
        $builder->add('professionalism', ChoiceType::class, [
            'choices'  => [5 => 5, 4 => 4, 3 => 3, 2 => 2, 1 => 1],
            'required' => true,
        ]);
        $builder->add('work_with_again', ChoiceType::class, [
            'choices'  => [5 => 5, 4 => 4, 3 => 3, 2 => 2, 1 => 1],
            'required' => true,
        ]);
        $builder->add('on_time');
        $builder->add('content', TextareaType::class);
    }

    public function getDefaultOptions(array $options)
    {
        return [
            'data_class' => 'App\Entity\UserReview',
        ];
    }

    public function getName()
    {
        return 'user_review';
    }
}
