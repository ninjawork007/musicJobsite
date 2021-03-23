<?php

// src/Vocalizr/AppBundle/Form/Type/VocalizrSelectType.php

namespace Vocalizr\AppBundle\Form\CustomFieldType;

use Symfony\Component\Form\AbstractType;

class VocalizrSelectType extends AbstractType
{
    public function getParent()
    {
        return 'choice';
    }

    public function getName()
    {
        return 'vocalizr_select';
    }
}