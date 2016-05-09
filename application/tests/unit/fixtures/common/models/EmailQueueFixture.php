<?php
namespace tests\unit\fixtures\common\models;

use yii\test\ActiveFixture;

class EmailQueueFixture extends ActiveFixture
{
    public $modelClass = 'common\models\EmailQueue';
    public $dataFile = 'tests/unit/fixtures/data/common/models/EmailQueue.php';
    public $depends = [
    ];
}
