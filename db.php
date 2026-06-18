<?php
// Cargar parámetros desde variables de entorno para producción.
$servername = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER') ?: 'itace';
$password = getenv('DB_PASS') ?: '';
$dbname = getenv('DB_NAME') ?: 'georol_cartillas';

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password, $options);
} catch(PDOException $e) {
    // Si la base de datos no existe, intentamos conectar sin dbname para crearla luego en el setup
    try {
        $conn = new PDO("mysql:host=$servername;charset=utf8mb4", $username, $password, $options);
    } catch(PDOException $e2) {
        die("Connection failed: " . $e2->getMessage());
    }
}
?>