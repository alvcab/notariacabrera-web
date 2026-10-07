<?php
require __DIR__ . '/inc/app.php';

$errores = [];
$exito = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = strtolower(trim((string) ($_POST['email'] ?? '')));
  if (!verificar_csrf()) $errores[] = 'La página estuvo abierta mucho rato. Vuelva a enviar el formulario.';
  elseif (demasiados_intentos('reenviar', $email, 3, 60) || demasiados_intentos('reenviar-ip', ip(), 10, 60)) {
    $errores[] = 'Ya pidió varios correos. Espere una hora antes de pedir otro.';
  } else {
    anotar_intento('reenviar', $email);
    anotar_intento('reenviar-ip', ip());
    $st = db()->prepare('SELECT id, nombre FROM usuarios WHERE email = ? AND confirmado_en IS NULL');
    $st->execute([$email]);
    if ($usuario = $st->fetch()) enviar_confirmacion((int) $usuario['id'], $usuario['nombre'], $email);
    // Mismo mensaje exista o no la cuenta, para no revelar qué correos están registrados
    $exito = 'Si hay una cuenta sin activar con ese correo, le enviamos un nuevo enlace. Revise también la carpeta de spam.';
  }
}

cabecera('Reenviar confirmación');
?>
    <h1>Reenviar correo de confirmación</h1>
    <?php mensajes($errores, $exito); ?>
    <?php if (!$exito): ?>
    <form method="post" class="cuenta-form" novalidate>
      <?= campo_csrf() ?>
      <label>Correo con el que creó la cuenta
        <input type="email" name="email" autocomplete="email" required>
      </label>
      <button type="submit" class="cuenta-boton">Enviar enlace</button>
    </form>
    <?php endif; ?>
    <p class="cuenta-pie"><a href="/cuenta/ingresar.php" class="contact-link">Volver a Ingresar</a></p>
<?php
pie();
