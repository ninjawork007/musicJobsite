<?php

namespace App\Model;

use Doctrine\ORM\EntityManager;
use App\Entity\UserInfo;
use App\Entity\UserVideo;
use App\Repository\UserVideoRepository;

/**
 * Class UserVideoModel
 *
 * @package App\Model
 */
class UserVideoModel extends Model
{
    /**
     * @param string   $link
     * @param UserInfo $user
     *
     * @return bool|UserVideo|null
     */
    public function createUserVideo($link, $user)
    {
        $video = new UserVideo();
        $video->setUserInfo($user);

        if (!filter_var($link, FILTER_VALIDATE_URL)) {
            return null;
        }

        $url = parse_url($link);

        switch ($url['host']) {
            case 'www.youtube.com':case 'youtube.com':

                $id = explode('=', $url['query']);

                if (!isset($id[1])) {
                    return null;
                }

                $video->setProvider('youtube');
                $video->setLink($id[1]);
                break;
            case 'youtu.be': case 'www.youtu.be':

                $id = str_replace('/', '', $url['path']);

                if (empty($id)) {
                    return null;
                }

                $video->setProvider('youtube');
                $video->setLink($id);
                break;
            case 'www.vimeo.com':case 'vimeo.com':

                $id = str_replace('/', '', $url['path']);

                if (empty($id)) {
                    return null;
                }

                $video->setProvider('vimeo');
                $video->setLink($id);
                break;
            case 'player.vimeo.com': case 'www.player.vimeo.com':

                $id = explode('/', $url['path']);

                if (!isset($id[2])) {
                    return null;
                }
                $video->setProvider('vimeo');
                $video->setLink($id[2]);
                break;
            default:
                return null;
        }

        $user->addUserVideo($video);

        $this->updateObject($video);

        return $video;
    }

    /**
     * @param UserInfo $userInfo
     * @param int      $offset
     * @param int      $limit
     *
     * @return mixed
     */
    public function getUserVideos($userInfo, $offset = 0, $limit = 10)
    {
        if ($userInfo->isSubscribed()) {
            return $this->repository->getUserVideos($userInfo, $offset, $limit);
        } else {
            return [];
        }
    }

    /**
     * @param $data
     *
     * @return bool
     */
    public function sortVideo($data)
    {
        $ids = [];

        $indexes = [];

        foreach ($data as $row) {
            $ids[]             = $row->id;
            $indexes[$row->id] = $row->position;
        }

        $videos = $this->repository->getUserVideosByIds($ids);

        /** @var UserVideo $video */
        foreach ($videos as $video) {
            $video->setSortNumber($indexes[$video->getId()]);
            $this->em->persist($video);
        }

        $this->em->flush();

        return true;
    }

    protected function getEntityName()
    {
        return UserVideo::class;
    }
}