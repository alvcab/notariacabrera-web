<?php
require __DIR__ . '/inc/app.php';

$usuario = usuario_actual();
if (!$usuario) redirigir('/cuenta/ingresar.php');

cabecera('Mi cuenta');
?>
    <h1>Mi cuenta</h1>
    <dl class="cuenta-datos">
      <dt>Nombre</dt><dd><?= e($usuario['nombre']) ?></dd>
      <dt>RUT</dt><dd><?= e(formatear_rut($usuario['rut'])) ?></dd>
      <dt>Correo</dt><dd><?= e($usuario['email']) ?></dd>
    </dl>
    <p class="cuenta-intro">Con su cuenta puede abrir los documentos de los registros del Conservador de Minas.</p>
    <p class="cuenta-pie">
      <a href="/index.html#registros" class="cuenta-boton">Ir a los registros</a>
      <?php if (es_admin($usuario)): ?>
        <a href="/cuenta/admin.php" class="cuenta-boton cuenta-boton-secundario">Administración</a>
      <?php endif; ?>
    </p>

    <form method="post" action="/cuenta/salir.php" class="cuenta-salir">
      <?= campo_csrf() ?>
      <button type="submit" class="cuenta-boton cuenta-boton-secundario">Cerrar sesión</button>
    </form>
    <p class="cuenta-pie cuenta-ayuda">Para cambiar sus datos o eliminar su cuenta, escriba a <a href="mailto:contacto@notariacabrera.cl" class="contact-link">contacto@notariacabrera.cl</a>.</p>
<?php
pie();
