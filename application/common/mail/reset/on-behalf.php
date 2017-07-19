<?php
use yii\helpers\Html as yHtml;

/**
 * @var string $name
 * @var string $idpDisplayName
 * @var string $expireTime
 * @var string $code
 * @var string $helpCenterUrl
 * @var string $fromName
 */
?>
Hi there,
<p>
    <?php echo yHtml::encode($name); ?> recently requested a password change for their
    <?php echo yHtml::encode($idpDisplayName); ?> account and they have requested your assistance.
</p>
<p>
    Please contact them directly to ensure that you are only providing the following reset code to them
    and not to someone else. This code is valid until <?php echo yHtml::encode($expireTime); ?>.
</p>
<p>
    Reset Code: <?php echo yHtml::encode($code); ?>
</p>
<p>
    To keep their account secure, please don't forward this email to anyone.
    See our Help Center for <a href="<?php echo yHtml::encode($helpCenterUrl); ?>">more security tips</a>.
</p>
<p>
    Thanks!
    - <?php echo yHtml::encode($fromName); ?>
</p>
    