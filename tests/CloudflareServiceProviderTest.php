<?php

namespace Concrete5\Cloudflare\Tests;

use Concrete\Core\Application\Application;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Console\Command;
use Concrete\Core\Http\Request;
use Concrete5\Cloudflare\CloudflareListCommand;
use Concrete5\Cloudflare\CloudflareServiceProvider;
use Concrete5\Cloudflare\CloudflareUpdateCommand;

class CloudflareServiceProviderTest extends TestCase
{

    protected $cachedProxies;


    public function testRegistersCommands()
    {
        // Mock the commands we plan to return
        $command1 = $this->createMock(Command::class);
        $command2 = $this->createMock(Command::class);

        // Mock a console object
        $console = $this->createMock(\Concrete\Core\Console\Application::class);
        $console
            ->expects($this->once())
            ->method('addCommands')
            ->with([$command1, $command2]);

        // Mock the application to return the things we want it to return
        $app = $this->createMock(Application::class);

        // Return the proper values from the container
        $app
            ->method('make')
            ->willReturnMap([
                [CloudflareListCommand::class, [], $command1],
                [CloudflareUpdateCommand::class, [], $command2],
                ['config', [], ['cloudflare_proxy::ips.default' => [], 'cloudflare_proxy::ips.user' => []]]
            ]);


        $app->expects($this->once())
            ->method('extend')
            ->with('console')
            ->willReturnCallback(function($handle, callable $callback) use ($console) {
                return $callback($console);
            });

        // Register the service provider
        $provider = new CloudflareServiceProvider($app);
        $provider->register();
    }

    /**
     * Test registering proxies from config
     * @dataProvider proxySets
     */
    public function testRegistersProxies($proxies, $expected)
    {
        $configValues = [
            ['cloudflare_proxy::ips', $proxies],
            ['cloudflare_proxy::ips.default', $proxies['default']],
            ['cloudflare_proxy::ips.user', $proxies['user']]
        ];

        // Mock the config
        $config = $this->createMock(Repository::class);
        $config
            ->method('get')
            ->willReturnMap($configValues);
        $config
            ->method('offsetGet')
            ->willReturnMap($configValues);

        // Mock the application to return the things we want it to return
        $app = $this->createMock(Application::class);

        // Return the proper values from the container
        $app
            ->method('make')
            ->willReturnMap([
                ['config', [], $config]
            ]);

        // Make sure we DO NOT have the expected proxies set yet
        $this->assertNotEquals($expected, Request::getTrustedProxies());

        // Register the provider
        $provider = new CloudflareServiceProvider($app);
        $provider->register();

        // Make sure we DO have the proper proxies set
        $this->assertEquals($expected, Request::getTrustedProxies());
    }

    /**
     * Data provider for the cloudflare proxy config
     * 1: Has `default` and `user` set. `user` should be used.
     * 2: Has only `default` set, `user` is an empty array. `default` should be used.
     *
     * @return array[]
     */
    public function proxySets()
    {
        $proxies = [
            'default' => ['1.1.1.1', '2.2.2.2', '3.3.3.3', '2001:0db8:85a3:0000:0000:8a2e:0370:7334'],
            'user' => ['2.1.1.1', '3.2.2.2', 'ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff']
        ];

        $onlyDefault = [
            'default' => $proxies['default'],
            'user' => []
        ];

        // Return two trials, first with a "user" config value set, second without one
        return [
            [$proxies, $proxies['user']],
            [$onlyDefault, $proxies['default']]
        ];
    }

    /**
     * Store the default proxies so that we can reset after this test
     * @before
     */
    public function cacheProxies()
    {
        $this->cachedProxies = Request::getTrustedProxies();
    }

    /**
     * Reset proxies back to their original state
     * @after
     */
    public function resetProxies()
    {
        Request::setTrustedProxies($this->cachedProxies);
    }

}
