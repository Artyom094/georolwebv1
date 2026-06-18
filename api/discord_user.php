<?php
/**
 * API Controller para obtener información de Discord
 * Endpoint: /api/discord_user.php?user_id=USER_ID
 */

header('Content-Type: application/json');

// Cargar configuración
require_once __DIR__ . '/../config/discord.php';

// Token del Bot de Discord
$DISCORD_BOT_TOKEN = DISCORD_BOT_TOKEN;

if (empty($DISCORD_BOT_TOKEN)) {
    echo json_encode([
        'success' => false,
        'error' => 'Bot token no configurado'
    ]);
    exit;
}

// Obtener user_id de Discord
$discord_user_id = $_GET['user_id'] ?? '';

if (empty($discord_user_id)) {
    echo json_encode([
        'success' => false,
        'error' => 'user_id requerido'
    ]);
    exit;
}

// Validar que sea numérico (Discord IDs son snowflakes)
if (!ctype_digit($discord_user_id)) {
    echo json_encode([
        'success' => false,
        'error' => 'user_id debe ser numérico'
    ]);
    exit;
}

// Llamar a la API de Discord
$url = "https://discord.com/api/v10/users/{$discord_user_id}";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bot {$DISCORD_BOT_TOKEN}",
        "Content-Type: application/json"
    ],
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    echo json_encode([
        'success' => false,
        'error' => 'Error de conexión: ' . $curl_error
    ]);
    exit;
}

if ($http_code !== 200) {
    echo json_encode([
        'success' => false,
        'error' => 'Usuario no encontrado o bot sin acceso',
        'http_code' => $http_code
    ]);
    exit;
}

$user_data = json_decode($response, true);

if (!$user_data) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al procesar respuesta'
    ]);
    exit;
}

// Construir URL del avatar
$avatar_url = null;
if (!empty($user_data['avatar'])) {
    $extension = (strpos($user_data['avatar'], 'a_') === 0) ? 'gif' : 'png';
    $avatar_url = "https://cdn.discordapp.com/avatars/{$user_data['id']}/{$user_data['avatar']}.{$extension}?size=256";
} else {
    // Avatar por defecto
    $default_avatar = intval($user_data['discriminator']) % 5;
    $avatar_url = "https://cdn.discordapp.com/embed/avatars/{$default_avatar}.png";
}

// Construir username completo
$username = $user_data['username'];
if (isset($user_data['discriminator']) && $user_data['discriminator'] !== '0') {
    $username .= '#' . $user_data['discriminator'];
}

// Respuesta exitosa
echo json_encode([
    'success' => true,
    'data' => [
        'id' => $user_data['id'],
        'username' => $username,
        'display_name' => $user_data['global_name'] ?? $user_data['username'],
        'avatar_url' => $avatar_url,
        'banner_color' => $user_data['banner_color'] ?? null
    ]
]);
