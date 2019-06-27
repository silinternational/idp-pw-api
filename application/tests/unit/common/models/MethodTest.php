<?php
namespace tests\unit\common\models;

use Sil\Codeception\TestCase\Test;
use common\models\Method;
use common\models\User;
use tests\unit\fixtures\common\models\UserFixture;

/**
 * Class MethodTest
 * @package tests\unit\common\models
 * @method User users($key)
 * @method Method methods($key)
 * @property \Codeception\Module\Yii2 tester
 */
class MethodTest extends Test
{
    public function _fixtures()
    {
        return [
            'users' => UserFixture::class,
        ];
    }
}
