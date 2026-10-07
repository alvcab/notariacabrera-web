<?php
// Núcleo de las cuentas de usuario: configuración, base de datos, sesión, correo y plantilla.
// Lo incluyen todas las páginas de cuenta/ y documento.php.

declare(strict_types=1);

// La configuración (base de datos, correo) vive fuera de public_html, en domains/notariacabrera.cl/privado/config.php.
// Ver scripts/config.ejemplo.php.
$rutaConfig = getenv('NOTARIA_CONFIG') ?: dirname(__DIR__, 3) . '/privado/config.php';
if (!is_file($rutaConfig)) {
  http_response_code(500);
  exit('Falta la configuración del sitio.');
}
$CONFIG = require $rutaConfig;

// Registros del Conservador cuyos PDF piden iniciar sesión (carpetas dentro de docs_dir)
const REGISTROS_PROTEGIDOS = ['accionistas', 'descubrimientos', 'hipotecas', 'prohibiciones', 'propiedad'];

function config(string $clave)
{
  global $CONFIG;
  return $CONFIG[$clave] ?? null;
}

function db(): PDO
{
  static $pdo = null;
  if ($pdo === null) {
    $pdo = new PDO(config('db_dsn'), config('db_user'), config('db_pass'), [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,
    ]);
  }
  return $pdo;
}

function e(?string $texto): string
{
  return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

function ip(): string
{
  return substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45);
}

function redirigir(string $url): void
{
  header('Location: ' . $url, true, 303);
  exit;
}

// ── Sesión ──

function iniciar_sesion(): void
{
  if (session_status() === PHP_SESSION_ACTIVE) return;
  $https = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
  session_name('notaria_sesion');
  session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $https,
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();
}

function usuario_actual(): ?array
{
  static $usuario = false;
  if ($usuario !== false) return $usuario;
  iniciar_sesion();
  $usuario = null;
  if (!empty($_SESSION['usuario_id'])) {
    $st = db()->prepare('SELECT id, nombre, rut, email FROM usuarios WHERE id = ? AND confirmado_en IS NOT NULL');
    $st->execute([$_SESSION['usuario_id']]);
    $usuario = $st->fetch() ?: null;
    if ($usuario === null) unset($_SESSION['usuario_id']);
  }
  return $usuario;
}

// Administradores: sus correos están en la tabla administradores (no en el código, que es público en GitHub)
function es_admin(?array $usuario): bool
{
  if (!$usuario) return false;
  try {
    $st = db()->prepare('SELECT 1 FROM administradores WHERE email = ?');
    $st->execute([$usuario['email']]);
    return (bool) $st->fetchColumn();
  } catch (PDOException $e) {
    return false; // la tabla aún no se ha creado en la base
  }
}

function entrar_como(int $usuarioId): void
{
  iniciar_sesion();
  session_regenerate_id(true);
  $_SESSION['usuario_id'] = $usuarioId;
  db()->prepare('UPDATE usuarios SET ultimo_ingreso = NOW() WHERE id = ?')->execute([$usuarioId]);
}

// Página a la que volver después de ingresar (por ejemplo, el PDF que se intentó abrir).
// Solo rutas del mismo sitio: empiezan con una "/" y no tienen dominio ni caracteres raros.
function ruta_valida(?string $ruta): ?string
{
  if ($ruta === null || !preg_match('#^/(?!/)[A-Za-z0-9/_.-]*$#', $ruta)) return null;
  return $ruta;
}

function recordar_volver(?string $ruta): void
{
  iniciar_sesion();
  if ($ruta = ruta_valida($ruta)) $_SESSION['volver'] = $ruta;
}

function tomar_volver(): string
{
  iniciar_sesion();
  $ruta = ruta_valida($_SESSION['volver'] ?? null) ?? '/cuenta/';
  unset($_SESSION['volver']);
  return $ruta;
}

// ── Protección de formularios (CSRF) ──

function token_csrf(): string
{
  iniciar_sesion();
  if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
  return $_SESSION['csrf'];
}

function campo_csrf(): string
{
  return '<input type="hidden" name="csrf" value="' . e(token_csrf()) . '">';
}

function verificar_csrf(): bool
{
  iniciar_sesion();
  $enviado = $_POST['csrf'] ?? '';
  return is_string($enviado) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $enviado);
}

// ── Límite de intentos (ingreso, registro, recuperación) ──

function demasiados_intentos(string $accion, string $clave, int $maximo, int $minutos): bool
{
  $st = db()->prepare('SELECT COUNT(*) FROM intentos WHERE accion = ? AND clave = ? AND creado_en > DATE_SUB(NOW(), INTERVAL ? MINUTE)');
  $st->execute([$accion, $clave, $minutos]);
  return (int) $st->fetchColumn() >= $maximo;
}

function anotar_intento(string $accion, string $clave): void
{
  db()->prepare('INSERT INTO intentos (accion, clave) VALUES (?, ?)')->execute([$accion, $clave]);
  db()->exec('DELETE FROM intentos WHERE creado_en < DATE_SUB(NOW(), INTERVAL 1 DAY)');
}

// ── RUT ──

// Devuelve el RUT como "12345678-9" si el dígito verificador es correcto; si no, null.
function normalizar_rut(string $rut): ?string
{
  $rut = strtoupper(preg_replace('/[^0-9kK]/', '', $rut));
  if (!preg_match('/^(\d{6,8})([0-9K])$/', $rut, $m)) return null;
  [$cuerpo, $dv] = [$m[1], $m[2]];
  $suma = 0;
  $factor = 2;
  for ($i = strlen($cuerpo) - 1; $i >= 0; $i--) {
    $suma += (int) $cuerpo[$i] * $factor;
    $factor = $factor === 7 ? 2 : $factor + 1;
  }
  $resto = 11 - ($suma % 11);
  $esperado = $resto === 11 ? '0' : ($resto === 10 ? 'K' : (string) $resto);
  return $dv === $esperado ? ltrim($cuerpo, '0') . '-' . $dv : null;
}

function formatear_rut(string $rut): string
{
  [$cuerpo, $dv] = explode('-', $rut);
  return number_format((int) $cuerpo, 0, ',', '.') . '-' . $dv;
}

// ── Enlaces de un solo uso (confirmar cuenta, restablecer contraseña) ──

function crear_token(int $usuarioId, string $tipo, int $horas): string
{
  db()->prepare('UPDATE tokens SET usado_en = NOW() WHERE usuario_id = ? AND tipo = ? AND usado_en IS NULL')
    ->execute([$usuarioId, $tipo]);
  $token = bin2hex(random_bytes(32));
  db()->prepare('INSERT INTO tokens (usuario_id, tipo, token_hash, expira_en) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? HOUR))')
    ->execute([$usuarioId, $tipo, hash('sha256', $token), $horas]);
  return $token;
}

// Devuelve el id del usuario si el enlace es válido y no ha vencido. Con $usar = true lo marca como usado.
function revisar_token(string $token, string $tipo, bool $usar): ?int
{
  if (!preg_match('/^[0-9a-f]{64}$/', $token)) return null;
  $st = db()->prepare('SELECT id, usuario_id FROM tokens WHERE token_hash = ? AND tipo = ? AND usado_en IS NULL AND expira_en > NOW()');
  $st->execute([hash('sha256', $token), $tipo]);
  $fila = $st->fetch();
  if (!$fila) return null;
  if ($usar) db()->prepare('UPDATE tokens SET usado_en = NOW() WHERE id = ?')->execute([$fila['id']]);
  return (int) $fila['usuario_id'];
}

// ── Correo ──

function enviar_correo(string $para, string $asunto, string $texto): bool
{
  $remitente = config('correo_remitente');
  $nombre = config('correo_nombre') ?? 'Notaría Cabrera';
  $cabeceras = implode("\r\n", [
    'From: =?UTF-8?B?' . base64_encode($nombre) . '?= <' . $remitente . '>',
    'Reply-To: ' . (config('correo_responder_a') ?? $remitente),
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'Content-Transfer-Encoding: 8bit',
  ]);
  $asuntoCodificado = '=?UTF-8?B?' . base64_encode($asunto) . '?=';

  // En pruebas locales los correos se escriben en un archivo en vez de enviarse
  if (config('correo_modo') === 'archivo') {
    $linea = "Para: $para\nAsunto: $asunto\n\n$texto\n" . str_repeat('-', 60) . "\n";
    return file_put_contents(config('correo_archivo'), $linea, FILE_APPEND) !== false;
  }
  return mail($para, $asuntoCodificado, $texto, $cabeceras, '-f' . $remitente);
}

function enviar_confirmacion(int $usuarioId, string $nombre, string $email): bool
{
  $enlace = rtrim(config('url_base'), '/') . '/cuenta/confirmar.php?t=' . crear_token($usuarioId, 'confirmar', 48);
  return enviar_correo($email, 'Confirme su cuenta — Notaría Cabrera', <<<TXT
    Hola, $nombre:

    Para activar su cuenta en el sitio de la Notaría Cabrera, abra este enlace:

    $enlace

    El enlace vence en 48 horas. Si usted no creó esta cuenta, ignore este correo.

    Segunda Notaría Pública y Conservador de Minas de Ovalle
    TXT);
}

// ── Plantilla de las páginas de cuenta ──

function cabecera(string $titulo): void
{
  header('X-Robots-Tag: noindex');
  $usuario = usuario_actual();
  ?>
<!DOCTYPE html>
<html lang="es" data-tema="alternado">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex">
  <title><?= e($titulo) ?> — Notaría Cabrera, Segunda Notaría de Ovalle</title>
  <link rel="icon" type="image/png" href="/assets/logo.png">
  <link rel="stylesheet" href="/css/style.css?v=92">
  <link rel="stylesheet" href="/css/registro.css?v=7">
  <link rel="stylesheet" href="/css/cuenta.css?v=2">
</head>
<body>
  <header class="site-header">
    <div class="container header-inner">
      <a href="/index.html" class="logo">
        <img src="/assets/logo.png" alt="" class="logo-icon">
        Notaría Cabrera
      </a>
      <?php if ($usuario): ?>
        <a href="/cuenta/" class="cuenta-enlace-header">Mi cuenta</a>
      <?php endif; ?>
    </div>
  </header>

  <main class="container article-page cuenta-page">
    <a href="/index.html" class="back-link" data-close-tab>&larr; Volver a Notaría Cabrera</a>
  <?php
}

function pie(): void
{
  ?>
  </main>

  <footer class="site-footer">
    <div class="container">
      <p>&copy; <span id="year"></span> Segunda Notaría Pública y Conservador de Minas de Ovalle. Todos los derechos reservados.</p>
    </div>
  </footer>

  <script src="/js/script.js?v=52"></script>
</body>
</html>
  <?php
}

function mensajes(array $errores, ?string $exito = null): void
{
  foreach ($errores as $error) echo '<p class="cuenta-aviso cuenta-error">' . e($error) . '</p>';
  if ($exito) echo '<p class="cuenta-aviso cuenta-exito">' . e($exito) . '</p>';
}
