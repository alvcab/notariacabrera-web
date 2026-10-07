<?php
require __DIR__ . '/inc/app.php';

$usuarioId = revisar_token((string) ($_GET['t'] ?? ''), 'confirmar', true);
if ($usuarioId !== null) {
  db()->prepare('UPDATE usuarios SET confirmado_en = COALESCE(confirmado_en, NOW()) WHERE id = ?')->execute([$usuarioId]);
  entrar_como($usuarioId);
}

cabecera('Confirmar cuenta');
?>
    <h1>Confirmar cuenta</h1>
    <?php if ($usuarioId !== null): ?>
      <p class="cuenta-aviso cuenta-exito">Su cuenta quedó activada y ya ingresó al sitio.</p>
      <p class="cuenta-pie">
        <?php if (!empty($_SESSION['volver'])): ?>
          <a href="<?= e(tomar_volver()) ?>" class="cuenta-boton">Abrir el documento</a>
        <?php else: ?>
          <a href="/index.html#registros" class="cuenta-boton">Ir a los registros</a>
        <?php endif; ?>
      </p>
    <?php else: ?>
      <p class="cuenta-aviso cuenta-error">El enlace no es válido o ya venció (dura 48 horas).</p>
      <p class="cuenta-pie">Si su cuenta ya está activa, <a href="/cuenta/ingresar.php" class="contact-link">ingrese aquí</a>. Si no, <a href="/cuenta/reenviar.php" class="contact-link">pida un enlace nuevo</a>.</p>
    <?php endif; ?>
<?php
pie();
