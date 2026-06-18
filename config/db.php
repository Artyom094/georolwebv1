<?php
// Cargar parámetros desde variables de entorno para producción.
$servername = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: 'forsuperearth21';
$dbname = getenv('DB_NAME') ?: 'dbs15216161';

// Opciones PDO recomendadas para producción
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password, $options);
} catch(PDOException $e) {
    // Si la base de datos no existe, intentamos conectar sin dbname (para setup)
    try {
        $conn = new PDO("mysql:host=$servername;charset=utf8mb4", $username, $password, $options);
    } catch(PDOException $e2) {
        die("Connection failed: " . $e2->getMessage());
    }
}
?>
