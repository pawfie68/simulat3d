<?php
// Local development only. Never deploy this router.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
if ($path === '/api/beta.php') { require dirname(__DIR__) . '/public/api/beta.php'; return true; }
http_response_code(404);
header('Content-Type: application/json');
echo '{"error":"Not found"}';
