<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "reset".
 *
 * @property int $id
 * @property string $uid
 * @property int $user_id
 * @property string $type
 * @property string $code
 * @property int $attempts
 * @property string $expires
 * @property string $disable_until
 * @property string $created
 * @property string $email
 *
 * @property User $user
 */
class ResetBase extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'reset';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['uid', 'user_id', 'type', 'expires', 'created'], 'required'],
            [['user_id', 'attempts'], 'integer'],
            [['type'], 'string'],
            [['expires', 'disable_until', 'created'], 'safe'],
            [['uid'], 'string', 'max' => 32],
            [['code'], 'string', 'max' => 64],
            [['email'], 'string', 'max' => 255],
            [['uid'], 'unique'],
            [['user_id'], 'unique'],
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
            'code' => Yii::t('model', 'Code'),
            'attempts' => Yii::t('model', 'Attempts'),
            'expires' => Yii::t('model', 'Expires'),
            'disable_until' => Yii::t('model', 'Disable Until'),
            'created' => Yii::t('model', 'Created'),
            'email' => Yii::t('model', 'Email'),
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
