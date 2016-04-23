<?php

use yii\db\Schema;
use yii\db\Migration;

class m160414_140140_email_queue extends Migration
{
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
        $this->createTable(
            '{{email_queue}}',
            [
                'id' => 'pk',
                'to_address' => 'varchar(255) not null',
                'cc_address' => 'varchar(255) null',
                'subject' => 'varchar(255) not null',
                'text_body' => 'text null',
                'html_body' => 'text null',
                'attempts_count' => 'tinyint not null default 0',
                'last_attempt' => 'datetime null',
                'created' => 'datetime not null',
                'error' => 'varchar(255) null',
                'event_log_user_id' => 'int(11) null',
                'event_log_topic' => 'varchar(255) null',
                'event_log_details' => 'varchar(1024) null',
            ],
            'ENGINE=InnoDB DEFAULT CHARSET=utf8'
        );

    }

    public function safeDown()
    {
        $this->dropTable('{{email_queue}}');
    }
}
