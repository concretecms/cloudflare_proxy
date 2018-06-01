<?php

namespace Concrete5\Cloudflare;

use Concrete\Core\Application\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CloudflareListCommand extends Command
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
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
        // Get the current IPs
        $config = $this->app['config'];
        if (!$ips = $config['cloudflare_proxy::ips.user']) {
            $ips = $config['cloudflare_proxy::ips.default'];
        }

        // Output the IPs
        $output->writeln($ips);

        // Return success status
        return 0;
    }
}
