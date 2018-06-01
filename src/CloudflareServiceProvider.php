<?php

namespace Concrete5\Cloudflare;

use Concrete\Core\Console\Application;
use Concrete\Core\Foundation\Service\Provider;
use Symfony\Component\HttpFoundation\Request;

final class CloudflareServiceProvider extends Provider
{
    /**
     * Registers the services provided by this provider.
     */
    public function register()
    {
        $this->registerProxy();
        $this->registerCommands();
    }

    /**
     * Register known CloudFlare proxy IPs.
     */
    private function registerProxy()
    {
        $config = $this->app->make('config');
        if (!$ips = $config['cloudflare_proxy::ips.user']) {
            $ips = $config['cloudflare_proxy::ips.default'];
        }

        Request::setTrustedProxies($ips);
    }

    /**
     * Register the commands
     * `concrete5 cf:ip:update`
     * `concrete5 cf:ip:list`.
     */
    private function registerCommands()
    {
        $app = $this->app;
        $this->app->extend('console', function (Application $console) use ($app) {
            $console->addCommands([
                $app->make(CloudflareUpdateCommand::class),
                $app->make(CloudflareListCommand::class),
            ]);

            return $console;
        });
    }
}
