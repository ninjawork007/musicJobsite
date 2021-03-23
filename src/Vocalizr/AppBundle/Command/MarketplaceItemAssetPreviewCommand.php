<?php

namespace Vocalizr\AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MarketplaceItemAssetPreviewCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
                ->setName('vocalizr:marketplace-item-asset-previews')
                ->setDescription('Generate marketplace item asset previews')
                ->addArgument('ids', InputArgument::REQUIRED, 'Ids for marketplace item assets to generate previews for')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $doctrine  = $container->get('doctrine');
        $em        = $doctrine->getManager();
        $helper    = $this->getApplication()->getKernel()->getContainer()->get('service.helper');

        // Get all marketplae item assets where
        $ids = $input->getArgument('ids');

        $ids = explode(',', $ids);

        $q = $em->getRepository('VocalizrAppBundle:MarketplaceItemAsset')
                ->createQueryBuilder('pa');
        $q->add('where', $q->expr()->in('pa.id', ':ids'));
        $q->setParameter('ids', $ids);
        $results = $q->getQuery()->execute();

        foreach ($results as $asset) {
            // Get path
            $realFile     = realpath($asset->getAbsolutePath());
            $realFilePath = pathinfo($realFile);

            //chmod($realFilePath['dirname'].DIRECTORY_SEPARATOR, 0777);
            $previewFileName = uniqid() . '.mp3';
            $previewPath     = $realFilePath['dirname'] . DIRECTORY_SEPARATOR . $previewFileName;

            $helper->execLame('-h -m m -b 96 ' . $realFile . ' ' . $previewPath);

            $asset->setPreviewPath($previewFileName);
            $em->persist($asset);
        }

        $em->flush();
    }
}
