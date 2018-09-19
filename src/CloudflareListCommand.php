<?php

namespace Concrete5\Cloudflare;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class CloudflareListCommand extends Command
{
    /**
     * @var \Concrete5\Cloudflare\CloudflareUpdater
     */
    protected $updater;

    /**
     * @param \Concrete5\Cloudflare\CloudflareUpdater $updater
     */
    public function __construct(CloudflareUpdater $updater)
    {
        $this->updater = $updater;
        parent::__construct('cf:ip:list');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('all')) {
            $list = $this->updater->getTrustedIPs();
        } else {
            $list = $this->updater->getPreviousCloudflareIPs();
        }
        $output->writeln($list);

        return 0;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
            ->setDescription('Display the list of trusted IP addresses')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Show all the IP trusted IP addresses (otherwise we\'ll show only the Cloudflare ones).')
            ->setHelp(<<<'EOT'
If the --all option is specified, all the currently trusted IP addresses are listed.
If the --all option is not specified, only the Cloudflare IP addresses are listed.
EOT
            )
        ;
    }
}
