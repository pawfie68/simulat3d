<?php
// Copy to ~/simulat3d-private/config.php, OUTSIDE ~/www. Never commit real secrets.
return [
    'password_hash' => '', // Generate with password_hash(..., PASSWORD_DEFAULT).
    'rate_secret' => '',   // 32+ random bytes represented as hex.
    'version' => '2.24',
];
