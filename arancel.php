<?php
// Los aranceles piden iniciar sesión: /aranceles/<tipo> llega aquí (ver .htaccess),
// se anota en "Documentos abiertos" y se lleva a la ley en la Biblioteca del Congreso.
require __DIR__ . '/cuenta/inc/app.php';

const ARANCELES = [
  'notarios' => 'https://www.bcn.cl/leychile/navegar?idNorma=127917&idParte=',
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
redirigir(ARANCELES[$tipo]);
