<?php

/**
 * Translation map for fr-FR
 */
return [
    // Utils.php
    'Invalid email address provided' => 'Adresse email invalide fournie',

    'Unable to verify reCAPTCHA' => 'Impossible de vérifier reCAPTCHA',

    // ZxcvbnPasswordValidator.php
    'The "minScore" property must be in range 1-4.' => 'La propriété "minScore" doit être comprise entre 1 et 4.',

    // Method.php
    'Invalid method type' => 'Type de méthode invalide',
    'Error locating personnel record' => 'Erreur de localisation du dossier personnel',

    // Reset.php
    'Requested method not found' => 'Méthode demandée non trouvée',

    'Unable to create new reset.' => 'Impossible de créer une nouvelle réinitialisation.',

    '{idpDisplayName} password reset request' => '{idpDisplayName} demande de réinitialisation du mot de passe',

    '{idpDisplayName} password reset request for {name}' =>
        '{idpDisplayName} demande de réinitialisation du mot de passe pour {name}',

    'Unable to update reset in database, email not sent.' =>
        'Impossible de mettre à jour la réinitialisation dans la base de données, e-mail non envoyé.',

    'Unable to save reset with disable_until.' => 'Impossible d\'enregistrer la réinitialisation avec disable_until.',

    'Unable to enable reset.' => 'Impossible d\'activer la réinitialisation.',

    'Method UID required for reset type method' => 'Méthode UID requise pour la méthode de type de réinitialisation',

    'Method not found' => 'Méthode non trouvée',

    'Unknown reset type requested' => 'Type de réinitialisation inconnu demandé',

    'Unable to update reset type.' => 'Impossible de mettre à jour le type de réinitialisation.',

    'Unable to increment attempts count.' => 'Impossible d\'incrémenter les tentatives comptent.',

    // Password.php
    'Your password does not meet the minimum length of {minLength} (code 100)' =>
        'Votre mot de passe ne respecte pas la longueur minimale de {minLength} (code 100)',

    'Your password exceeds the maximum length of {maxLength} (code 110)' =>
        'Votre mot de passe dépasse la longueur maximale de {maxLength} (code 110)',

    'Your password does not meet the minimum strength of {minScore} (code 150)' =>
        'Votre mot de passe ne répond pas à la force minimale de {minScore} (code 150)',

    'Unable to update password. Please contact support.' =>
        'Impossible de mettre à jour le mot de passe. S\'il vous plaît contacter le support. ',

    'Your password may not contain any of these: {labelList} (code 180)' =>
        'Votre mot de passe ne peut contenir aucun de ceux-ci: {labelList} (code 180)',

    'New password validation failed: {errors}' =>
        'La validation du nouveau mot de passe a échoué: {errors}',

    'Unable to update password. '
    . 'If this password has been used before please use something different.' =>
        'Impossible de mettre à jour le mot de passe. '
        . 'Si ce mot de passe a déjà été utilisé, veuillez utiliser quelque chose de différent. ',

    'Unable to update password, please wait a minute and try again. '
    . 'If this problem persists, please contact support.' =>
        'Impossible de mettre à jour le mot de passe, attendez une minute, puis réessayez. '
        . 'Si ce problème persiste, contactez le support technique. ',

    'This password is not secure. It has been revealed {count} times in '
    . 'password breaches. Please create a new password.' =>
        'Ce mot de passe n\'est pas sécurisé. Il a été révélé {count} fois dans des violations '
        . 'de mot de passe. S\'il vous plaît créer un nouveau mot de passe.',

    // MethodController.php
    'Type is required. Options are: {email}' => 'Le type est requis. Les options sont: {email} ',
    'Value is required' => 'Valeur est requise',
    'Recovery method already exists' => 'La méthode de récupération existe déjà',
    'Code is required' => 'Code est requis',
    'Invalid verification code' => 'Code de vérification invalide',
    'Method already verified' => 'Méthode déjà vérifiée',
    'Too many failures for this recovery method' =>
        'Trop d\'échecs pour cette méthode de récupération',
    'Recovery method not found' => 'Méthode de récupération non trouvée',

    // MfaController.php
    'Type is required' => '\'Type\' est requis',
    'Invalid code provided' => 'Code invalide fourni',
    'MFA record not found' => 'Enregistrement MFA non trouvé',
    'Value is required' => 'Valeur est requise',
    'MFA verify failure' => 'MFA vérifier l\'échec',
    'MFA rate limit failure' => 'Échec de limite de taux MFA',
    'MFA update failure' => 'Echec de la mise à jour MFA',

    // PasswordController.php
    'Password is required' => 'Mot de passe requis',

    // ResetController.php
    'Username is required' => 'Nom d\'utilisateur est nécessaire',

    'reCAPTCHA verification code is required' => 'Le code de vérification reCAPTCHA est requis',

    'reCAPTCHA failed verification' => 'reCAPTCHA a échoué la vérification',

    'Unable to create new reset' => 'Impossible de créer une nouvelle réinitialisation',

    'Reset not found' => 'Réinitialiser introuvable',

    'Invalid reset type' => 'Type de réinitialisation invalide',

    'Client ID is missing' => 'Client ID est manquant',
    
];
