<?php
use yii\helpers\Html as yHtml;

/**
 * @var string $idpName
 * @var string $expireTime
 * @var string $resetUrl
 * @var string $helpCenterUrl
 * @var string $fromName
 */
?>
Hi there,
<p>
    Someone recently requested a password change for your <?php echo yHtml::encode($idpName); ?>
    account. If this was you, click the link below to set a new password. This link is valid until
    <?php echo yHtml::encode($expireTime); ?>.
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
    See our Help Center for <a href="<?php echo yHtml::encode($helpCenterUrl); ?>">more security tips</a>.
</p>
<p>
    Thanks!
     - <?php echo yHtml::encode($fromName); ?>
</p>
