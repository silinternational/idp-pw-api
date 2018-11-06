<?php

use yii\db\Migration;

/**
 * Handles adding email to table `reset`.
 */
class m181106_152702_add_email_column_to_reset_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('reset', 'email', 'varchar(255) null');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('reset', 'email');
    }
}
