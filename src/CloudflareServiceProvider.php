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
     * Register known CloudFlare proxy IPs
     */
    private function registerProxy()
    {
        $config = $this->app->make('config');
        
        if (!$ips = $config['cloudflare_proxy::ips.user']) {
            $ips = $config['cloudflare_proxy::ips.default'];
        }
        
        $ips = array_merge($ips, $config->get('concrete.security.trusted_proxies.ips', []));

        // Handle different symfony versions
        if (defined(SymphonyRequest::class . '::HEADER_X_FORWARDED_ALL')) {
            Request::setTrustedProxies($ips, HEADER_X_FORWARDED_ALL);
        } else {
            Request::setTrustedProxies($ips);
        }
    }

    /**
     * Register the commands
     * `concrete5 cf:ip:update`
     * `concrete5 cf:ip:list`
     */
    private function registerCommands()
    {
        $app = $this->app;
        $this->app->extend('console', function (Application $console) use ($app) {
            $console->addCommands([
                $app->make(CloudflareUpdateCommand::class),
                $app->make(CloudflareListCommand::class)
            ]);

            return $console;
        });
    }
}
