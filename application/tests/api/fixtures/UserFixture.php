<?php
namespace tests\api\fixtures;

use yii\test\ActiveFixture;

class UserFixture extends ActiveFixture
{
    public $modelClass = 'common\models\User';
    public $dataFile = 'tests/api/fixtures/data/User.php';
    public $depends = [
    ];
}
