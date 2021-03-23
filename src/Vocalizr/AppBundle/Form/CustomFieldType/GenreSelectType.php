<?php

// src/Vocalizr/AppBundle/Form/Type/GenreSelectType.php

namespace Vocalizr\AppBundle\Form\CustomFieldType;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class GenreSelectType extends AbstractType
{
    public function setDefaultOptions(OptionsResolverInterface $resolver)
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