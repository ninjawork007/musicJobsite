<?php

// src/Vocalizr/AppBundle/Form/Type/VocalizrSelectType.php

namespace App\Form\CustomFieldType;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class VocalizrSelectType extends AbstractType
{
    public function getParent()
    {
        return ChoiceType::class;
    }

    public function getName()
    {
        return 'vocalizr_select';
    }
}