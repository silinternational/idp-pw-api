<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "email_queue".
 *
 * @property integer $id
 * @property string $to_address
 * @property string $cc_address
 * @property string $subject
 * @property string $text_body
 * @property string $html_body
 * @property integer $attempts_count
 * @property string $last_attempt
 * @property string $created
 * @property string $error
 * @property integer $event_log_user_id
 * @property string $event_log_topic
 * @property string $event_log_details
 */
class EmailQueueBase extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'email_queue';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['to_address', 'subject', 'created'], 'required'],
            [['text_body', 'html_body'], 'string'],
            [['attempts_count', 'event_log_user_id'], 'integer'],
            [['last_attempt', 'created'], 'safe'],
            [['to_address', 'cc_address', 'subject', 'error', 'event_log_topic'], 'string', 'max' => 255],
            [['event_log_details'], 'string', 'max' => 1024],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'to_address' => Yii::t('app', 'To Address'),
            'cc_address' => Yii::t('app', 'Cc Address'),
            'subject' => Yii::t('app', 'Subject'),
            'text_body' => Yii::t('app', 'Text Body'),
            'html_body' => Yii::t('app', 'Html Body'),
            'attempts_count' => Yii::t('app', 'Attempts Count'),
            'last_attempt' => Yii::t('app', 'Last Attempt'),
            'created' => Yii::t('app', 'Created'),
            'error' => Yii::t('app', 'Error'),
            'event_log_user_id' => Yii::t('app', 'Event Log User ID'),
            'event_log_topic' => Yii::t('app', 'Event Log Topic'),
            'event_log_details' => Yii::t('app', 'Event Log Details'),
        ];
    }
}
