<?php
// Cargar parámetros desde variables de entorno para producción.
$servername = getenv('DB_HOST') ?: 'db5020605310.hosting-data.io';
$username = getenv('DB_USER') ?: 'dbu2364430';
$password = getenv('DB_PASS') ?: 'Sexo.anal12';
$dbname = getenv('DB_NAME') ?: 'dbs15747588';

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
