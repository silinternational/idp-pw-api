<?php

namespace tests\api\fixtures;

use yii\test\ActiveFixture;

class ResetFixture extends ActiveFixture
{
    public $modelClass = 'common\models\Reset';
    public $dataFile = 'tests/api/fixtures/data/Reset.php';
    public $depends = [
        'tests\api\fixtures\UserFixture',
    ];
}
