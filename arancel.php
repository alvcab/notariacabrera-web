<?php
// Los aranceles piden iniciar sesión: /aranceles/<tipo> llega aquí (ver .htaccess) y se anota en
// "Documentos abiertos". Los de la notaría son PDF en assets/aranceles/ (el .htaccess no deja abrirlos
// directo); el del Conservador de Minas lleva a la ley en la Biblioteca del Congreso.
require __DIR__ . '/cuenta/inc/app.php';

const ARANCELES = [
  'instrumentos-publicos' => 'instrumentos-publicos-2026.pdf',
  'instrumentos-privados' => 'instrumentos-privados-2026.pdf',
  'minas' => 'https://www.bcn.cl/leychile/navegar?i=127924&f=1998-12-03',
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

header('Cache-Control: private, no-store');
if (strpos(ARANCELES[$tipo], 'https://') === 0) {
  redirigir(ARANCELES[$tipo]);
}

$ruta = __DIR__ . '/assets/aranceles/' . ARANCELES[$tipo];
header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($ruta));
header('Content-Disposition: inline; filename="arancel-' . ARANCELES[$tipo] . '"');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex');
readfile($ruta);
