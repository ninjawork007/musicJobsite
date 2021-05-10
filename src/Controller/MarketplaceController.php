<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Request;
use App\Entity\MarketplaceItem;

use App\Entity\MarketplaceItemAudio;

use App\Form\Type\MarketplaceItemType;

// Forms

class MarketplaceController extends AbstractController
{
    /**
     * @Route("/marketplace", name="marketplace")
     * @Template()
     *
     * @param Request $request
     */
    public function indexAction(Request $request)
    {
        return [];
    }

    /**
     * @Route("/marketplace/create", name="marketplace_create")
     * @Template()
     *
     * @param Request $request
     */
    public function createAction(Request $request)
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirect($this->generateUrl('login'));
        }

        $em = $this->getDoctrine()->getManager();

        $marketplaceItem = new MarketplaceItem();

        $marketplaceItemAudioRepo = $em->getRepository('App:MarketplaceItemAudio');

        $defaultItemAudio = null;

        $form = $this->createForm(MarketplaceItemType::class, $marketplaceItem);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $marketplaceItem = $form->getData();
                $marketplaceItem->setUserInfo($user);
                $marketplaceItem->setHasAssets(false);

                $em->persist($marketplaceItem);
                $em->flush();

                // Attempt to save file
                if ($request->get('audio_file')) {
                    $itemAudio = $marketplaceItemAudioRepo
                            ->saveUploadedFile(
                                $marketplaceItem->getId(),
                                $user->getId(),
                                $request->get('audio_title'),
                                $request->get('audio_file'),
                                MarketplaceItemAudio::FLAG_FEATURED
                            );

                    if (!$itemAudio) {
                        $this->get('session')->getFlashBag()->add('error', 'There was a issue with your uploaded audio. Please try again');
                        return $this->redirect($this->generateUrl('marketplace_create', ['uuid' => $marketplaceItem->getUuid()]));
                    }

                    if ($defaultItemAudio) {
                        $em->remove($defaultItemAudio);
                    }
                    $em->flush();

                    $defaultItemAudio = $itemAudio;

                    // Convert uploaded file to 112
                    $helper = $this->container->get('service.helper');
                    $cmd    = '--abr 112 ' . $itemAudio->getAbsolutePath() . ' ' . $itemAudio->getAbsolutePath();
                    $helper->execLame($cmd);

                    $this->get('session')->getFlashBag()->add('notice', 'Marketplace item successfully saved!');
                    return $this->redirect($this->generateUrl('marketplace_assets', ['uuid' => $marketplaceItem->getUuid()]));
                }
            }
        }

        return [
            'marketplaceItem'  => $marketplaceItem,
            'defaultItemAudio' => $defaultItemAudio,
            'form'             => $form->createView(),
        ];
    }

    /**
     * @Route("/marketplace/{uuid}/edit", name="marketplace_item_edit")
     * @Template()
     *
     * @param Request $request
     */
    public function editAction(Request $request, $uuid)
    {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createNotFoundException('Access Denied');
        }

        $em = $this->getDoctrine()->getManager();

        $itemAudioRepo = $em->getRepository('App:MarketplaceItemAudio');

        // load the marketplace item
        $marketplaceItem = $em->getRepository('App:MarketplaceItem')->findOneByUuid($uuid);
        if (!$marketplaceItem) {
            throw $this->createNotFoundException('Item not found');
        }

        if ($marketplaceItem->getStatus() == 'sold') {
            return $this->redirect($this->generateUrl('marketplace_item_view', ['uuid' => $uuid]));
        }

        if ($user->getId() != $marketplaceItem->getUserInfo()->getId()) {
            throw $this->createNotFoundException('Access Denied');
        }

        $marketplaceItemAudioRepo = $em->getRepository('App:MarketplaceItemAudio');

        // Get item featured audio
        $defaultItemAudio = $itemAudioRepo->findOneBy([
            'marketplace_item' => $marketplaceItem->getId(),
            'flag'             => MarketplaceItemAudio::FLAG_FEATURED,
        ]);

        $form = $this->createForm(MarketplaceItemType::class, $marketplaceItem);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $marketplaceItem = $form->getData();
                $marketplaceItem->setUserInfo($user);
                $marketplaceItem->setHasAssets(false);

                // If item is published, and they edit, it'll need to be reviewed again
                if ($marketplaceItem->getStatus() == MarketplaceItem::STATUS_PUBLISHED) {
                    $marketplaceItem->setStatus(MarketplaceItem::STATUS_REVIEW);
                }

                $em->persist($marketplaceItem);
                $em->flush();

                // Attempt to save file
                if ($request->get('audio_file')) {
                    $itemAudio = $marketplaceItemAudioRepo
                            ->saveUploadedFile(
                                $marketplaceItem->getId(),
                                $user->getId(),
                                $request->get('audio_title'),
                                $request->get('audio_file'),
                                MarketplaceItemAudio::FLAG_FEATURED
                            );

                    if (!$itemAudio) {
                        $this->get('session')->getFlashBag()->add('error', 'There was a issue with your uploaded audio. Please try again');
                        return $this->redirect($this->generateUrl('marketplace_create', ['uuid' => $marketplaceItem->getUuid()]));
                    }

                    if ($defaultItemAudio) {
                        $em->remove($defaultItemAudio);
                    }
                    $em->flush();

                    $defaultItemAudio = $itemAudio;

                    // Convert uploaded file to 112
                    $helper = $this->container->get('service.helper');
                    $cmd    = '--abr 112 ' . $itemAudio->getAbsolutePath() . ' ' . $itemAudio->getAbsolutePath();
                    $helper->execLame($cmd);
                }

                $this->get('session')->getFlashBag()->add('notice', 'Marketplace item successfully saved!');
                return $this->redirect($this->generateUrl('marketplace_item_edit', ['uuid' => $marketplaceItem->getUuid()]));
            }
        }

        return [
            'marketplaceItem'  => $marketplaceItem,
            'defaultItemAudio' => $defaultItemAudio,
            'form'             => $form->createView(),
        ];
    }

    /**
     * @Route("/marketplace/{uuid}", name="marketplace_item_view")
     * @Template()
     *
     * @param Request $request
     */
    public function viewAction(Request $request, $uuid)
    {
        $user          = $this->getUser();
        $em            = $this->getDoctrine()->getManager();
        $itemAudioRepo = $em->getRepository('App:MarketplaceItemAudio');

        // load the marketplace item
        $marketplaceItem = $em->getRepository('App:MarketplaceItem')->findOneByUuid($uuid);
        if (!$marketplaceItem) {
            throw $this->createNotFoundException('Item not found');
        }

        // Get item featured audio
        $defaultItemAudio = $itemAudioRepo->findOneBy([
            'marketplace_item' => $marketplaceItem->getId(),
            'flag'             => MarketplaceItemAudio::FLAG_FEATURED,
        ]);

        $assets = $marketplaceItem->getMarketplaceItemAssets();

        return [
            'marketplaceItem'  => $marketplaceItem,
            'defaultItemAudio' => $defaultItemAudio,
            'assets'           => $assets,
        ];
    }

    /**
     * Stream markeplace item audio for audio player
     *
     * @Route("/marketplace/{uuid}/audio/{slug}", name="marketplace_item_audio")
     */
    public function audioAction(Request $request)
    {
        $uuid    = $request->get('uuid');
        $em      = $this->getDoctrine()->getManager();
        $user    = $this->getUser();
        $helper  = $this->get('service.helper');

        $marketplaceItem = $em->getRepository('App:MarketplaceItem')
                    ->findOneByUuid($uuid);

        if (!$marketplaceItem) {
            throw $this->createNotFoundException('Item not found');
        }

        // Get user audio by marketplace item id and audio slug, and make sure it's not a marketplace item audio file
        $itemAudio = $em->getRepository('App:MarketplaceItemAudio')->findOneBy(['slug' => $request->get('slug')]);

        if (!$itemAudio) {
            throw $this->createNotFoundException('Audio file not found 1');
        }

        $file = $itemAudio->getAbsolutePath();
        if (!file_exists($file)) {
            throw $this->createNotFoundException('Audio file not found 2');
        }

        // redirect to actual file
        header('Location: /a/marketplace/' . $itemAudio->getId() . '/' . $itemAudio->getPath());
        exit;

        $helper->streamAudio($file);
    }

    /**
     * @Route("/marketplace/{uuid}/publish", name="marketplace_item_publish")
     * @Template()
     *
     * @param Request $request
     */
    public function publishAction(Request $request, $uuid)
    {
        $user = $this->getUser();
        $em   = $this->getDoctrine()->getManager();

        // load the marketplace item
        $marketplaceItem = $em->getRepository('App:MarketplaceItem')->findOneByUuid($uuid);
        if (!$marketplaceItem) {
            throw $this->createNotFoundException('Item not found');
        }

        $marketplaceItem->setStatus(MarketplaceItem::STATUS_REVIEW);
        $marketplaceItem->setPublishedAt(new \DateTime());
        $em->flush();

        $this->get('session')->getFlashBag()->add('notice', 'Marketplace item successfully published and submitted for review!<br>You will be notified when the review is completed!');
        return $this->redirect($this->generateUrl('marketplace_assets', ['uuid' => $marketplaceItem->getUuid()]));
    }

    /**
     * @Route("/marketplace/{uuid}/assets", name="marketplace_assets")
     * @Template()
     *
     * @param Request $request
     */
    public function assetsAction(Request $request, $uuid)
    {
        $user = $this->getUser();
        $em   = $this->getDoctrine()->getManager();

        if (!$user) {
            throw $this->createNotFoundException('Access Denied');
        }

        // load the marketplace item
        $marketplaceItem = $em->getRepository('App:MarketplaceItem')->findOneByUuid($uuid);
        if (!$marketplaceItem) {
            throw $this->createNotFoundException('Item not found');
        }

        if ($user->getId() != $marketplaceItem->getUserInfo()->getId()) {
            throw $this->createNotFoundException('Access Denied');
        }

        $form = $this->createFormBuilder($marketplaceItem)
            ->getForm();

        if ($request->isMethod('POST')) {
            $form->bind($request);
            if ($form->isValid()) {
                if ($assets = $request->get('asset_file')) {

                    // If item is published, and they edit, it'll need to be reviewed again
                    if ($marketplaceItem->getStatus() == MarketplaceItem::STATUS_PUBLISHED) {
                        $marketplaceItem->setStatus(MarketplaceItem::STATUS_REVIEW);
                    }

                    $this->_submitAssets($request, $marketplaceItem);
                }
                $em->flush();
            }
        }

        $assets = $marketplaceItem->getMarketplaceItemAssets();
        $marketplaceItem->setHasAssets(count($assets) > 0);
        $em->flush();

        return [
            'marketplaceItem' => $marketplaceItem,
            'form'            => $form->createView(),
            'assets'          => $assets,
        ];
    }

    /**
     * @Route("/marketplace/{uuid}/asset/{slug}/delete", name="marketplace_asset_delete")
     * @Template()
     *
     * @param Request $request
     */
    public function deleteAssetAction(Request $request, $uuid, $slug)
    {
        $user = $this->getUser();
        $em   = $this->getDoctrine()->getManager();

        // load the marketplace item
        $marketplaceItem = $em->getRepository('App:MarketplaceItem')->findOneByUuid($uuid);
        if (!$marketplaceItem) {
            throw $this->createNotFoundException('Item not found');
        }

        $asset = $em->getRepository('App:MarketplaceItemAsset')->getBySlug($slug);

        if (!$asset) {
            throw $this->createNotFoundException('Invalid asset file');
        }

        // Make sure logged in user owns asset
        if ($asset->getUserInfo()->getId() != $user->getId()) {
            throw $this->createNotFoundException('You do not have permission to delete this asset');
        }

        // make sure asset belongs to this item
        if ($asset->getMarketplaceItem()->getId() !== $marketplaceItem->getId()) {
            throw $this->createNotFoundException('Asset not found for this item');
        }

        $request->query->set('notice', 'Asset ' . $asset->getTitle() . ' has been deleted');

        $em->remove($asset); // This will also delete the file
        $em->flush();

        return $this->redirect($this->generateUrl('marketplace_assets', ['uuid' => $marketplaceItem->getUuid()]));
    }

    /**
     * Studio Action
     * Submit uploaded assets to marketplace item
     */
    private function _submitAssets($request, $marketplaceItem)
    {
        $user = $this->getUser();
        $em   = $this->getDoctrine()->getManager();

        $files      = $request->get('asset_file');
        $dbfiles    = $request->get('dropbox_file_name');
        $fileTitles = $request->get('asset_file_title');
        $helper     = $this->get('service.helper');

        if (count($files) > 0 || count($dbfiles) > 0) {
            $fileError               = false;
            $marketplaceItemAssets   = [];
            $marketplaceItemAssetIds = [];

            if (count($files) > 0) {
                foreach ($files as $i => $file) {
                    $title = isset($fileTitles[$i]) ? $fileTitles[$i] : $file;
                    if (!$marketplaceItemAsset = $em->getRepository('App:MarketplaceItemAsset')
                                    ->saveUploadedFile($user->getId(), $marketplaceItem->getId(), $title, $file)) {
                        $fileError[] = $file;
                    } else {
                        $marketplaceItemAssets[]   = $marketplaceItemAsset;
                        $marketplaceItemAssetIds[] = $marketplaceItemAsset->getId();
                    }
                }
            }

            if ($dbFileNames = $request->get('dropbox_file_name')) {
                $dbFileSizes = $request->get('dropbox_file_size');
                $dbFileLinks = $request->get('dropbox_file_link');
                foreach ($dbFileNames as $k => $name) {
                    $data = [
                        'name' => $name,
                        'link' => $dbFileLinks[$k],
                        'size' => $dbFileSizes[$k],
                    ];
                    $marketplaceItemAsset = $em->getRepository('App:MarketplaceItemAsset')
                            ->saveDropboxFile($user, $marketplaceItem, $data);
                    $marketplaceItemAssets[] = $marketplaceItemAsset;
                }
            }

            $totalSuccess = count($marketplaceItemAssets);
            // If any files failed to be saved, display messages
            if ($fileError) {
                if (count($fileError) == count($files)) {
                    $request->query->set('error', 'Failed to submit files, please upload again');
                    return false;
                } else {
                    $message = 'Successfully submitted ' . $totalSuccess . ' uploaded asset' . ($totalSuccess > 1 ? 's' : '') .
                                    '.<br>Failed to submit ' . count($fileError) . ' file' . (count($fileError) > 1 ? 's' : '');
                    $request->query->set('notice', $message);
                }
            } else {
                $request->query->set('notice', 'Successfully submitted your uploaded assets');
            }

            // Generate previews for uploaded assets
            if (count($marketplaceItemAssetIds)) {
                $helper->execSfCmd('vocalizr:marketplace-item-asset-previews ' . implode(',', $marketplaceItemAssetIds));
            }

            $em->flush();
        }
    }

//     * @Secure(roles="ROLE_USER")
    /**
     * Stream audio for asset
     *
     * @IsGranted("ROLE_USER")
     * @Route("/marketplace/{uuid}/asset/{slug}", name="marketplace_asset_stream")
     */
    public function audioMarketplaceAssetAction(Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createNotFoundException();
        }

        $em     = $this->getDoctrine()->getManager();
        $helper = $this->get('service.helper');

        // Get audio based on slug
        if (!$asset = $em->getRepository('App:MarketplaceItemAsset')->findOneBy(['slug' => $request->get('slug')])) {
            throw $this->createNotFoundException();
        }

        $marketplaceItem = $sset->getMarketplaceItem();

        if ($marketplaceItem->getUserInfo()->getId() != $user->getId()) {
            throw $this->createNotFoundException('Permission denied');
        }

        $file = $asset->getAbsolutePreviewPath();

        // Does the file exist?
        if (!file_exists($file)) {
            throw $this->createNotFoundException();
        }

        $helper->streamAudio($file);
    }

    /**
     * Show the widget that has information about the items current status
     *
     * @Route("/marketplace/{uuid}/status", name="item_status_widget")
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param type                                      $uuid
     * @Template()
     */
    public function itemStatusWidgetAction(Request $request, $uuid)
    {
        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        // load the marketplace item
        $marketplaceItem = $em->getRepository('App:MarketplaceItem')->findOneByUuid($uuid);
        if (!$marketplaceItem) {
            throw $this->createNotFoundException('Item not found');
        }

        return $this->render('Marketplace/itemStatusWidget.html.twig', [
            'marketplaceItem' => $marketplaceItem,
        ]);
    }

    /**
     * @Route("/user/marketplace", name="user_marketplace")
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @Template()
     */
    public function userMarketplaceAction(Request $request)
    {
        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        // load the marketplace item
        $items = $em->getRepository('App:MarketplaceItem')->findAll([], ['created_at' => 'DESC', 'updated_at' => 'DESC']);

        return $this->render('Marketplace/userMarketplace.html.twig', [
            'items' => $items,
        ]);
    }
}
