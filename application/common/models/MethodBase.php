<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "method".
 *
 * @property integer $id
 * @property string $uid
 * @property integer $user_id
 * @property string $type
 * @property string $value
 * @property integer $verified
 * @property string $verification_code
 * @property integer $verification_attempts
 * @property string $verification_expires
 * @property string $created
 *
 * @property User $user
 * @property Reset[] $resets
 */
class MethodBase extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'method';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid', 'user_id', 'type', 'value', 'created'], 'required'],
            [['user_id', 'verified', 'verification_attempts'], 'integer'],
            [['type'], 'string'],
            [['verification_expires', 'created'], 'safe'],
            [['uid'], 'string', 'max' => 32],
            [['value'], 'string', 'max' => 255],
            [['verification_code'], 'string', 'max' => 64],
            [['uid'], 'unique'],
            [['user_id', 'type', 'value'], 'unique', 'targetAttribute' => ['user_id', 'type', 'value'], 'message' => 'The combination of User ID, Type and Value has already been taken.'],
            [['verification_code'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'uid' => Yii::t('app', 'Uid'),
            'user_id' => Yii::t('app', 'User ID'),
            'type' => Yii::t('app', 'Type'),
            'value' => Yii::t('app', 'Value'),
            'verified' => Yii::t('app', 'Verified'),
            'verification_code' => Yii::t('app', 'Verification Code'),
            'verification_attempts' => Yii::t('app', 'Verification Attempts'),
            'verification_expires' => Yii::t('app', 'Verification Expires'),
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

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getResets()
    {
        return $this->hasMany(Reset::className(), ['method_id' => 'id']);
    }
}
