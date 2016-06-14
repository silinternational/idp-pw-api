<?php
namespace tests\unit\common\models;

use common\models\Method;
use common\models\User;
use common\models\Reset;
use yii\codeception\DbTestCase;

use tests\unit\fixtures\common\models\UserFixture;
use tests\unit\fixtures\common\models\MethodFixture;
use tests\unit\fixtures\common\models\ResetFixture;

/**
 * Class UserTest
 * @package tests\unit\common\models
 * @method User users($key)
 * @method Method methods($key)
 * @method Reset resets($key)
 */
class UserTest extends DbTestCase
{
    public function fixtures()
    {
        return [
            'users' => UserFixture::className(),
            'methods' => MethodFixture::className(),
            'resets' => ResetFixture::className(),
        ];
    }

    public function testDefaultValues()
    {
        User::deleteAll();
        $user = new User();
        $user->employee_id = '1456771651';
        $user->first_name = 'User';
        $user->last_name = 'One';
        $user->idp_username = 'user_1456771651';
        $user->email = 'user-1456771651@domain.org';
        if ( ! $user->save()) {
            $this->fail('Failed to create User: ' . print_r($user->getFirstErrors(), true));
        }

        $this->assertEquals(32, strlen($user->uid));
        $this->assertNull($user->last_login);
        $this->assertNull($user->pw_last_changed);
        $this->assertNull($user->pw_expires);
        $this->assertNotNull($user->created);
    }

    public function testFindOrCreateNew()
    {
        User::deleteAll();
        $user = User::findOrCreate('first_last');

        $this->assertEquals(32, strlen($user->uid));
        $this->assertNull($user->last_login);
        $this->assertNull($user->pw_last_changed);
        $this->assertNull($user->pw_expires);
        $this->assertNotNull($user->created);
    }

    public function testFindOrCreateExisting()
    {
        $existing = $this->users('user1');
        $new = User::findOrCreate($existing->idp_username);

        $this->assertEquals($existing->id, $new->id);
    }

    public function testFindOrCreateDoesntExist()
    {
        $this->expectException(\Sil\IdpPw\Common\Personnel\NotFoundException::class);
        User::findOrCreate('doesnt_exist');
    }

    public function testUpdateProfileIfNeeded()
    {
        $user = $this->users('user1');

        /*
         * Make no changes and ensure it is not updated
         */
        $changed = $user->updateProfileIfNeeded(
            $user->first_name,
            $user->last_name,
            $user->idp_username,
            $user->email
        );
        $this->assertFalse($changed);

        /*
         * Test changed for each property
         */
        $changed = $user->updateProfileIfNeeded(
            $user->first_name . 'a',
            $user->last_name,
            $user->idp_username,
            $user->email
        );
        $this->assertTrue($changed);

        $changed = $user->updateProfileIfNeeded(
            $user->first_name,
            $user->last_name . 'a',
            $user->idp_username,
            $user->email
        );
        $this->assertTrue($changed);

        $changed = $user->updateProfileIfNeeded(
            $user->first_name,
            $user->last_name,
            $user->idp_username . 'a',
            $user->email
        );
        $this->assertTrue($changed);

        $changed = $user->updateProfileIfNeeded(
            $user->first_name,
            $user->last_name,
            $user->idp_username,
            'a' . $user->email
        );
        $this->assertTrue($changed);
    }

    public function testGetPersonnelUser()
    {
        $user = $this->users('user1');
        $personnelData = $user->getPersonnelUser();
        $this->assertInstanceOf('\Sil\IdpPw\Common\Personnel\PersonnelUser', $personnelData);
    }

    public function testSupervisor()
    {
        $user = $this->users('user1');
        $this->assertTrue($user->hasSupervisor());
        $this->assertEquals('supervisor@domain.org', $user->getSupervisorEmail());
    }

    public function testSpouse()
    {
        $user = $this->users('user1');
        $this->assertTrue($user->hasSpouse());
        $this->assertEquals('spouse@domain.org', $user->getSpouseEmail());
    }


}