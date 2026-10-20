<?php
require_once __DIR__ . '/config/config.php';

// Si ya hay una sesión activa, redirigir al panel correspondiente
if (!empty($_SESSION['clinic_id'])) {
    header('Location: ' . url('dashboard.php'));
    exit;
}
if (!empty($_SESSION['client_id'])) {
    header('Location: ' . url('cliente/mascotas.php'));
    exit;
}
if (!empty($_SESSION['admin_id'])) {
    header('Location: ' . url('admin/veterinarias.php'));
    exit;
}

csrf_check();

$error = '';
$tipo = ($_GET['tipo'] ?? 'veterinaria') === 'cliente' ? 'cliente' : 'veterinaria';

$clinic_name = '';
$email = '';
$dni = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = ($_POST['tipo'] ?? 'veterinaria') === 'cliente' ? 'cliente' : 'veterinaria';

    if ($tipo === 'cliente') {
        $dni = trim($_POST['dni'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($dni === '' || $email === '') {
            $error = 'Por favor ingresá tu DNI y el correo electrónico registrado.';
        } else {
            try {
                $q = db()->prepare("SELECT * FROM clients WHERE dni = ? AND LOWER(email) = LOWER(?)");
                $q->execute([$dni, $email]);
                $c = $q->fetch();

                if ($c) {
                    session_regenerate_id(true);
                    $_SESSION['client_id'] = (int)$c['id'];
                    header('Location: ' . url('cliente/mascotas.php'));
                    exit;
                }
                $error = 'No se encontró un cliente registrado con ese DNI y correo.';
            } catch (Throwable $e) {
                $error = 'Error de conexión con la base de datos. Verificá que el servicio MySQL esté activo.';
            }
        }
    } else {
        $clinic_name = trim($_POST['clinic_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($clinic_name === '' || $email === '' || $password === '') {
            $error = 'Completá todos los campos para ingresar.';
        } else {
            try {
                $q = db()->prepare("
                    SELECT u.*, c.name AS clinic_name, c.active AS clinic_active 
                    FROM vet_users u 
                    JOIN clinics c ON c.id = u.clinic_id 
                    WHERE c.name = ? AND u.email = ? AND u.active = 1 
                    LIMIT 1
                ");
                $q->execute([$clinic_name, $email]);
                $u = $q->fetch();

                if ($u && $u['clinic_active'] && password_verify($password, $u['password_hash'])) {
                    session_regenerate_id(true);
                    $_SESSION['vet_user_id']  = (int)$u['id'];
                    $_SESSION['clinic_id']    = (int)$u['clinic_id'];
                    $_SESSION['vet_username'] = $u['username'];
                    $_SESSION['clinic_name']  = $u['clinic_name'];

                    if (!empty($_POST['remember'])) {
                        $selector  = bin2hex(random_bytes(12));
                        $validator = bin2hex(random_bytes(32));

                        $q = db()->prepare("INSERT INTO remember_tokens(vet_user_id, selector, validator_hash, expires_at) VALUES(?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))");
                        $q->execute([$u['id'], $selector, hash('sha256', $validator)]);

                        setcookie('vet_remember', $selector . ':' . $validator, [
                            'expires'  => time() + 2592000,
                            'path'     => '/',
                            'secure'   => !empty($_SERVER['HTTPS']),
                            'httponly' => true,
                            'samesite' => 'Lax'
                        ]);
                    }

                    header('Location: ' . url('dashboard.php'));
                    exit;
                }

                $error = 'Los datos ingresados no coinciden o la clínica se encuentra suspendida.';
            } catch (Throwable $e) {
                $error = 'Error de conexión con la base de datos. Verificá que el servicio MySQL esté activo.';
            }
        }
    }
}

$pageTitle = 'Iniciar sesión | VetCompartido';
require __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-badge">
                <span class="auth-paw">✚</span>
            </div>
            <h2>Iniciar sesión</h2>
            <p class="auth-subtitle">Accedé a la plataforma médica y clínica de VetCompartido</p>
        </div>

        <div class="tabs auth-tabs">
            <a class="<?= $tipo === 'veterinaria' ? 'active' : '' ?>" href="<?= url('login.php?tipo=veterinaria') ?>">
                <span class="tab-icon">🏥</span> Veterinaria
            </a>
            <a class="<?= $tipo === 'cliente' ? 'active' : '' ?>" href="<?= url('login.php?tipo=cliente') ?>">
                <span class="tab-icon">🐾</span> Portal Tutores
            </a>
        </div>

        <?php if ($error): ?>
            <div class="notice error">
                <span class="notice-icon">⚠️</span>
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= url('login.php?tipo=' . e($tipo)) ?>" class="auth-form" autocomplete="on">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="tipo" value="<?= e($tipo) ?>">

            <?php if ($tipo === 'veterinaria'): ?>
                <div class="form-group">
                    <label for="clinic_name">
                        <span>Nombre de la veterinaria</span>
                    </label>
                    <div class="input-icon-wrapper">
                        <span class="field-icon">🏢</span>
                        <input id="clinic_name" name="clinic_name" value="<?= e($clinic_name) ?>" required autocomplete="organization" placeholder="Ej. Veterinaria Central">
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">
                        <span>Correo electrónico</span>
                    </label>
                    <div class="input-icon-wrapper">
                        <span class="field-icon">✉️</span>
                        <input id="email" type="email" name="email" value="<?= e($email) ?>" required autocomplete="username" placeholder="doctor@veterinaria.com">
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

                <div class="form-group" style="margin: 12px 0 16px;">
                    <label style="font-weight: 500; font-size: 13px; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="remember" style="width: auto; margin: 0;"> Recordarme en este dispositivo por 30 días
                    </label>
                </div>
            <?php else: ?>
                <div class="form-group">
                    <label for="dni">
                        <span>DNI del tutor</span>
                    </label>
                    <div class="input-icon-wrapper">
                        <span class="field-icon">🪪</span>
                        <input id="dni" name="dni" value="<?= e($dni) ?>" required inputmode="numeric" placeholder="Número de documento">
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">
                        <span>Correo electrónico registrado</span>
                    </label>
                    <div class="input-icon-wrapper">
                        <span class="field-icon">✉️</span>
                        <input id="email" type="email" name="email" value="<?= e($email) ?>" required autocomplete="email" placeholder="tutor@correo.com">
                    </div>
                </div>

                <p class="form-note" style="margin-top: 8px; font-size: 12px; color: var(--muted); line-height: 1.5;">
                    ℹ️ Como tutor de mascotas no necesitás contraseña. Ingresás de forma segura con tu DNI y el correo electrónico brindado a tu clínica veterinaria.
                </p>
            <?php endif; ?>

            <button class="btn primary full auth-submit" type="submit">
                <span><?= $tipo === 'veterinaria' ? 'Ingresar al panel médico' : 'Ver libreta de mis mascotas' ?></span>
                <span class="btn-arrow">→</span>
            </button>
        </form>

        <div class="auth-footer-help">
            <p class="form-note">
                <?= $tipo === 'veterinaria' 
                    ? '¿Tu clínica no tiene acceso? Las cuentas son creadas por la administración.' 
                    : '¿No encontrás a tu mascota? Pedile a tu veterinaria que verifique tu registro.' ?>
            </p>
            
            <div class="auth-extra-links">
                <a href="<?= url('admin/login.php') ?>" class="admin-link">⚙️ Acceso administración</a>
            </div>
        </div>
        
        <div class="auth-security-badge">
            <span>🛡️ Conexión cifrada y registros médicos protegidos</span>
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