<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "event_log".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $topic
 * @property string $details
 * @property string $created
 *
 * @property User $user
 */
class EventLogBase extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'event_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id'], 'integer'],
            [['topic', 'details', 'created'], 'required'],
            [['created'], 'safe'],
            [['topic'], 'string', 'max' => 64],
            [['details'], 'string', 'max' => 1024],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'topic' => Yii::t('app', 'Topic'),
            'details' => Yii::t('app', 'Details'),
            'created' => Yii::t('app', 'Created'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
}
