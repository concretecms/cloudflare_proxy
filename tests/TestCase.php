<?php

namespace Concrete5\Cloudflare\Tests;

use Buttress\ConcreteClient\Adapter\Version8Adapter;
use Buttress\ConcreteClient\Concrete5;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class TestCase extends PHPUnitTestCase
{

    protected static $connection;

    /**
     * @beforeClass
     */
    public static function beforeAll()
    {
        // Load the controller class
        require_once __DIR__ . '/../controller.php';
    }

    /**
     * @beforeClass
     */
    public static function defineConstants()
    {
        $contants = [
            'DIR_PACKAGES' => __DIR__ . '/../build/packages',
            'REL_DIR_PACKAGES' => 'packages',
            'DIR_PACKAGES_CORE' => __DIR__ . '/../vendor/concrete5/core/config/install/packages',
            'REL_DIR_PACKAGES_CORE' => 'packages'
        ];

        // Set up constants that aren't defined
        foreach ($contants as $key => $value) {
            if (!defined($key)) {
                define($key, $value);
            }
        }
    }
}
