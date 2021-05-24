<?php

// src/Vocalizr/AppBundle/Form/Type/GenreSelectType.php

namespace App\Form\CustomFieldType;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GenreSelectType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'multiple' => true,
        ]);
    }

    public function getParent()
    {
        return 'entity';
    }

    public function getName()
    {
        return 'genre_select';
    }
}