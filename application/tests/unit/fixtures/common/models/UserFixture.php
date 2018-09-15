<?php
namespace tests\unit\fixtures\common\models;

use Sil\yii\test\ActiveFixture;

class UserFixture extends ActiveFixture
{
    public $modelClass = 'common\models\User';
    public $dataFile = 'tests/unit/fixtures/data/common/models/User.php';
    public $depends = [
    ];
}
