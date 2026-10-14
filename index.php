<?php
    require __DIR__ . '/config/config.php';

    // Redirect if already logged in
    if (!empty($_SESSION['vet_id'])) {
        header('Location: vet.php');
        exit;
    }
    if (!empty($_SESSION['client_id'])) {
        header('Location: client.php');
        exit;
    }

    $error = '';
    $tipo = ($_GET['tipo'] ?? 'veterinaria') === 'cliente' ? 'cliente' : 'veterinaria';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        $tipo = $_POST['tipo'] ?? 'veterinaria';
        
        try {
            if ($tipo === 'cliente') {
                $dni = trim($_POST['dni'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $pass = $_POST['password'] ?? '';
                
                $s = db()->prepare('SELECT id, password_hash FROM clientes WHERE dni = ? AND email = ?');
                $s->execute([$dni, $email]);
                $c = $s->fetch();
                
                if ($c && password_verify($pass, $c['password_hash'])) {
                    session_regenerate_id(true);
                    $_SESSION['client_id'] = (int)$c['id'];
                    header('Location: client.php');
                    exit;
                }
            } else {
                $name = trim($_POST['clinic_name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $pass = $_POST['password'] ?? '';
                
                $s = db()->prepare('SELECT id, name, password_hash FROM veterinarias WHERE name = ? AND email = ?');
                $s->execute([$name, $email]);
                $v = $s->fetch();
                
                if ($v && password_verify($pass, $v['password_hash'])) {
                    session_regenerate_id(true);
                    $_SESSION['vet_id'] = (int)$v['id'];
                    $_SESSION['vet_name'] = $v['name'];
                    header('Location: vet.php');
                    exit;
                }
            }
            $error = 'Los datos ingresados no son correctos.';
        } catch (Throwable $e) {
            $error = 'No se pudo conectar con el sistema. Verificá la configuración de la base de datos.';
        }
    }

    $pageTitle = 'Iniciar sesión';
    require __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="hero-copy">
        <span class="eyebrow">CUIDADO ANIMAL CONECTADO</span>
        <h1>La salud de cada mascota, <em>en un solo lugar.</em></h1>
        <p>Un espacio compartido para que las veterinarias gestionen la atención y las familias consulten el historial de sus mascotas.</p>
        <div class="hero-points">
            <span>✓ Historial médico</span>
            <span>✓ Gestión de stock</span>
            <span>✓ Perfil de cliente</span>
        </div>
    </div>
    
    <div class="login-card">
        <div class="login-heading">
            <span class="paw">✚</span>
            <div>
                <h2>Bienvenido</h2>
                <p>Ingresá a tu cuenta</p>
            </div>
        </div>
        
        <div class="tabs">
            <a class="<?= $tipo === 'veterinaria' ? 'active' : '' ?>" href="index.php?tipo=veterinaria">Veterinaria</a>
            <a class="<?= $tipo === 'cliente' ? 'active' : '' ?>" href="index.php?tipo=cliente">Cliente</a>
        </div>
        
        <?php if ($error): ?>
            <div class="notice error"><?= e($error) ?></div>
        <?php endif; ?>
        
        <form method="post">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="tipo" value="<?= e($tipo) ?>">
            
            <?php if ($tipo === 'veterinaria'): ?>
                <label>Nombre de la veterinaria
                    <input name="clinic_name" required autocomplete="organization" placeholder="Ej. Veterinaria Central">
                </label>
            <?php else: ?>
                <label>DNI
                    <input name="dni" required inputmode="numeric" placeholder="Tu documento">
                </label>
            <?php endif; ?>
            
            <label>Correo electrónico
                <input type="email" name="email" required autocomplete="email" placeholder="nombre@correo.com">
            </label>
            
            <label>Contraseña
                <input type="password" name="password" required autocomplete="current-password" placeholder="Ingresá tu contraseña">
            </label>
            
            <button class="btn primary full" type="submit">Iniciar sesión <span>→</span></button>
        </form>
        
        <p class="form-note">
            <?= $tipo === 'veterinaria' 
                ? 'Las cuentas de veterinarias son creadas por la administración del sistema.' 
                : '¿Necesitás acceso? Pedile a tu veterinaria que registre tu cuenta.' ?>
        </p>
    </div>
</section>

<section class="feature-grid">
    <article>
        <span class="feature-icon">♡</span>
        <h3>Historial compartido</h3>
        <p>Los registros médicos acompañan a la mascota cuando visita otra veterinaria del sistema.</p>
    </article>
    <article>
        <span class="feature-icon">⌕</span>
        <h3>Todo organizado</h3>
        <p>Datos de clientes, mascotas y consultas reunidos en una plataforma.</p>
    </article>
    <article>
        <span class="feature-icon">▦</span>
        <h3>Stock bajo control</h3>
        <p>Consultá cantidades, precios y fechas de vencimiento de medicamentos.</p>
    </article>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>