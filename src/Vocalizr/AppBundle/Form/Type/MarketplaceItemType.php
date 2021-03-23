<?php

namespace Vocalizr\AppBundle\Form\Type;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class MarketplaceItemType extends AbstractType
{
    public function __construct()
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', null, [
            'label' => 'Title',
            'attr'  => [
                'class' => 'form-control',
            ],
        ])
                ->add(
                    'item_type',
                    'choice',
                    [
                        'label'             => 'What are you selling?',
                        'attr'              => ['class' => 'select2'],
                        'preferred_choices' => [''],
                        'choices'           => [
                            'vocal' => 'Vocal (Acapella)',
                            'music' => 'Music (full backing track)',
                            'song'  => 'Song (lyrics and melody)', ],
                    ]
                )
                ->add('buyout_price', null, [
                    'label' => 'Price',
                    'attr'  => [
                        'class' => 'form-control',
                    ],
                ])
                ->add('reserve_price', null, [
                    'label' => 'Reserve Price',
                    'attr'  => [
                        'class' => 'form-control',
                    ],
                ])
                ->add('is_auction', null, [
                    'label' => 'Auction this item?',
                    'attr'  => [
                        'class' => 'form-control',
                    ],
                ])
                ->add('royalty_master', null, [
                    'label' => 'Master Royalties',
                    'attr'  => [
                        'class' => 'form-control',
                    ],
                ])
                ->add('royalty_publishing', null, [
                    'label' => 'Publishing Royalties',
                    'attr'  => [
                        'class' => 'form-control',
                    ],
                ])
                ->add('royalty_mechanical', null, [
                    'label' => 'Mechanical Royalties',
                    'attr'  => [
                        'class' => 'form-control',
                    ],
                ])
                ->add('royalty_performance', null, [
                    'label' => 'Performance Royalties',
                    'attr'  => [
                        'class' => 'form-control',
                    ],
                ])
                ->add('bpm', null, [
                    'label' => 'BPM',
                    'attr'  => [
                        'class' => 'form-control',
                    ],
                ])
                ->add('audio_key', null, [
                    'label' => 'Key',
                    'attr'  => [
                        'class' => 'form-control',
                    ],
                ])
                ->add('additional_info', null, [
                    'label' => 'Additional information',
                    'attr'  => [
                        'class' => 'form-control',
                        'rows'  => '4',
                    ],
                ])
                ->add(
                    'gender',
                    'choice',
                    [
                        'label'             => 'Gender',
                        'attr'              => ['class' => 'select2'],
                        'preferred_choices' => [''],
                        'choices'           => ['' => 'Either',
                            'female'               => 'Female',
                            'male'                 => 'Male', ],
                    ]
                )
                ->add(
                    'genres',
                    null,
                    [
                        'label'         => 'Genres',
                        'attr'          => ['class' => 'select2'],
                        'query_builder' => function (EntityRepository $er) {
                            return $er->createQueryBuilder('g')
                                        ->orderBy('g.title', 'ASC');
                        },
                    ]
                );
    }

    public function getDefaultOptions(array $options)
    {
        return [
            'data_class' => 'Vocalizr\AppBundle\Entity\MarketplaceItem',
        ];
    }

    public function getName()
    {
        return 'marketplace_item';
    }
}