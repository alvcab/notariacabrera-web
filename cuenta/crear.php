<?php
require __DIR__ . '/inc/app.php';

if (usuario_actual()) redirigir('/cuenta/');

$errores = [];
$exito = null;
$datos = ['nombre' => '', 'rut' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $datos['nombre'] = trim(preg_replace('/\s+/u', ' ', (string) ($_POST['nombre'] ?? '')));
  $datos['rut'] = trim((string) ($_POST['rut'] ?? ''));
  $datos['email'] = strtolower(trim((string) ($_POST['email'] ?? '')));
  $clave = (string) ($_POST['clave'] ?? '');
  $clave2 = (string) ($_POST['clave2'] ?? '');
  $rut = normalizar_rut($datos['rut']);

  if (!verificar_csrf()) $errores[] = 'La página estuvo abierta mucho rato. Vuelva a enviar el formulario.';
  elseif (demasiados_intentos('crear', ip(), 10, 60)) $errores[] = 'Demasiados intentos desde esta conexión. Intente de nuevo en una hora.';
  else {
    if (mb_strlen($datos['nombre']) < 3 || mb_strlen($datos['nombre']) > 150) $errores[] = 'Ingrese su nombre completo.';
    if ($rut === null) $errores[] = 'El RUT no es válido. Revise el número y el dígito verificador.';
    if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL) || strlen($datos['email']) > 190) $errores[] = 'El correo no es válido.';
    if (strlen($clave) < 8) $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
    elseif ($clave !== $clave2) $errores[] = 'Las contraseñas no coinciden.';
    if (empty($_POST['acepto'])) $errores[] = 'Debe aceptar las condiciones de uso.';
  }

  if (!$errores) {
    anotar_intento('crear', ip());
    $st = db()->prepare('SELECT email, rut, confirmado_en FROM usuarios WHERE email = ? OR rut = ?');
    $st->execute([$datos['email'], $rut]);
    $existentes = $st->fetchAll();
    if ($existentes) {
      $errores[] = 'Ya existe una cuenta con ese correo o RUT. Puede ingresar o recuperar su contraseña.';
    } else {
      db()->prepare('INSERT INTO usuarios (nombre, rut, email, clave_hash) VALUES (?, ?, ?, ?)')
        ->execute([$datos['nombre'], $rut, $datos['email'], password_hash($clave, PASSWORD_DEFAULT)]);
      $id = (int) db()->lastInsertId();
      if (enviar_confirmacion($id, $datos['nombre'], $datos['email'])) {
        $exito = 'Cuenta creada. Le enviamos un correo a ' . $datos['email'] . ' con un enlace para activarla. Si no lo ve, revise la carpeta de spam.';
      } else {
        $errores[] = 'La cuenta se creó, pero no pudimos enviar el correo de confirmación. Escríbanos a contacto@notariacabrera.cl.';
      }
    }
  }
}

cabecera('Crear cuenta');
?>
    <h1>Crear cuenta</h1>
    <p class="cuenta-intro">Con una cuenta puede ver los documentos de las inscripciones del Conservador de Minas. Es gratis.</p>

    <?php mensajes($errores, $exito); ?>

    <?php if (!$exito): ?>
    <form method="post" class="cuenta-form" novalidate>
      <?= campo_csrf() ?>
      <label>Nombre completo
        <input type="text" name="nombre" value="<?= e($datos['nombre']) ?>" autocomplete="name" required maxlength="150">
      </label>
      <label>RUT
        <input type="text" name="rut" value="<?= e($datos['rut']) ?>" placeholder="12.345.678-9" required maxlength="14">
      </label>
      <label>Correo electrónico
        <input type="email" name="email" value="<?= e($datos['email']) ?>" autocomplete="email" required maxlength="190">
      </label>
      <label>Contraseña <span class="cuenta-ayuda">(mínimo 8 caracteres)</span>
        <input type="password" name="clave" autocomplete="new-password" required minlength="8">
      </label>
      <label>Repita la contraseña
        <input type="password" name="clave2" autocomplete="new-password" required minlength="8">
      </label>
      <label class="cuenta-check">
        <input type="checkbox" name="acepto" value="1" <?= !empty($_POST['acepto']) ? 'checked' : '' ?>>
        <span>Acepto las <a href="/cuenta/condiciones.php" target="_blank" class="contact-link">condiciones de uso y el tratamiento de mis datos</a>.</span>
      </label>
      <button type="submit" class="cuenta-boton">Crear cuenta</button>
    </form>
    <?php endif; ?>

    <p class="cuenta-pie">¿Ya tiene cuenta? <a href="/cuenta/ingresar.php" class="contact-link">Ingresar</a></p>
<?php
pie();
