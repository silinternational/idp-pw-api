<?php
namespace tests\unit\common\models;

use common\models\Password;
use yii\codeception\TestCase;
use ZxcvbnPhp\Zxcvbn;

class PasswordTest extends TestCase
{

    public function testZxcvbn()
    {
        $testData = $this->getTestData();

        foreach($testData as $testCase) {
            $zxcvbn = new Zxcvbn();
            $strength = $zxcvbn->passwordStrength($testCase['password']);
            if ($testCase['password'] == '1John 3:16') {
                die(print_r($strength, true));
            }

            $this->assertEquals($testCase['zxcvbn'], $strength['score'], 'Zxcvbn score mismatch for password ' . $testCase['password']);
        }
    }

//    public function testValidation()
//    {
//        $testData = $this->getTestData();
//
//        foreach ($testData as $testCase) {
//            $password = Password::create($testCase['password']);
//            $this->assertEquals($testCase['zxcvbn'], Zxcvbn:: )
//        }
//    }

    private function getTestData()
    {
        return [
            [
                'password' => 'asdf1234',
                'zxcvbn' => 0,
                'minLength' => false,
                'maxLength' => true,
                'minNum' => true,
                'minUpper' => false,
                'minSpecial' => false,
                'overall' => false,
            ],
            [
                'password' => 'Complex-ish p$ssw!or',
                'zxcvbn' => 4,
                'minLength' => true,
                'maxLength' => true,
                'minNum' => true,
                'minUpper' => true,
                'minSpecial' => true,
                'overall' => true,
            ],
            [
                'password' => 'ALL CAPS QUERTY 1234',
                'zxcvbn' => 4,
                'minLength' => true,
                'maxLength' => true,
                'minNum' => true,
                'minUpper' => true,
                'minSpecial' => false,
                'overall' => false,
            ],
            [
                'password' => 'password',
                'zxcvbn' => 0,
                'minLength' => false,
                'maxLength' => true,
                'minNum' => false,
                'minUpper' => false,
                'minSpecial' => false,
                'overall' => false,
            ],
            [
                'password' => '1John 3:16',
                'zxcvbn' => 3,
                'minLength' => true,
                'maxLength' => true,
                'minNum' => true,
                'minUpper' => true,
                'minSpecial' => true,
                'overall' => true,
            ],
            [
                'password' => 'luv kitties4!',
                'zxcvbn' => 4,
                'minLength' => true,
                'maxLength' => true,
                'minNum' => true,
                'minUpper' => true,
                'minSpecial' => true,
                'overall' => true,
            ],
            [
                'password' => 'jesus',
                'zxcvbn' => 0,
                'minLength' => false,
                'maxLength' => true,
                'minNum' => false,
                'minUpper' => false,
                'minSpecial' => false,
                'overall' => false,
            ],
            [
                'password' => 'Je$u$12345',
                'zxcvbn' => 1,
                'minLength' => true,
                'maxLength' => true,
                'minNum' => true,
                'minUpper' => true,
                'minSpecial' => true,
                'overall' => false,
            ],

        ];
    }
}