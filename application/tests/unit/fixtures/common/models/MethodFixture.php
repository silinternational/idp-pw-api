<?php
namespace tests\unit\fixtures\common\models;

use yii\test\ActiveFixture;

class MethodFixture extends ActiveFixture
{
    public $modelClass = 'common\models\Method';
    public $dataFile = 'tests/unit/fixtures/data/common/models/Method.php';
    public $depends = [
        'tests\unit\fixtures\common\models\UserFixture',
    ];
}
