<?php

namespace App\Controller;

use App\Document\ProfileView;
use App\Document\ProfileViewUser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class StatsController extends AbstractController
{
    /**
     * @Route("/stats", name="user_stats")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        return $this->redirect($this->generateUrl('user_stat_plays'));
    }

    /**
     * @Route("/stats/plays/{filter}", defaults={"filter" = "7days"}, name="user_stat_plays")
     * @Template()
     *
     * @param Request $request
     * @param ContainerInterface $container
     *
     * @return array
     */
    public function playsAction(Request $request, ContainerInterface $container)
    {
        $user   = $this->getUser();
        $filter = $request->get('filter');

        $categories = [];
        $filters    = [
            '7days'    => 'Last 7 days',
            '14days'   => 'Last 14 days',
            '30days'   => 'Last 30 days',
            '12months' => 'Last 12 months',
            'overall'  => 'Overall',
        ];

        // Get audio ids
        $em        = $this->getDoctrine()->getManager();
        $audioRepo = $em->getRepository('App:UserAudio');

        $userAudio  = $audioRepo->findBy(['user_info' => $user]);
        $audioFiles = [];
        if ($userAudio) {
            foreach ($userAudio as $audio) {
                $audioFiles[$audio->getId()] = $audio->getTitle();
            }
        }

        // If they arn't subscribed, always make it last 7 days
        if (!$user->isSubscribed()) {
            $filter = '7days';
        }

        switch ($filter) {
            case '12months':
                $startDate = new \DateTime('-12 months');
                $endDate   = new \DateTime('now');

            break;

            case '30days':
                $startDate = new \DateTime('-30 days');
                $endDate   = new \DateTime('now');

            break;

            case '14days':
                $startDate = new \DateTime('-14 days');
                $endDate   = new \DateTime('now');

            break;

            case '7days':
            default:
                $startDate = new \DateTime('-7 days');
                $endDate   = new \DateTime('now');

            break;
        }

        $dm = $container->get('doctrine_mongodb')->getManager();

        if ($filter == '12months') {
            $datePeriod = new \DatePeriod(
                $startDate,
                new \DateInterval('P1D'),
                $endDate
            );

            $dateRange = [];
            foreach ($datePeriod as $date) {
                $dateRange[] = $date->format('Y-m');
            }

            $audioPlayStat = $dm->createQueryBuilder('App:AudioPlay')
                ->field('user_id')->equals($user->getId())
                ->field('date')->gte($startDate->format('Y-m-d'))
                ->field('date')->lte($endDate->format('Y-m-d'))
//                ->group(['month' => 1, 'audio_id' => 1], ['date' => 1, 'total' => 0])
//                ->reduce('function ( curr, result ) { '
//                            . 'result.total += curr.count;'
//                            . 'result.date = curr.date;'
//                            . 'month = ISODate(curr.date);'
//                            . 'result.month = month.getMonth();'
//                        . '}')
                ->getQuery()
                ->toArray();

            $stats = [];
            foreach ($audioPlayStat as $row) {
                $row = $row->toArray();
                list($row['yr'], $row['mth'], $row['day']) = explode('-', $row['date']);
                $monthYear                                 = date('Y-m', strtotime($row['date']));
                $monthName                                 = date('F', strtotime($row['date']));
                $row['monthName']                          = $monthName;
                $row['total']                              = $row['count'];
                $row['mth'] = ((int)$row['mth'])-1;

                if(isset($stats[$row['audio_id']]) && isset($stats[$row['audio_id']][$monthYear])) {
                    $row['total'] = $stats[$row['audio_id']][$monthYear]['total'] + $row['total'];
                }

                $stats[$row['audio_id']][$monthYear]       = $row;
            }

            foreach (array_keys($stats) as $audioId) {
                foreach ($dateRange as $date) {
                    if (!isset($stats[$audioId][$date])) {
                        $monthName              = date('F', strtotime($date . '-01'));
                        $row['monthName']       = $monthName;
                        $row['total']           = 0;
                        $stats[$audioId][$date] = $row;
                    }
                }
                ksort($stats[$audioId]);
            }

            $categories = current($stats);
            $categories = array_keys($categories);
        }
        else {
            $datePeriod = new \DatePeriod(
                $startDate,
                new \DateInterval('P1D'),
                $endDate
            );

            $dateRange = [];
            foreach ($datePeriod as $date) {
                $dateRange[] = $date->format('Y-m-d');
            }

            $audioPlayStat = [];
            $audioPlayStatArray = $dm->createQueryBuilder('App:AudioPlay')
                ->field('user_id')->equals($user->getId())
                ->field('date')->gte($startDate->format('Y-m-d'))
                ->field('date')->lte($endDate->format('Y-m-d'))
                ->getQuery()
                ->toArray();

            $stats = [];
            foreach ($audioPlayStatArray as $row) {
                $row = $row->toArray();
                list($row['yr'], $row['mth'], $row['day']) = explode('-', $row['date']);
                $row['mth']                                = $row['mth'] - 1;
                $row['total']                              = $row['count'];

                if(isset($stats[$row['audio_id']]) && isset($stats[$row['audio_id']][$row['date']])) {
                    $row['total'] = $stats[$row['audio_id']][$row['date']]['total'] + $row['total'];
                }

                $stats[$row['audio_id']][$row['date']]     = $row;
            }

            foreach (array_keys($stats) as $audioId) {
                foreach ($dateRange as $date) {
                    if (!isset($stats[$audioId][$date])) {
                        list($row['yr'], $row['mth'], $row['day']) = explode('-', $date);
                        $row['mth']                                = $row['mth'] - 1;
                        $row['total']                              = 0;
                        $stats[$audioId][$date]                    = $row;
                    }
                }
                ksort($stats[$audioId]);
            }
        }

        // Get who has been playing the audio
        $audioPlayUsers =[];
        $audioPlayUsersArray = $dm->createQueryBuilder('App:AudioPlayUser')
            ->field('user_id')->equals($user->getId())
            ->field('date')->gte(date('Y-m-d', strtotime('-90 days')))
            ->field('date')->lte(date('Y-m-d'))
            ->sort([
                'date' => 'desc',
            ])
//            ->group(['from_user_id' => 1], ['from_user_id' => 1])
//            ->reduce('function ( curr, result ) { result.from_user_id = curr.from_user_id; result.date = curr.date; result.created_at = curr.created_at; }')
            ->limit(12)
            ->getQuery()
            ->toArray();

        /** @var ProfileViewUser $data */
        foreach ($audioPlayUsersArray as $data) {
            $audioPlayUsers[] = $data->toArray();
        }

        // Sort in desc
        usort($audioPlayUsers, function ($b, $a) {
            return strtotime($a['date']) - strtotime($b['date']);
        });

        array_splice($audioPlayUsers, 12);

        // Loop through and get user profiles
        $fromUserIds = [];
        foreach ($audioPlayUsers as $audioPlayUser) {
            if ($audioPlayUser['from_user_id']) {
                $fromUserIds[] = $audioPlayUser['from_user_id'];
            }
        }

        $fromUsers = [];
        if ($fromUserIds) {
            $q = $em->getRepository('App:UserInfo')
                    ->createQueryBuilder('ui')
                    ->select('ui, uc, ucs');
            $q->leftJoin('ui.user_connect_invites', 'uc', 'WITH', 'uc.from = :userId');
            $q->leftJoin('ui.user_connect_invites_sent', 'ucs', 'WITH', 'ucs.to = :userId');

            $q->where($q->expr()->in('ui.id', $fromUserIds));

            $q->setParameters([
                'userId' => $user->getId(),
            ]);

            $query     = $q->getQuery();
            $fromUsers = $query->execute();

            $fromUsersTmp = [];
            foreach ($fromUsers as $fuser) {
                $fromUsersTmp[$fuser->getId()] = $fuser;
            }
            $fromUsers = $fromUsersTmp;
        }

        return $this->render('Stats/plays.html.twig', [
            'filter'         => $filter,
            'filters'        => $filters,
            'audioFiles'     => $audioFiles,
            'stats'          => $stats,
            'startDate'      => $startDate,
            'endDate'        => $endDate,
            'categories'     => $categories,
            'fromUsers'      => $fromUsers,
            'audioPlayUsers' => $audioPlayUsers,
        ]);
    }

    /**
     * @Route("/stats/likes/{filter}", defaults={"filter" = "7days"}, name="user_stat_likes")
     * @Template()
     *
     * @param Request            $request
     * @param ContainerInterface $container
     *
     * @return Response
     */
    public function likesAction(Request $request, ContainerInterface $container)
    {
        $user   = $this->getUser();
        $filter = $request->get('filter');

        $categories = [];
        $filters    = [
            '7days'    => 'Last 7 days',
            '14days'   => 'Last 14 days',
            '30days'   => 'Last 30 days',
            '12months' => 'Last 12 months',
            'overall'  => 'Overall',
        ];

        // Get audio ids
        $em        = $this->getDoctrine()->getManager();
        $audioRepo = $em->getRepository('App:UserAudio');

        $userAudio  = $audioRepo->findBy(['user_info' => $user]);
        $audioFiles = [];
        if ($userAudio) {
            foreach ($userAudio as $audio) {
                $audioFiles[$audio->getId()] = $audio->getTitle();
            }
        }

        // If they arn't subscribed, always make it last 7 days
        if (!$user->isSubscribed()) {
            $filter = '7days';
        }

        switch ($filter) {
            case '12months':
                $startDate = new \DateTime('-12 months');
                $endDate   = new \DateTime('+1 day');

            break;

            case '30days':
                $startDate = new \DateTime('-30 days');
                $endDate   = new \DateTime('+1 day');

            break;

            case '14days':
                $startDate = new \DateTime('-14 days');
                $endDate   = new \DateTime('+1 day');

            break;

            case '7days':
            default:
                $startDate = new \DateTime('-7 days');
                $endDate   = new \DateTime('+1 day');

            break;
        }

        $dm = $container->get('doctrine_mongodb')->getManager();

        if ($filter == '12months') {
            $datePeriod = new \DatePeriod(
                $startDate,
                new \DateInterval('P1D'),
                $endDate
            );

            $dateRange = [];
            foreach ($datePeriod as $date) {
                $dateRange[] = $date->format('Y-m');
            }

            $audioLikes = [];
            $audioLikes = $dm->createQueryBuilder('App:AudioLike')
                ->field('user_id')->equals($user->getId())
                ->field('date')->gte($startDate->format('Y-m-d'))
                ->field('date')->lte($endDate->format('Y-m-d'))
//                ->group(['month' => 1, 'audio_id' => 1, 'total' => 0], ['date' => 1])
//                ->reduce('function ( curr, result ) { '
//                            . 'result.total++;'
//                            . 'result.date = curr.date;'
//                            . 'month = ISODate(curr.date);'
//                            . 'result.month = month.getMonth();'
//                        . '}')
                ->getQuery()
                ->toArray();

            $stats = [];
            foreach ($audioLikes as $row) {
                $row = $row->toArray();
                list($row['yr'], $row['mth'], $row['day']) = explode('-', $row['date']);
                $monthYear                                 = date('Y-m', strtotime($row['date']));
                $monthName                                 = date('F', strtotime($row['date']));
                $row['monthName']                          = $monthName;
                $row['total']                              = 1;

                if(isset($stats[$row['audio_id']]) && isset($stats[$row['audio_id']][$monthYear])) {
                    $row['total'] = $row['total']+1;
                }

                $stats[$row['audio_id']][$monthYear]       = $row;
            }

            foreach (array_keys($stats) as $audioId) {
                foreach ($dateRange as $date) {
                    if (!isset($stats[$audioId][$date])) {
                        $monthName              = date('F', strtotime($date . '-01'));
                        $row['monthName']       = $monthName;
                        $row['total']           = 0;
                        $stats[$audioId][$date] = $row;
                    }
                }
                ksort($stats[$audioId]);
            }

            $categories = current($stats);
            $categories = array_keys($categories);
        }
        else {
            $datePeriod = new \DatePeriod(
                $startDate,
                new \DateInterval('P1D'),
                $endDate
            );

            $dateRange = [];
            foreach ($datePeriod as $date) {
                $dateRange[] = $date->format('Y-m-d');
            }

            $audioLikeStat = [];
            $audioLikeStat = $dm->createQueryBuilder('App:AudioLike')
                ->field('user_id')->equals($user->getId())
                ->field('date')->gte($startDate->format('Y-m-d'))
                ->field('date')->lte($endDate->format('Y-m-d'))
//                ->group(['date_new' => 1, 'audio_id' => 1, 'total' => 0], ['date' => 1])
//                ->reduce("function ( curr, result ) {
//                            result.total++;
//                            result.date = curr.date;
//                            month = ISODate(curr.date);
//                            result.month = month.getMonth();
//                            curdate = ISODate(curr.date);
//
//                            result.date_new = curdate.getFullYear() + '-'
//                            + ('0' + (curdate.getUTCMonth() + 1) ).slice(-2) + '-'
//                            + curdate.getDate();
//
//                        }")
                ->getQuery()
                ->toArray();

            $stats = [];
            foreach ($audioLikeStat as $row) {
                $row             = $row->toArray();
                $date            = new \DateTime($row['date']);
                $row['date_new'] = $date->format('Y') . '-' . $date->format('m'). '-' . $date->format('d');
                list($row['yr'], $row['mth'], $row['day']) = explode('-', $row['date_new']);
                $row['mth']                                = $row['mth'] - 1;
                $row['total']                              = 1;

                if(isset($stats[$row['audio_id']]) && isset($stats[$row['audio_id']][$row['date_new']])) {
                    $row['total'] = $row['total']+1;
                }

                $stats[$row['audio_id']][$row['date_new']] = $row;
            }

            foreach (array_keys($stats) as $audioId) {
                foreach ($dateRange as $date) {
                    if (!isset($stats[$audioId][$date])) {
                        list($row['yr'], $row['mth'], $row['day']) = explode('-', $date);
                        $row['mth']                                = $row['mth'] - 1;
                        $row['total']                              = 0;
                        $stats[$audioId][$date]                    = $row;
                    }
                }
                ksort($stats[$audioId]);
            }
        }

        // Get who has been liking the audio
        $audioLikeUsers = [];
        $audioLikeUsersData = $dm->createQueryBuilder('App:AudioLike')
            ->field('user_id')->equals($user->getId())
            ->field('date')->gte(date('Y-m-d', strtotime('-90 days')))
            ->field('date')->lte(date('Y-m-d', strtotime('+1 day')))
            ->sort([
                'date' => 'desc',
            ])
            ->limit(15)
//            ->group(['from_user_id' => 0], ['from_user_id' => 1])
//                ->reduce('function ( curr, result ) {
//                            result.from_user_id = curr.from_user_id;
//                            result.date = curr.date;
//                        }')
            ->getQuery()
            ->toArray();

        foreach ($audioLikeUsersData as $row) {
            $audioLikeUsers[] = $row->toArray();
        }

        // Sort in desc
        usort($audioLikeUsers, function ($b, $a) {
            return strtotime($a['date']) - strtotime($b['date']);
        });

        array_splice($audioLikeUsers, 12);

        // Loop through and get user profiles
        $fromUserIds = [];
        foreach ($audioLikeUsers as $audioLike) {
            $fromUserIds[] = $audioLike['from_user_id'];
        }

        $fromUsers = [];
        if ($fromUserIds) {
            $q = $em->getRepository('App:UserInfo')
                    ->createQueryBuilder('ui')
                    ->select('ui, uc, ucs');
            $q->leftJoin('ui.user_connect_invites', 'uc', 'WITH', 'uc.from = :userId');
            $q->leftJoin('ui.user_connect_invites_sent', 'ucs', 'WITH', 'ucs.to = :userId');

            $q->where($q->expr()->in('ui.id', $fromUserIds));

            $q->setParameters([
                'userId' => $user->getId(),
            ]);

            $query     = $q->getQuery();
            $fromUsers = $query->execute();

            $fromUsersTmp = [];
            foreach ($fromUsers as $fuser) {
                $fromUsersTmp[$fuser->getId()] = $fuser;
            }
            $fromUsers = $fromUsersTmp;
        }

        return $this->render('Stats/likes.html.twig', [
            'filter'         => $filter,
            'filters'        => $filters,
            'audioFiles'     => $audioFiles,
            'stats'          => $stats,
            'startDate'      => $startDate,
            'endDate'        => $endDate,
            'categories'     => $categories,
            'fromUsers'      => $fromUsers,
            'audioLikeUsers' => $audioLikeUsers,
        ]);
    }

    /**
     * @Route("/stats/views/{filter}", defaults={"filter" = "7days"}, name="user_stat_views")
     * @Template()
     *
     * @param Request            $request
     * @param ContainerInterface $container
     *
     * @return Response
     * @throws \Exception
     */
    public function viewsAction(Request $request, ContainerInterface $container)
    {
        $em     = $this->getDoctrine()->getManager();
        $user   = $this->getUser();
        $filter = $request->get('filter');

        $categories = [];
        $filters    = [
            '7days'    => 'Last 7 days',
            '14days'   => 'Last 14 days',
            '30days'   => 'Last 30 days',
            '12months' => 'Last 12 months',
            'overall'  => 'Overall',
        ];

        // If they arn't subscribed, always make it last 7 days
        if (!$user->isSubscribed()) {
            $filter = '7days';
        }

        switch ($filter) {
            case '12months':
                $startDate = new \DateTime('-12 months');
                $endDate   = new \DateTime('now');

            break;

            case '30days':
                $startDate = new \DateTime('-30 days');
                $endDate   = new \DateTime('now');

            break;

            case '14days':
                $startDate = new \DateTime('-14 days');
                $endDate   = new \DateTime('now');

            break;

            case '7days':
            default:
                $startDate = new \DateTime('-7 days');
                $endDate   = new \DateTime('now');

            break;
        }

        $dm = $container->get('doctrine_mongodb')->getManager();

        if ($filter == '12months') {
            $datePeriod = new \DatePeriod(
                $startDate,
                new \DateInterval('P1D'),
                $endDate
            );

            $dateRange = [];
            foreach ($datePeriod as $date) {
                $dateRange[] = $date->format('Y-m');
            }

            $profileViews = [];
            $profileViewsDataArray = $dm->createQueryBuilder('App:ProfileView')
                ->field('user_id')->equals($user->getId())
                ->field('unique')->equals(false)
                ->field('date')->gte($startDate->format('Y-m-d'))
                ->field('date')->lte($endDate->format('Y-m-d'))
                ->getQuery()
                ->toArray();

            $profileViewsData = [];
            foreach ($profileViewsDataArray as $row) {
                $row = $row->toArray();
                $date = new \DateTime($row['date']);
                $dateIndex = $date->format('Y').'-'.$date->format('m');
                if(array_key_exists($dateIndex, $profileViews)) {
                    $profileViewsData[$dateIndex] = $profileViews[$dateIndex] + $row['count'];
                } else {
                    $profileViewsData[$dateIndex] = $row['count'];
                }
            }

            $monthyr = $total = 1;
            foreach ($profileViewsData as $index => $profileViewsDataRow) {
                if($profileViewsDataRow >= $total) {
                    $total   = $profileViewsDataRow;
                    $monthyr = $index;
                }

            }

            $profileViews            = $profileViewsData;
            $profileViews['monthyr'] = $monthyr;
            $profileViews['total']   = $total;

            $stats = [];

            foreach ($dateRange as $date) {
                $yearMonth = $date;
                $monthName = date('F', strtotime($date . '-01'));
                if (!isset($profileViews[$yearMonth])) {
                    $stats[$yearMonth] = [
                        'yearMonth' => $yearMonth,
                        'monthName' => $monthName,
                        'total'     => 0,
                    ];
                } else {
                    $stats[$yearMonth] = [
                        'yearMonth' => $yearMonth,
                        'monthName' => $monthName,
                        'total'     => $profileViews[$yearMonth],
                    ];
                }
            }

            // Sort in asc
            ksort($stats);
            $categories = array_keys($stats);
        } else {
            $datePeriod = new \DatePeriod(
                $startDate,
                new \DateInterval('P1D'),
                $endDate
            );

            $dateRange = [];
            foreach ($datePeriod as $date) {
                $dateRange[] = $date->format('Y-m-d');
            }

            $profileViewStat = $dm->createQueryBuilder('App:ProfileView')
                ->field('user_id')->equals($user->getId())
                ->field('unique')->equals(false)
                ->field('date')->gte($startDate->format('Y-m-d'))
                ->field('date')->lte($endDate->format('Y-m-d'))
                ->getQuery()
                ->toArray();

            foreach ($profileViewStat as $row) {
                $row = $row->toArray();
                list($row['yr'], $row['mth'], $row['day']) = explode('-', $row['date']);
                $row['total']                              = $row['count'];
                $row['mth']                                = $row['mth'] - 1;
                $stats[$row['date']]                       = $row;
            }

            foreach ($dateRange as $date) {
                if (!isset($stats[$date])) {
                    list($row['yr'], $row['mth'], $row['day']) = explode('-', $date);
                    $row['mth']                                = $row['mth'] - 1;
                    $row['total']                              = 0;
                    $row['date']                               = $date;
                    $stats[$date]                              = $row;
                }
            }

            // Sort in asc
            usort($stats, function ($a, $b) {
                return strtotime($a['date']) - strtotime($b['date']);
            });
        }

        // Get who has been liking the audio
        $profileViewUsers = [];
        $profileViewUsersData = $dm->createQueryBuilder('App:ProfileViewUser')
            ->field('user_id')->equals($user->getId())
            ->field('from_user_id')->gte(0)
            ->field('date')->gte(date('Y-m-d', strtotime('-90 days')))
            ->field('date')->lte(date('Y-m-d'))
            ->sort([
                'created_at' => 'desc',
            ])
            ->limit(12)
//            ->group(['from_user_id' => 0], ['from_user_id' => 1])
//                ->reduce('function ( curr, result ) {
//                            result.from_user_id = curr.from_user_id;
//                            result.date = curr.date;
//                        }')
            ->getQuery()
            ->toArray();

        /** @var ProfileViewUser $data */
        foreach ($profileViewUsersData as $data) {
            $profileViewUsers[] = $data->toArray();
        }
        // Sort in desc
        usort($profileViewUsers, function ($b, $a) {
            return strtotime($a['date']) - strtotime($b['date']);
        });

        array_splice($profileViewUsers, 12);

        // Loop through and get user profiles
        $fromUserIds = [];
        foreach ($profileViewUsers as $profileViewUser) {
            $fromUserIds[] = $profileViewUser['from_user_id'];
        }

        $fromUsers = [];
        if ($fromUserIds) {
            $q = $em->getRepository('App:UserInfo')
                    ->createQueryBuilder('ui')
                    ->select('ui, uc, ucs')
                    ->where('ui.active = 1');
            $q->leftJoin('ui.user_connect_invites', 'uc', 'WITH', 'uc.from = :userId');
            $q->leftJoin('ui.user_connect_invites_sent', 'ucs', 'WITH', 'ucs.to = :userId');

            $q->where($q->expr()->in('ui.id', $fromUserIds));

            $q->setParameters([
                'userId' => $user->getId(),
            ]);

            $q->setMaxResults(15);

            $query     = $q->getQuery();
            $fromUsers = $query->execute();

            $fromUsersTmp = [];
            foreach ($fromUsers as $fuser) {
                $fromUsersTmp[$fuser->getId()] = $fuser;
            }
            $fromUsers = $fromUsersTmp;
        }

        return $this->render('Stats/views.html.twig', [
            'filter'           => $filter,
            'filters'          => $filters,
            'stats'            => $stats,
            'startDate'        => $startDate,
            'endDate'          => $endDate,
            'categories'       => $categories,
            'fromUsers'        => $fromUsers,
            'profileViewUsers' => $profileViewUsers,
        ]);
    }
}
