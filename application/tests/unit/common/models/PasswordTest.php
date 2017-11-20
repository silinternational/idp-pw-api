<?php
namespace tests\unit\common\models;

use common\helpers\Utils;
use common\models\Password;
use common\models\User;
use tests\unit\fixtures\common\models\UserFixture;
use yii\codeception\TestCase;

class PasswordTest extends TestCase
{

    public function fixtures()
    {
        return [
            'users' => UserFixture::className(),
        ];
    }

    public function testZxcvbn()
    {
        $this->markTestSkipped('Depends on zxcvbn api, enable after refactoring to use a mock or something.');
        $testData = $this->getTestData();
        foreach ($testData as $testCase) {
            $strength = Utils::getZxcvbnScore($testCase['password']);
            $this->assertEquals(
                $testCase['zxcvbnScore'], $strength['score'],
                'Zxcvbn score mismatch for password ' . $testCase['password']
            );
        }
    }

    public function testValidation()
    {
        $this->markTestSkipped('Depends on zxcvbn api, enable after refactoring to use a mock or something.');
        $testData = $this->getTestData();

        foreach ($testData as $testCase) {
            $password = Password::create(1234, $testCase['password']);
            $valid = $password->validate();
            $errors = $password->getErrors('password');
            $validationErrorsString = join('|', array_values($errors));

            $this->assertEquals(
                $testCase['overall'],
                $valid,
                'Failed validating test case: ' . $testCase['password']
            );

            $minLengthStatus = ! substr_count($validationErrorsString, 'code 100') > 0;
            $this->assertEquals(
                $testCase['minLength'],
                $minLengthStatus,
                'Failed validating test case: ' . $testCase['password']
            );

            $maxLengthStatus = ! substr_count($validationErrorsString, 'code 110') > 0;
            $this->assertEquals(
                $testCase['maxLength'],
                $maxLengthStatus,
                'Failed validating test case: ' . $testCase['password']
            );

            $minNumStatus = ! substr_count($validationErrorsString, 'code 120') > 0;
            $this->assertEquals(
                $testCase['minNum'],
                $minNumStatus,
                'Failed validating test case: ' . $testCase['password']
            );

            $minUpperStatus = ! substr_count($validationErrorsString, 'code 130') > 0;
            $this->assertEquals(
                $testCase['minUpper'],
                $minUpperStatus, 'Failed validating test case: ' . $testCase['password']
            );

            $minSpecialStatus = ! substr_count($validationErrorsString, 'code 140') > 0;
            $this->assertEquals(
                $testCase['minSpecial'],
                $minSpecialStatus,
                'Failed validating test case: ' . $testCase['password']
            );

            /*
             * Zxcvbn validation is skipped if any other validation errors occur, so only assert
             * failure if other tests pass and this should fail
             */
            if ($testCase['nonZxcvbnPass']) {
                $zxcvbnStatus = ! substr_count($validationErrorsString, 'code 150') > 0;
                $this->assertEquals(
                    $testCase['zxcvbnPass'],
                    $zxcvbnStatus,
                    'Failed validating test case: ' . $testCase['password']
                );
            }

        }
    }


    public function testVsUserAttributes()
    {
        $employeeId = '111111';
        $user = User::findOne(['employee_id' => $employeeId]);

        $testData = [
            $user->idp_username,
            $user->email,
        ];
        foreach ($testData as $testPassword) {
            $password = Password::create($employeeId, $testPassword);
            $password->user = $user;

            $valid = $password->validate();
            $errors = $password->getErrors('password');
            $validationErrorsString = join('|', array_values($errors));

            $this->assertTrue(
                substr_count($validationErrorsString, 'code 180') > 0,
                'Failed validating test case: ' . $testPassword .
                '. No error for matching a user attribute.'
            );
        }
    }

    private function getTestData()
    {
        return [
            [
                'password' => 'asdf1234',
                'zxcvbnScore' => 0,
                'zxcvbnPass' => false,
                'minLength' => false,
                'maxLength' => true,
                'minNum' => true,
                'minUpper' => false,
                'minSpecial' => false,
                'overall' => false,
                'nonZxcvbnPass' => false,
            ],
            [
                'password' => 'Complex-ish p$ssw!or12',
                'zxcvbnScore' => 4,
                'zxcvbnPass' => true,
                'minLength' => true,
                'maxLength' => true,
                'minNum' => true,
                'minUpper' => true,
                'minSpecial' => true,
                'overall' => true,
                'nonZxcvbnPass' => true,
            ],
            [
                'password' => 'ALL CAPS QUERTY 1234',
                'zxcvbnScore' => 4,
                'zxcvbnPass' => true,
                'minLength' => true,
                'maxLength' => true,
                'minNum' => true,
                'minUpper' => true,
                'minSpecial' => true,
                'overall' => true,
                'nonZxcvbnPass' => true,
            ],
            [
                'password' => 'password',
                'zxcvbnScore' => 0,
                'zxcvbnPass' => false,
                'minLength' => false,
                'maxLength' => true,
                'minNum' => false,
                'minUpper' => false,
                'minSpecial' => false,
                'overall' => false,
                'nonZxcvbnPass' => false,
            ],
            [
                'password' => '1John 3:16',
                'zxcvbnScore' => 3,
                'zxcvbnPass' => true,
                'minLength' => true,
                'maxLength' => true,
                'minNum' => true,
                'minUpper' => true,
                'minSpecial' => true,
                'overall' => true,
                'nonZxcvbnPass' => true,
            ],
            [
                'password' => 'luv kitties4!',
                'zxcvbnScore' => 4,
                'zxcvbnPass' => true,
                'minLength' => true,
                'maxLength' => true,
                'minNum' => false,
                'minUpper' => false,
                'minSpecial' => true,
                'overall' => false,
                'nonZxcvbnPass' => false,
            ],
            [
                'password' => 'jesus',
                'zxcvbnScore' => 0,
                'zxcvbnPass' => false,
                'minLength' => false,
                'maxLength' => true,
                'minNum' => false,
                'minUpper' => false,
                'minSpecial' => false,
                'overall' => false,
                'nonZxcvbnPass' => false,
            ],
            [
                'password' => 'Je$u$12345',
                'zxcvbnScore' => 1,
                'zxcvbnPass' => false,
                'minLength' => true,
                'maxLength' => true,
                'minNum' => true,
                'minUpper' => true,
                'minSpecial' => true,
                'overall' => false,
                'nonZxcvbnPass' => true,
            ],

        ];
    }
}