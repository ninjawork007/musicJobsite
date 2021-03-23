<?php

namespace Vocalizr\AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Vocalizr\AppBundle\Entity\EngineOrder;
use Vocalizr\AppBundle\Form\Type\EngineOrderType;

class EngineController extends Controller
{
    /**
     * @Route("/engine", name="engine")
     * @Template()
     *
     * @param Request $request
     *
     * @return array
     */
    public function indexAction(Request $request)
    {
        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        $products = $em->getRepository('VocalizrAppBundle:EngineProduct')->findBy(['pro_only' => false], ['sort_order' => 'ASC']);

        $proResults = false;
        if ($user && $user->isSubscribed()) {
            // Get only pro products
            $proResults = $em->getRepository('VocalizrAppBundle:EngineProduct')->findBy(['pro_only' => true], ['sort_order' => 'ASC']);
        }

        $proProducts = [];
        if ($proResults) {
            foreach ($proResults as $proProduct) {
                $proProducts[$proProduct->getCode()] = $proProduct;
            }
        }

        return [
            'products'    => $products,
            'proProducts' => $proProducts,
        ];
    }

    /**
     * @Route("/engine/order/{uid}", name="engine_order", defaults={"uid" = ""})
     * @Route("/engine/create/{code}", name="engine_create")
     * @Template()
     *
     * @param Request $request
     *
     * @return array
     */
    public function editAction(Request $request)
    {
        $em     = $this->getDoctrine()->getManager();
        $user   = $this->getUser();
        $paypal = $this->get('service.paypal');

        if (!$user) {
            return $this->redirect($this->generateUrl('login'));
        }

        $code    = $request->get('code');
        $uid     = $request->get('uid');
        $product = false;

        if (!$uid && !$code) {
            return $this->redirect($this->generateUrl('engine'));
        }

        $engineOrder = false;
        if ($uid) {
            $engineOrder = $em->getRepository('VocalizrAppBundle:EngineOrder')->findOneBy(['uid' => $uid]);
            $product     = $engineOrder->getEngineProduct();
        }

        if (!$engineOrder) {
            $engineOrder = new EngineOrder();
            $engineOrder->setEmail($user->getEmail());
            if ($code == 'VOCAL-REEL') {
                $engineOrder->setTitle($user->getUsernameOrDisplayName() . "'s Vocal Reel");
            }
        }

        $proProduct = false;

        // Get engine product from db
        if ($code) {

            // If they are subscribed, add pro to end so they get discount
            if ($user->isSubscribed()) {
                $code .= '-PRO';
            } else {
                // get pro product for upgrade options
                $proProduct = $em->getRepository('VocalizrAppBundle:EngineProduct')->findOneBy(['code' => $code . '-PRO']);
            }

            $product = $em->getRepository('VocalizrAppBundle:EngineProduct')->findOneBy(['code' => $code]);

            if (!$product) {
                throw $this->createNotFoundException('Invalid product - ' . $code);
            }
        }

        $form = $this->createForm(new EngineOrderType(), $engineOrder);

        if ($request->isMethod('POST')) {
            $form->bind($request);
            if ($form->isValid()) {
                $engineOrder = $form->getData();
                $engineOrder->setUserInfo($user);
                $engineOrder->setEngineProduct($product);
                $engineOrder->setAmount($product->getAmount());
                $fee = ($product->getAmount() * 30) / 100; // Vocalizr take 30%

                $engineOrder->setFee($fee);

                if (!$engineOrder->getStatus()) {
                    $engineOrder->setStatus('DRAFT');
                }

                // If order is draft, we need to make sure there are assets
                if ($engineOrder->getStatus() == 'DRAFT') {
                }

                $em->persist($engineOrder);
                $em->flush();

                if ($assets = $request->get('asset_file')) {
                    $this->_submitAssets($request, $engineOrder);
                }

                if ($engineOrder->getStatus() != 'DRAFT') {
                    $this->get('session')->getFlashBag()->add('notice', 'Your order has been updated.');
                } else {
                    $this->get('session')->getFlashBag()->add('notice', 'Your order has been saved. Please make payment to submit to our engineers.');
                }
                $em->flush();

                return $this->redirect($this->generateUrl('engine_order', ['uid' => $engineOrder->getUid()]));
            } else {
                //$request->query->set('error', 'There was an issue trying to place your order. Please try again');
            }
        }

        $assets = [];

        if ($engineOrder->getId() > 0) {
            $assets = $engineOrder->getAssets();
        }

        return [
            'product'     => $product,
            'assets'      => $assets,
            'form'        => $form->createView(),
            'paypal'      => $paypal,
            'engineOrder' => $engineOrder,
            'proProduct'  => $proProduct,
        ];
    }

    /**
     * Studio Action
     * Submit uploaded assets to engineOrder item
     */
    private function _submitAssets($request, $engineOrder)
    {
        $user = $this->getUser();
        $em   = $this->getDoctrine()->getManager();

        $files      = $request->get('asset_file');
        $dbfiles    = $request->get('dropbox_file_name');
        $fileTitles = $request->get('asset_file_title');
        $helper     = $this->get('service.helper');

        if (count($files) > 0 || count($dbfiles) > 0) {
            $fileError           = false;
            $engineOrderAssets   = [];
            $engineOrderAssetIds = [];

            if (count($files) > 0) {
                foreach ($files as $i => $file) {
                    $title = isset($fileTitles[$i]) ? $fileTitles[$i] : $file;
                    if (!$engineOrderAsset = $em->getRepository('VocalizrAppBundle:EngineOrderAsset')
                                    ->saveUploadedFile($user->getId(), $engineOrder->getId(), $title, $file)) {
                        $fileError[] = $file;
                    } else {
                        $engineOrderAssets[]   = $engineOrderAsset;
                        $engineOrderAssetIds[] = $engineOrderAsset->getId();
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
                    $engineOrderAsset = $em->getRepository('VocalizrAppBundle:EngineOrderAsset')
                            ->saveDropboxFile($user, $engineOrder, $data);
                    $engineOrderAssets[] = $engineOrderAsset;
                }
            }

            $totalSuccess = count($engineOrderAssets);
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

            $em->flush();
        }
    }

    /**
     * @Route("/engine/order/{uid}/asset/{slug}/delete", name="engine_order_asset_delete")
     *
     * @param Request $request
     */
    public function deleteAssetAction(Request $request, $uid, $slug)
    {
        $user = $this->getUser();
        $em   = $this->getDoctrine()->getManager();

        if (!$user) {
            return $this->redirect($this->generateUrl('engine'));
        }

        $engineOrder = $em->getRepository('VocalizrAppBundle:EngineOrder')->findOneByUid($uid);
        if (!$engineOrder) {
            throw $this->createNotFoundException('Item not found');
        }

        $assets = $engineOrder->getAssets();
        if (count($assets) == 1) {
            $this->get('session')->getFlashBag()->add('error', 'You need at least one asset uploaded for this order');
            return $this->redirect($this->generateUrl('engine_order', ['uid' => $engineOrder->getUid()]));
        }

        $asset = $em->getRepository('VocalizrAppBundle:EngineOrderAsset')->getBySlugAndEngineOrder($slug, $engineOrder);

        if (!$asset) {
            throw $this->createNotFoundException('Invalid asset file');
        }

        // Make sure logged in user owns asset
        if ($asset->getUserInfo()->getId() != $user->getId()) {
            throw $this->createNotFoundException('You do not have permission to delete this asset');
        }

        $this->get('session')->getFlashBag()->add('notice', 'Asset ' . $asset->getTitle() . ' has been deleted');

        $em->remove($asset); // This will also delete the file
        $em->flush();

        return $this->redirect($this->generateUrl('engine_order', ['uid' => $engineOrder->getUid()]));
    }
}
