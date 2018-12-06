<?php

use yii\db\Migration;

/**
 * Handles adding hide to table `user`.
 */
class m181126_182233_add_hide_column_to_user_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('user', 'hide', "enum('no','yes') not null");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('user', 'hide');
    }
}
