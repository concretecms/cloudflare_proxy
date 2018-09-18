<?php

namespace Concrete\Package\CloudflareProxy\Job;

use Concrete\Core\Job\Job as AbstractJob;
use Concrete\Core\Support\Facade\Application;
use Concrete5\Cloudflare\CloudflareUpdater;
use Concrete5\Cloudflare\CloudflareUpdaterState;

class UpdateCloudflareIps extends AbstractJob
{
    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Job\Job::getJobName()
     */
    public function getJobName()
    {
        return t('Update Cloudflare IPs');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Job\Job::getJobDescription()
     */
    public function getJobDescription()
    {
        return t('This job updates the Cloudflare IP list');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Job\Job::run()
     */
    public function run()
    {
        $app = Application::getFacadeApplication();
        $updater = $app->make(CloudflareUpdater::class);
        $oldIPs = $updater->getConfiguredIPs();
        $newIPs = array_merge($updater->getCustomIPs(), $updater->getCloudflareIPs($updater->getCloudfareEndpoints()));
        $state = new CloudflareUpdaterState($oldIPs, $newIPs);
        if ($state->getAddedIPs() === [] && $state->getRemovedIPs() === []) {
            $result = t('No changes to the %d IP addresses.', count($state->getOldIPs()));
        } else {
            $updater->setConfiguredIPs($state->getNewIPs());
            $lines = [];
            if ($state->getAddedIPs() !== []) {
                $lines[] = t('IP addresses added: %d', count($state->getAddedIPs()));
            }
            if ($state->getRemovedIPs() !== []) {
                $lines[] = t('IP addresses removed: %d', count($state->getRemovedIPs()));
            }
            $lines[] = t('Resulting IP addresses: %d', count($state->getNewIPs()));
            $result = implode("<br />", $lines);
        }

        return $result;
    }
}
