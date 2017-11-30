<?php
use yii\helpers\Html as yHtml;

/**
 * @var string $toAddress
 * @var string $idpDisplayName
 * @var string $expireTime
 * @var string $code
 * @var string $helpCenterUrl
 * @var string $fromName
 */
?>
Hi there,
<p>
    Someone recently requested to add this email address, <?php echo yHtml::encode($toAddress); ?>,
    as a method for verifying themselves should they need to reset their
    <?php echo yHtml::encode($idpDisplayName); ?> account password. If this was you, you can use the verification code
    below to add it to your account.
</p>
<p>
    Verification Code: <?php echo yHtml::encode($code); ?>
</p>
<p>
    If you did not request adding this email address to your account please delete this email.
</p>
<p>
    To keep your account secure, please don't forward this email to anyone.
    See our Help Center for <a href="<?php echo yHtml::encode($helpCenterUrl); ?>">more security tips</a>.
</p>
<p>
    Thanks!
    - <?php echo yHtml::encode($fromName); ?>
</p>
    