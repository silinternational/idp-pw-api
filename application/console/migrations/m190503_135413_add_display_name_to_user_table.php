<?php

use yii\db\Migration;

/**
 * Class m190503_135413_add_display_name_to_user_table
 */
class m190503_135413_add_display_name_to_user_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{user}}', 'display_name', 'varchar(255) null');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{user}}', 'display_name');
    }
}
