<?php

namespace App\Controller;

use Knp\Component\Pager\Pagination\PaginationInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\UserInfo;

/**
 * Class AbstractUserSearchController
 * @package App\Controller
 */
abstract class AbstractUserSearchController extends AbstractController
{
    /**
     * @return array
     */
    protected function getProjectConfigData()
    {
        // Get fee options
        $ymlParser  = new \Symfony\Component\Yaml\Parser();
        $file       = $this->getParameter('kernel.project_dir') . '/config/packages/project.yml';

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