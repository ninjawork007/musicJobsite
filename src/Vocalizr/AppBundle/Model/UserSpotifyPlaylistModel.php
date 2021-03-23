<?php

namespace Vocalizr\AppBundle\Model;

use Doctrine\ORM\EntityManager;
use Vocalizr\AppBundle\Entity\UserInfo;
use Vocalizr\AppBundle\Entity\UserSpotifyPlaylist;
use Vocalizr\AppBundle\Exception\CreateSpotifyPlaylistException;
use Vocalizr\AppBundle\Repository\UserSpotifyPlaylistRepository;

/**
 * Class UserSpotifyPlaylistModel
 *
 * @package Vocalizr\AppBundle\Model
 */
class UserSpotifyPlaylistModel extends Model
{
    /**
     * @param UserInfo $user
     * @param int      $limit
     * @param int      $offset
     *
     * @return UserSpotifyPlaylist[]
     */
    public function getSpotifyPlaylists($user, $limit = 4, $offset = 0)
    {
        if ($user->isSubscribed()) {
            return $this->repository->getSpotifyPlaylists($user, $limit, $offset);
        } else {
            return [];
        }
    }

    /**
     * @param UserInfo $user
     *
     * @return mixed
     */
    public function removeAllUserPlaylists(UserInfo $user)
    {
        return $this->repository->removeUserPlaylists($user);
    }

    /**
     * @param UserInfo $user
     * @param $link
     *
     * @return UserSpotifyPlaylist
     * @throws CreateSpotifyPlaylistException
     */
    public function createSpotifyPlaylist($user, $link)
    {
        $playlist = new UserSpotifyPlaylist();
        $playlist->setUserInfo($user);

        if (!filter_var($link, FILTER_VALIDATE_URL)) {
            throw new CreateSpotifyPlaylistException('Value should be a valid URL');
        }

        $url  = parse_url($link);
        if (!isset($url['path'])) {
            throw new CreateSpotifyPlaylistException(
                'URL path should not be empty.'
            );
        }
        $path = explode('/', $url['path']);

        if ($url['host'] != 'open.spotify.com' || !isset($path[1])) {
            throw new CreateSpotifyPlaylistException(
                'The link should point to the site https://open.spotify.com'
            );
        }

        switch ($path[1]) {
            case 'album':

                if (!isset($path[2])) {
                    throw new CreateSpotifyPlaylistException(
                        'Album ID is required. Example url: https://open.spotify.com/album/some_album_id'
                    );
                }

                $playlist->setType(UserSpotifyPlaylist::SPOTIFY_PLAYLIST_TYPE_ALBUM);
                $playlist->setSpotifyId($path[2]);
                break;
            case 'track':

                if (!isset($path[2])) {
                    throw new CreateSpotifyPlaylistException(
                        'Track ID is required. Example url: https://open.spotify.com/track/some_album_id'
                    );
                }

                $playlist->setType(UserSpotifyPlaylist::SPOTIFY_PLAYLIST_TYPE_TRACK);
                $playlist->setSpotifyId($path[2]);
                break;
            case 'user':

                if (!isset($path[2]) ||
                    !isset($path[3]) ||
                    !isset($path[4]) ||
                    $path[3] != 'playlist'
                ) {
                    throw new CreateSpotifyPlaylistException(
                        'Playlist ID is required. Example url: https://open.spotify.com/user/some_user_id/playlist/some_playlist_id'
                    );
                }

                $playlist->setType(UserSpotifyPlaylist::SPOTIFY_PLAYLIST_TYPE_PLAYLIST);
                $playlist->setUserId($path[2]);
                $playlist->setSpotifyId($path[4]);
                break;
            case 'playlist':
                if (!isset($path[2])) {
                    throw new CreateSpotifyPlaylistException(
                        'Playlist ID is required. Example url: https://open.spotify.com/playlist/some_playlist_id'
                    );
                }

                $playlist->setType(UserSpotifyPlaylist::SPOTIFY_PLAYLIST_TYPE_PLAYLIST);
                $playlist->setSpotifyId($path[2]);
                break;
            case 'artist':
                throw new CreateSpotifyPlaylistException(
                    'Artist links are not supported'
                );
                break;
            default:
                throw new CreateSpotifyPlaylistException(
                    'Unknown url format.'
                );
        }

        $this->updateObject($playlist);

        return $playlist;
    }

    protected function getEntityName()
    {
        return 'VocalizrAppBundle:UserSpotifyPlaylist';
    }
}