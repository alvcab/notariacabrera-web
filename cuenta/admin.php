<?php
// Administración: usuarios registrados y documentos abiertos. Solo para correos de la tabla administradores.
require __DIR__ . '/inc/app.php';

$usuario = usuario_actual();
if (!$usuario) {
  recordar_volver('/cuenta/admin.php');
  redirigir('/cuenta/ingresar.php');
}
if (!es_admin($usuario)) {
  http_response_code(403);
  cabecera('Acceso denegado');
  echo '<h1>Acceso denegado</h1><p class="cuenta-intro">Su cuenta no tiene permiso para ver esta página.</p>';
  pie();
  exit;
}

$vista = ($_GET['vista'] ?? '') === 'descargas' ? 'descargas' : 'usuarios';
$q = trim((string) ($_GET['q'] ?? ''));
$like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
// El RUT está guardado como 12345678-9: se compara sin puntos ni guion
$rut = strtoupper(preg_replace('/[^0-9kK]/', '', $q));
$rutLike = $rut === '' ? $like : "%$rut%";
$limite = 500;

if ($vista === 'usuarios') {
  $sql = "SELECT u.nombre, u.rut, u.email, u.creado_en, u.confirmado_en, u.ultimo_ingreso,
                 (SELECT COUNT(*) FROM descargas d WHERE d.usuario_id = u.id) AS documentos
          FROM usuarios u
          WHERE ? = '' OR u.nombre LIKE ? OR u.email LIKE ? OR REPLACE(u.rut, '-', '') LIKE ?
          ORDER BY u.creado_en DESC";
  $params = [$q, $like, $like, $rutLike];
  $columnas = ['Nombre', 'RUT', 'Correo', 'Registro', 'Activada', 'Último ingreso', 'Documentos abiertos'];
} else {
  $sql = "SELECT d.fecha, u.nombre, u.rut, u.email, d.registro, d.archivo, d.ip
          FROM descargas d JOIN usuarios u ON u.id = d.usuario_id
          WHERE ? = '' OR u.nombre LIKE ? OR u.email LIKE ? OR REPLACE(u.rut, '-', '') LIKE ? OR d.archivo LIKE ? OR d.registro LIKE ?
          ORDER BY d.fecha DESC";
  $params = [$q, $like, $like, $rutLike, $like, $like];
  $columnas = ['Fecha', 'Nombre', 'RUT', 'Correo', 'Registro', 'Documento', 'IP'];
}

function fila(string $vista, array $f): array
{
  return $vista === 'usuarios'
    ? [$f['nombre'], formatear_rut($f['rut']), $f['email'], $f['creado_en'], $f['confirmado_en'] ? 'Sí' : 'No', $f['ultimo_ingreso'] ?? '', $f['documentos']]
    : [$f['fecha'], $f['nombre'], formatear_rut($f['rut']), $f['email'], $f['registro'], $f['archivo'], $f['ip']];
}

// Descarga para Excel: CSV con punto y coma (Excel en español) y BOM para que lea bien las tildes
if (isset($_GET['exportar'])) {
  $st = db()->prepare($sql);
  $st->execute($params);
  header('Content-Type: text/csv; charset=UTF-8');
  header('Content-Disposition: attachment; filename="' . $vista . '-' . date('Y-m-d') . '.csv"');
  header('Cache-Control: private, no-store');
  $salida = fopen('php://output', 'w');
  fwrite($salida, "\xEF\xBB\xBF");
  fputcsv($salida, $columnas, ';');
  while ($f = $st->fetch()) fputcsv($salida, fila($vista, $f), ';');
  exit;
}

$st = db()->prepare($sql . " LIMIT $limite");
$st->execute($params);
$filas = $st->fetchAll();

$totales = db()->query("SELECT
    (SELECT COUNT(*) FROM usuarios) AS usuarios,
    (SELECT COUNT(*) FROM usuarios WHERE confirmado_en IS NOT NULL) AS activos,
    (SELECT COUNT(*) FROM descargas WHERE fecha > DATE_SUB(NOW(), INTERVAL 30 DAY)) AS descargas30")->fetch();

cabecera('Administración');
?>
    <h1>Administración</h1>

    <div class="admin-totales">
      <div><strong><?= (int) $totales['usuarios'] ?></strong><span>usuarios registrados</span></div>
      <div><strong><?= (int) $totales['activos'] ?></strong><span>con la cuenta activada</span></div>
      <div><strong><?= (int) $totales['descargas30'] ?></strong><span>documentos abiertos en 30 días</span></div>
    </div>

    <nav class="admin-pestanas">
      <a href="?vista=usuarios" class="<?= $vista === 'usuarios' ? 'activa' : '' ?>">Usuarios</a>
      <a href="?vista=descargas" class="<?= $vista === 'descargas' ? 'activa' : '' ?>">Documentos abiertos</a>
    </nav>

    <form method="get" class="admin-buscar">
      <input type="hidden" name="vista" value="<?= e($vista) ?>">
      <input type="search" name="q" value="<?= e($q) ?>" class="registro-search" placeholder="<?= $vista === 'usuarios' ? 'Buscar por nombre, RUT o correo' : 'Buscar por usuario o documento' ?>">
      <button type="submit" class="cuenta-boton">Buscar</button>
      <a href="?vista=<?= e($vista) ?>&amp;q=<?= e(rawurlencode($q)) ?>&amp;exportar=1" class="cuenta-boton cuenta-boton-secundario">Descargar Excel</a>
    </form>

    <p class="registro-count">
      <?= count($filas) ?> resultado<?= count($filas) === 1 ? '' : 's' ?><?= count($filas) === $limite ? " (se muestran los $limite más recientes; el Excel trae todos)" : '' ?>
    </p>

    <div class="admin-tabla-wrap">
      <table class="admin-tabla">
        <thead><tr><?php foreach ($columnas as $c) echo '<th>' . e($c) . '</th>'; ?></tr></thead>
        <tbody>
          <?php foreach ($filas as $f): ?>
            <tr><?php foreach (fila($vista, $f) as $v) echo '<td>' . e((string) $v) . '</td>'; ?></tr>
          <?php endforeach; ?>
          <?php if (!$filas): ?>
            <tr><td colspan="<?= count($columnas) ?>">No hay resultados.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
<?php
pie();
