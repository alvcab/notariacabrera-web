<?php
// Índices públicos leídos en vivo desde la base del Sistema Notaría (sistema.notariacabrera.cl),
// para que lo que se ingresa allá se vea aquí de inmediato. Entrega las filas con los mismos nombres
// de campo que los JSON de assets/data/, que js/registro.js sigue usando de respaldo si esto falla.
//   indices.php?tipo=publicos&anio=2026    indices.php?tipo=minero&anio=2026

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function salir(int $codigo, string $error): void
{
  http_response_code($codigo);
  echo json_encode(['error' => $error]);
  exit;
}

$tipo = (string) ($_GET['tipo'] ?? '');
$anio = filter_var($_GET['anio'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1900, 'max_range' => 2100]]);
if (!in_array($tipo, ['publicos', 'minero'], true) || !$anio) salir(400, 'tipo o año no válido');

// Misma configuración que usa el sistema: domains/sistema.notariacabrera.cl/config.php (fuera de public_html).
// Se puede indicar otra con la variable de entorno SISTEMA_CONFIG (pruebas locales).
$rutaConfig = getenv('SISTEMA_CONFIG') ?: dirname(__DIR__, 2) . '/sistema.notariacabrera.cl/config.php';
$config = is_file($rutaConfig) ? (require $rutaConfig) : null;
$bd = is_array($config) ? ($config['db'] ?? null) : null;
if (!is_array($bd)) salir(503, 'sin configuración');

try {
  $pdo = new PDO("mysql:host={$bd['host']};dbname={$bd['nombre']};charset=utf8mb4", $bd['usuario'], $bd['clave'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ]);

  // Fecha: solo la del documento. Los traspasados del sistema anterior no la tienen (su fecha es la del tipeo): la página muestra "--"
  if ($tipo === 'publicos') {
    $st = $pdo->prepare("SELECT id AS Id, bimestre, materia AS Materia, numero, anio,
                                otorgante1_paterno AS appaterno1, otorgante1_materno AS apmaterno1, otorgante1_nombres AS nom1,
                                otorgante2_paterno AS appaterno2, otorgante2_materno AS apmaterno2, otorgante2_nombres AS nom2,
                                DATE_FORMAT(fecha_documento, '%d/%m/%Y') AS fechaindice
                         FROM publicos_inscripciones WHERE anio = ?");
  } else {
    $st = $pdo->prepare("SELECT id AS Id, DATE_FORMAT(fecha_documento, '%d/%m/%Y') AS fechadoc,
                                UPPER(registro) AS Registro, fojas AS Fojas, fojas_sufijo AS fojasdigito, repertorio AS Repertorio,
                                nombre AS Nombre, materia AS Materia, anio AS aniorepertorio
                         FROM minero_inscripciones WHERE anio = ?");
  }
  $st->execute([$anio]);
  $filas = $st->fetchAll();
} catch (PDOException $e) {
  salir(503, 'base no disponible');
}

// Años que no están en la base: la página usa el JSON de assets/data/
if (!$filas) salir(404, 'sin datos para ese año');

// Todo como texto, igual que en los JSON
foreach ($filas as &$fila) {
  $fila = array_map(fn($v) => (string) ($v ?? ''), $fila);
}
unset($fila);

echo json_encode($filas, JSON_UNESCAPED_UNICODE);
