<?php

use yii\db\Schema;
use yii\db\Migration;

class m160613_143858_add_user_access_token extends Migration
{
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
        $this->addColumn('{{user}}', 'access_token', 'char(64) null');
        $this->addColumn('{{user}}', 'access_token_expiration', 'datetime null');
        $this->createIndex('idx_user_access_token', '{{user}}', 'access_token', true);
    }

    public function safeDown()
    {
        $this->dropColumn('{{user}}', 'access_token_expiration');
        $this->dropColumn('{{user}}', 'access_token');
    }
}
