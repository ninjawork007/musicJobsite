<?php

namespace App\Form\Type;

use App\Entity\Article;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ArticleType extends AbstractType
{
    public $article = null;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->article = $options['data'];

        $builder->add('title', null, [
            'attr' => [
                'class' => 'form-control',
            ],
        ]);
        $builder->add('slug', null, [
            'label' => 'URL Slug',
            'attr'  => [
                'class' => 'form-control',
            ],
        ]);
        $builder->add('article_category', null, [
            'label'       => 'Category',
//            'empty_value' => '',
            'attr'        => [
                'class' => 'form-control',
            ],
        ]);

        $builder->add('file', FileType::class, [
            'label' => 'Header Image - 1588 x 350',
            'attr'  => [
                'class' => 'form-control',
            ],
        ]);

        $builder->add('author', null, [
            'attr' => [
                'class' => 'form-control',
            ],
        ]);

        $builder->add('spotlight_user', null, [
            'query_builder' => function (EntityRepository $er) {
                $qb = $er->createQueryBuilder('u');
                if ($this->article->getSpotlightUser()) {
                    $qb->where('u.id = ' . $this->article->getSpotlightUser()->getId());
                }
                $qb->setMaxResults(1);
                return $qb;
            },
        ]);

        $builder->add('short_desc', TextType::class, [
            'attr' => [
                'class' => 'form-control',
            ],
        ]);

        $builder->add('content', null, [
            'attr' => [
                'class' => 'form-control article-content',
            ],
        ]);

        $builder->add('seo_title', null, [
            'label' => 'SEO Title - 55 chars (Optional)',
            'attr'  => [
                'class' => 'form-control',
            ],
        ]);

        $builder->add('seo_desc', null, [
            'label' => 'SEO Description - 160 chars (Optional)',
            'attr'  => [
                'class' => 'form-control',
            ],
        ]);
    }

    public function getName()
    {
        return 'article';
    }
}