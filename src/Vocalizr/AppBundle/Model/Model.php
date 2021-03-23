<?php

namespace Vocalizr\AppBundle\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Vocalizr\AppBundle\Entity\UserInfo;

/**
 * Class Model
 *
 * @package Vocalizr\AppBundle\Model
 */
abstract class Model
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var EntityRepository
     */
    protected $repository;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param EntityManager $em
     * @param ContainerInterface $container
     */
    public function setBaseDependencies(EntityManager $em, ContainerInterface $container)
    {
        $this->em        = $em;
        $this->container = $container;
        $this->repository = $em->getRepository($this->getEntityName());
    }

    /**
     * @param $id
     *
     * @return UserInfo|object|null
     */
    public function getObject($id)
    {
        return $this->repository->find($id);
    }

    /**
     * @param $object
     */
    public function updateObject($object)
    {
        $this->em->persist($object);
        $this->em->flush();
    }

    /**
     * @param $object
     */
    public function removeObject($object)
    {
        $this->em->remove($object);
        $this->em->flush();
    }

    /**
     * @return EntityRepository
     */
    protected function repo()
    {
        return $this->repository;
    }

    /**
     * @return string
     */
    abstract protected function getEntityName();
}
