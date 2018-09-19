<?php

namespace Concrete\Package\CloudflareProxy;

use Concrete\Core\Console\Application as ConsoleApplication;
use Concrete\Core\Database\EntityManager\Provider\ProviderInterface;
use Concrete\Core\Package\Package;
use Concrete5\Cloudflare\CloudflareListCommand;
use Concrete5\Cloudflare\CloudflareUpdateCommand;
use Concrete5\Cloudflare\CloudflareUpdater;

class Controller extends Package implements ProviderInterface
{
    protected $pkgHandle = 'cloudflare_proxy';

    protected $pkgVersion = '3.0.0';

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::$appVersionRequired
     */
    protected $appVersionRequired = '8.2.0';

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::$pkgAutoloaderRegistries
     */
    protected $pkgAutoloaderRegistries = [
        'src' => 'Concrete5\\Cloudflare',
    ];

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::getPackageName()
     */
    public function getPackageName()
    {
        return t('CloudFlare IP Proxy');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::getPackageDescription()
     */
    public function getPackageDescription()
    {
        return t('A package that configures your concrete5 site to work with cloudflare');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Database\EntityManager\Provider\ProviderInterface::getDrivers()
     */
    public function getDrivers()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::install()
     */
    public function install()
    {
        parent::install();
        $this->installContentFile('install.xml');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::install()
     */
    public function upgrade()
    {
        parent::upgrade();
        $this->installContentFile('install.xml');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::uninstall()
     */
    public function uninstall()
    {
        $updater = $this->app->make(CloudflareUpdater::class);
        $cloudflareIPs = $updater->getPreviousCloudflareIPs();
        $oldTrustedIPs = $updater->getTrustedIPs();
        $newTrustedIPs = array_values(array_diff($oldTrustedIPs, $cloudflareIPs));
        if ($newTrustedIPs !== $oldTrustedIPs) {
            $updater->setTrustedIPs($newTrustedIPs);
            $updater->setPreviousCloudflareIPs([]);
        }
        parent::uninstall();
    }

    public function on_start()
    {
        if ($this->app->isRunThroughCommandLineInterface()) {
            $app = $this->app;
            $this->app->extend('console', function (ConsoleApplication $console) use ($app) {
                $console->addCommands([
                    $app->build(CloudflareListCommand::class),
                    $app->build(CloudflareUpdateCommand::class),
                ]);

                return $console;
            });
        }
    }
}
