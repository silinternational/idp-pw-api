<?php

use yii\db\Migration;

/**
 * Class m190301_020042_add_deleted_at_column_on_method_table
 */
class m190301_020042_add_deleted_at_column_on_method_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{method}}', 'deleted_at', "datetime null null");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{method}}', 'deleted_at');
    }
}
