<?php
// Run daily with PHP CLI on OVH. This file is not part of public/.
if (PHP_SAPI !== 'cli') exit(1);
$private = getenv('SIMULAT3D_PRIVATE_DIR') ?: dirname(__DIR__) . '/simulat3d-private';
foreach (['rate' => 900, 'sessions' => 604800] as $folder => $age) {
    foreach (glob($private . '/' . $folder . '/*') ?: [] as $file) {
        if (!is_file($file) || is_link($file)) continue;
        $expired = filemtime($file) < time() - $age;
        if ($folder === 'sessions' && preg_match('/expires\|i:(\d+);/', file_get_contents($file), $match)) {
            $expired = (int) $match[1] < time();
        }
        if ($expired) unlink($file);
    }
}
