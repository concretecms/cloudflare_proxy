<?php

namespace Concrete5\Cloudflare;

use Concrete\Core\Application\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class CloudflareUpdateCommand extends Command
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
        parent::__construct('cf:ip:update');
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
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('quiet')) {
            $output->setVerbosity($output::VERBOSITY_QUIET);
        }

        $config = $this->app['config'];

        // Get the configuration
        $endpoints = $config['cloudflare_proxy::endpoints'];

        // Get the old IPs
        if (!$oldIps = $config['cloudflare_proxy::ips.user']) {
            $oldIps = $config['cloudflare_proxy::ips.default'];
        }

        // Get the new list of IPs
        $ips = $this->getIps($endpoints, $output);

        // If we should update, lets update
        if ($this->shouldApplyChanges($input, $output, $ips, $oldIps)) {
            $config->save('cloudflare_proxy::ips.user', $ips);

            // Return a success response
            return 0;
        }

        // Return a failure response
        return 1;
    }

    protected function configure()
    {
        $this->setName('cf:ip:update')
            ->setDescription('Update cloudflare IPs')
            ->addOption('force', ['f', 'y'], InputOption::VALUE_NONE, 'Force the update')
            ->addOption('quiet', 'q', InputOption::VALUE_NONE, 'Don\'t output');
    }

    /**
     * Get the IPs from a url service.
     *
     * @param $urls
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return array
     */
    private function getIps($urls, OutputInterface $output)
    {
        $ips = [];
        foreach ($urls as $url) {
            $output->writeln('Downloading IPs from ["' . $url . '"]');
            if ($contents = file_get_contents($url)) {
                $ips = array_merge($ips, explode(PHP_EOL, $contents));
            }
        }

        return array_filter($ips);
    }

    private function shouldApplyChanges(InputInterface $input, OutputInterface $output, array $ips, array $oldIps)
    {
        // There is no difference between the two arrays
        if (!$ips) {
            $output->writeln('No IPs were found.');

            return false;
        }

        // Diff the old IP array with the new one
        $addIps = array_diff($ips, $oldIps);
        $removeIps = array_diff($oldIps, $ips);

        // There is no difference between the two arrays
        if (!$addIps && !$removeIps) {
            $output->writeln('No changes detected.');

            return true;
        }

        // If we have IP's being added
        if ($addIps) {
            $output->writeln(['', 'Adding IPs:']);
            $output->writeln($this->indented($addIps));
        }

        // If we have IP's being removed
        if ($removeIps) {
            $output->writeln(['', 'Removing IPs:']);
            $output->writeln($this->indented($removeIps));
        }

        // Output a general count of IPs
        $output->writeln(['', 'Leaving us with ' . count($ips) . ' IPs remaining.', '']);

        // If the user has forced this to update
        if ($input->hasOption('force')) {
            return true;
        }

        // Confirm with the user
        $question = new ConfirmationQuestion('Do you want to apply these changes? ', false);
        $question->setAutocompleterValues(['yes', 'no']);

        /* @var \Symfony\Component\Console\Helper\QuestionHelper $questionHelper */
        return (bool) $this->getHelper('question')->ask($input, $output, $question);
    }

    private function indented(array $data, $spaces = 4)
    {
        foreach ($data as &$item) {
            $item = str_repeat(' ', $spaces) . $item;
        }

        return $data;
    }
}
