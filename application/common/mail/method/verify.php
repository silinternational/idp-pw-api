Hi there,

Someone recently requested to add this email address, <?php echo \yii\helpers\Html::encode($toAddress); ?>,
as a method for verifying themselves should they need to reset their <?php echo \Yii::$app->params['idpName']; ?>
account. If this was you, you can use the reset code below to set a new password.

Reset Code: <?php echo \yii\helpers\Html::encode($code); ?>

If you did not request adding this email address to your account please delete this email.

To keep your account secure, please don't forward this email to anyone.
See our Help Center for <a href="<?php echo \Yii::$app->params['helpCenterUrl']; ?>">more security tips</a>.

Thanks!
- <?php echo \yii\helpers\Html::encode(\Yii::$app->params['fromName']); ?>