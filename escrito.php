<?php
// Escrito "Se tenga presente" con el audio de la audiencia: pide iniciar sesión. Los archivos no están en el
// repositorio (es público): se suben a mano a domains/notariacabrera.cl/privado/escrito/ (escrito.html, escrito.docx, audio.m4a).
// En el texto, los botones "minuto N" llevan el audio a ese minuto.
require __DIR__ . '/cuenta/inc/app.php';

const ARCHIVOS_ESCRITO = [
  'audio' => ['audio.m4a', 'audio/mp4', 'audiencia.m4a'],
  'docx' => ['escrito.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'se-tenga-presente.docx'],
];

$usuario = usuario_actual();
if (!$usuario) {
  redirigir('/cuenta/ingresar.php?volver=' . rawurlencode('/escrito.php'));
}

$carpeta = dirname(rtrim(config('docs_dir'), '/')) . '/escrito';
$archivo = (string) ($_GET['archivo'] ?? '');

if ($archivo !== '') {
  if (!isset(ARCHIVOS_ESCRITO[$archivo]) || !is_file($ruta = $carpeta . '/' . ARCHIVOS_ESCRITO[$archivo][0])) {
    http_response_code(404);
    exit('Archivo no encontrado.');
  }
  [, $tipo, $nombre] = ARCHIVOS_ESCRITO[$archivo];
  $tamano = filesize($ruta);
  $inicio = 0;
  $fin = $tamano - 1;

  // El reproductor pide el audio por partes (Range) para poder saltar a un minuto sin bajarlo entero
  if (isset($_SERVER['HTTP_RANGE']) && preg_match('/^bytes=(\d*)-(\d*)$/', $_SERVER['HTTP_RANGE'], $m) && ($m[1] !== '' || $m[2] !== '')) {
    if ($m[1] === '') {
      $inicio = max(0, $tamano - (int) $m[2]);
    } else {
      $inicio = (int) $m[1];
      if ($m[2] !== '') $fin = min((int) $m[2], $tamano - 1);
    }
    if ($inicio > $fin) {
      http_response_code(416);
      header("Content-Range: bytes */$tamano");
      exit;
    }
    http_response_code(206);
    header("Content-Range: bytes $inicio-$fin/$tamano");
  }

  header('Content-Type: ' . $tipo);
  header('Accept-Ranges: bytes');
  header('Content-Length: ' . ($fin - $inicio + 1));
  header('Content-Disposition: ' . ($archivo === 'docx' ? 'attachment' : 'inline') . '; filename="' . $nombre . '"');
  header('Cache-Control: private, no-store');
  header('X-Content-Type-Options: nosniff');
  header('X-Robots-Tag: noindex');

  $f = fopen($ruta, 'rb');
  fseek($f, $inicio);
  $quedan = $fin - $inicio + 1;
  while ($quedan > 0 && !feof($f)) {
    $trozo = fread($f, min(65536, $quedan));
    echo $trozo;
    $quedan -= strlen($trozo);
    flush();
  }
  fclose($f);
  exit;
}

$texto = is_file($carpeta . '/escrito.html') ? file_get_contents($carpeta . '/escrito.html') : null;
if ($texto === null) {
  http_response_code(404);
  exit('Documento no encontrado.');
}

db()->prepare('INSERT INTO descargas (usuario_id, registro, archivo, ip) VALUES (?, ?, ?, ?)')
  ->execute([$usuario['id'], 'escrito', 'se-tenga-presente', ip()]);

cabecera('Se tenga presente');
?>
    <div class="escrito">
      <div class="escrito-audio">
        <p>Audio de la audiencia</p>
        <audio id="audio-audiencia" controls preload="metadata" src="/escrito.php?archivo=audio"></audio>
      </div>

      <div class="cuenta-texto escrito-texto">
        <?= $texto ?>
      </div>

      <p class="cuenta-pie"><a href="/escrito.php?archivo=docx" class="contact-link">Descargar el escrito en Word</a></p>
    </div>

    <script>
      // Cada botón "minuto N" lleva el audio a ese minuto y lo reproduce
      const audio = document.getElementById('audio-audiencia');
      document.querySelectorAll('.escrito .minuto').forEach((boton) => {
        boton.addEventListener('click', () => {
          audio.currentTime = Number(boton.dataset.segundo);
          audio.play();
          audio.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        });
      });
    </script>
<?php
pie();
