<?php
    require __DIR__ . '/config/config.php';
    require_vet();

    $pdo = db();
    $vetId = (int)$_SESSION['vet_id'];
    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        $action = $_POST['action'] ?? '';

        try {
            if ($action === 'add_client') {
                $dni = trim($_POST['dni'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $password = $_POST['client_password'] ?? '';

                if (!$dni || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$phone || strlen($password) < 8) {
                    throw new RuntimeException('Completá los datos y usá una contraseña inicial de al menos 8 caracteres.');
                }

                $s = $pdo->prepare('INSERT INTO clientes (dni, email, phone, password_hash) VALUES (?, ?, ?, ?)');
                $s->execute([$dni, $email, $phone, password_hash($password, PASSWORD_DEFAULT)]);
                
                flash('Cliente creado. Entregale la contraseña inicial de forma segura.');
                header('Location: vet.php?dni=' . urlencode($dni));
                exit;
            }

            if ($action === 'add_pet') {
                $clientId = (int)$_POST['client_id'];
                $type = trim($_POST['animal_type'] ?? '');
                $name = trim($_POST['pet_name'] ?? '');

                $s = $pdo->prepare('SELECT id FROM clientes WHERE id = ?');
                $s->execute([$clientId]);
                if (!$s->fetch()) {
                    throw new RuntimeException('Cliente no encontrado.');
                }

                if (!$type || !$name) {
                    throw new RuntimeException('Completá especie y nombre de la mascota.');
                }

                $s = $pdo->prepare('INSERT INTO mascotas (client_id, animal_type, name) VALUES (?, ?, ?)');
                $s->execute([$clientId, $type, $name]);
                
                flash('Mascota agregada correctamente.');
                header('Location: vet.php?client_id=' . $clientId);
                exit;
            }

            if ($action === 'add_record') {
                $petId = (int)$_POST['pet_id'];
                $date = $_POST['visit_date'] ?? date('Y-m-d');
                $title = trim($_POST['title'] ?? '');
                $details = trim($_POST['details'] ?? '');
                $med = trim($_POST['medication'] ?? '');

                $s = $pdo->prepare('SELECT id FROM mascotas WHERE id = ?');
                $s->execute([$petId]);
                if (!$s->fetch()) {
                    throw new RuntimeException('Mascota no encontrada.');
                }

                if (!$title || !$details) {
                    throw new RuntimeException('El motivo y el detalle son obligatorios.');
                }

                $s = $pdo->prepare('INSERT INTO historiales_medicos (pet_id, vet_id, visit_date, title, details, medication) VALUES (?, ?, ?, ?, ?, ?)');
                $s->execute([$petId, $vetId, $date, $title, $details, $med ?: null]);
                
                log_history($pdo, $petId, $vetId, 'Agregó registro médico: ' . $title);
                flash('Historial médico actualizado para todas las veterinarias del sistema.');
                header('Location: vet.php?pet_id=' . $petId);
                exit;
            }


            // Procesar cambio de estado de un turno
            if ($action === 'update_turno') {
                $turnoId = (int)$_POST['turno_id'];
                $status = $_POST['status']; // 'confirmado', 'cancelado', 'completado'
                
                if (in_array($status, ['confirmado', 'cancelado', 'completado'])) {
                    $s = $pdo->prepare('UPDATE turnos SET status = ? WHERE id = ? AND vet_id = ?');
                    $s->execute([$status, $turnoId, $vetId]);
                    flash('Estado del turno actualizado.');
                }
                header('Location: vet.php');
                exit;
            }

            // Crear un turno directamente desde el perfil del cliente
            if ($action === 'create_turno_vet') {
                $clientId = (int)$_POST['client_id'];
                $petId = !empty($_POST['pet_id']) ? (int)$_POST['pet_id'] : null;
                $date = $_POST['appointment_date'];
                $reason = trim($_POST['reason']);

                $s = $pdo->prepare('INSERT INTO turnos (client_id, vet_id, pet_id, appointment_date, reason, status) VALUES (?, ?, ?, ?, ?, ?)');
                $s->execute([$clientId, $vetId, $petId, $date, $reason, 'confirmado']); // Se guarda como confirmado directo
                
                flash('Turno agendado exitosamente.');
                header('Location: vet.php?client_id=' . $clientId);
                exit;
            }



        } catch (Throwable $e) {
            $error = $e instanceof RuntimeException ? $e->getMessage() : 'No se pudo guardar. Revisá si el DNI ya está registrado.';
        }
    }

    $dni = trim($_GET['dni'] ?? '');
    $clientId = (int)($_GET['client_id'] ?? 0);
    $petId = (int)($_GET['pet_id'] ?? 0);
    $client = null;
    $mascotas = [];
    $records = [];

    if ($clientId) {
        $s = $pdo->prepare('SELECT * FROM clientes WHERE id = ?');
        $s->execute([$clientId]);
        $client = $s->fetch();
    } elseif ($dni) {
        $s = $pdo->prepare('SELECT * FROM clientes WHERE dni = ?');
        $s->execute([$dni]);
        $client = $s->fetch();
    }

    if ($client) {
        $s = $pdo->prepare('SELECT * FROM mascotas WHERE client_id = ? ORDER BY name');
        $s->execute([$client['id']]);
        $mascotas = $s->fetchAll();
    }

    $selectedPet = null;
    if ($petId) {
        $s = $pdo->prepare('SELECT p.*, c.dni, c.email AS client_email FROM mascotas p JOIN clientes c ON c.id = p.client_id WHERE p.id = ?');
        $s->execute([$petId]);
        $selectedPet = $s->fetch();
    }


    // Obtener la agenda de la veterinaria (desde hoy en adelante)
    $s = $pdo->prepare('
        SELECT t.*, c.dni, c.email as client_email, p.name as pet_name 
        FROM turnos t 
        JOIN clientes c ON t.client_id = c.id 
        LEFT JOIN mascotas p ON t.pet_id = p.id 
        WHERE t.vet_id = ? AND t.appointment_date >= CURDATE()
        ORDER BY t.appointment_date ASC
    ');
    $s->execute([$vetId]);
    $agenda = $s->fetchAll();



    $pageTitle = 'Panel veterinaria';
    require __DIR__ . '/includes/header.php';
?>

<div class="page-heading">
    <div>
        <span class="eyebrow">PANEL PROFESIONAL</span>
        <h1>Hola, <?= e($_SESSION['vet_name'] ?? 'Veterinaria') ?></h1>
        <p>Gestioná clientes, mascotas e historiales compartidos.</p>
    </div>
    <a class="btn secondary" href="stock.php">Administrar stock ↗</a>
</div>




<section class="panel">
    <div class="panel-title">
        <div>
            <span class="step">🗓️</span>
            <h2>Agenda de Turnos</h2>
        </div>
    </div>
    
    <?php if (!$agenda): ?>
        <p class="muted">No hay turnos próximos.</p>
    <?php else: ?>
        <div class="stack">
            <?php foreach ($agenda as $t): ?>
                <details class="stock-item"> <!-- Reutilizando estilos de stock -->
                    <summary>
                        <span>
                            <strong><?= e(date('d/m H:i', strtotime($t['appointment_date']))) ?> - <?= e($t['reason']) ?></strong>
                            <small>DNI <?= e($t['dni']) ?> <?= $t['pet_name'] ? ' (Mascota: '.e($t['pet_name']).')' : '' ?></small>
                        </span>
                        <span class="expiry <?= $t['status'] === 'pendiente' ? 'warning' : ($t['status'] === 'confirmado' ? 'danger' : '') ?>">
                            <?= strtoupper(e($t['status'])) ?>
                        </span>
                    </summary>
                    <form method="post" style="padding-top: 10px; display: flex; gap: 10px;">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="update_turno">
                        <input type="hidden" name="turno_id" value="<?= (int)$t['id'] ?>">
                        
                        <select name="status" class="btn secondary">
                            <option value="pendiente" <?= $t['status'] === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                            <option value="confirmado" <?= $t['status'] === 'confirmado' ? 'selected' : '' ?>>Confirmar</option>
                            <option value="completado" <?= $t['status'] === 'completado' ? 'selected' : '' ?>>Completado</option>
                            <option value="cancelado" <?= $t['status'] === 'cancelado' ? 'selected' : '' ?>>Cancelar</option>
                        </select>
                        <button class="btn primary" type="submit">Guardar estado</button>
                    </form>
                </details>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>




<?php if ($error): ?>
    <div class="notice error"><?= e($error) ?></div>
<?php endif; ?>

<div class="dashboard-grid">
    <section class="panel">
        <div class="panel-title">
            <div>
                <span class="step">01</span>
                <h2>Buscar cliente</h2>
            </div>
        </div>
        
        <form method="get" class="search-form">
            <label>DNI del cliente
                <input name="dni" required value="<?= e($dni) ?>" placeholder="Ingresá el DNI">
            </label>
            <button class="btn primary" type="submit">Buscar</button>
        </form>

        <?php if ($dni && !$client): ?>
            <div class="notice">No hay un cliente con ese DNI. Podés registrarlo abajo.</div>
        <?php endif; ?>

        <?php if ($client): ?>
            <div class="client-card">
                <div class="avatar"><?= e(mb_strtoupper(mb_substr($client['dni'], 0, 1))) ?></div>
                <div>
                    <h3>Cliente · DNI <?= e($client['dni']) ?></h3>
                    <p><?= e($client['email']) ?> · <?= e($client['phone']) ?></p>
                </div>
            </div>
            
            <h3 class="subheading">Mascotas registradas</h3>
            
            <?php if (!$mascotas): ?>
                <p class="muted">Todavía no tiene mascotas cargadas.</p>
            <?php endif; ?>
            
            <?php foreach ($mascotas as $p): ?>
                <a class="pet-row" href="vet.php?pet_id=<?= (int)$p['id'] ?>">
                    <span class="pet-emoji">🐾</span>
                    <span>
                        <strong><?= e($p['name']) ?></strong>
                        <small><?= e($p['animal_type']) ?></small>
                    </span>
                    <span class="arrow">→</span>
                </a>
            <?php endforeach; ?>
            
            <form method="post" class="form-block">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="add_pet">
                <input type="hidden" name="client_id" value="<?= (int)$client['id'] ?>">
                
                <h3>Agregar mascota</h3>
                <div class="form-grid">
                    <label>Especie
                        <input name="animal_type" required placeholder="Perro, gato, ave...">
                    </label>
                    <label>Nombre
                        <input name="pet_name" required placeholder="Nombre de la mascota">
                    </label>
                </div>
                <button class="btn primary" type="submit">Agregar mascota</button>
            </form>
        <?php endif; ?>
    </section>

    <section class="panel">
        <div class="panel-title">
            <div>
                <span class="step">02</span>
                <h2>Registrar cliente</h2>
            </div>
        </div>
        <p class="muted">Si ya existe en otra veterinaria, buscá primero por DNI para acceder a su perfil compartido.</p>
        
        <form method="post" class="stack">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="add_client">
            
            <label>DNI
                <input name="dni" required placeholder="Documento">
            </label>
            <label>Correo electrónico
                <input type="email" name="email" required placeholder="cliente@correo.com">
            </label>
            <label>Celular
                <input name="phone" required placeholder="11 1234-5678">
            </label>
            <label>Contraseña inicial del cliente
                <input type="password" name="client_password" required minlength="8" placeholder="Mínimo 8 caracteres">
            </label>
            <button class="btn primary" type="submit">Crear cliente</button>
        </form>
    </section>
</div>

<?php if ($selectedPet): ?>
    <section class="panel history-panel">
        <div class="page-heading compact">
            <div>
                <span class="eyebrow">HISTORIAL COMPARTIDO</span>
                <h2><?= e($selectedPet['name']) ?> <span class="species"><?= e($selectedPet['animal_type']) ?></span></h2>
                <p>Cliente DNI <?= e($selectedPet['dni']) ?></p>
            </div>
            <a class="btn secondary" href="vet.php?client_id=<?= (int)$selectedPet['client_id'] ?>">Volver al perfil</a>
        </div>
        
        <div class="record-list">
            <?php 
            $s = $pdo->prepare('SELECT m.*, v.name AS clinic_name FROM historiales_medicos m JOIN veterinarias v ON v.id = m.vet_id WHERE m.pet_id = ? ORDER BY m.visit_date DESC, m.id DESC');
            $s->execute([$petId]);
            $records = $s->fetchAll(); 
            ?>
            
            <?php if (!$records): ?>
                <div class="empty-state">Todavía no hay registros médicos para esta mascota.</div>
            <?php endif; ?>
            
            <?php foreach ($records as $r): ?>
                <article class="record">
                    <div class="record-date"><?= e(date('d/m/Y', strtotime($r['visit_date']))) ?></div>
                    <div>
                        <h3><?= e($r['title']) ?></h3>
                        <p><?= nl2br(e($r['details'])) ?></p>
                        <?php if ($r['medication']): ?>
                            <p><strong>Medicación:</strong> <?= e($r['medication']) ?></p>
                        <?php endif; ?>
                        <small>Atendido en <?= e($r['clinic_name']) ?></small>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        
        <form method="post" class="form-block">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="add_record">
            <input type="hidden" name="pet_id" value="<?= (int)$petId ?>">
            
            <h3>Agregar consulta al historial</h3>
            <div class="form-grid">
                <label>Fecha
                    <input type="date" name="visit_date" value="<?= e(date('Y-m-d')) ?>" required>
                </label>
                <label>Motivo / título
                    <input name="title" required placeholder="Control, vacuna, consulta...">
                </label>
            </div>
            <label>Detalle clínico
                <textarea name="details" rows="4" required placeholder="Síntomas, diagnóstico, indicaciones..."></textarea>
            </label>
            <label>Medicamentos / tratamiento
                <input name="medication" placeholder="Medicamento, dosis indicada...">
            </label>
            <button class="btn primary" type="submit">Guardar en historial</button>
        </form>
    </section>
<?php elseif ($petId): ?>
    <div class="notice error">No se encontró esa mascota.</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>