<?php

namespace App\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use App\Entity\UserInfo;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class Model
 *
 * @package App\Model
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
     * @var ContainerInterface
     */
    protected $requestStack;

    /**
     * @param EntityManagerInterface $em
     * @param ContainerInterface $container
     */
    public function __construct(EntityManagerInterface $em, ContainerInterface $container, RequestStack $requestStack)
    {
        $this->em        = $em;
        $this->container = $container;
        $this->requestStack = $requestStack;
        $this->repository   = $em->getRepository($this->getEntityName());
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
