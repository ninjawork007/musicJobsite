<?php

namespace Vocalizr\AppBundle\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Exception;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Vocalizr\AppBundle\Entity\Project;
use Vocalizr\AppBundle\Entity\ProjectAsset;
use Vocalizr\AppBundle\Model\ProjectModel;
use Vocalizr\AppBundle\Service\HelperService;

/**
 * Class ProjectAssetPreviewAllCommand
 * @package Vocalizr\AppBundle\Command
 */
class ProjectAssetPreviewAllCommand extends ContainerAwareCommand
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var HelperService
     */
    private $helper;

    /**
     * @var OutputInterface
     */
    private $output;

    protected function configure()
    {
        $this
            ->setName('vocalizr:project-asset-previews-all')
            ->setDescription('Generate previews and waveforms for all recently added  project assets')
            ->addOption('all', 'a', InputOption::VALUE_OPTIONAL, 'if "true" - generate for completed projects too.', false)
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container    = $this->getContainer();
        $this->em     = $container->get('doctrine')->getManager();
        $this->helper = $container->get('service.helper');
        $this->output = $output;

        $fromDate = new \DateTime('yesterday midnight');

        $isForAllAssets = ($input->getOption('all') === 'true' || $input->getOption('all') === '=true');
        if ($isForAllAssets) {
            $output->writeln('Generating assets for all projects');
        } else {
            $output->writeln('Generating assets for recently updated projects only');
        }

        /** @var QueryBuilder $qb */
        $qb = $this->em->getRepository('VocalizrAppBundle:ProjectAsset')
                ->createQueryBuilder('pa');
        $qb
            ->select('pa, p')
            ->innerJoin('pa.project', 'p')
            ->where('pa.preview_path IS NULL')
        ;

        if (!$isForAllAssets) {
            $qb
                ->andWhere('pa.created_at >= :fromDate')
                ->setParameter('fromDate', $fromDate)
            ;
        }

        /** @var ProjectAsset[] $assets */
        $assets = $qb->getQuery()->execute();

        $output->writeln(sprintf('Plan to process %d assets files', count($assets)));

        /** @var Project[] $projectsByIds */
        $projectsByIds = [];

        /** @var ProjectAsset[][] $assetsByProjectIds */
        $assetsByProjectIds = [];

        foreach ($assets as $asset) {
            $projectsByIds[$asset->getProject()->getId()] = $asset->getProject();
            if (!$asset->getPreviewPath()) {
                $assetsByProjectIds[$asset->getProject()->getId()][] = $asset;
            }

            try {
                $this->processAsset($asset);
            } catch (Exception $exception) {
                error_log('Could not process asset: ' . $exception->getMessage());
                continue;
            } catch (\Error $exception) {
                error_log('Could not process asset: ' . $exception->getMessage());
                continue;
            }
            $this->em->flush();
        }

        /** @var ProjectModel $projectModel */
        $projectModel = $this->getContainer()->get('vocalizr_app.model.project');

        foreach ($projectsByIds as $project) {

            $this->output->writeln(sprintf(
                'Send notification for project %s to owner %s',
                $project->getId(),
                $project->getUserInfo()
            ));

            if (!isset($assetsByProjectIds[$project->getId()])) {
                $msg = sprintf(
                    'Cannot notify user that assets for project %d are uploaded and converted: assets array is empty',
                    $project->getId()
                );
                error_log($msg);
                $output->writeln($msg);
            } else {
                $projectModel->notifyOwnerAssetsUploaded($project, $assetsByProjectIds[$project->getId()]);
            }
        }
    }

    /**
     * @param ProjectAsset $asset
     */
    private function processAsset(ProjectAsset $asset)
    {
        $this->output->writeln(sprintf(
            'Start processing asset %d: %s (MIME: %s)',
            $asset->getId(),
            $asset->getTitle(),
            $asset->getMimeType()
        ));

        if (!$asset->getPreviewPath()) {
            // Get path
            $realFile     = realpath($asset->getAbsolutePath());
            $realFilePath = pathinfo($realFile);

            $previewFileName = uniqid() . '.mp3';
            $previewPath     = $realFilePath['dirname'] . DIRECTORY_SEPARATOR . $previewFileName;

            $this->helper->convertToMp3($realFile, $previewPath);

            $asset->setPreviewPath($previewFileName);
        }

        $this->em->persist($asset);
    }
}