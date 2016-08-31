<?php
namespace tests\api\fixtures;

use yii\test\ActiveFixture;

class MethodFixture extends ActiveFixture
{
    public $modelClass = 'common\models\Method';
    public $dataFile = 'tests/api/fixtures/data/Method.php';
    public $depends = [
        'tests\api\fixtures\UserFixture',
    ];
}
