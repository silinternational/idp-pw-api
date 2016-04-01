<?php
namespace tests\unit\common\models;

use common\models\Method;
use common\models\User;
use yii\codeception\DbTestCase;

use tests\unit\fixtures\common\models\UserFixture;
use tests\unit\fixtures\common\models\MethodFixture;

/**
 * Class MethodTest
 * @package tests\unit\common\models
 * @method User users($key)
 * @method Method methods($key)
 */
class MethodTest extends DbTestCase
{
    public function fixtures()
    {
        return [
            'users' => UserFixture::className(),
            'methods' => MethodFixture::className(),
        ];
    }

    public function testDefaultValues()
    {
        $user1 = $this->users('user1');
        $method = new Method();
        $method->user_id = $user1->id;
        $method->type = Method::TYPE_EMAIL;
        $method->value = 'email-1456765289@domain.org';
        if ( ! $method->save()) {
            $this->fail('Failed to save Method when testing default values: ' .
                print_r($method->getFirstErrors(), true));
        }

        $this->assertNotNull($method->id);
        $this->assertEquals(32, strlen($method->uid));
        $this->assertEquals(0, $method->verified);
        $this->assertNull($method->verification_code);
        $this->assertEquals(0, $method->verification_attempts);
        $this->assertNull($method->verification_expires);
        $this->assertNotNull($method->created);
    }

    public function testGetMaskedValuePhone()
    {
        $method = $this->methods('method1');
        $this->assertEquals('+1 #######890', $method->getMaskedValue());
    }

    public function testGetMaskedValueEmail()
    {
        $method = $this->methods('method2');
        $this->assertEquals('e**************9@d*****.o**', $method->getMaskedValue());
    }


}