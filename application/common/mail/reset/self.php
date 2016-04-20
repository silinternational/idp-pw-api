Hi there,

Someone recently requested a password change for your <?php echo \Yii::$app->params['appName']; ?>
account. If this was you, you can use the reset code below to set a new password.

Reset Code: <?php echo \yii\helpers\Html::encode($resetCode); ?> 

If you don't want to change your password or didn't request this, just 
ignore and delete this message. 

To keep your account secure, please don't forward this email to anyone. 
See our Help Center for <a href="<?php echo \Yii::$app->params['helpCenterUrl']; ?>">more security tips</a>.

Thanks!
 - <?php echo \yii\helpers\Html::encode(\Yii::$app->params['fromName']); ?>