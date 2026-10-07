<?php
require __DIR__ . '/inc/app.php';

// Viene de documento.php: recordar el PDF para abrirlo después de ingresar
if (isset($_GET['volver'])) recordar_volver((string) $_GET['volver']);

if (usuario_actual()) redirigir(tomar_volver());

$errores = [];
$email = '';
$sinConfirmar = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = strtolower(trim((string) ($_POST['email'] ?? '')));
  $clave = (string) ($_POST['clave'] ?? '');

  if (!verificar_csrf()) $errores[] = 'La página estuvo abierta mucho rato. Vuelva a enviar el formulario.';
  elseif (demasiados_intentos('ingresar', $email, 5, 15) || demasiados_intentos('ingresar-ip', ip(), 30, 15)) {
    $errores[] = 'Demasiados intentos fallidos. Espere 15 minutos o recupere su contraseña.';
  } else {
    $st = db()->prepare('SELECT id, clave_hash, confirmado_en FROM usuarios WHERE email = ?');
    $st->execute([$email]);
    $usuario = $st->fetch();
    if (!$usuario || !password_verify($clave, $usuario['clave_hash'])) {
      anotar_intento('ingresar', $email);
      anotar_intento('ingresar-ip', ip());
      $errores[] = 'Correo o contraseña incorrectos.';
    } elseif ($usuario['confirmado_en'] === null) {
      $sinConfirmar = true;
    } else {
      if (password_needs_rehash($usuario['clave_hash'], PASSWORD_DEFAULT)) {
        db()->prepare('UPDATE usuarios SET clave_hash = ? WHERE id = ?')
          ->execute([password_hash($clave, PASSWORD_DEFAULT), $usuario['id']]);
      }
      entrar_como((int) $usuario['id']);
      redirigir(tomar_volver());
    }
  }
}

cabecera('Ingresar');
?>
    <h1>Ingresar</h1>
    <?php if (!empty($_SESSION['volver'])): ?>
      <p class="cuenta-intro">Para ver los documentos de las inscripciones debe ingresar con su cuenta. Si no tiene una, <a href="/cuenta/crear.php" class="contact-link">créela aquí</a>; es gratis.</p>
    <?php endif; ?>

    <?php mensajes($errores); ?>
    <?php if ($sinConfirmar): ?>
      <p class="cuenta-aviso cuenta-error">Su cuenta aún no está activada. Abra el enlace que le enviamos por correo, o <a href="/cuenta/reenviar.php" class="contact-link">pida uno nuevo</a>.</p>
    <?php endif; ?>

    <form method="post" class="cuenta-form" novalidate>
      <?= campo_csrf() ?>
      <label>Correo electrónico
        <input type="email" name="email" value="<?= e($email) ?>" autocomplete="email" required>
      </label>
      <label>Contraseña
        <input type="password" name="clave" autocomplete="current-password" required>
      </label>
      <button type="submit" class="cuenta-boton">Ingresar</button>
    </form>

    <p class="cuenta-pie">
      <a href="/cuenta/recuperar.php" class="contact-link">¿Olvidó su contraseña?</a><br>
      ¿No tiene cuenta? <a href="/cuenta/crear.php" class="contact-link">Crear cuenta</a>
    </p>
<?php
pie();
