<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../config/db.php';

// Obtener datos del cuerpo de la solicitud (JSON)
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Datos de solicitud inválidos']);
    exit;
}

$action = $input['action'] ?? '';
$google_uid = trim($input['google_uid'] ?? '');
$photo_url = trim($input['photo_url'] ?? '');

if (empty($google_uid)) {
    echo json_encode(['success' => false, 'error' => 'El UID de Google es requerido']);
    exit;
}

try {
    if ($action === 'check') {
        // Buscar si existe un usuario con este google_uid
        $stmt = $conn->prepare("SELECT id_usuario, username, id_rol, id_pais, activo, debe_cambiar_password, avatar_url FROM usuarios WHERE google_uid = :google_uid");
        $stmt->execute([':google_uid' => $google_uid]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if ($user['activo'] == 0) {
                echo json_encode(['success' => false, 'error' => 'Esta cuenta ha sido suspendida. Contacta al administrador.']);
                exit;
            }

            // Iniciar sesión
            $_SESSION['user_id']  = $user['id_usuario'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['id_rol']   = $user['id_rol'];
            $_SESSION['id_pais']  = $user['id_pais'];
            session_regenerate_id(true);

            // Si el avatar_url de la DB está vacío pero el de Google no, podemos actualizarlo
            if (empty($user['avatar_url']) && !empty($photo_url)) {
                $conn->prepare("UPDATE usuarios SET avatar_url = :avatar_url WHERE id_usuario = :id")
                     ->execute([':avatar_url' => $photo_url, ':id' => $user['id_usuario']]);
            }

            $dest = $user['debe_cambiar_password'] ? 'change_password.php' : 'index.php';
            echo json_encode(['success' => true, 'status' => 'logged_in', 'redirect' => $dest]);
            exit;
        } else {
            echo json_encode(['success' => true, 'status' => 'not_linked']);
            exit;
        }
    } 
    
    elseif ($action === 'create_new') {
        $username = trim($input['username'] ?? '');
        if (empty($username)) {
            echo json_encode(['success' => false, 'error' => 'El nombre de usuario es requerido']);
            exit;
        }

        // Validar caracteres del usuario (letras, números, guiones, guiones bajos)
        if (!preg_match('/^[a-zA-Z0-9_\-\.]{3,30}$/', $username)) {
            echo json_encode(['success' => false, 'error' => 'El usuario debe tener entre 3 y 30 caracteres y solo contener letras, números, puntos, guiones o guiones bajos.']);
            exit;
        }

        // Verificar si el username ya existe
        $stmt = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE username = :username");
        $stmt->execute([':username' => $username]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'error' => 'El nombre de usuario ya está en uso']);
            exit;
        }

        // Verificar si este google_uid ya fue vinculado por el camino
        $stmt = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE google_uid = :google_uid");
        $stmt->execute([':google_uid' => $google_uid]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'error' => 'Esta cuenta de Google ya está vinculada a otro usuario.']);
            exit;
        }

        // Generar una contraseña aleatoria muy larga
        $random_password = bin2hex(random_bytes(32));
        $password_hash = password_hash($random_password, PASSWORD_BCRYPT);

        // Rol por defecto: Participante (id_rol = 4)
        $id_rol = 4; 

        // Insertar nuevo usuario
        $avatar_to_save = !empty($photo_url) ? $photo_url : null;
        $stmt = $conn->prepare("INSERT INTO usuarios (username, password_hash, id_rol, google_uid, avatar_url, activo) VALUES (:username, :password_hash, :id_rol, :google_uid, :avatar_url, 1)");
        $stmt->execute([
            ':username' => $username,
            ':password_hash' => $password_hash,
            ':id_rol' => $id_rol,
            ':google_uid' => $google_uid,
            ':avatar_url' => $avatar_to_save
        ]);

        $new_user_id = $conn->lastInsertId();

        // Iniciar sesión
        $_SESSION['user_id']  = $new_user_id;
        $_SESSION['username'] = $username;
        $_SESSION['id_rol']   = $id_rol;
        $_SESSION['id_pais']  = null;
        session_regenerate_id(true);

        echo json_encode(['success' => true, 'status' => 'logged_in', 'redirect' => 'index.php']);
        exit;
    } 
    
    elseif ($action === 'link_existing') {
        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';

        if (empty($username) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Usuario y contraseña son requeridos']);
            exit;
        }

        // Buscar usuario por username
        $stmt = $conn->prepare("SELECT id_usuario, password_hash, id_rol, id_pais, activo, debe_cambiar_password, google_uid, avatar_url FROM usuarios WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode(['success' => false, 'error' => 'El usuario no existe']);
            exit;
        }

        if ($user['activo'] == 0) {
            echo json_encode(['success' => false, 'error' => 'Esta cuenta ha sido suspendida. Contacta al administrador.']);
            exit;
        }

        // Verificar contraseña
        if (!password_verify($password, $user['password_hash'])) {
            echo json_encode(['success' => false, 'error' => 'Contraseña incorrecta']);
            exit;
        }

        // Verificar si el usuario ya tiene otra cuenta de Google vinculada
        if (!empty($user['google_uid'])) {
            echo json_encode(['success' => false, 'error' => 'Este usuario ya tiene una cuenta de Google vinculada']);
            exit;
        }

        // Verificar si la cuenta de Google ya está vinculada a otro usuario
        $stmt = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE google_uid = :google_uid");
        $stmt->execute([':google_uid' => $google_uid]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'error' => 'Esta cuenta de Google ya está vinculada a otro usuario']);
            exit;
        }

        // Vincular google_uid
        // Si no tiene avatar_url local, guardamos el de Google
        $avatar_to_save = $user['avatar_url'];
        if (empty($avatar_to_save) && !empty($photo_url)) {
            $avatar_to_save = $photo_url;
        }

        $stmt = $conn->prepare("UPDATE usuarios SET google_uid = :google_uid, avatar_url = :avatar_url WHERE id_usuario = :id");
        $stmt->execute([
            ':google_uid' => $google_uid,
            ':avatar_url' => $avatar_to_save,
            ':id' => $user['id_usuario']
        ]);

        // Iniciar sesión
        $_SESSION['user_id']  = $user['id_usuario'];
        $_SESSION['username'] = $username;
        $_SESSION['id_rol']   = $user['id_rol'];
        $_SESSION['id_pais']  = $user['id_pais'];
        session_regenerate_id(true);

        $dest = $user['debe_cambiar_password'] ? 'change_password.php' : 'index.php';
        echo json_encode(['success' => true, 'status' => 'logged_in', 'redirect' => $dest]);
        exit;
    }
    
    elseif ($action === 'link_profile') {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Sesión no iniciada']);
            exit;
        }
        
        $user_id = $_SESSION['user_id'];
        
        // Verificar si la cuenta de Google ya está vinculada a otro usuario
        $stmt = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE google_uid = :google_uid AND id_usuario != :id");
        $stmt->execute([':google_uid' => $google_uid, ':id' => $user_id]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'error' => 'Esta cuenta de Google ya está vinculada a otro usuario.']);
            exit;
        }

        // Vincular al usuario actual
        // Buscar el avatar actual para ver si lo actualizamos
        $stmt = $conn->prepare("SELECT avatar_url FROM usuarios WHERE id_usuario = :id");
        $stmt->execute([':id' => $user_id]);
        $current_avatar = $stmt->fetchColumn();
        
        $avatar_to_save = $current_avatar;
        if (empty($avatar_to_save) && !empty($photo_url)) {
            $avatar_to_save = $photo_url;
        }

        $stmt = $conn->prepare("UPDATE usuarios SET google_uid = :google_uid, avatar_url = :avatar_url WHERE id_usuario = :id");
        $stmt->execute([
            ':google_uid' => $google_uid,
            ':avatar_url' => $avatar_to_save,
            ':id' => $user_id
        ]);

        echo json_encode(['success' => true]);
        exit;
    }
    
    else {
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error de base de datos: ' . $e->getMessage()]);
    exit;
}
