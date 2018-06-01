<?php

namespace Concrete5\Cloudflare;

use Concrete\Core\Console\Command;
use ReflectionClass;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class CloudflareUpdateCommand extends Command
{
    /**
     * @var int
     */
    const RETURN_CODE_UPDATED = 0;

    /**
     * @var int
     */
    const RETURN_CODE_NOT_UPDATED = 1;

    /**
     * @var int
     */
    const RETURN_CODE_ON_FAILURE = 2;

    /**
     * @var \Concrete5\Cloudflare\CloudflareUpdater
     */
    protected $updater;

    /**
     * @param CloudflareUpdater $updater
     */
    public function __construct(CloudflareUpdater $updater)
    {
        $this->updater = $updater;
        parent::__construct('cf:ip:update');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $constants = (new ReflectionClass($this))->getConstants();
        $this->setName('cf:ip:update')
            ->addOption('force', ['f', 'y'], InputOption::VALUE_NONE, 'Force the update')
            ->addOption('quiet', 'q', InputOption::VALUE_NONE, 'Don\'t output')
            ->setDescription(<<<EOT
Update Cloudflare IPs.

Exit codes:
  - IP list updated: {$constants['RETURN_CODE_UPDATED']}
  - IP list not updated: {$constants['RETURN_CODE_NOT_UPDATED']}
  - errors: {$constants['RETURN_CODE_ON_FAILURE']}
EOT
            )
        ;
    }

    /**
     * `cf:ip:update` command
     * Update IPs from the cloudflare's static endpoints.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('quiet')) {
            $output->setVerbosity($output::VERBOSITY_QUIET);
        }

        $newIPs = $this->updater->getCustomIPs();
        $endpoints = $this->updater->getCloudfareEndpoints();
        foreach ($endpoints as $endpoint) {
            if ($endpoint) {
                $output->writeln('Downloading IPs from ["' . $endpoint . '"]');
            }
            $newIPs = array_merge($newIPs, $this->updater->getCloudflareIPs([$endpoint]));
        }

        $oldIPs = $this->updater->getConfiguredIPs();

        $state = new CloudflareUpdaterState($oldIPs, $newIPs);

        if ($this->shouldApplyChanges($input, $output, $state)) {
            $this->updater->setConfiguredIPs($newIPs);
            $rc = static::RETURN_CODE_UPDATED;
        } else {
            $rc = static::RETURN_CODE_NOT_UPDATED;
        }

        return $rc;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param CloudflareUpdaterState $state
     *
     * @return bool
     */
    private function shouldApplyChanges(InputInterface $input, OutputInterface $output, CloudflareUpdaterState $state)
    {
        $result = false;
        $addedIPs = $state->getAddedIPs();
        $removedIPs = $state->getRemovedIPs();
        if (count($addedIPs) === 0 && count($removedIPs) === 0) {
            // There is no difference between the two arrays
            $output->writeln(sprintf('No changes detected to the %d currently configured IPs.', count($state->getOldIPs())));
        } else {
            if (count($addedIPs) > 0) {
                $output->writeln(['', 'Adding IPs:']);
                $output->writeln($this->indented($addedIPs));
            }
            if (count($removedIPs) > 0) {
                $output->writeln(['', 'Removing IPs:']);
                $output->writeln($this->indented($removedIPs));
            }
            // Output a general count of IPs
            $output->writeln(['', 'Leaving us with ' . count($state->getNewIPs()) . ' IPs remaining.', '']);

            // If the user has forced this to update
            if ($input->getOption('force')) {
                $result = true;
            } elseif ($input->getOption('no-interaction')) {
                $output->writeln('Changes NOT applied since we are not in interaction (use the --force option).');
                $result = false;
            } else {
                // Confirm with the user
                $question = new ConfirmationQuestion('Do you want to apply these changes? ', false);
                $question->setAutocompleterValues(['yes', 'no']);
                $result = (bool) $this->getHelper('question')->ask($input, $output, $question);
            }
        }

        return $result;
    }

    private function indented(array $data, $with = '    ')
    {
        foreach ($data as &$item) {
            $item = $with . $item;
        }

        return $data;
    }
}
