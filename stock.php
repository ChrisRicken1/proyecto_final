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
            if ($action === 'add_stock') {
                $name = trim($_POST['name'] ?? '');
                $qty = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT);
                $price = filter_var($_POST['unit_price'] ?? null, FILTER_VALIDATE_FLOAT);
                $expiry = $_POST['expiry_date'] ?? '';

                if (!$name || $qty === false || $qty < 0 || $price === false || $price < 0) {
                    throw new RuntimeException('Completá nombre, cantidad y precio con valores válidos.');
                }

                $s = $pdo->prepare('INSERT INTO productos_stock (vet_id, name, quantity, unit_price, expiry_date) VALUES (?, ?, ?, ?, ?)');
                $s->execute([$vetId, $name, $qty, $price, $expiry ?: null]);
                
                flash('Producto agregado al stock.');
                header('Location: stock.php');
                exit;
            }

            if ($action === 'delete_stock') {
                $s = $pdo->prepare('DELETE FROM productos_stock WHERE id = ? AND vet_id = ?');
                $s->execute([(int)$_POST['stock_id'], $vetId]);
                
                flash('Producto eliminado del stock.');
                header('Location: stock.php');
                exit;
            }

            if ($action === 'update_stock') {
                $id = (int)$_POST['stock_id'];
                $name = trim($_POST['name'] ?? '');
                $qty = (int)($_POST['quantity'] ?? 0);
                $price = (float)($_POST['unit_price'] ?? 0);
                $expiry = $_POST['expiry_date'] ?? '';

                if (!$name || $qty < 0 || $price < 0) {
                    throw new RuntimeException('Revisá los datos del producto.');
                }

                $s = $pdo->prepare('UPDATE productos_stock SET name = ?, quantity = ?, unit_price = ?, expiry_date = ? WHERE id = ? AND vet_id = ?');
                $s->execute([$name, $qty, $price, $expiry ?: null, $id, $vetId]);
                
                flash('Stock actualizado.');
                header('Location: stock.php');
                exit;
            }
        } catch (Throwable $e) {
            $error = $e instanceof RuntimeException ? $e->getMessage() : 'No se pudo guardar el producto.';
        }
    }

    $s = $pdo->prepare('SELECT * FROM productos_stock WHERE vet_id = ? ORDER BY expiry_date IS NULL, expiry_date ASC, name');
    $s->execute([$vetId]);
    $items = $s->fetchAll();

    $pageTitle = 'Stock';
    require __DIR__ . '/includes/header.php';
?>

<div class="page-heading">
    <div>
        <span class="eyebrow">INVENTARIO DE LA CLÍNICA</span>
        <h1>Stock de medicamentos</h1>
        <p>El inventario es privado para <?= e($_SESSION['vet_name'] ?? 'esta veterinaria') ?>.</p>
    </div>
    <a class="btn secondary" href="vet.php">← Volver al panel</a>
</div>

<?php if ($error): ?>
    <div class="notice error"><?= e($error) ?></div>
<?php endif; ?>

<div class="dashboard-grid stock-layout">
    <section class="panel">
        <div class="panel-title">
            <div>
                <span class="step">+</span>
                <h2>Agregar medicamento</h2>
            </div>
        </div>
        <form method="post" class="stack">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="add_stock">
            
            <label>Nombre del producto
                <input name="name" required placeholder="Ej. Antiparasitario">
            </label>
            <div class="form-grid">
                <label>Cantidad
                    <input type="number" name="quantity" min="0" required value="1">
                </label>
                <label>Precio por unidad ($)
                    <input type="number" name="unit_price" min="0" step="0.01" required placeholder="0,00">
                </label>
            </div>
            <label>Fecha de vencimiento
                <input type="date" name="expiry_date">
            </label>
            <button class="btn primary" type="submit">Agregar al inventario</button>
        </form>
    </section>

    <section class="panel">
        <div class="panel-title">
            <div>
                <span class="step"><?= count($items) ?></span>
                <h2>Inventario actual</h2>
            </div>
        </div>
        
        <?php if (!$items): ?>
            <div class="empty-state">Todavía no hay productos cargados.</div>
        <?php endif; ?>
        
        <div class="stock-list">
            <?php foreach ($items as $item): 
                $expired = $item['expiry_date'] && $item['expiry_date'] < date('Y-m-d');
                $soon = $item['expiry_date'] && !$expired && $item['expiry_date'] <= date('Y-m-d', strtotime('+60 days')); 
            ?>
                <details class="stock-item">
                    <summary>
                        <span>
                            <strong><?= e($item['name']) ?></strong>
                            <small><?= (int)$item['quantity'] ?> unidades · $<?= number_format((float)$item['unit_price'], 2, ',', '.') ?></small>
                        </span>
                        <span class="expiry <?= $expired ? 'danger' : ($soon ? 'warning' : '') ?>">
                            <?= $item['expiry_date'] ? e(date('d/m/Y', strtotime($item['expiry_date']))) : 'Sin vencimiento' ?>
                        </span>
                    </summary>
                    
                    <form method="post" class="stock-edit">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="update_stock">
                        <input type="hidden" name="stock_id" value="<?= (int)$item['id'] ?>">
                        
                        <label>Nombre
                            <input name="name" required value="<?= e($item['name']) ?>">
                        </label>
                        <div class="form-grid">
                            <label>Cantidad
                                <input type="number" min="0" name="quantity" required value="<?= (int)$item['quantity'] ?>">
                            </label>
                            <label>Precio
                                <input type="number" min="0" step="0.01" name="unit_price" required value="<?= e((string)$item['unit_price']) ?>">
                            </label>
                        </div>
                        <label>Vencimiento
                            <input type="date" name="expiry_date" value="<?= e($item['expiry_date'] ?? '') ?>">
                        </label>
                        <button class="btn secondary" type="submit">Guardar cambios</button>
                    </form>
                    
                    <form method="post" class="delete-form" data-confirm="¿Eliminar este producto del stock?">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete_stock">
                        <input type="hidden" name="stock_id" value="<?= (int)$item['id'] ?>">
                        <button class="btn danger-btn" type="submit">Eliminar producto</button>
                    </form>
                </details>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>