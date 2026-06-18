<?php
require_once 'config/db.php';

try {
    $sqlFile = file_get_contents('SQL/SQL V3.sql');
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0");

    $queries = explode(';', $sqlFile);
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            try {
                $conn->exec($query);
            } catch (PDOException $e) {
                echo "Error en query: " . substr($query, 0, 100) . "... -> " . $e->getMessage() . "<br>";
            }
        }
    }
    
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1");

    // Restaurar admin
    $stmt = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $password = password_hash('admin123', PASSWORD_BCRYPT);
        $sqlAdmin = "INSERT INTO usuarios (username, password_hash, id_rol, activo) VALUES ('admin', '$password', 1, TRUE)";
        $conn->exec($sqlAdmin);
        echo "Usuario administrador creado/restaurado.<br>";
    }
    
    echo "Migración a V3 completada exitosamente.";
} catch(PDOException $e) {
    echo "Error fatal: " . $e->getMessage();
}
?>
