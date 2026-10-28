<?php
// La Declaración de Intereses pide iniciar sesión y queda anotada en "Documentos abiertos".
// Se sigue abriendo como /declaracion-de-intereses.html (el .htaccess la manda aquí). El PDF tampoco se abre
// directo: lo entrega esta misma página con ?pdf=1.
require __DIR__ . '/cuenta/inc/app.php';

$usuario = usuario_actual();
if (!$usuario) {
  redirigir('/cuenta/ingresar.php?volver=' . rawurlencode('/declaracion-de-intereses.html'));
}

if (isset($_GET['pdf'])) {
  $ruta = __DIR__ . '/assets/documents/declaracion-de-intereses-2026.pdf';
  header('Content-Type: application/pdf');
  header('Cache-Control: private, no-store');
  header('Content-Length: ' . filesize($ruta));
  header('Content-Disposition: inline; filename="declaracion-de-intereses-2026.pdf"');
  header('X-Content-Type-Options: nosniff');
  header('X-Robots-Tag: noindex');
  readfile($ruta);
  exit;
}

db()->prepare('INSERT INTO descargas (usuario_id, registro, archivo, ip) VALUES (?, ?, ?, ?)')
  ->execute([$usuario['id'], 'transparencia', 'declaracion-intereses', ip()]);

header('Cache-Control: private, no-store');
header('X-Robots-Tag: noindex');
?>
<!DOCTYPE html>
<html lang="es" data-tema="alternado">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Declaración de Intereses — Notaría Cabrera, Segunda Notaría de Ovalle</title>
  <meta name="description" content="Declaración de intereses del notario Rodrigo Cabrera Albarrán, Segunda Notaría Pública y Conservador de Minas de Ovalle. Información de transparencia.">
  <link rel="canonical" href="https://notariacabrera.cl/declaracion-de-intereses.html">
  <link rel="icon" type="image/png" href="assets/logo-verde.png">
  <link rel="stylesheet" href="css/style.css?v=93">
  <link rel="stylesheet" href="css/registro.css?v=11">
  <style>
    .pdf-frame {
      width: 100%;
      height: 80vh;
      border: 1px solid var(--border);
      border-radius: 8px;
      margin-top: 0.75rem;
    }
  </style>
</head>
<body>
  <header class="site-header">
    <div class="container header-inner">
      <a href="index.html" class="logo">
        <img src="assets/logo-verde.png" alt="" class="logo-icon">
        Notaría Cabrera
      </a>
    </div>
  </header>

  <main class="container article-page">
    <a href="index.html#recursos" class="back-link" data-close-tab>&larr; Volver a Transparencia</a>
    <h1>Declaración de Intereses</h1>
    <p class="registro-count">Declaración de intereses y patrimonio del notario Rodrigo Cabrera Albarrán del 27 de agosto de 2026, publicada en <a href="https://www.infoprobidad.cl/Declaracion/Declaracion?ID=5122624" target="_blank" rel="noopener" class="contact-link">InfoProbidad</a> · <a href="declaracion-de-intereses.php?pdf=1" target="_blank" rel="noopener" class="contact-link">Abrir PDF en otra pestaña</a></p>
    <embed src="declaracion-de-intereses.php?pdf=1" type="application/pdf" class="pdf-frame">
  </main>

  <footer class="site-footer">
    <div class="container">
      <p>&copy; <span id="year"></span> Segunda Notaría Pública y Conservador de Minas de Ovalle. Todos los derechos reservados.</p>
    </div>
  </footer>

  <script src="js/script.js?v=54"></script>
</body>
</html>
