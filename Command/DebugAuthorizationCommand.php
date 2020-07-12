<?php

namespace Ybenhssaien\AuthorizationBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\OutputInterface;
use Ybenhssaien\AuthorizationBundle\Annotation\Authorization;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Ybenhssaien\AuthorizationBundle\Service\AuthorizationService;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class DebugAuthorizationCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function __construct()
    {
        parent::__construct('debug:authorization');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $console = new SymfonyStyle($input, $output);

        /** @var AuthorizationService $authorization */
        $authorization = $this->container->get('epo.authorization_service');

        $console->title('<info>Fetching authorization map from entities</info>');

        $map = $authorization->getAuthorizationMap()->getMap();
        if ($count = \count($map)) {
            $console->success(sprintf('Found %d mapped entities', $count));

            foreach ($map as $class => $properties) {
                $console->section($class);

                $this->displayProperties($console, $properties);
            }
        } else {
            $console->warning('Found no mapped entites');
        }

        return 0;
    }

    public function getDescription()
    {
        return 'Displays authorizations mapping on entities';
    }

    private function displayProperties(SymfonyStyle $console, array $properties)
    {
        $header = \array_merge(['Property', 'Role'], Authorization::ACTIONS);
        $data = [];

        foreach ($properties as $property => $roles) {
            $propertyCell = new TableCell($property, ['rowspan' => \count($roles)]);
            \end($data);
            $firstKey = (\key($data) ?? -1) + 1;

            foreach ($roles as $role => $authorizations) {
                $rights = [];
                foreach (Authorization::ACTIONS as $key) {
                    $rights[$key] = isset($authorizations[$key]) && $authorizations[$key] ? '<fg=green>Yes</>' : '<fg=red>No</>';
                }

                $data[] = \array_merge([$role], $rights);
            }
            \array_unshift($data[$firstKey], $propertyCell);
            $data[] = new TableSeparator();
        }

        $console->table($header, $data);
    }

    private function displayPropertiesOld(OutputInterface $console, array $properties)
    {
        $console->writeln('Properties :');

        foreach ($properties as $property => $roles) {
            $console->writeln(sprintf('    <fg=blue>%s</> :', $property));

            foreach ($roles as $role => $authorizations) {
                $console->writeln(sprintf('        - <fg=yellow>%s</> :', $role));

                foreach ($authorizations as $key => $value) {
                    $console->writeln(sprintf('            - %s : %s', $key, $value ? '<fg=green>Yes</>' : '<fg=red>No</>'));
                }
            }
        }
    }
}
