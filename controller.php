<?php

namespace Concrete\Package\CloudflareProxy;

use Concrete\Core\Foundation\Psr4ClassLoader;
use Concrete\Core\Package\Package;
use Concrete5\Cloudflare\CloudflareServiceProvider;

class Controller extends Package
{

    protected $appVersionRequired = '8.2.0';
    protected $pkgVersion = '1.0.0';
    protected $pkgHandle = 'cloudflare_proxy';
    protected $pkgName = 'CloudFlare IP Proxy';
    protected $pkgDescription = 'A package that configures your concrete5 site to work with cloudflare';

    public function on_start()
    {
        // Make sure that we are registered to autoload
        $this->forceAutoload();

        // Add our service provider
        $provider = new CloudflareServiceProvider($this->app);
        $provider->register();
    }

    /**
     * In the event that composer hasn't been included, register our own classloader
     */
    private function forceAutoload()
    {
        // If we're not included with composer, add our autoloader manually
        if (!class_exists(CloudflareServiceProvider::class)) {
            $autoload = new Psr4ClassLoader();
            $autoload->addPrefix('Concrete5\\Cloudflare', __DIR__ . '/src');
            $autoload->register();
        }
    }
}
