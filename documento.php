<?php
// Entrega los PDF de los registros del Conservador solo a usuarios con sesión iniciada.
// Los enlaces de las páginas siguen apuntando a assets/documents/<registro>/<archivo>.pdf;
// el .htaccess los manda aquí. Los PDF están fuera de public_html (config 'docs_dir').
require __DIR__ . '/cuenta/inc/app.php';

$registro = (string) ($_GET['registro'] ?? '');
$archivo = (string) ($_GET['archivo'] ?? '');

if (!in_array($registro, REGISTROS_PROTEGIDOS, true) || !preg_match('/^[a-z0-9-]+\.pdf$/', $archivo)) {
  http_response_code(404);
  exit('Documento no encontrado.');
}

$usuario = usuario_actual();
if (!$usuario) {
  redirigir('/cuenta/ingresar.php?volver=' . rawurlencode("/assets/documents/$registro/$archivo"));
}

$ruta = rtrim(config('docs_dir'), '/') . "/$registro/$archivo";
if (!is_file($ruta)) {
  http_response_code(404);
  exit('Documento no encontrado.');
}

db()->prepare('INSERT INTO descargas (usuario_id, registro, archivo, ip) VALUES (?, ?, ?, ?)')
  ->execute([$usuario['id'], $registro, $archivo, ip()]);

header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($ruta));
header('Content-Disposition: inline; filename="' . $registro . '-' . $archivo . '"');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex');
readfile($ruta);
