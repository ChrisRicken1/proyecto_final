<?php
declare(strict_types=1);

define('DB_HOST', 'localhost');
define('DB_NAME', 'ver2');
define('DB_USER', 'root');
define('DB_PASS', '');
define('APP_NAME', 'VetCompartidoo');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('vetcompartido_session');
    session_start();
}
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
             PDO::ATTR_EMULATE_PREPARES => false]
        );
    }
    return $pdo;
}
function e(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
        !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(403); exit('Solicitud no válida. Recargá la página.');
    }
}
function require_vet(): void {
    if (empty($_SESSION['vet_id'])) { header('Location: index.php'); exit; }
}
function require_client(): void {
    if (empty($_SESSION['client_id'])) { header('Location: index.php?tipo=cliente'); exit; }
}
function flash(string $message, string $type='ok'): void {
    $_SESSION['flash'] = ['message'=>$message, 'type'=>$type];
}
function show_flash(): void {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash']; unset($_SESSION['flash']);
        echo '<div class="notice '.e($f['type']).'">'.e($f['message']).'</div>';
    }
}
function log_history(PDO $pdo, int $petId, int $vetId, string $action): void {
    $s=$pdo->prepare('INSERT INTO registro_actividad (pet_id, vet_id, action_text, created_at) VALUES (?,?,?,NOW())');
    $s->execute([$petId,$vetId,$action]);
}
