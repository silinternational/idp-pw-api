<?php

use yii\db\Migration;

class m230515_162100_create_session_table extends Migration
{
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
        /*
         * UserTable
         */
        $this->createTable(
            '{{session}}',
            [
                'id' => 'char(64) primary key not null',
                'expire' => 'int(11) null',
                'data' => 'blob not null',
            ],
            "ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );
    }
    
    public function safeDown()
    {
        $this->dropTable('{{session}}');
    }
}
