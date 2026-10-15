<?php require_once __DIR__ . '/../config/config.php'; 
$currentScript = basename($_SERVER['PHP_SELF'] ?? '');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Plataforma de Gestión Veterinaria') ?> | VetCompartido</title>
    <meta name="description" content="<?= e($pageDescription ?? 'Plataforma médica veterinaria integral. Historias clínicas unificadas, gestión de stock, libreta sanitaria digital para tutores y migración asistida sin costo.') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400..700;1,9..40,400..700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/public.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220%22%20width=%2232%22%20height=%2232%22><rect width=%2232%22 height=%2232%22 rx=%228%22 fill=%22%23237765%22/><text x=%2250%%22 y=%2255%%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-size=%2220%22 fill=%22white%22 font-family=%22sans-serif%22 font-weight=%22bold%22>✚</text></svg>">
</head>
<body class="<?= !empty($bodyClass) ? e($bodyClass) : '' ?>">
    <header class="topbar public-topbar">
        <div class="topbar-inner">
            <a class="brand" href="index.php" title="VetCompartido - Gestión Veterinaria">
                <span class="brand-icon">✚</span>
                <div class="brand-text-group">
                    <span class="brand-name">VetCompartido</span>
                    <span class="brand-tagline">Gestión Clínica &amp; Tutores</span>
                </div>
            </a>

            <!-- Navegación de Escritorio -->
            <nav class="desktop-nav">
                <?php if (!empty($_SESSION['vet_id'])): ?>
                    <a href="vet.php" class="<?= $currentScript === 'vet.php' ? 'active-nav' : '' ?>">Panel veterinaria</a>
                    <a href="stock.php" class="<?= $currentScript === 'stock.php' ? 'active-nav' : '' ?>">Stock</a>
                    <span class="nav-divider"></span>
                    <a href="logout.php" class="btn-nav-outline">Cerrar sesión</a>
                <?php elseif (!empty($_SESSION['client_id'])): ?>
                    <a href="client.php" class="<?= $currentScript === 'client.php' ? 'active-nav' : '' ?>">Mis mascotas</a>
                    <span class="nav-divider"></span>
                    <a href="logout.php" class="btn-nav-outline">Cerrar sesión</a>
                <?php else: ?>
                    <a href="index.php" class="<?= $currentScript === 'index.php' ? 'active-nav' : '' ?>">Inicio</a>
                    <a href="index.php#soluciones">Soluciones</a>
                    <a href="index.php#migracion" class="nav-link-badge">
                        Migración asistida <span class="badge-pill-micro">Gratis</span>
                    </a>
                    <a href="planes.php" class="<?= $currentScript === 'planes.php' ? 'active-nav' : '' ?>">Planes</a>
                    <a href="nosotros.php" class="<?= $currentScript === 'nosotros.php' ? 'active-nav' : '' ?>">Quiénes somos</a>
                    <a href="contacto.php" class="<?= $currentScript === 'contacto.php' ? 'active-nav' : '' ?>">Contacto</a>
                    
                    <div class="nav-actions">
                        <a href="login.php" class="btn-nav-login">
                            <span class="login-dot"></span> Iniciar sesión
                        </a>
                        <a href="planes.php" class="btn-nav-cta">Comenzar gratis</a>
                    </div>
                <?php endif; ?>
            </nav>

            <!-- Botón Menú Móvil -->
            <button type="button" class="mobile-menu-toggle" id="mobileMenuBtn" aria-label="Abrir menú de navegación">
                <span class="hamburger-bar"></span>
                <span class="hamburger-bar"></span>
                <span class="hamburger-bar"></span>
            </button>
        </div>

        <!-- Menú Móvil Desplegable -->
        <div class="mobile-nav-drawer" id="mobileNavDrawer">
            <div class="mobile-nav-content">
                <?php if (!empty($_SESSION['vet_id'])): ?>
                    <a href="vet.php">🏥 Panel veterinaria</a>
                    <a href="stock.php">📦 Gestión de Stock</a>
                    <a href="logout.php" class="mobile-nav-logout">Cerrar sesión</a>
                <?php elseif (!empty($_SESSION['client_id'])): ?>
                    <a href="client.php">🐾 Mis mascotas</a>
                    <a href="logout.php" class="mobile-nav-logout">Cerrar sesión</a>
                <?php else: ?>
                    <a href="index.php" class="<?= $currentScript === 'index.php' ? 'active' : '' ?>">🏠 Inicio</a>
                    <a href="index.php#soluciones">✨ Soluciones clínicas</a>
                    <a href="index.php#migracion" class="mobile-highlight">🚀 Migración asistida sin costo</a>
                    <a href="planes.php" class="<?= $currentScript === 'planes.php' ? 'active' : '' ?>">💳 Planes y Precios</a>
                    <a href="nosotros.php" class="<?= $currentScript === 'nosotros.php' ? 'active' : '' ?>">👥 Quiénes somos</a>
                    <a href="contacto.php" class="<?= $currentScript === 'contacto.php' ? 'active' : '' ?>">✉️ Contacto y Soporte</a>
                    <a href="terminos.php" class="<?= $currentScript === 'terminos.php' ? 'active' : '' ?>">📄 Términos para veterinarias</a>
                    <div class="mobile-nav-buttons">
                        <a href="login.php" class="btn-mobile-login">Iniciar sesión</a>
                        <a href="planes.php" class="btn-mobile-primary">Comenzar prueba gratis</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>
    
    <main class="<?= !empty($isLanding) ? 'main-landing' : 'container' ?>">
        <?php show_flash(); ?>