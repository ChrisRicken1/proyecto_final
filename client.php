<?php
    require __DIR__ . '/config/config.php';
    require_client();

    $pdo = db();
    $id = (int)$_SESSION['client_id'];

    // Obtener datos del cliente
    $s = $pdo->prepare('SELECT dni, email, phone FROM clientes WHERE id = ?');
    $s->execute([$id]);
    $client = $s->fetch();

    if (!$client) {
        session_destroy();
        header('Location: index.php?tipo=cliente');
        exit;
    }

    // Obtener mascotas del cliente
    $s = $pdo->prepare('SELECT * FROM mascotas WHERE client_id = ? ORDER BY name');
    $s->execute([$id]);
    $mascotas = $s->fetchAll();

    // Procesar mascota seleccionada y su historial (si existe)
    $selectedPet = null;
    $records = [];

    if (isset($_GET['pet_id'])) {
        $pid = (int)$_GET['pet_id'];
        $s = $pdo->prepare('SELECT * FROM mascotas WHERE id = ? AND client_id = ?');
        $s->execute([$pid, $id]);
        $selectedPet = $s->fetch();
        
        if ($selectedPet) {
            $s = $pdo->prepare('SELECT m.*, v.name AS clinic_name 
                                FROM historiales_medicos m 
                                JOIN veterinarias v ON v.id = m.vet_id 
                                WHERE m.pet_id = ? 
                                ORDER BY m.visit_date DESC, m.id DESC');
            $s->execute([$pid]);
            $records = $s->fetchAll();
        }
    }

    $pageTitle = 'Mis mascotas';

    // --- LÓGICA DE TURNOS PARA EL CLIENTE ---
    // 1. Obtener lista de todas las veterinarias para el select
    $s = $pdo->query('SELECT id, name FROM veterinarias ORDER BY name');
    $clinicas = $s->fetchAll();

    // 2. Procesar solicitud de nuevo turno
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'request_appointment') {
        verify_csrf();
        $vetId = filter_var($_POST['vet_id'], FILTER_VALIDATE_INT);
        $petId = !empty($_POST['pet_id']) ? filter_var($_POST['pet_id'], FILTER_VALIDATE_INT) : null;
        $date = $_POST['appointment_date'] ?? '';
        $reason = trim($_POST['reason'] ?? '');

        if ($vetId && $date && $reason) {
            $s = $pdo->prepare('INSERT INTO turnos (client_id, vet_id, pet_id, appointment_date, reason, status) VALUES (?, ?, ?, ?, ?, ?)');
            $s->execute([$id, $vetId, $petId, $date, $reason, 'pendiente']);
            flash('Turno solicitado. Esperá la confirmación de la veterinaria.');
            header('Location: client.php');
            exit;
        }
    }

        // 3. Obtener mis turnos
        $s = $pdo->prepare('
            SELECT t.*, v.name as vet_name, p.name as pet_name 
            FROM turnos t 
            JOIN veterinarias v ON t.vet_id = v.id 
            LEFT JOIN mascotas p ON t.pet_id = p.id 
            WHERE t.client_id = ? 
            ORDER BY t.appointment_date DESC
        ');
        $s->execute([$id]);
        $mis_turnos = $s->fetchAll();

        // --- LÓGICA DE TURNOS PARA EL CLIENTE ---
    // 1. Obtener lista de todas las veterinarias para el select
    $s = $pdo->query('SELECT id, name FROM veterinarias ORDER BY name');
    $clinicas = $s->fetchAll();

    // 2. Procesar solicitud de nuevo turno
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'request_appointment') {
        verify_csrf();
        $vetId = filter_var($_POST['vet_id'], FILTER_VALIDATE_INT);
        $petId = !empty($_POST['pet_id']) ? filter_var($_POST['pet_id'], FILTER_VALIDATE_INT) : null;
        $date = $_POST['appointment_date'] ?? '';
        $reason = trim($_POST['reason'] ?? '');

        if ($vetId && $date && $reason) {
            $s = $pdo->prepare('INSERT INTO turnos (client_id, vet_id, pet_id, appointment_date, reason, status) VALUES (?, ?, ?, ?, ?, ?)');
            $s->execute([$id, $vetId, $petId, $date, $reason, 'pendiente']);
            flash('Turno solicitado. Esperá la confirmación de la veterinaria.');
            header('Location: client.php');
            exit;
        }
    }

    // 3. Obtener mis turnos
    $s = $pdo->prepare('
        SELECT t.*, v.name as vet_name, p.name as pet_name 
        FROM turnos t 
        JOIN veterinarias v ON t.vet_id = v.id 
        LEFT JOIN mascotas p ON t.pet_id = p.id 
        WHERE t.client_id = ? 
        ORDER BY t.appointment_date DESC
    ');
    $s->execute([$id]);
    $mis_turnos = $s->fetchAll();

    require __DIR__ . '/includes/header.php';
?>

<div class="page-heading">
    <div>
        <span class="eyebrow">MI ESPACIO</span>
        <h1>Mis mascotas</h1>
        <p>Consultá los datos y el historial médico de cada una.</p>
    </div>
    <div class="profile-chip">
        <span class="avatar"><?= e(mb_strtoupper(mb_substr($client['dni'], 0, 1))) ?></span>
        <div>
            <strong>DNI <?= e($client['dni']) ?></strong>
            <small><?= e($client['email']) ?></small>
        </div>
    </div>
</div>

<section class="panel">
    <div class="panel-title">
        <div><h2>Mis Turnos</h2></div>
    </div>
    
    <!-- Lista de turnos -->
    <?php if ($mis_turnos): ?>
        <div class="record-list" style="margin-bottom: 2rem;">
            <?php foreach ($mis_turnos as $t): ?>
                <article class="record">
                    <div class="record-date"><?= e(date('d/m H:i', strtotime($t['appointment_date']))) ?></div>
                    <div>
                        <h3><?= e($t['reason']) ?> <span class="status-badge <?= e($t['status']) ?>">[<?= strtoupper(e($t['status'])) ?>]</span></h3>
                        <small>Clínica: <?= e($t['vet_name']) ?> <?= $t['pet_name'] ? '· Mascota: ' . e($t['pet_name']) : '' ?></small>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Formulario para pedir turno -->
    <form method="post" class="form-block">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="request_appointment">
        
        <h3>Solicitar nuevo turno</h3>
        <div class="form-grid">
            <label>Veterinaria
                <select name="vet_id" required>
                    <option value="">Seleccionar clínica...</option>
                    <?php foreach ($clinicas as $v): ?>
                        <option value="<?= (int)$v['id'] ?>"><?= e($v['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Mascota (opcional)
                <select name="pet_id">
                    <option value="">Ninguna / Consulta general</option>
                    <?php foreach ($mascotas as $m): ?>
                        <option value="<?= (int)$m['id'] ?>"><?= e($m['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <div class="form-grid">
            <label>Fecha y Hora preferida
                <input type="datetime-local" name="appointment_date" required>
            </label>
            <label>Motivo
                <input name="reason" required placeholder="Ej. Vacunación, control...">
            </label>
        </div>
        <button class="btn primary" type="submit">Solicitar turno</button>
    </form>
</section>

<?php if (!$mascotas): ?>
    <div class="empty-state large">
        <span>🐾</span>
        <h2>Todavía no hay mascotas asociadas</h2>
        <p>Pedile a tu veterinaria que las agregue a tu perfil.</p>
    </div>
<?php endif; ?>

<div class="pet-grid">
    <?php foreach ($mascotas as $p): ?>
        <article class="panel pet-card">
            <div class="pet-card-head">
                <span class="pet-emoji big">🐾</span>
                <span class="status">Perfil activo</span>
            </div>
            <h2><?= e($p['name']) ?></h2>
            <p><?= e($p['animal_type']) ?></p>
            <a class="btn secondary full" href="client.php?pet_id=<?= (int)$p['id'] ?>">Ver historial médico →</a>
        </article>
    <?php endforeach; ?>
</div>

<?php if ($selectedPet): ?>
    <section class="panel history-panel">
        <div class="page-heading compact">
            <div>
                <span class="eyebrow">HISTORIAL MÉDICO · SOLO LECTURA</span>
                <h2><?= e($selectedPet['name']) ?> <span class="species"><?= e($selectedPet['animal_type']) ?></span></h2>
                <p>Los registros solo pueden ser agregados o corregidos por profesionales veterinarios.</p>
            </div>
        </div>
        
        <?php if (!$records): ?>
            <div class="empty-state">Todavía no hay registros médicos.</div>
        <?php else: ?>
            <div class="record-list">
                <?php foreach ($records as $r): ?>
                    <article class="record">
                        <div class="record-date"><?= e(date('d/m/Y', strtotime($r['visit_date']))) ?></div>
                        <div>
                            <h3><?= e($r['title']) ?></h3>
                            <p><?= nl2br(e($r['details'])) ?></p>
                            
                            <?php if ($r['medication']): ?>
                                <p><strong>Medicación / tratamiento:</strong> <?= e($r['medication']) ?></p>
                            <?php endif; ?>
                            
                            <small>Veterinaria: <?= e($r['clinic_name']) ?></small>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>