<?php

namespace Vocalizr\AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\HttpFoundation\Request;

class VmagController extends Controller
{
    /**
     * @Route("/vmag", name="vmag_index")
     * @Template()
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $q = $em->getRepository('VocalizrAppBundle:Article')->createQueryBuilder('a');
        $q->select('a, ac, author');
        $q->innerJoin('a.article_category', 'ac');
        $q->innerJoin('a.author', 'author');
        $q->where('a.published_at IS NOT NULL');
        $q->orderBy('a.published_at', 'DESC');
        $q->setMaxResults(3);

        $query = $q->getQuery();

        $latest = $query->execute();

        $ids = [];
        foreach ($latest as $row) {
            $ids[] = $row->getId();
        }

        $q = $em->getRepository('VocalizrAppBundle:Article')->createQueryBuilder('a');
        $q->select('a, ac, author');
        $q->innerJoin('a.article_category', 'ac');
        $q->innerJoin('a.author', 'author');
        $q->where('a.published_at IS NOT NULL AND a.id NOT IN (' . implode(',', $ids) . ')');
        $q->orderBy('a.published_at', 'DESC');

        $query = $q->getQuery();

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $this->get('request')->query->get('page', 1)/*page number*/,
            10// limit per page
        );

        return [
            'latest'     => $latest,
            'pagination' => $pagination,
        ];
    }

    /**
     * @Route("/vmag/category/{slug}", name="vmag_category")
     * @Template()
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function categoryAction($slug)
    {
        $em = $this->getDoctrine()->getManager();

        $category = $em->getRepository('VocalizrAppBundle:ArticleCategory')->findOneBy(['slug' => $slug]);

        $q = $em->getRepository('VocalizrAppBundle:Article')->createQueryBuilder('a');
        $q->select('a, ac, author');
        $q->innerJoin('a.article_category', 'ac');
        $q->innerJoin('a.author', 'author');
        $q->where('a.published_at IS NOT NULL AND ac.slug = :slug');
        $q->orderBy('a.published_at', 'DESC');
        $q->setParameter('slug', $slug);

        $query = $q->getQuery();

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $this->get('request')->query->get('page', 1)/*page number*/,
            10// limit per page
        );

        return [
            'pagination' => $pagination,
            'category'   => $category,
        ];
    }

    /**
     * @Route("/vmag/{mth}/{day}/{yr}/{slug}", name="vmag_article")
     * @Template()
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function viewAction(Request $request)
    {
        $user = $this->getUser();

        $em      = $this->getDoctrine()->getManager();
        $article = $em->getRepository('VocalizrAppBundle:Article')->findOneBy([
            'slug' => $request->get('slug'),
        ]);
        $em = $this->getDoctrine()->getManager();
        $q  = $em->getRepository('VocalizrAppBundle:Article')->createQueryBuilder('a');
        $q->select('a, ac, spu, ua, author');
        $q->innerJoin('a.article_category', 'ac');
        $q->innerJoin('a.author', 'author');
        $q->leftJoin('a.spotlight_user', 'spu');
        $q->leftJoin('spu.user_audio', 'ua');
        $q->where('a.slug = :slug');
        $q->setParameter('slug', $request->get('slug'));

        $query = $q->getQuery();

        $articles = $query->getResult();
        if (!$articles) {
            throw $this->createNotFoundException('Invalid aritcle');
        }

        $article = $articles[0];

        $spotlightUser = $article->getSpotlightUser();
        $freePlan      = null;
        $audioLikes    = [];

        if ($spotlightUser) {
            // Get all audio ids on this screen
            if ($user) {
                $dm = $this->get('doctrine_mongodb')->getManager();

                $audioIds = [];

                $defaultAudio = $spotlightUser->getUserAudio();
                if (count($defaultAudio) > 0) {
                    $audioIds[] = $defaultAudio[0]->getId();
                }

                foreach ($spotlightUser->getUserAudio() as $audio) {
                    $audioIds[] = $audio->getId();
                }
                if ($audioIds) {
                    $qb = $dm->createQueryBuilder('VocalizrAppBundle:AudioLike')
                            ->field('from_user_id')->equals($user->getId())
                            ->field('audio_id')->in($audioIds);
                    $results = $qb->getQuery()->execute();

                    foreach ($results as $result) {
                        $audioLikes[] = $result->getAudioId();
                    }
                }
            }

            $freePlan = $em->getRepository('VocalizrAppBundle:SubscriptionPlan')->findOneBy([
                'static_key' => 'FREE',
            ]);
        }

        return [
            'article'    => $article,
            'freePlan'   => $freePlan,
            'audioLikes' => $audioLikes,
        ];
    }

    /**
     * @Route("/vmag/sub", name="vmag_subscribe")
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function subscribeAction(Request $request)
    {
        $user = $this->getUser();

        $em = $this->getDoctrine()->getManager();

        $email = $request->get('email');

        if (!$email) {
            return new JsonResponse(['error' => 'Required']);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['error' => 'Invalid email address']);
        }

        // Search our database
        $qb = $em->getRepository('VocalizrAppBundle:MagUser')
                ->createQueryBuilder('m')
                ->where('UPPER(m.email) = :email');
        $qb->setParameter('email', strtoupper($email));
        $q = $qb->getQuery();

        $magUser = $q->execute();

        if (!$magUser || !isset($magUser[0])) {
            $magUser = new \Vocalizr\AppBundle\Entity\MagUser();
        } else {
            $magUser = $magUser[0];
        }
        $magUser->setEmail($email);
        $magUser->setUnsubscribeAt(null);
        $magUser->setUserInfo($user);
        $em->persist($magUser);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    /**
     * @Route("/vmag/unsub/{uid}", name="vmag_unsubscribe")
     * @Template()
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function unsubscribeAction($uid)
    {
        $em = $this->getDoctrine()->getManager();

        $magUser = $em->getRepository('VocalizrAppBundle:MagUser')->findOneBy(['uid' => $uid]);
        if ($magUser) {
            $magUser->setUnsubscribeAt(new \DateTime());
            $em->flush();
        }

        return [
            'magUser' => $magUser,
        ];
    }

    /**
     * Render side bar
     *
     * @Template()
     *
     * @return array
     */
    public function _sidebarAction($article = null)
    {
        $hireUser       = null;
        $recentArticles = null;
        if ($article) {
            $hireUser       = $article->getSpotlightUser();
            $recentArticles = $this->getRecentArticles($article->getId());
        }

        return [
            'article'        => $article,
            'hireUser'       => $hireUser,
            'recentArticles' => $recentArticles,
        ];
    }

    /**
     * Render category nav
     *
     * @Template()
     *
     * @return array
     */
    public function _categoryNavAction($route, $slug)
    {
        $em = $this->getDoctrine()->getManager();

        $categories = $em->getRepository('VocalizrAppBundle:ArticleCategory')
                ->findBy(['display' => true], ['sort_order' => 'ASC']);

        return [
            'categories' => $categories,
            'route'      => $route,
            'slug'       => $slug,
        ];
    }

    private function getRecentArticles($excludeArticleId = null)
    {
        $em = $this->getDoctrine()->getManager();

        $q = $em->getRepository('VocalizrAppBundle:Article')->createQueryBuilder('a');
        $q->select('a, ac');
        $q->innerJoin('a.article_category', 'ac');
        $q->where('a.published_at IS NOT NULL');
        if ($excludeArticleId) {
            $q->andWhere('a.id != :id');
            $q->setParameter('id', $excludeArticleId);
        }
        $q->orderBy('a.published_at', 'DESC');
        $q->setMaxResults(5);

        $query = $q->getQuery();

        return $query->execute();
    }
}
