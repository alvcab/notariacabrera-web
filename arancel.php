<?php
// Los aranceles piden iniciar sesión: /aranceles/<tipo> llega aquí (ver .htaccess) y se anota en
// "Documentos abiertos". Son PDF en assets/aranceles/ (el .htaccess no deja abrirlos directo).
require __DIR__ . '/cuenta/inc/app.php';

const ARANCELES = [
  'instrumentos-publicos' => 'instrumentos-publicos-2026.pdf',
  'instrumentos-privados' => 'instrumentos-privados-2026.pdf',
  'minas' => 'conservador-minas.pdf',
];

$tipo = (string) ($_GET['tipo'] ?? '');
if (!isset(ARANCELES[$tipo])) {
  http_response_code(404);
  exit('Arancel no encontrado.');
}

$usuario = usuario_actual();
if (!$usuario) {
  redirigir('/cuenta/ingresar.php?volver=' . rawurlencode("/aranceles/$tipo"));
}

db()->prepare('INSERT INTO descargas (usuario_id, registro, archivo, ip) VALUES (?, ?, ?, ?)')
  ->execute([$usuario['id'], 'aranceles', $tipo, ip()]);

$ruta = __DIR__ . '/assets/aranceles/' . ARANCELES[$tipo];
header('Content-Type: application/pdf');
header('Cache-Control: private, no-store');
header('Content-Length: ' . filesize($ruta));
header('Content-Disposition: inline; filename="arancel-' . ARANCELES[$tipo] . '"');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex');
readfile($ruta);
