<?php

namespace Vocalizr\AppBundle\Command;

use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Vocalizr\AppBundle\Entity\ProjectAsset;
use Vocalizr\AppBundle\Service\HelperService;

/**
 * Class ProjectAssetPreviewCommand
 *
 * @package Vocalizr\AppBundle\Command
 */
class ProjectAssetPreviewCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('vocalizr:project-asset-previews')
            ->setDescription('Generate project asset previews')
            ->addArgument('ids', InputArgument::REQUIRED, 'Ids for project assets to generate previews for')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $doctrine  = $container->get('doctrine');
        $em        = $doctrine->getManager();
        /** @var HelperService $helper */
        $helper = $container->get('service.helper');

        // Get all project assets where
        $ids = $input->getArgument('ids');

        $ids = explode(',', $ids);

        /** @var QueryBuilder $q */
        $q = $em->getRepository('VocalizrAppBundle:ProjectAsset')
                ->createQueryBuilder('pa');
        $q->add('where', $q->expr()->in('pa.id', ':ids'));
        $q->setParameter('ids', $ids);
        /** @var ProjectAsset[] $results */
        $results = $q->getQuery()->execute();

        foreach ($results as $asset) {
            // Get path
            $realFile     = realpath($asset->getAbsolutePath());
            $realFilePath = pathinfo($realFile);

            //chmod($realFilePath['dirname'].DIRECTORY_SEPARATOR, 0777);
            $previewFileName = uniqid() . '.mp3';
            $previewPath     = $realFilePath['dirname'] . DIRECTORY_SEPARATOR . $previewFileName;

            $helper->execLame('-h -m m -b 64 ' . $realFile . ' ' . $previewPath);

            $asset->setPreviewPath($previewFileName);
            $em->persist($asset);
        }

        $em->flush();

        $command = $this->getApplication()->find('vocalizr:generate-waveform');

        foreach ($results as $asset) {
            $output->writeln("Generating waveform for asset '{$asset->getTitle()}' ({$asset->getId()}) :");

            $args = [
                'command'         => 'vocalizr:generate-waveform',
                '--project_asset' => true,
                'id'              => (string) $asset->getId(),
            ];

            $simulatedInput = new ArrayInput($args);

            $command->run($simulatedInput, $output);
        }
    }
}