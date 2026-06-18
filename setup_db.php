<?php
require_once 'config/db.php';

try {
    // Crear base de datos si no existe
    $sql = "CREATE DATABASE IF NOT EXISTS georol_cartillas";
    $conn->exec($sql);
    echo "Base de datos creada o ya existente.<br>";

    // Seleccionar la base de datos
    $conn->exec("USE georol_cartillas");

    // Leer el archivo SQL
    $sqlFile = file_get_contents('SQL/SQL V1.sql');
    
    // Ejecutar queries del archivo SQL
    // Separamos por punto y coma, pero hay que tener cuidado con triggers o procedures si los hubiera. 
    // En este archivo simple parece seguro.
    // Sin embargo, PDO no soporta multi-query directamente en exec de forma robusta en todos los drivers, 
    // pero para este caso simple podemos intentar ejecutar todo el bloque o separar.
    // Vamos a separar por ';'.
    
    // Mejor aún, como es un script de setup, podemos ejecutar todo el contenido si el driver lo permite, 
    // o separar manualmente las sentencias CREATE e INSERT.
    
    // Para asegurar idempotencia (que no falle si ya existen las tablas), el SQL original no tiene IF NOT EXISTS en las tablas.
    // Debería modificar el SQL o manejar los errores. 
    // Voy a asumir que si las tablas existen fallará, así que envolveré en try-catch cada query o 
    // simplemente inyectaré IF NOT EXISTS en el SQL en tiempo de ejecución.
    
    // Vamos a hacerlo simple: si falla, informamos.
    
    // Dividir sentencias.
    // Nota: El archivo SQL V1.sql tiene "CREATE DATABASE". Ya lo manejamos arriba.
    // Vamos a filtrar esa primera parte.

    $queries = explode(';', $sqlFile);

    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            try {
                $conn->exec($query);
                echo "Query ejecutada correctamente: " . substr($query, 0, 50) . "...<br>";
            } catch (PDOException $e) {
                // Ignorar error si la tabla ya existe (codigos 1050, etc) o la DB ya existe
                echo "Nota sobre query: " . $e->getMessage() . "<br>";
            }
        }
    }

    // Crear un usuario administrador por defecto si no existe
    $stmt = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $password = password_hash('admin123', PASSWORD_BCRYPT);
        // Primero asegurarnos de que el rol 1 existe
        $conn->exec("INSERT IGNORE INTO roles (id_rol, nombre_rol) VALUES (1, 'Administrador')");
        
        $sqlAdmin = "INSERT INTO usuarios (username, password_hash, id_rol) VALUES ('admin', '$password', 1)";
        $conn->exec($sqlAdmin);
        echo "Usuario administrador creado (User: admin, Pass: admin123).<br>";
    } else {
        echo "El usuario administrador ya existe.<br>";
    }

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

$conn = null;
?>