<?php
namespace tests\unit\common\models;

use common\models\Method;
use common\models\User;
use common\models\Reset;
use Sil\IdpPw\Common\Personnel\PersonnelUser;
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

    public function testFields()
    {
        $expected = [
            'first_name' => 'User',
            'last_name' => 'One',
            'idp_username' => 'first_last',
            'email' => 'first_last@organization.org',
            'password_meta' => [
                'last_changed' => '2016-06-15T19:00:32+00:00',
                'expires' => '2016-06-15T19:00:32+00:00',
            ],
        ];

        $user = $this->users('user1');
        $fields = $user->toArray();
        $this->assertEquals($expected['first_name'], $fields['first_name']);
        $this->assertEquals($expected['last_name'], $fields['last_name']);
        $this->assertEquals($expected['idp_username'], $fields['idp_username']);
        $this->assertEquals($expected['email'], $fields['email']);
    }

    public function testFindOrCreateException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1459974492);
        $user = User::findOrCreate();
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
        $byUsername = User::findOrCreate($existing->idp_username);
        $this->assertEquals($existing->id, $byUsername->id);

        $byEmail = User::findOrCreate(null, $existing->email);
        $this->assertEquals($existing->id, $byEmail->id);

        $byEmployeeId = User::findOrCreate(null, null, $existing->employee_id);
        $this->assertEquals($existing->id, $byEmployeeId->id);
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

    public function testGetMaskedMethods()
    {
        $user = $this->users('user1');
        $methods = $user->getMaskedMethods();
        $this->assertTrue(is_array($methods));
        $this->assertEquals(6, count($methods));

        foreach ($methods as $method) {
            if ($method['type'] == 'primary') {
                $this->assertEquals('f****_l**t@o***********.o**', $method['value']);
            } elseif ($method['type'] == 'spouse') {
                $this->assertEquals('s****e@d*****.o**', $method['value']);
            } elseif ($method['type'] == 'supervisor') {
                $this->assertEquals('s********r@d*****.o**', $method['value']);
            } elseif ($method['type'] == 'phone' && $method['uid'] == '11111111111111111111111111111111') {
                $this->assertEquals('+1 #######890', $method['value']);
            } elseif ($method['type'] == 'email' && $method['uid'] == '22222222222222222222222222222222') {
                $this->assertEquals('e**************9@d*****.o**', $method['value']);
            } elseif ($method['type'] == 'email' && $method['uid'] == '33333333333333333333333333333333') {
                $this->assertEquals('e**************1@d*****.o**', $method['value']);
            }

        }

    }

    public function testGetPersonnelUserFromInterface()
    {
        $user = $this->users('user1');
        // test finding by employee_id
        $personnelUser = $user->getPersonnelUserFromInterface();
        $this->assertInstanceOf(PersonnelUser::class, $personnelUser);

        $user->employee_id = null;
        // test finding by username
        $personnelUser = $user->getPersonnelUserFromInterface();
        $this->assertInstanceOf(PersonnelUser::class, $personnelUser);

        $user->idp_username = null;
        // test finding by email
        $personnelUser = $user->getPersonnelUserFromInterface();
        $this->assertInstanceOf(PersonnelUser::class, $personnelUser);

        $user->email = null;
        // test exception after unsetting email
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1456690741);
        $personnelUser = $user->getPersonnelUserFromInterface();
    }

    public function testFindIdentity()
    {
        $expected = $this->users('user1');
        $user = User::findIdentity($expected->id);
        $this->assertInstanceOf(User::class, $user);
    }

    public function testFindIdentityByAccessToken()
    {
        $expected = $this->users('user1');
        $user = User::findIdentityByAccessToken($expected->access_token);
        $this->assertInstanceOf(User::class, $user);
    }

    public function testGetAuthKey()
    {
        $user = $this->users('user1');
        $this->assertNull($user->getAuthKey());
    }

    public function testGetAuthUser()
    {
        $user = $this->users('user1');
        $authUser = $user->getAuthUser();
        $this->assertInstanceOf(\Sil\IdpPw\Common\Auth\User::class, $authUser);
        $this->assertEquals($user->first_name, $authUser->firstName);
        $this->assertEquals($user->last_name, $authUser->lastName);
        $this->assertEquals($user->email, $authUser->email);
        $this->assertEquals($user->employee_id, $authUser->employeeId);
        $this->assertEquals($user->idp_username, $authUser->idpUsername);
    }

    public function testGetVerifiedMethods()
    {
        $user = $this->users('user1');
        $methods = $user->getVerifiedMethods();

        $verifiedCount = 0;
        foreach ($user->methods as $method) {
            if ($method->verified === 1) {
                $verifiedCount++;
            }
        }
        $this->assertEquals($verifiedCount, count($methods));
    }

    public function testGetPasswordMeta()
    {
        $user = $this->users('user1');
        $pwMeta = $user->getPasswordMeta();
        $this->assertArrayHasKey('last_changed', $pwMeta);
        $this->assertArrayHasKey('expires', $pwMeta);
    }


}