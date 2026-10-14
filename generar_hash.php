
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    if (strlen($password) < 12) {
        echo "Usá una contraseña de al menos 12 caracteres.";
    } else {
        echo "<h3>Copiá este hash en config/admin_config.php</h3>";
        echo "<textarea rows='4' cols='70'>";
        echo htmlspecialchars(password_hash($password, PASSWORD_DEFAULT));
        echo "</textarea>";
    }
}
?>

<form method="POST">
    <label>Tu contraseña de administrador:</label>
    <input type="password" name="password" required>
    <button type="submit">Generar hash</button>
</form>