<?php

namespace Vocalizr\AppBundle\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Vocalizr\AppBundle\Entity\SubscriptionPlan;

class SubscriptionPlanCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('vocalizr:plan')
            ->addOption('list', 'l', InputOption::VALUE_NONE, 'lists available plans')
            ->addOption(
                'update',
                'u',
                InputOption::VALUE_REQUIRED,
                'updates field in plan. You need to specify id option and column to change in format column_name="column_value"'
            )
            ->addOption('id', 'id', InputOption::VALUE_REQUIRED, 'id for update')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $plansBuilder = $this->getBuilderFromInput($input);

        if ($input->getOption('update')) {
            $affectedRows = $plansBuilder->getQuery()->execute();
            if ($affectedRows == 0) {
                $output->writeln('Warning: zero lines have been updated.');
            } else {
                $output->writeln('The value has been updated successfully.');
            }
        } elseif ($input->getOption('list')) {
            $plans = $plansBuilder
                ->getQuery()
                ->getArrayResult();
            foreach ($plans as $plan) {
                $output->writeln(sprintf('Plan "%s"', $plan['title']));
                $tableSpacing = max(array_map('strlen', array_keys($plan)));

                foreach ($plan as $option => $value) {
                    $rowSpacing = join(array_fill(0, $tableSpacing - strlen($option) + 1, ' '));
                    if (is_object($value) && $value instanceof \DateTime) {
                        $value = $value->format('Y-m-d');
                    } elseif (is_bool($value)) {
                        $value = $value ? '(true)' : '(false)';
                    } elseif (is_null($value)) {
                        $value = '(null)';
                    }
                    $output->writeln(sprintf("    %s%s: %s", $option, $rowSpacing, $value));
                }

                $output->writeln('');
            }
        } else {
            $output->writeln('You must specify some options to run this command. Add --help option to see command requirements.');
        }
    }

    /**
     * @param InputInterface $input
     * @return QueryBuilder
     */
    private function getBuilderFromInput(InputInterface $input)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $metadata = $em->getClassMetadata(SubscriptionPlan::class);

        $builder = $em->getRepository('VocalizrAppBundle:SubscriptionPlan')
            ->createQueryBuilder('sp');

        if ($idOption = $input->getOption('id')) {
            $builder
                ->where('sp.id = :id')
                ->setParameter('id', $input->getOption('id'))
                ->setMaxResults(1)
            ;
        }

        if ($input->getOption('update')) {
            if (!$idOption) {
                throw new \InvalidArgumentException(sprintf('id option is not specified, but is required for update option'));
            }
            $updateArray = explode('=', $input->getOption('update'));
            $updateValue = (string)array_pop($updateArray);
            $updateKey = array_pop($updateArray);

            if (!$metadata->hasField($updateKey)) {
                throw new \InvalidArgumentException(sprintf(
                    'Field %s has not been found in entity metadata. There are only these fields: %s',
                    $updateKey,
                    join(', ', $metadata->getFieldNames())
                ));
            }

            $builder
                ->update()
                ->set('sp.' . $updateKey, $updateValue);
        }

        return $builder;
    }
}