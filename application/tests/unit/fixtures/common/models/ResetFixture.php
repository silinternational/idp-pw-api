<?php
namespace tests\unit\fixtures\common\models;

use Sil\yii\test\ActiveFixture;

class ResetFixture extends ActiveFixture
{
    public $modelClass = 'common\models\Reset';
    public $dataFile = 'tests/unit/fixtures/data/common/models/Reset.php';
    public $depends = [
        'tests\unit\fixtures\common\models\UserFixture',
    ];
}
