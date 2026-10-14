<?php
require_once 'config.php';
require_once 'admin_config.php';

$pdo = db();
$error = '';
$mensaje = '';

// Cerrar sesión
if (isset($_GET['salir'])) {
    unset($_SESSION['admin']);
    header('Location: admin.php');
    exit;
}

// Iniciar sesión de administrador
if (isset($_POST['entrar'])) {
    if (password_verify($_POST['password'], ADMIN_PASSWORD_HASH)) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;

        header('Location: admin.php');
        exit;
    } else {
        $error = 'Contraseña incorrecta.';
    }
}

// Pantalla de acceso
if (empty($_SESSION['admin'])) {
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso administrador | VetCompartido</title>
    <link rel="stylesheet" href="estilos_acceso.css">
</head>
<body class="login-page">

    <main class="login-card">
        <div class="logo">✚</div>

        <h1>VetCompartido</h1>
        <p>Panel privado de administración</p>

        <?php if ($error !== ''): ?>
            <div class="notice error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <label for="password">Contraseña de administrador</label>
            <input
                type="password"
                id="password"
                name="password"
                placeholder="Ingresá tu contraseña"
                required
            >

            <button type="submit" name="entrar">
                Ingresar al panel
            </button>
        </form>
    </main>

</body>
</html>

<?php 
exit;
}?>