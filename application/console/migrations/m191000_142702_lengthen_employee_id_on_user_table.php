<?php

use yii\db\Migration;

/**
 * Handles lengthening employee_id on table `user`.
 */

class m191000_142702_lengthen_employee_id_on_user_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('user', 'employee_id', 'varchar(255) not null');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn('user', 'employee_id', 'varchar(32) not null');
    }
}
