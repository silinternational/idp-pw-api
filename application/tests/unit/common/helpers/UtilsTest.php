<?php
namespace tests\unit\common\helpers;

use yii\codeception\TestCase;
use common\helpers\Utils;

class UtilsTest extends TestCase
{
    public function testUidRegexGenerateRandomString()
    {
        $regex = '/'.Utils::UID_REGEX.'/';
        for ($i=0; $i < 50; $i++) {
            $uid = Utils::generateRandomString();
            $this->assertRegExp($regex,$uid);
        }
    }
}