<?php

use yii\db\Migration;

class m160803_175017_refactor_password_change_logging extends Migration
{
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
        /*
         * Update reset related columns in password_change_log to allow null in case of scenario = change
         */
        $this->alterColumn('{{password_change_log}}', 'reset_type', "enum('primary', 'method', 'supervisor', 'spouse') null");
        $this->alterColumn('{{password_change_log}}', 'method_type', "enum('email', 'phone') null");
        $this->alterColumn('{{password_change_log}}', 'masked_value', "varchar(255) null");

        /*
         * Add auth_type column to user to track whether the logged in directly or via reset
         */
        $this->addColumn('{{user}}', 'auth_type', "enum('login', 'reset') null");
    }

    public function safeDown()
    {
        $this->dropColumn('{{user}}', 'auth_type');
    }
}
