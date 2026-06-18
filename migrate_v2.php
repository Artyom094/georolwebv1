<?php
require_once 'config/db.php';

try {
    // Leer el archivo SQL V2
    $sqlFile = file_get_contents('SQL/SQL V2.sql');
    
    // Desactivar chequeos de FK para permitir drops masivos
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0");

    // Ejecutar queries
    // Como el archivo tiene muchos ; y DELIMITER no se usa, explotamos por ;
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
    
    // Reactivar FK
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1");

    // Verificar admin
    $stmt = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $password = password_hash('admin123', PASSWORD_BCRYPT);
        $sqlAdmin = "INSERT INTO usuarios (username, password_hash, id_rol) VALUES ('admin', '$password', 1)";
        $conn->exec($sqlAdmin);
        echo "Usuario administrador creado/restaurado.<br>";
    }
    
    echo "Migración a V2 completada exitosamente.";

} catch(PDOException $e) {
    echo "Error fatal: " . $e->getMessage();
}
?>