<?php

use yii\db\Schema;
use yii\db\Migration;

class m160225_162828_create_initial_tables extends Migration
{
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
        /*
         * UserTable
         */
        $this->createTable(
            '{{user}}',
            [
                'id' => 'pk',
                'uid' => 'char(32) not null',
                'employee_id' => 'varchar(32) not null',
                'first_name' => 'varchar(255) not null',
                'last_name' => 'varchar(255) not null',
                'idp_username' => 'varchar(255) not null',
                'email' => 'varchar(255) not null',
                'created' => 'datetime not null',
                'last_login' => 'datetime null',
                'pw_last_changed' => 'datetime null',
                'pw_expires' => 'datetime null',

            ],
            "ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );
        $this->createIndex('uq_user_uid','{{user}}','uid',true);
        $this->createIndex('uq_user_employee_id','{{user}}','employee_id',true);
        $this->createIndex('uq_user_email','{{user}}','email',true);

        /*
         * Method table
         */
        $this->createTable(
            '{{method}}',
            [
                'id' => 'pk',
                'uid' => 'char(32) not null',
                'user_id' => 'int(11) not null',
                'type' => "enum('email','phone') not null",
                'value' => 'varchar(255) not null',
                'verified' => 'boolean not null default false',
                'verification_code' => 'varchar(64) null',
                'verification_attempts' => 'smallint null',
                'verification_expires' => 'datetime null',
                'created' => 'datetime not null',
            ],
            "ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );
        $this->addForeignKey('fk_method_user_id','{{method}}','user_id','{{user}}','id','CASCADE','NO ACTION');
        $this->createIndex('uq_method_uid','{{method}}','uid',true);
        $this->createIndex('uq_method_user_type_value','{{method}}',['user_id','type','value'],true);
        $this->createIndex('uq_method_verification_code','{{method}}','verification_code',true);

        /*
         * Reset table
         */
        $this->createTable(
            '{{reset}}',
            [
                'id' => 'pk',
                'uid' => 'char(32) not null',
                'user_id' => 'int(11) not null',
                'type' => "enum('primary', 'method','supervisor','spouse') not null default 'primary'",
                'method_id' => 'int(11) null',
                'code' => 'varchar(64) null',
                'attempts' => 'smallint not null default 0',
                'expires' => 'datetime not null',
                'disable_until' => 'datetime null',
                'created' => 'datetime not null',
            ],
            "ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );
        $this->addForeignKey('fk_reset_user_id','{{reset}}','user_id','{{user}}','id','CASCADE','NO ACTION');
        $this->addForeignKey('fk_reset_method_id','{{reset}}','method_id','{{method}}','id','CASCADE','NO ACTION');
        $this->createIndex('uq_reset_uid','{{reset}}','uid',true);
        $this->createIndex('uq_reset_user_id','{{reset}}','user_id',true);
        $this->createIndex('uq_reset_code','{{reset}}','code',true);

        /*
         * Requests By IP table
         */
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

        /*
         * Password Change Log table
         */
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
        $this->addForeignKey('fk_passwordchangelog_user_id','{{password_change_log}}','user_id','{{user}}','id','CASCADE','NO ACTION');

    }
    
    public function safeDown()
    {
        $this->dropForeignKey('fk_passwordchangelog_user_id','{{password_change_log}}');
        $this->dropTable('{{password_change_log}}');

        $this->dropTable('{{requests_by_ip}}');

        $this->dropForeignKey('fk_reset_method_id','{{reset}}');
        $this->dropForeignKey('fk_reset_user_id','{{reset}}');
        $this->dropTable('{{reset}}');

        $this->dropForeignKey('fk_method_user_id','{{method}}');
        $this->dropTable('{{method}}');

        $this->dropTable('{{user}}');
    }
}
