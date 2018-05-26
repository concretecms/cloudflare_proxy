<?php

namespace Concrete5\Cloudflare\Tests;

use Concrete\Core\Application\Application;
use Concrete\Core\Foundation\Service\Provider;
use Concrete\Package\CloudflareProxy\Controller;
use Concrete5\Cloudflare\CloudflareServiceProvider;

class ControllerTest extends TestCase
{

    /** @var Application */
    protected $app;

    /** @var Provider */
    protected $serviceProvider;

    /**
     * Make sure our service provider actually gets registered
     */
    public function testRegistersServiceProvider(): void
    {
        // Set up app expectations
        $this->app
            ->expects($this->once())
            ->method('make');

        // Set up service provider expectations
        $this->serviceProvider
            ->expects($this->once())
            ->method('register');

        // Run the controller on_start method
        (new Controller($this->app))->on_start();
    }

    /**
     * Set up our base mocks we will need for most tests
     * @throws \ReflectionException
     */
    public function setUp(): void
    {
        // Create mock objects
        $app = $this->getMockBuilder(Application::class)
            ->disableProxyingToOriginalMethods()
            ->disableOriginalConstructor()
            ->getMock();

        $serviceProvider = $this->getMockBuilder(Provider::class)
            ->setConstructorArgs([$app])
            ->disableProxyingToOriginalMethods()
            ->getMock();

        // Set up app expectations
        $app->method('make')
            ->with(CloudflareServiceProvider::class)
            ->willReturn($serviceProvider);

        $this->app = $app;
        $this->serviceProvider = $serviceProvider;
    }

}
