<?php

use yii\db\Migration;

/**
 * Handles adding uuid to table `user`.
 */
class m190102_203154_add_uuid_column_to_user_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('user', 'uuid', "varchar(64)");
        $this->dropColumn('user', 'uid');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('user', 'uuid');
        $this->addColumn('user', 'uid', "char(32) not null");
    }
}
