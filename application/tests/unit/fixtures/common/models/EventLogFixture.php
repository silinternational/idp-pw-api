<?php
namespace tests\unit\fixtures\common\models;

use Sil\yii\test\ActiveFixture;

class EventLogFixture extends ActiveFixture
{
    public $modelClass = 'common\models\EventLog';
    public $dataFile = 'tests/unit/fixtures/data/common/models/EventLog.php';
    public $depends = [
    ];
}
