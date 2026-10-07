<?php
require __DIR__ . '/inc/app.php';

$errores = [];
$exito = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = strtolower(trim((string) ($_POST['email'] ?? '')));
  if (!verificar_csrf()) $errores[] = 'La página estuvo abierta mucho rato. Vuelva a enviar el formulario.';
  elseif (demasiados_intentos('recuperar', $email, 3, 60) || demasiados_intentos('recuperar-ip', ip(), 10, 60)) {
    $errores[] = 'Ya pidió varios correos. Espere una hora antes de pedir otro.';
  } else {
    anotar_intento('recuperar', $email);
    anotar_intento('recuperar-ip', ip());
    $st = db()->prepare('SELECT id, nombre FROM usuarios WHERE email = ?');
    $st->execute([$email]);
    if ($usuario = $st->fetch()) {
      $enlace = rtrim(config('url_base'), '/') . '/cuenta/restablecer.php?t=' . crear_token((int) $usuario['id'], 'restablecer', 1);
      enviar_correo($email, 'Restablecer contraseña — Notaría Cabrera', <<<TXT
        Hola, {$usuario['nombre']}:

        Para elegir una nueva contraseña, abra este enlace:

        $enlace

        El enlace vence en 1 hora. Si usted no lo pidió, ignore este correo; su contraseña no cambiará.

        Segunda Notaría Pública y Conservador de Minas de Ovalle
        TXT);
    }
    // Mismo mensaje exista o no la cuenta, para no revelar qué correos están registrados
    $exito = 'Si hay una cuenta con ese correo, le enviamos un enlace para elegir una nueva contraseña. Revise también la carpeta de spam.';
  }
}

cabecera('Recuperar contraseña');
?>
    <h1>Recuperar contraseña</h1>
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
