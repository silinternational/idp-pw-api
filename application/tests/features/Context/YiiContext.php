<?php

namespace tests\features\Context;

use Behat\Behat\Context\Context;
use yii\web\Application;

class YiiContext implements Context
{
    private static $application;

    /**
     * @BeforeSuite
     */
    public static function loadYiiApp()
    {
        if (empty(self::$application)) {
            $config = require(__DIR__ . '/../../../frontend/config/load-configs.php');

            self::$application = new Application($config);
        }
    }
}
