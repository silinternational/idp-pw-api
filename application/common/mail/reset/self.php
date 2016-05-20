Hi there,
<p>
    Someone recently requested a password change for your <?php echo \yii\helpers\Html::encode($idpName); ?>
    account. If this was you, click the link below to set a new password.
</p>
<p>
    <a href="<?php echo $resetUrl; ?>"><?php echo $resetUrl; ?></a>
</p>
<p>
    If you don't want to change your password or didn't request this, just
    ignore and delete this message.
</p>
<p>
    To keep your account secure, please don't forward this email to anyone.
    See our Help Center for <a href="<?php echo \yii\helpers\Html::encode($helpCenterUrl); ?>">more security tips</a>.
</p>
<p>
    Thanks!
     - <?php echo \yii\helpers\Html::encode($fromName); ?>
</p>
