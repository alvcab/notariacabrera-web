<?php
// Copia de ejemplo de la configuración de las cuentas de usuario.
// En el hosting va en domains/notariacabrera.cl/privado/config.php (fuera de public_html, nunca en git),
// con los datos reales de la base de datos creada en el panel del hosting.
return [
  'db_dsn' => 'mysql:host=localhost;dbname=NOMBRE_DE_LA_BASE;charset=utf8mb4',
  'db_user' => 'USUARIO_DE_LA_BASE',
  'db_pass' => 'CONTRASEÑA_DE_LA_BASE',

  // Carpeta con los PDF de los registros (accionistas/, descubrimientos/, ...), fuera de public_html
  'docs_dir' => __DIR__ . '/documentos',

  'url_base' => 'https://notariacabrera.cl',

  // Remitente de los correos de la cuenta: debe ser un correo del dominio para que no lleguen a spam
  'correo_remitente' => 'no-responder@notariacabrera.cl',
  'correo_nombre' => 'Notaría Cabrera',
  'correo_responder_a' => 'contacto@notariacabrera.cl',
  'correo_modo' => 'mail', // 'archivo' en pruebas locales: escribe los correos en 'correo_archivo'
];
