<?php

/**
 * Translation map for fr-FR
 */
return [
    // Utils.php
    'Invalid email' => 'Invalid email address provided',

    'Unable to verify reCAPTCHA' => 'Unable to verify reCAPTCHA',

    // ZxcvbnPasswordValidator.php
    'The "minScore" property must be in range 1-4.' => 'The "minScore" property must be in range 1-4.',

    // Method.php
    'Invalid method type' => 'Invalid method type',
    'Error locating personnel record' => 'Error locating personnel record',

    // Reset.php
    'Requested method not found' => 'Requested method not found',

    'Unable to create new reset.' => 'Unable to create new reset.',

    '{idpDisplayName} password reset request' => '{idpDisplayName} password reset request',

    '{idpDisplayName} password reset request for {name}' => '{idpDisplayName} password reset request for {name}', 

    'Unable to update reset in database, email not sent.' => 'Unable to update reset in database, email not sent.', 

    'Unable to save reset with disable_until.' => 'Unable to save reset with disable_until.',

    'Unable to enable reset.' => 'Unable to enable reset.',

    'Method UID required for reset type method' => 'Method UID required for reset type method',

    'Method not found' => 'Method not found',

    'Unknown reset type requested' => 'Unknown reset type requested',

    'Unable to update reset type.' => 'Unable to update reset type.',

    'Unable to increment attempts count.' => 'Unable to increment attempts count.',

    // Password.php
    'Your password does not meet the minimum length of {minLength} (code 100)' => 'Your password does not meet the minimum length of {minLength} (code 100)',

    'Your password exceeds the maximum length of {maxLength} (code 110)' => 'Your password exceeds the maximum length of {maxLength} (code 110)',

    'Your password must contain at least {minNum} numbers (code 120)' => 'Your password must contain at least {minNum} numbers (code 120)',

    'Your password must contain at least {minUpper} upper case letters (code 130)' => 'Your password must contain at least {minUpper} upper case letters (code 130)',

    'Your password must contain at least {minSpecial} special characters (code 140)' => 'Your password must contain at least {minSpecial} special characters (code 140)',

    'Your password does not meet the minimum strength of {minScore} (code 150)' => 'Your password does not meet the minimum strength of {minScore} (code 150)',

    'Unable to update password. Please contact support.' => 'Unable to update password. Please contact support.',

    'Your password may not contain any of these: {labelList} (code 180)' => 'Your password may not contain any of these: {labelList} (code 180)',

    'New password validation failed: {errors}' => 'New password validation failed: {errors}',

    'Unable to update password. If this password has been used before please use something different.' => 'If this password has been used before please use something different.',

    'Unable to update password, please wait a minute and try again. If this problem persists, please contact support.' => 'If this problem persists, please contact support.',

    // MethodController.php
    'Type is required. Options are: {email}' => 'Type is required. Options are: {email}',
    'Value is required' => 'Value is required',
    'Recovery method already exists' => 'Recovery method already exists',
    'Code is required' => 'Code is required',
    'Invalid verification code' => 'Invalid verification code',
    'Method already verified' => 'Method already verified',
    'Too many failures for this recovery method' => 'Too many failures for this recovery method',
    'Recovery method not found' => 'Recovery method not found',

    // MfaController.php
    'Type is required' => 'Type is required',
    'Invalid code provided' => 'Invalid code provided',
    'MFA record not found' => 'MFA record not found',
    'MFA verify failure' => 'MFA verify failure',
    'MFA rate limit failure' => 'MFA rate limit failure',
    'MFA update failure' => 'MFA update failure',

    // PasswordController.php
    'Password is required' => 'Password is required',

    // ResetController.php
    'Username is required' => 'Username is required',

    'reCAPTCHA verification code is required' => 'reCAPTCHA verification code is required',

    'reCAPTCHA failed verification' => 'reCAPTCHA failed verification',

    'Unable to create new reset' => 'Unable to create new reset',

    'Reset not found' => 'Reset not found',

    'Invalid reset type' => 'Invalid reset type',

    'Client ID is missing' => 'Client ID is missing',
];
