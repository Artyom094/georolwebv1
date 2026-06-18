<?php
session_start();
require_once 'config/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php"); exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    if (empty($username) || empty($password)) {
        $error = "Por favor ingrese usuario y contraseña.";
    } else {
        try {
            $sql  = "SELECT id_usuario, username, password_hash, id_rol, id_pais, activo, debe_cambiar_password FROM usuarios WHERE username = :username";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($row['activo'] == 0) {
                    $error = "Esta cuenta ha sido suspendida. Contacta al administrador.";
                } elseif (password_verify($password, $row['password_hash'])) {
                    $_SESSION['user_id']  = $row['id_usuario'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['id_rol']   = $row['id_rol'];
                    $_SESSION['id_pais']  = $row['id_pais'];
                    session_regenerate_id(true);
                    $dest = $row['debe_cambiar_password'] ? 'change_password.php' : 'index.php';
                    header("Location: $dest"); exit();
                } else {
                    $error = "Contraseña incorrecta.";
                }
            } else {
                $error = "El usuario no existe.";
            }
        } catch(PDOException $e) {
            $error = "Error en el sistema: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión · Georol</title>
    <meta name="description" content="Accede a Georol, el sistema de simulación geopolítica por turnos.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/color-extract.js"></script>
    <style>
        .login-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            background: var(--glass-bg);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            box-shadow: 0 24px 64px rgba(0,0,0,.25), inset 0 1px 0 rgba(255,255,255,.12);
            padding: 2.5rem 2rem;
        }
        .login-logo {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, var(--accent), #6610f2);
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem; color: #fff;
            box-shadow: 0 8px 24px rgba(13,110,253,.35);
            margin: 0 auto 1.5rem;
        }
        .btn-login {
            background: linear-gradient(135deg, var(--accent), #6610f2);
            color: #fff; border: none;
            padding: .75rem 1.5rem;
            font-weight: 600; font-size: .95rem;
            border-radius: 10px;
            transition: all .25s ease;
        }
        .btn-login:hover { opacity: .9; transform: translateY(-1px); box-shadow: 0 8px 24px rgba(13,110,253,.4); color:#fff; }
        
        .animate-fade-in {
            animation: fadeIn 0.3s ease forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .w-48 {
            width: 48%;
        }
    </style>
</head>
<body>
<div class="page-bg-layer" id="pageBgLayer"></div>

<div class="login-wrap">
    <div class="login-card">
        
        <!-- Standard Login Panel -->
        <div id="login-standard-container">
            <div class="login-logo"><i class="bi bi-globe-americas"></i></div>

            <h1 class="text-center mb-1" style="font-size:1.6rem;font-weight:800;letter-spacing:-.5px">Georol</h1>
            <p class="text-center mb-4" style="color:var(--text-secondary);font-size:.875rem">
                Sepa la bola que sea esto, inicie sesión para continuar.
            </p>

            <?php if ($error): ?>segun
            <div class="alert alert-danger alert-dismissible fade show py-2" style="font-size:.875rem">
                <i class="bi bi-exclamation-circle-fill me-1"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- JS error message -->
            <div id="js-error-alert" class="alert alert-danger alert-dismissible fade show py-2 d-none" style="font-size:.875rem">
                <i class="bi bi-exclamation-circle-fill me-1"></i><span id="js-error-text"></span>
                <button type="button" class="btn-close btn-sm" onclick="document.getElementById('js-error-alert').classList.add('d-none')"></button>
            </div>

            <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>" method="post">
                <div class="mb-3">
                    <label for="username" class="form-label fw-500">Usuario</label>
                    <div class="input-group">
                        <span class="input-group-text" style="background:var(--glass-bg);border-color:var(--border-color)">
                            <i class="bi bi-person" style="color:var(--text-secondary)"></i>
                        </span>
                        <input type="text" class="form-control form-control-lg" name="username" id="username"
                               placeholder="Tu usuario" autocomplete="username" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label fw-500">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text" style="background:var(--glass-bg);border-color:var(--border-color)">
                            <i class="bi bi-lock" style="color:var(--text-secondary)"></i>
                        </span>
                        <input type="password" class="form-control form-control-lg" name="password" id="password"
                               placeholder="••••••••" autocomplete="current-password" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-login w-100 mb-3">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión
                </button>
            </form>

            <div class="d-flex align-items-center my-3">
                <hr class="flex-grow-1" style="border-color: var(--glass-border); opacity: 0.5;">
                <span class="px-2 text-muted" style="font-size: .8rem;">o</span>
                <hr class="flex-grow-1" style="border-color: var(--glass-border); opacity: 0.5;">
            </div>

            <!-- Google Login Button -->
            <button type="button" id="btn-google-login" class="btn btn-outline-light w-100 mb-3 d-flex align-items-center justify-content-center gap-2" style="border-radius:10px; border-color: var(--glass-border); background: rgba(255,255,255,0.05); color: var(--text-primary); transition: all 0.2s ease;">
                <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z" fill="#4285F4"/>
                    <path d="M9 18c2.43 0 4.467-.806 5.956-2.184l-2.908-2.258c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332C2.438 15.938 5.48 18 9 18z" fill="#34A853"/>
                    <path d="M3.964 10.707c-.18-.54-.282-1.117-.282-1.707s.102-1.167.282-1.707V4.961H.957C.347 6.173 0 7.549 0 9s.347 2.827.957 4.039l3.007-2.332z" fill="#FBBC05"/>
                    <path d="M9 3.58c1.32 0 2.505.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0 5.48 0 2.438 2.062.957 4.961L3.964 7.293C4.672 5.166 6.656 3.58 9 3.58z" fill="#EA4335"/>
                </svg>
                <span id="btn-google-text">Continuar con Google</span>
            </button>

            <div class="text-center">
                <a href="register.php" class="btn btn-outline-secondary w-100" style="border-radius:10px;font-size:.875rem">
                    <i class="bi bi-person-plus me-1"></i>Crear cuenta nueva
                </a>
            </div>
        </div>

        <!-- Google Account Linking Panel -->
        <div id="login-google-linking-container" class="d-none animate-fade-in">
            <div class="d-flex align-items-center justify-content-center gap-3 mb-4">
                <div class="login-logo m-0" style="width: 52px; height: 52px; font-size: 1.4rem;"><i class="bi bi-globe-americas"></i></div>
                <i class="bi bi-arrow-left-right text-muted fs-4"></i>
                <div id="google-linking-avatar-container"></div>
            </div>
            
            <h3 class="text-center mb-1" style="font-size:1.35rem;font-weight:800;letter-spacing:-.5px">Vincular Cuenta</h3>
            <p class="text-center mb-4 text-muted" style="font-size:.82rem">
                Tu cuenta de Google no está conectada a Georol. Elige cómo deseas continuar:
            </p>

            <!-- JS error message for linking screen -->
            <div id="linking-error-alert" class="alert alert-danger alert-dismissible fade show py-2 d-none" style="font-size:.875rem">
                <i class="bi bi-exclamation-circle-fill me-1"></i><span id="linking-error-text"></span>
                <button type="button" class="btn-close btn-sm" onclick="document.getElementById('linking-error-alert').classList.add('d-none')"></button>
            </div>

            <!-- Tabs selector -->
            <div class="d-flex justify-content-between mb-4 gap-1">
                <button class="btn btn-sm btn-primary active w-48" id="tab-new-account" type="button" onclick="selectLinkingTab('new')">
                    <i class="bi bi-person-plus me-1"></i>Cuenta Nueva
                </button>
                <button class="btn btn-sm btn-outline-secondary w-48" id="tab-existing-account" type="button" onclick="selectLinkingTab('existing')">
                    <i class="bi bi-link-45deg me-1"></i>Cuenta Existente
                </button>
            </div>

            <!-- Tab 1: New Account Form -->
            <div id="form-linking-new" class="animate-fade-in">
                <form id="js-form-new-account">
                    <div class="mb-3">
                        <label for="new_username" class="form-label fw-500">Nombre de Usuario</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background:var(--glass-bg);border-color:var(--border-color)">
                                <i class="bi bi-person" style="color:var(--text-secondary)"></i>
                            </span>
                            <input type="text" class="form-control form-control-lg" id="new_username" placeholder="Elige tu usuario" required>
                        </div>
                        <div class="form-text" style="font-size:0.75rem; color: var(--text-secondary);">
                            Usa de 3 a 30 caracteres (letras, números, puntos o guiones).
                        </div>
                    </div>
                    <button type="submit" class="btn btn-login w-100 mb-3">
                        <i class="bi bi-check-circle me-1"></i>Crear Cuenta e Iniciar Sesión
                    </button>
                </form>
            </div>

            <!-- Tab 2: Existing Account Form -->
            <div id="form-linking-existing" class="d-none animate-fade-in">
                <form id="js-form-existing-account">
                    <div class="mb-3">
                        <label for="existing_username" class="form-label fw-500">Usuario Existente</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background:var(--glass-bg);border-color:var(--border-color)">
                                <i class="bi bi-person" style="color:var(--text-secondary)"></i>
                            </span>
                            <input type="text" class="form-control form-control-lg" id="existing_username" placeholder="Tu usuario" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="existing_password" class="form-label fw-500">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background:var(--glass-bg);border-color:var(--border-color)">
                                <i class="bi bi-lock" style="color:var(--text-secondary)"></i>
                            </span>
                            <input type="password" class="form-control form-control-lg" id="existing_password" placeholder="••••••••" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-login w-100 mb-3">
                        <i class="bi bi-link-45deg me-1"></i>Vincular e Iniciar Sesión
                    </button>
                </form>
            </div>

            <div class="text-center mt-3">
                <button type="button" class="btn btn-outline-secondary w-100" style="border-radius:10px;font-size:.875rem" onclick="cancelGoogleLinking()">
                    <i class="bi bi-x-circle me-1"></i>Cancelar
                </button>
            </div>
        </div>

        <p class="text-center mt-4 mb-0" style="font-size:.72rem;color:var(--text-secondary)">
            V 1.0 Alpha 1 · Build 260517
        </p>
    </div>
</div>

<button id="theme-toggle" class="btn theme-toggle" title="Cambiar tema">
    <i class="bi bi-moon-fill"></i>
</button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Firebase App & Auth Implementation -->
<script type="module">
  import { initializeApp } from "https://www.gstatic.com/firebasejs/12.14.0/firebase-app.js";
  import { getAuth, signInWithPopup, GoogleAuthProvider } from "https://www.gstatic.com/firebasejs/12.14.0/firebase-auth.js";

  const firebaseConfig = {
    apiKey: "AIzaSyCtm8hKynIP42smkz-rCmpqG4Q3vDrBMfw",
    authDomain: "georol.firebaseapp.com",
    projectId: "georol",
    storageBucket: "georol.firebasestorage.app",
    messagingSenderId: "913683951965",
    appId: "1:913683951965:web:902050146ffb3a0b0fb8b4",
    measurementId: "G-4464DJ5L08"
  };

  const app = initializeApp(firebaseConfig);
  const auth = getAuth(app);
  const provider = new GoogleAuthProvider();

  // Expose Google Login to window context
  window.triggerGoogleLogin = async function() {
      const btn = document.getElementById('btn-google-login');
      const text = document.getElementById('btn-google-text');
      const originalText = text.innerText;
      
      try {
          btn.disabled = true;
          text.innerText = 'Conectando con Google...';
          document.getElementById('js-error-alert').classList.add('d-none');
          
          const result = await signInWithPopup(auth, provider);
          const user = result.user;
          const photoURL = user.photoURL || '';
          
          const response = await fetch('api/google_login.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({
                  action: 'check',
                  google_uid: user.uid,
                  photo_url: photoURL
              })
          });
          
          const data = await response.json();
          
          if (!data.success) {
              throw new Error(data.error || 'Error al verificar la cuenta');
          }
          
          if (data.status === 'logged_in') {
              window.location.href = data.redirect;
          } else if (data.status === 'not_linked') {
              window.currentGoogleUser = {
                  uid: user.uid,
                  photoURL: photoURL
              };
              
              const avatarContainer = document.getElementById('google-linking-avatar-container');
              if (photoURL) {
                  avatarContainer.innerHTML = `<img src="${photoURL}" alt="Google Avatar" style="width: 52px; height: 52px; border-radius: 12px; border: 2px solid var(--accent); box-shadow: 0 4px 12px rgba(13,110,253,.25); object-fit: cover;">`;
              } else {
                  avatarContainer.innerHTML = `<div style="width: 52px; height: 52px; border-radius: 12px; background: var(--accent); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem; font-weight: bold;"><i class="bi bi-person"></i></div>`;
              }
              
              document.getElementById('login-standard-container').classList.add('d-none');
              document.getElementById('login-google-linking-container').classList.remove('d-none');
          }
      } catch (err) {
          console.error(err);
          const errorAlert = document.getElementById('js-error-alert');
          const errorText = document.getElementById('js-error-text');
          errorText.innerText = err.message || 'Error al conectar con Google.';
          errorAlert.classList.remove('d-none');
      } finally {
          btn.disabled = false;
          text.innerText = originalText;
      }
  };
</script>

<script>
  function selectLinkingTab(tab) {
      const tabNew = document.getElementById('tab-new-account');
      const tabExisting = document.getElementById('tab-existing-account');
      const formNew = document.getElementById('form-linking-new');
      const formExisting = document.getElementById('form-linking-existing');
      
      document.getElementById('linking-error-alert').classList.add('d-none');
      
      if (tab === 'new') {
          tabNew.className = 'btn btn-sm btn-primary active w-48';
          tabExisting.className = 'btn btn-sm btn-outline-secondary w-48';
          formNew.classList.remove('d-none');
          formExisting.classList.add('d-none');
      } else {
          tabNew.className = 'btn btn-sm btn-outline-secondary w-48';
          tabExisting.className = 'btn btn-sm btn-primary active w-48';
          formNew.classList.add('d-none');
          formExisting.classList.remove('d-none');
      }
  }

  function cancelGoogleLinking() {
      window.currentGoogleUser = null;
      document.getElementById('login-google-linking-container').classList.add('d-none');
      document.getElementById('login-standard-container').classList.remove('d-none');
      document.getElementById('new_username').value = '';
      document.getElementById('existing_username').value = '';
      document.getElementById('existing_password').value = '';
      document.getElementById('linking-error-alert').classList.add('d-none');
  }

  document.addEventListener('DOMContentLoaded', function() {
      const googleBtn = document.getElementById('btn-google-login');
      if (googleBtn) {
          googleBtn.addEventListener('click', function() {
              if (typeof window.triggerGoogleLogin === 'function') {
                  window.triggerGoogleLogin();
              }
          });
      }

      // New Account Form Submit
      const formNew = document.getElementById('js-form-new-account');
      if (formNew) {
          formNew.addEventListener('submit', async function(e) {
              e.preventDefault();
              document.getElementById('linking-error-alert').classList.add('d-none');
              
              const username = document.getElementById('new_username').value.trim();
              if (!window.currentGoogleUser) {
                  showLinkingError('No se encontró información de la sesión de Google. Inténtalo de nuevo.');
                  return;
              }
              
              try {
                  const response = await fetch('api/google_login.php', {
                      method: 'POST',
                      headers: { 'Content-Type': 'application/json' },
                      body: JSON.stringify({
                          action: 'create_new',
                          google_uid: window.currentGoogleUser.uid,
                          photo_url: window.currentGoogleUser.photoURL,
                          username: username
                      })
                  });
                  
                  const data = await response.json();
                  if (!data.success) {
                      throw new Error(data.error || 'Error al crear la cuenta.');
                  }
                  
                  window.location.href = data.redirect;
              } catch (err) {
                  showLinkingError(err.message);
              }
          });
      }

      // Existing Account Form Submit
      const formExisting = document.getElementById('js-form-existing-account');
      if (formExisting) {
          formExisting.addEventListener('submit', async function(e) {
              e.preventDefault();
              document.getElementById('linking-error-alert').classList.add('d-none');
              
              const username = document.getElementById('existing_username').value.trim();
              const password = document.getElementById('existing_password').value;
              
              if (!window.currentGoogleUser) {
                  showLinkingError('No se encontró información de la sesión de Google. Inténtalo de nuevo.');
                  return;
              }
              
              try {
                  const response = await fetch('api/google_login.php', {
                      method: 'POST',
                      headers: { 'Content-Type': 'application/json' },
                      body: JSON.stringify({
                          action: 'link_existing',
                          google_uid: window.currentGoogleUser.uid,
                          photo_url: window.currentGoogleUser.photoURL,
                          username: username,
                          password: password
                      })
                  });
                  
                  const data = await response.json();
                  if (!data.success) {
                      throw new Error(data.error || 'Error al vincular la cuenta.');
                  }
                  
                  window.location.href = data.redirect;
              } catch (err) {
                  showLinkingError(err.message);
              }
          });
      }
  });

  function showLinkingError(message) {
      const alertDiv = document.getElementById('linking-error-alert');
      const textSpan = document.getElementById('linking-error-text');
      textSpan.innerText = message;
      alertDiv.classList.remove('d-none');
  }
</script>
</body>
</html>
