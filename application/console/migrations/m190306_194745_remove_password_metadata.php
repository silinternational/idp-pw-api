<?php

use yii\db\Migration;

/**
 * Class m190306_194745_remove_password_metadata
 */
class m190306_194745_remove_password_metadata extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropColumn('{{user}}', 'pw_last_changed');
        $this->dropColumn('{{user}}', 'pw_expires');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->addColumn('{{user}}', 'pw_last_changed', 'datetime null');
        $this->addColumn('{{user}}', 'pw_expires', 'datetime null');
    }
}
