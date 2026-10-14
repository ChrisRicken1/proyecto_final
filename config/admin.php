
<?php
require_once 'config.php';
require_once 'admin_config.php';
include 'acceso_admin.php';



    

// Registrar veterinaria
if (isset($_POST['crear'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $passwordVet = $_POST['password_vet'] ?? '';

    if (
        $nombre === '' ||
        !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
        strlen($passwordVet) < 8
    ) {
        $error = 'Completá correctamente los datos. La contraseña debe tener al menos 8 caracteres.';
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO veterinarias
                (name, email, password_hash)
                VALUES (?, ?, ?)"
            );

            $stmt->execute([
                $nombre,
                $correo,
                password_hash($passwordVet, PASSWORD_DEFAULT)
            ]);

            $mensaje = 'Veterinaria agregada correctamente.';
        } catch (PDOException $e) {
            $error = 'No se pudo registrar. Verificá que el correo no esté repetido.';
        }
    }
}

// Obtener veterinarias
$veterinarias = $pdo->query(
    "SELECT name, email
     FROM veterinarias
     ORDER BY id DESC"
)->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel administrador | VetCompartido</title>
    <link rel="stylesheet" href="estilos_acceso.css">
</head>
<body>

<header class="navbar">
    <div class="brand">
        ✚ VetCompartido
        <span>Administración del sistema</span>
    </div>

    <a class="logout" href="admin.php?salir=1">
        Cerrar sesión
    </a>
</header>

<main class="container">

    <section class="welcome">
        <h1>Panel de administración</h1>
        <p>Gestioná las veterinarias que utilizan tu sistema.</p>
    </section>

    <?php if ($mensaje !== ''): ?>
        <div class="notice success"><?= e($mensaje) ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="notice error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="panel-grid">

        <section class="card">
            <h2>Agregar veterinaria</h2>
            <p class="description">
                Creá una cuenta nueva completando los siguientes datos.
            </p>

            <form method="POST">
                <label for="nombre">Nombre de la veterinaria</label>
                <input
                    type="text"
                    id="nombre"
                    name="nombre"
                    placeholder="Ej. Veterinaria San Martín"
                    maxlength="150"
                    required
                >

                <label for="correo">Correo electrónico</label>
                <input
                    type="email"
                    id="correo"
                    name="correo"
                    placeholder="contacto@veterinaria.com"
                    maxlength="190"
                    required
                >

                <label for="password_vet">Contraseña de acceso</label>
                <input
                    type="password"
                    id="password_vet"
                    name="password_vet"
                    placeholder="Mínimo 8 caracteres"
                    minlength="8"
                    required
                >

                <button type="submit" name="crear">
                    + Registrar veterinaria
                </button>
            </form>
        </section>

        <section class="card">
            <h2>Veterinarias registradas</h2>
            <p class="description">
                Cuentas creadas en VetCompartido.
            </p>

            <div class="vet-list">
                <?php if (empty($veterinarias)): ?>
                    <div class="empty">
                        Todavía no registraste ninguna veterinaria.
                    </div>
                <?php else: ?>
                    <?php foreach ($veterinarias as $vet): ?>
                        <div class="vet-item">
                            <span class="vet-name">
                                <?= e($vet['name']) ?>
                            </span>

                            <span class="vet-email">
                                <?= e($vet['email']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

    </div>
</main>

</body>
</html>