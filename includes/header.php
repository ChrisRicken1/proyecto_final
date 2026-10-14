<?php require_once __DIR__ . '/../config/config.php'; ?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? APP_NAME) ?> | VetCompartido</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <header class="topbar">
        <a class="brand" href="index.php">
            <span class="brand-icon">✚</span> VetCompartido
        </a>
        <nav>
            <?php if (!empty($_SESSION['vet_id'])): ?>
                <a href="vet.php">Panel veterinaria</a>
                <a href="stock.php">Stock</a>
                <a href="logout.php">Cerrar sesión</a>
            <?php elseif (!empty($_SESSION['client_id'])): ?>
                <a href="client.php">Mis mascotas</a>
                <a href="logout.php">Cerrar sesión</a>
            <?php else: ?>
                <a href="index.php">Inicio</a>
            <?php endif; ?>
        </nav>
    </header>
    
    <main class="container">
        <?php show_flash(); ?>