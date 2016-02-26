<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "requests_by_ip".
 *
 * @property integer $id
 * @property string $username
 * @property string $ip_address
 * @property string $created
 */
class RequestsByIpBase extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'requests_by_ip';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['username', 'ip_address', 'created'], 'required'],
            [['created'], 'safe'],
            [['username'], 'string', 'max' => 255],
            [['ip_address'], 'string', 'max' => 48]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'username' => Yii::t('app', 'Username'),
            'ip_address' => Yii::t('app', 'Ip Address'),
            'created' => Yii::t('app', 'Created'),
        ];
    }
}
