<?php
namespace tests\unit\fixtures\common\models;

use yii\test\ActiveFixture;

class PasswordChangeLogFixture extends ActiveFixture
{
    public $modelClass = 'common\models\PasswordChangeLog';
    public $dataFile = 'tests/unit/fixtures/data/common/models/PasswordChangeLog.php';
    public $depends = [
        'tests\unit\fixtures\common\models\UserFixture',
    ];
}
