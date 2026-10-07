<?php
require __DIR__ . '/inc/app.php';

$token = (string) ($_GET['t'] ?? $_POST['t'] ?? '');
$valido = revisar_token($token, 'restablecer', false) !== null;
$errores = [];
$exito = null;

if ($valido && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $clave = (string) ($_POST['clave'] ?? '');
  if (!verificar_csrf()) $errores[] = 'La página estuvo abierta mucho rato. Vuelva a enviar el formulario.';
  elseif (strlen($clave) < 8) $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
  elseif ($clave !== (string) ($_POST['clave2'] ?? '')) $errores[] = 'Las contraseñas no coinciden.';
  else {
    $usuarioId = revisar_token($token, 'restablecer', true);
    if ($usuarioId === null) {
      $valido = false;
    } else {
      // Abrir el enlace del correo también demuestra que el correo es suyo: activa la cuenta si faltaba
      db()->prepare('UPDATE usuarios SET clave_hash = ?, confirmado_en = COALESCE(confirmado_en, NOW()) WHERE id = ?')
        ->execute([password_hash($clave, PASSWORD_DEFAULT), $usuarioId]);
      entrar_como($usuarioId);
      $exito = 'Su contraseña quedó cambiada y ya ingresó al sitio.';
    }
  }
}

cabecera('Nueva contraseña');
?>
    <h1>Elegir nueva contraseña</h1>
    <?php mensajes($errores, $exito); ?>
    <?php if ($exito): ?>
      <p class="cuenta-pie"><a href="/index.html#registros" class="cuenta-boton">Ir a los registros</a></p>
    <?php elseif (!$valido): ?>
      <p class="cuenta-aviso cuenta-error">El enlace no es válido o ya venció (dura 1 hora).</p>
      <p class="cuenta-pie"><a href="/cuenta/recuperar.php" class="contact-link">Pedir un enlace nuevo</a></p>
    <?php else: ?>
    <form method="post" class="cuenta-form" novalidate>
      <?= campo_csrf() ?>
      <input type="hidden" name="t" value="<?= e($token) ?>">
      <label>Nueva contraseña <span class="cuenta-ayuda">(mínimo 8 caracteres)</span>
        <input type="password" name="clave" autocomplete="new-password" required minlength="8">
      </label>
      <label>Repita la contraseña
        <input type="password" name="clave2" autocomplete="new-password" required minlength="8">
      </label>
      <button type="submit" class="cuenta-boton">Guardar contraseña</button>
    </form>
    <?php endif; ?>
<?php
pie();
