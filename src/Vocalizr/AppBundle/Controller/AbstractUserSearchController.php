<?php

namespace Vocalizr\AppBundle\Controller;

use Knp\Component\Pager\Pagination\PaginationInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Vocalizr\AppBundle\Entity\UserInfo;

/**
 * Class AbstractUserSearchController
 * @package Vocalizr\AppBundle\Controller
 */
abstract class AbstractUserSearchController extends Controller
{
    /**
     * @return array
     */
    protected function getProjectConfigData()
    {
        // Get fee options
        $ymlParser  = new \Symfony\Component\Yaml\Parser();
        $file       = $this->get('kernel')->getRootDir() . '/../src/Vocalizr/AppBundle/Resources/config/project.yml';
        return $ymlParser->parse(file_get_contents($file));
    }

    /**
     * @param PaginationInterface $pagination
     * @return array
     */
    protected function splitPaginationAndAggregatedData(PaginationInterface $pagination)
    {
        $items                 = [];
        $relatedAggregatedData = [];
        foreach ($pagination as $item) {
            /** @var UserInfo $entity */
            $entity = array_shift($item);
            $items[$entity->getId()] = $entity;
            $relatedAggregatedData[$entity->getId()] = $item;
        }
        $pagination->setItems($items);

        return $relatedAggregatedData;
    }
}