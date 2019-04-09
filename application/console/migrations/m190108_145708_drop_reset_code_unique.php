<?php

use yii\db\Migration;

/**
 * Class m190108_145708_drop_reset_code_index
 */
class m190108_145708_drop_reset_code_unique extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropIndex('uq_reset_code', '{{reset}}');
        $this->createIndex('uq_reset_code', '{{reset}}', 'code', false);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('uq_reset_code', '{{reset}}');
        $this->createIndex('uq_reset_code', '{{reset}}', 'code', true);
    }
}
