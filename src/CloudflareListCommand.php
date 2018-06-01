<?php

namespace Concrete5\Cloudflare;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CloudflareListCommand extends Command
{
    /**
     * @var CloudflareUpdater
     */
    protected $updater;

    public function __construct(CloudflareUpdater $updater)
    {
        $this->updater = $updater;
        parent::__construct('cf:ip:list');
    }

    /**
     * `cf:ip:list` command
     * List all trusted cloudflare IPs.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        // Output the IPs
        $output->writeln($this->updater->getConfiguredIPs());

        // Return success status
        return 0;
    }
}
