<?php
    require __DIR__ . '/config/config.php';

    // Redireccionar si ya hay una sesión activa
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

    $dni = '';
    $clinic_name = '';
    $email = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        $tipo = ($_POST['tipo'] ?? 'veterinaria') === 'cliente' ? 'cliente' : 'veterinaria';
        
        try {
            if ($tipo === 'cliente') {
                $dni = trim($_POST['dni'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $pass = $_POST['password'] ?? '';
                
                if ($dni !== '' && $email !== '') {
                    $s = db()->prepare('SELECT id, password_hash FROM clientes WHERE dni = ? AND email = ?');
                    $s->execute([$dni, $email]);
                } elseif ($dni !== '') {
                    $s = db()->prepare('SELECT id, password_hash FROM clientes WHERE dni = ?');
                    $s->execute([$dni]);
                } else {
                    $s = db()->prepare('SELECT id, password_hash FROM clientes WHERE email = ?');
                    $s->execute([$email]);
                }
                $c = $s->fetch();
                
                if ($c && password_verify($pass, $c['password_hash'])) {
                    session_regenerate_id(true);
                    $_SESSION['client_id'] = (int)$c['id'];
                    header('Location: client.php');
                    exit;
                }
            } else {
                $clinic_name = trim($_POST['clinic_name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $pass = $_POST['password'] ?? '';
                
                if ($clinic_name !== '' && $email !== '') {
                    $s = db()->prepare('SELECT id, name, password_hash FROM veterinarias WHERE name = ? AND email = ?');
                    $s->execute([$clinic_name, $email]);
                } elseif ($email !== '') {
                    $s = db()->prepare('SELECT id, name, password_hash FROM veterinarias WHERE email = ?');
                    $s->execute([$email]);
                } else {
                    $s = db()->prepare('SELECT id, name, password_hash FROM veterinarias WHERE name = ?');
                    $s->execute([$clinic_name]);
                }
                $v = $s->fetch();
                
                if ($v && password_verify($pass, $v['password_hash'])) {
                    session_regenerate_id(true);
                    $_SESSION['vet_id'] = (int)$v['id'];
                    $_SESSION['vet_name'] = $v['name'];
                    header('Location: vet.php');
                    exit;
                }
            }
            $error = 'Los datos ingresados no son correctos. Verificá tus credenciales.';
        } catch (Throwable $e) {
            $error = 'No se pudo conectar con el sistema. Verificá la conexión y configuración de la base de datos.';
        }
    }

    $pageTitle = 'Iniciar sesión';
    require __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-badge">
                <span class="auth-paw">✚</span>
            </div>
            <h2>Iniciar sesión</h2>
            <p class="auth-subtitle">Accedé a la plataforma médica de VetCompartido</p>
        </div>

        <div class="tabs auth-tabs">
            <a class="<?= $tipo === 'veterinaria' ? 'active' : '' ?>" href="login.php?tipo=veterinaria">
                <span class="tab-icon">🏥</span> Veterinaria
            </a>
            <a class="<?= $tipo === 'cliente' ? 'active' : '' ?>" href="login.php?tipo=cliente">
                <span class="tab-icon">🐾</span> Cliente
            </a>
        </div>

        <?php if ($error): ?>
            <div class="notice error">
                <span class="notice-icon">⚠️</span>
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="post" action="login.php?tipo=<?= e($tipo) ?>" class="auth-form" autocomplete="on">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="tipo" value="<?= e($tipo) ?>">

            <?php if ($tipo === 'veterinaria'): ?>
                <div class="form-group">
                    <label for="clinic_name">
                        <span>Nombre de la veterinaria</span>
                        <span class="label-hint">(Opcional si usás correo)</span>
                    </label>
                    <div class="input-icon-wrapper">
                        <span class="field-icon">🏢</span>
                        <input id="clinic_name" name="clinic_name" value="<?= e($clinic_name) ?>" autocomplete="organization" placeholder="Ej. Veterinaria San Roque">
                    </div>
                </div>
            <?php else: ?>
                <div class="form-group">
                    <label for="dni">
                        <span>DNI del titular</span>
                    </label>
                    <div class="input-icon-wrapper">
                        <span class="field-icon">🪪</span>
                        <input id="dni" name="dni" value="<?= e($dni) ?>" required inputmode="numeric" placeholder="Número de documento">
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="email">
                    <span>Correo electrónico</span>
                </label>
                <div class="input-icon-wrapper">
                    <span class="field-icon">✉️</span>
                    <input id="email" type="email" name="email" value="<?= e($email) ?>" required autocomplete="email" placeholder="nombre@correo.com">
                </div>
            </div>

            <div class="form-group">
                <label for="password">
                    <span>Contraseña</span>
                </label>
                <div class="input-icon-wrapper">
                    <span class="field-icon">🔒</span>
                    <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Ingresá tu contraseña">
                    <button type="button" class="btn-toggle-password" id="togglePasswordBtn" aria-label="Mostrar u ocultar contraseña" title="Mostrar/ocultar contraseña">
                        👁️
                    </button>
                </div>
            </div>

            <button class="btn primary full auth-submit" type="submit">
                <span>Ingresar al sistema</span>
                <span class="btn-arrow">→</span>
            </button>
        </form>

        <div class="auth-footer-help">
            <p class="form-note">
                <?= $tipo === 'veterinaria' 
                    ? '¿Tu clínica no tiene acceso? Las cuentas son creadas por la administración.' 
                    : '¿Necesitás acceso a tu historial? Pedile a tu veterinaria que registre tu cuenta.' ?>
            </p>
            
            <div class="auth-extra-links">
                <a href="config/admin.php" class="admin-link">⚙️ Acceso administración</a>
            </div>
        </div>
        
        <div class="auth-security-badge">
            <span>🛡️ Conexión cifrada y registros protegidos</span>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const toggleBtn = document.getElementById('togglePasswordBtn');
        const passInput = document.getElementById('password');
        if (toggleBtn && passInput) {
            toggleBtn.addEventListener('click', () => {
                const isPassword = passInput.getAttribute('type') === 'password';
                passInput.setAttribute('type', isPassword ? 'text' : 'password');
                toggleBtn.textContent = isPassword ? '🙈' : '👁️';
            });
        }
    });
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>