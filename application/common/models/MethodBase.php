<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "method".
 *
 * @property int $id
 * @property string $uid
 * @property int $user_id
 * @property string $type
 * @property string $value
 * @property int $verified
 * @property string $verification_code
 * @property int $verification_attempts
 * @property string $verification_expires
 * @property string $created
 * @property string $deleted_at
 *
 * @property User $user
 */
class MethodBase extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'method';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['uid', 'user_id', 'type', 'value', 'created'], 'required'],
            [['user_id', 'verified', 'verification_attempts'], 'integer'],
            [['type'], 'string'],
            [['verification_expires', 'created', 'deleted_at'], 'safe'],
            [['uid'], 'string', 'max' => 32],
            [['value'], 'string', 'max' => 255],
            [['verification_code'], 'string', 'max' => 64],
            [['uid'], 'unique'],
            [['user_id', 'type', 'value'], 'unique', 'targetAttribute' => ['user_id', 'type', 'value']],
            [['verification_code'], 'unique'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('model', 'ID'),
            'uid' => Yii::t('model', 'Uid'),
            'user_id' => Yii::t('model', 'User ID'),
            'type' => Yii::t('model', 'Type'),
            'value' => Yii::t('model', 'Value'),
            'verified' => Yii::t('model', 'Verified'),
            'verification_code' => Yii::t('model', 'Verification Code'),
            'verification_attempts' => Yii::t('model', 'Verification Attempts'),
            'verification_expires' => Yii::t('model', 'Verification Expires'),
            'created' => Yii::t('model', 'Created'),
            'deleted_at' => Yii::t('model', 'Deleted At'),
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
