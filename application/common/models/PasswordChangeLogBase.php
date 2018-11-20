<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "password_change_log".
 *
 * @property int $id
 * @property int $user_id
 * @property string $scenario
 * @property string $reset_type
 * @property string $method_type
 * @property string $masked_value
 * @property string $created
 * @property string $ip_address
 *
 * @property User $user
 */
class PasswordChangeLogBase extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'password_change_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'scenario', 'created', 'ip_address'], 'required'],
            [['user_id'], 'integer'],
            [['scenario', 'reset_type', 'method_type'], 'string'],
            [['created'], 'safe'],
            [['masked_value'], 'string', 'max' => 255],
            [['ip_address'], 'string', 'max' => 48],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'scenario' => Yii::t('app', 'Scenario'),
            'reset_type' => Yii::t('app', 'Reset Type'),
            'method_type' => Yii::t('app', 'Method Type'),
            'masked_value' => Yii::t('app', 'Masked Value'),
            'created' => Yii::t('app', 'Created'),
            'ip_address' => Yii::t('app', 'Ip Address'),
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
