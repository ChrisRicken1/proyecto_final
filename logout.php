<?php
require __DIR__ . '/config/config.php';

// Vaciamos todas las variables de sesión
$_SESSION = [];

// Si se desea destruir la sesión completamente, borramos también la cookie de sesión
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

// Finalmente, destruimos la sesión
session_destroy();

// Redirigimos al login
header('Location: login.php');
exit;