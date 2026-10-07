<?php
require __DIR__ . '/inc/app.php';

// Solo por POST con token, para que un enlace externo no pueda cerrar la sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verificar_csrf()) {
  $_SESSION = [];
  session_destroy();
  setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => '/']);
}
redirigir('/index.html');
