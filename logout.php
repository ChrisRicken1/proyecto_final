<?php
require_once __DIR__ . '/config/config.php';

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $p['path'],
        $p['domain'],
        $p['secure'],
        $p['httponly']
    );
}

session_destroy();

// Eliminar cookie de recuerdame
setcookie('vet_remember', '', [
    'expires'  => time() - 3600,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax'
]);

header('Location: ' . url('login.php'));
exit;