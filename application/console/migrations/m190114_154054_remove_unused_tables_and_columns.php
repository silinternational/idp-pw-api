<?php

use yii\db\Migration;

/**
 * Class m190114_154054_remove_unused_tables_and_columns
 */
class m190114_154054_remove_unused_tables_and_columns extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropTable('{{email_queue}}');

        $this->dropForeignKey('fk_passwordchangelog_user_id', '{{password_change_log}}');
        $this->dropTable('{{password_change_log}}');

        $this->dropTable('{{requests_by_ip}}');

        $this->dropForeignKey('fk_reset_method_id', '{{reset}}');
        $this->dropColumn('{{reset}}', 'method_id');

        $this->dropColumn('{{user}}', 'last_login');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->addColumn('{{user}}', 'last_login', 'datetime null');

        $this->addColumn('{{reset}}', 'method_id', 'int(11) null');
        $this->addForeignKey(
            'fk_reset_method_id',
            '{{reset}}',
            'method_id',
            '{{method}}',
            'id',
            'CASCADE',
            'NO ACTION'
        );

        $this->createTable(
            '{{requests_by_ip}}',
            [
                'id' => 'pk',
                'username' => 'varchar(255) not null',
                'ip_address' => 'varchar(48) not null',
                'created' => 'datetime not null',
            ],
            "ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );

        $this->createTable(
            '{{password_change_log}}',
            [
                'id' => 'pk',
                'user_id' => 'int(11) not null',
                'scenario' => "enum('change','reset') not null",
                'reset_type' => "enum('primary','method','supervisor','spouse') not null",
                'method_type' => "enum('email','phone') not null",
                'masked_value' => 'varchar(255) not null',
                'created' => 'datetime not null',
                'ip_address' => 'varchar(48) not null',
            ],
            "ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );
        $this->addForeignKey(
            'fk_passwordchangelog_user_id',
            '{{password_change_log}}',
            'user_id',
            '{{user}}',
            'id',
            'CASCADE',
            'NO ACTION'
        );

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
}
