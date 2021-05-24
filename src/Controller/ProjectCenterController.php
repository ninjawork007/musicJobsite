<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\ProjectBid;

class ProjectCenterController extends AbstractController
{
    /**
     * The main gig center page. Loads the project activity for display to the user
     *
     * @Route("/gig-center", name="project_center")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        // load the active projects
        $qb = $this->getDoctrine()
                   ->getRepository('App:Project')
                   ->createQueryBuilder('p');
        $qb->select('p, u, pb')
           ->innerJoin('p.user_info', 'u')
           ->innerJoin('p.project_bid', 'pb')
           ->where('p.is_complete = :complete and p.is_active = 1')
           ->andWhere(
               $qb->expr()->orX(
                    $qb->expr()->eq('p.user_info', ':user'),
                    $qb->expr()->eq('pb.user_info', ':user')
                )
           )
           ->setParameter('user', $this->getUser())
           ->setParameter('complete', false)
           ->orderBy('p.created_at', 'DESC');
        $activeProjects = $qb->getQuery()->execute();

        // load the published gigs
        $qb = $this->getDoctrine()
                   ->getRepository('App:Project')
                   ->createQueryBuilder('p');
        $qb->select('p, u, pb')
           ->innerJoin('p.user_info', 'u')
           ->leftJoin('p.project_bids', 'pb', 'WITH', 'pb.flag = :flag')
           ->leftJoin('p.project_escrow', 'pe')
           ->where('p.project_bid is null')
           ->andWhere('p.user_info = :user')
           ->andWhere('p.published_at IS NOT NULL and p.is_active = 1')
           ->andWhere('(pe.id IS NOT NULL and pe.refunded = 0) OR pe.id IS NULL')
           ->setParameter('flag', 'A')
           ->setParameter('user', $this->getUser())
           ->orderBy('p.created_at', 'DESC');
        $publishedProjects = $qb->getQuery()->execute();

        $publishedProjectsBidsStats = $em->getRepository(ProjectBid::class)
            ->getBidStats($publishedProjects)
        ;

        // load the unpublished gigs
        $qb = $this->getDoctrine()
                   ->getRepository('App:Project')
                   ->createQueryBuilder('p');
        $qb->select('p, u')
           ->innerJoin('p.user_info', 'u')
           ->where('p.user_info = :user')
           ->andWhere('p.published_at IS NULL AND p.is_active = 1')
           ->setParameter('user', $this->getUser())
           ->orderBy('p.created_at', 'DESC');
        $unpublishedProjects = $qb->getQuery()->execute();

        // load the completed gigs
        $qb = $this->getDoctrine()
                   ->getRepository('App:Project')
                   ->createQueryBuilder('p');
        $qb->select('p, u, pb')
            ->innerJoin('p.user_info', 'u')
            ->innerJoin('p.project_bid', 'pb')
            ->where('p.is_complete = :complete')
            ->andWhere('p.is_active = 1')
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('p.user_info', ':user'),
                    $qb->expr()->eq('pb.user_info', ':user')
                )
            )
            ->setParameter('complete', true)
            ->setParameter('user', $this->getUser())
            ->orderBy('p.created_at', 'DESC');
        $completedProjects = $qb->getQuery()->execute();

        // load the expired gigs
        $qb = $this->getDoctrine()
                   ->getRepository('App:Project')
                   ->createQueryBuilder('p');
        $qb->select('p, u')
           ->innerJoin('p.user_info', 'u')
           ->where('p.project_bid is null AND p.is_active = 1')
           ->andWhere('p.bids_due < :now')
           ->andWhere('p.user_info = :user')
           ->setParameter('user', $this->getUser())
           ->setParameter('now', new \DateTime())
           ->orderBy('p.created_at', 'DESC');
        $expiredProjects = $qb->getQuery()->execute();

        $numProjects = count($activeProjects) + count($publishedProjects) + count($unpublishedProjects) +
                         count($completedProjects) + count($expiredProjects);

        $bidsDue = new \DateTime();
        $bidsDue->sub(new \DateInterval('P4D'));

        // load the current bids
        $qb = $this->getDoctrine()
                   ->getRepository('App:ProjectBid')
                   ->createQueryBuilder('pb');
        $qb->select('p, u, pb')
           ->innerJoin('pb.project', 'p')
           ->innerJoin('pb.user_info', 'u')
           ->where('p.project_bid is null and p.is_active = 1')
           ->andWhere('pb.user_info = :user')
           ->andWhere('p.bids_due >= :bidsDue')
           //->andWhere('p.project_type = :projectType')
           ->setParameter('user', $this->getUser())
           ->setParameter('bidsDue', $bidsDue)
           //->setParameter('projectType', \App\Entity\Project::PROJECT_TYPE_PAID)
           ->orderBy('pb.created_at', 'DESC');
        $currentBids = $qb->getQuery()->execute();

        // load the successful bids
        $qb = $this->getDoctrine()
                   ->getRepository('App:Project')
                   ->createQueryBuilder('p');
        $qb->select('p, u, pb')
           ->innerJoin('p.project_bid', 'pb')
           ->innerJoin('pb.user_info', 'u')
           ->where('pb.user_info = :user and p.is_active = 1')
           //->andWhere('p.project_type = :projectType')
           ->andWhere('p.completed_at IS NULL')
           ->setParameter('user', $this->getUser())
           //->setParameter('projectType', \App\Entity\Project::PROJECT_TYPE_PAID)
           ->orderBy('pb.created_at', 'DESC');
        $successfulBids = $qb->getQuery()->execute();

        // load the unsuccessful bids
        $qb = $this->getDoctrine()
                   ->getRepository('App:ProjectBid')
                   ->createQueryBuilder('pb');
        $qb->select('p, u, pb, wb, eui')
            ->innerJoin('pb.project', 'p')
            ->innerJoin('p.employee_user_info', 'eui')
            ->innerJoin('p.project_bid', 'wb')
            ->innerJoin('pb.user_info', 'u')
            ->where('pb.user_info = :user and p.is_active = 1')
            ->andWhere('p.project_bid != pb')
            ->setParameter('user', $this->getUser())
            ->orderBy('pb.created_at', 'DESC')
        ;
        $unsuccessfulBids = $qb->getQuery()->execute();

        // get the number of bids for this users
        $qb = $this->getDoctrine()
                   ->getRepository('App:Project')
                   ->createQueryBuilder('p');
        $qb->select('count(p)')
                ->where('p.user_info = :user')
                ->orWhere('p.employee_user_info = :user AND p.is_active = 1')
                ->setParameter('user', $this->getUser());
        $numProjects = $qb->getQuery()->getSingleScalarResult();

        // get the number of bids for this users
        $numBids = count($currentBids) + count($successfulBids) + count($unsuccessfulBids);

        // get the number of invites for this users
        $qb = $this->getDoctrine()
                   ->getRepository('App:ProjectInvite')
                   ->createQueryBuilder('i');
        $qb->select('count(i)')
            ->leftJoin('i.project', 'p')
            ->where('i.user_info = :user')
            ->andWhere('i.deleted = false')
            ->andWhere('p.is_active = 1')
            ->setParameter('user', $this->getUser());
        $numInvites = $qb->getQuery()->getSingleScalarResult();

        $this->getUser()->setUnreadProjectActivity(false);
        $em->flush();

        return $this->render('ProjectCenter/index.html.twig', [
            'numProjects'         => $numProjects,
            'activeProjects'      => $activeProjects,
            'publishedProjects'   => $publishedProjects,
            'publishedBidsStats'  => $publishedProjectsBidsStats,
            'unpublishedProjects' => $unpublishedProjects,
            'completedProjects'   => $completedProjects,
            'expiredProjects'     => $expiredProjects,
            'numBids'             => $numBids,
            'numInvites'          => $numInvites,
        ]);
    }

    /**
     * The main gig center page. Loads the project activity for display to the user
     *
     * @Route("/gig-center/bids", name="project_center_bids")
     * @Template()
     */
    public function bidsAction(Request $request)
    {
        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        $bidsDue = new \DateTime();
        $bidsDue->sub(new \DateInterval('P4D'));

        // load the current bids
        $qb = $this->getDoctrine()
                   ->getRepository('App:ProjectBid')
                   ->createQueryBuilder('pb');
        $qb->select('p, u, pb')
           ->innerJoin('pb.project', 'p')
           ->innerJoin('pb.user_info', 'u')
           ->where('p.project_bid is null and p.is_active = 1')
           ->andWhere('pb.user_info = :user')
           ->andWhere('p.bids_due >= :bidsDue')
           //->andWhere('p.project_type = :projectType')
           ->setParameter('user', $this->getUser())
           ->setParameter('bidsDue', $bidsDue)
           //->setParameter('projectType', \App\Entity\Project::PROJECT_TYPE_PAID)
           ->orderBy('pb.created_at', 'DESC');
        $currentBids = $qb->getQuery()->execute();

        // load the successful bids
        $qb = $this->getDoctrine()
                   ->getRepository('App:Project')
                   ->createQueryBuilder('p');
        $qb->select('p, u, pb')
           ->innerJoin('p.project_bid', 'pb')
           ->innerJoin('pb.user_info', 'u')
           ->where('pb.user_info = :user and p.is_active = 1')
           //->andWhere('p.project_type = :projectType')
           ->andWhere('p.completed_at IS NULL')
           ->setParameter('user', $this->getUser())
           //->setParameter('projectType', \App\Entity\Project::PROJECT_TYPE_PAID)
           ->orderBy('pb.created_at', 'DESC');
        $successfulBids = $qb->getQuery()->execute();

        // load the unsuccessful bids
        $qb = $this->getDoctrine()
                   ->getRepository('App:ProjectBid')
                   ->createQueryBuilder('pb');
        $qb->select('p, u, pb, wb, eui')
            ->innerJoin('pb.project', 'p')
            ->innerJoin('p.employee_user_info', 'eui')
            ->innerJoin('p.project_bid', 'wb')
            ->innerJoin('pb.user_info', 'u')
            ->where('pb.user_info = :user and p.is_active = 1')
            ->andWhere('p.project_bid != pb')
            ->setParameter('user', $this->getUser())
            ->orderBy('pb.created_at', 'DESC')
        ;
        $unsuccessfulBids = $qb->getQuery()->execute();

        // get the number of bids for this users
        $qb = $this->getDoctrine()
                   ->getRepository('App:Project')
                   ->createQueryBuilder('p');
        $qb->select('count(p)')
                ->where('p.user_info = :user AND p.is_active = 1')
                ->orWhere('p.employee_user_info = :user AND p.is_active = 1')
                ->setParameter('user', $this->getUser());
        $numProjects = $qb->getQuery()->getSingleScalarResult();

        // get the number of bids for this users
        $numBids = count($currentBids) + count($successfulBids) + count($unsuccessfulBids);

        // get the number of invites for this users
        $qb = $this->getDoctrine()
                   ->getRepository('App:ProjectInvite')
                   ->createQueryBuilder('i');
        $qb->select('count(i)')
            ->leftJoin('i.project', 'p')
            ->where('i.user_info = :user')
            ->andWhere('p.is_active = 1')
            ->andWhere('i.deleted = false')
            ->setParameter('user', $this->getUser());
        $numInvites = $qb->getQuery()->getSingleScalarResult();

        $this->getUser()->setUnreadProjectActivity(false);
        $em->flush();

        return $this->render('ProjectCenter/bids.html.twig', [
            'currentBids'      => $currentBids,
            'successfulBids'   => $successfulBids,
            'unsuccessfulBids' => $unsuccessfulBids,
            'numProjects'      => $numProjects,
            'numBids'          => $numBids,
            'numInvites'       => $numInvites,
        ]);
    }

    /**
     * Loads project invites for logged in user
     *
     * @Route("/gig-center/invites", name="project_center_invites")
     * @Route("/gig-center/invites/delete/{deleteId}", name="project_invites_delete")
     * @Template()
     */
    public function invitesAction(Request $request)
    {
        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        $createdAt = new \DateTime();
        $createdAt->sub(new \DateInterval('P30D'));
        $qb = $this->getDoctrine()
                   ->getRepository('App:ProjectInvite')
                   ->createQueryBuilder('pi');
        $qb->select('p, u, pi, pui')
           ->innerJoin('pi.project', 'p')
           ->innerJoin('p.user_info', 'pui')
           ->innerJoin('pi.user_info', 'u')
           ->andWhere('pi.user_info = :user and p.is_active = 1')
           ->andWhere('p.created_at >= :createdAt')
           ->setParameter('user', $this->getUser())
           ->setParameter('createdAt', $createdAt)
           ->orderBy('pi.created_at', 'DESC');
        $invites = $qb->getQuery()->execute();

        // get the number of bids for this users
        $qb = $this->getDoctrine()
                   ->getRepository('App:Project')
                   ->createQueryBuilder('p');
        $qb->select('count(p)')
                ->where('p.user_info = :user and p.is_active = 1')
                ->orWhere('p.employee_user_info = :user')
                ->setParameter('user', $this->getUser());
        $numProjects = $qb->getQuery()->getSingleScalarResult();

        $bidsDue = new \DateTime();
        $bidsDue->sub(new \DateInterval('P4D'));

        // load the current bids
        $qb = $this->getDoctrine()
                   ->getRepository('App:ProjectBid')
                   ->createQueryBuilder('pb');
        $qb->select('p, u, pb')
           ->innerJoin('pb.project', 'p')
           ->innerJoin('pb.user_info', 'u')
           ->where('p.project_bid is null and p.is_active = 1')
           ->andWhere('pb.user_info = :user')
           ->andWhere('p.bids_due >= :bidsDue')
           //->andWhere('p.project_type = :projectType')
           ->setParameter('user', $this->getUser())
           ->setParameter('bidsDue', $bidsDue)
           //->setParameter('projectType', \App\Entity\Project::PROJECT_TYPE_PAID)
           ->orderBy('pb.created_at', 'DESC');
        $currentBids = $qb->getQuery()->execute();

        // load the successful bids
        $qb = $this->getDoctrine()
                   ->getRepository('App:Project')
                   ->createQueryBuilder('p');
        $qb->select('p, u, pb')
           ->innerJoin('p.project_bid', 'pb')
           ->innerJoin('pb.user_info', 'u')
           ->where('pb.user_info = :user and p.is_active = 1')
           //->andWhere('p.project_type = :projectType')
           ->andWhere('p.completed_at IS NULL')
           ->setParameter('user', $this->getUser())
           //->setParameter('projectType', \App\Entity\Project::PROJECT_TYPE_PAID)
           ->orderBy('pb.created_at', 'DESC');
        $successfulBids = $qb->getQuery()->execute();

        // load the unsuccessful bids
        $qb = $this->getDoctrine()
                   ->getRepository('App:ProjectBid')
                   ->createQueryBuilder('pb');
        $qb->select('p, u, pb, wb, eui')
            ->innerJoin('pb.project', 'p')
            ->innerJoin('p.employee_user_info', 'eui')
            ->innerJoin('p.project_bid', 'wb')
            ->innerJoin('pb.user_info', 'u')
            ->where('pb.user_info = :user and p.is_active = 1')
            ->andWhere('p.project_bid != pb')
            ->setParameter('user', $this->getUser())
            ->orderBy('pb.created_at', 'DESC')
        ;
        $unsuccessfulBids = $qb->getQuery()->execute();

        // get the number of bids for this users
        $qb = $this->getDoctrine()
                   ->getRepository('App:Project')
                   ->createQueryBuilder('p');
        $qb->select('count(p)')
                ->where('p.user_info = :user')
                ->orWhere('p.employee_user_info = :user AND p.is_active = 1')
                ->setParameter('user', $this->getUser());
        $numProjects = $qb->getQuery()->getSingleScalarResult();

        // get the number of bids for this users
        $numBids = count($currentBids) + count($successfulBids) + count($unsuccessfulBids);

        // get the number of invites for this users
        $numInvites = count($invites);

        // update the invitation to read if there is one for this user and project
        if (count($invites) > 0) {
            foreach ($invites as $invitation) {
                $invitation->setReadAt(new \DateTime());
            }
            $em->flush();
        }

        // check to see if the user has any unread projects or invitations
        $q = $em->getRepository('App:Project')->createQueryBuilder('p');
        $q->select('count(p)')
                ->where('p.user_info = :user_info and p.is_active = 1')
                ->andWhere('p.last_activity != :empty_activity')
                ->andWhere('p.employer_read_at is null')
                ->setParameter(':user_info', $this->getUser())
                ->setParameter(':empty_activity', '{}');
        $numEmployerUnread = $q->getQuery()->getSingleScalarResult();

        $q = $em->getRepository('App:Project')->createQueryBuilder('p');
        $q->select('count(p)')
            ->where('p.employee_user_info = :user_info')
            ->andWhere('p.last_activity != :empty_activity')
            ->andWhere('p.employee_read_at is null')
            ->andWhere('p.is_active = 1')
            ->setParameter(':user_info', $this->getUser())
            ->setParameter(':empty_activity', '{}');
        $numEmployeeUnread = $q->getQuery()->getSingleScalarResult();

        if ($this->getUser() && ($numEmployerUnread == 0 && $numEmployeeUnread == 0)) {
            //$this->getUser()->setUnreadProjectActivity(false);
        }
        $this->getUser()->setUnreadProjectActivity(false);

        $q = $em->getRepository('App:ProjectInvite')->createQueryBuilder('pi');
        $q->select('count(pi)')
                ->innerJoin('pi.project', 'p')
                ->where('pi.user_info = :user_info and p.is_active = 1')
                ->andWhere('pi.read_at is null')
                ->setParameter(':user_info', $this->getUser());
        $numInvitesUnread = $q->getQuery()->getSingleScalarResult();
        if ($this->getUser() && $numInvitesUnread == 0) {
        }
        $this->getUser()->setUnseenProjectInvitation(false);

        $em->flush();

        return $this->render('ProjectCenter/invites.html.twig', [
            'invites'     => $invites,
            'numProjects' => $numProjects,
            'numBids'     => $numBids,
            'numInvites'  => $numInvites,
        ]);
    }

    /**
     * Get the count of unread activity for this user
     *
     */
    public function unreadActivityAction(Request $request)
    {
        return $this->render('ProjectCenter/unreadActivity.html.twig', []);
    }
}