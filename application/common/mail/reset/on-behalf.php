Hi there,
<p>
    <?php echo \yii\helpers\Html::encode($name); ?> recently requested a password change for their
    <?php echo \yii\helpers\Html::encode($idpName); ?> account and they have requested your assistance.
</p>
<p>
    Please contact them directly to ensure that you are only providing the following reset code to them
    and not to someone else.
</p>
<p>
    Reset Code: <?php echo \yii\helpers\Html::encode($code); ?>
</p>
<p>
    To keep their account secure, please don't forward this email to anyone.
    See our Help Center for <a href="<?php echo \yii\helpers\Html::encode($helpCenterUrl); ?>">more security tips</a>.
</p>
<p>
    Thanks!
    - <?php echo \yii\helpers\Html::encode($fromName); ?>
</p>
    