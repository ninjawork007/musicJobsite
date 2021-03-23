<?php

namespace Vocalizr\AppBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Vocalizr\AppBundle\Entity\MarketplaceItemAsset;

class MarketplaceItemAssetRepository extends EntityRepository
{
    /**
     * Save uploaded file to database
     *
     * @param int    $userInfoId
     * @param int    $marketplaceItemId
     * @param string $title
     * @param string $fileName          tmp file name in database
     *
     * @return bool|object MarketplaceItemAsset entity
     */
    public function saveUploadedFile($userInfoId, $marketplaceItemId, $title, $fileName)
    {
        $em = $this->getEntityManager();

        // Check if file exists
        $uploadDir = __DIR__ . '/../../../../tmp';

        if (!file_exists($uploadDir . DIRECTORY_SEPARATOR . $fileName)) {
            return false;
        }

        $userInfo        = $em->getReference('VocalizrAppBundle:UserInfo', $userInfoId);
        $marketplaceItem = $em->getReference('VocalizrAppBundle:MarketplaceItem', $marketplaceItemId);

        $itemAsset = new MarketplaceItemAsset();
        $itemAsset->setUserInfo($userInfo);
        $itemAsset->setMarketplaceItem($marketplaceItem);
        $itemAsset->setTitle($title);

        $itemAsset->setPath($fileName);
        // This will move the file to the correct directory once entity is saved
        $file            = new \Symfony\Component\HttpFoundation\File\File($uploadDir . DIRECTORY_SEPARATOR . $fileName);
        $itemAsset->file = $file;

        // Get mime type
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mimeType = finfo_file($finfo, $uploadDir . DIRECTORY_SEPARATOR . $fileName);
                finfo_close($finfo);

                $itemAsset->setMimeType($mimeType);
            }
        }

        // Calculate length of audio file
        $getID3   = new \getid3();
        $fileInfo = $getID3->analyze($uploadDir . DIRECTORY_SEPARATOR . $fileName);
        if (isset($fileInfo['playtime_seconds'])) {
            $milliseconds = $fileInfo['playtime_seconds'] * 1000;
            $itemAsset->setDuration($milliseconds);
            $itemAsset->setDurationString($fileInfo['playtime_string']);
        }

        $em->persist($itemAsset);
        $em->flush();

        return $itemAsset;
    }

    public function saveDropboxFile($userInfo, $marketplaceItem, $data)
    {
        $em = $this->getEntityManager();

        $itemAsset = new MarketplaceItemAsset();
        $itemAsset->setUserInfo($userInfo);
        $itemAsset->setMarketplaceItem($marketplaceItem);
        $itemAsset->setTitle($data['name']);
        $itemAsset->setDropboxLink($data['link']);

        $em->persist($itemAsset);
        $em->flush();

        return $itemAsset;
    }

    /**
     * Get assets by marketplace item id
     * join user_info table
     *
     * @param int $marketplaceItemId
     */
    public function getByMarketplaceItemId($marketplaceItemId)
    {
        $q = $this->createQueryBuilder('mia')
                ->select('mia, ui')
                ->innerJoin('mia.user_info', 'ui')
                ->where('mia.marketplace_item = :marketplaceItemId')
                ->orderBy('mia.created_at', 'DESC');
        $params = [
            ':marketplaceItemId' => $marketplaceItemId,
        ];
        $q->setParameters($params);

        $query = $q->getQuery();

        return $query->execute();
    }

    /**
     * Get asset by slug
     *
     * @param string $slug
     *
     * @return MarketplaceItemAsset|null
     */
    public function getBySlug($slug)
    {
        $q = $this->createQueryBuilder('mia')
                ->select('mia, ui')
                ->innerJoin('mia.user_info', 'ui')
                ->where('mia.slug = :slug');
        $params = [
            ':slug' => $slug,
        ];
        $q->setParameters($params);

        $query = $q->getQuery();

        try {
            return $query->getOneOrNullResult();
        } catch (\Doctrine\ORM\NonUniqueResultException $e) {
            return null;
        }
    }
}
