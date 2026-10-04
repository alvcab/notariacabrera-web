"""Genera una página HTML estática por año con el índice minero completo.

Los índices se muestran en registro.html, pero ahí se cargan con JavaScript y Google
casi no los lee. Estas páginas tienen la misma información como texto, para que quien
busque el nombre de una concesión o de una sociedad minera encuentre la notaría.

Uso (desde la raíz del proyecto), cada vez que se actualice assets/data/minero-AAAA.json:
    python3 scripts/generar-indices-mineros.py
"""
import glob
import html
import json
import os
import re

BASE = "https://notariacabrera.cl/"


def numero(valor):
    try:
        return float(str(valor or "0").replace(".", "").replace(",", "."))
    except ValueError:
        return 0


def fecha_orden(texto):
    m = re.match(r"(\d{2})/(\d{2})/(\d{4})", texto or "")
    return (m.group(3), m.group(2), m.group(1)) if m else ("", "", "")


archivos = sorted(glob.glob("assets/data/minero-*.json"), reverse=True)
anios = [re.search(r"(\d{4})", a).group(1) for a in archivos]


def navegacion(actual):
    links = []
    for a in anios:
        if a == actual:
            links.append(f'<span class="indice-anio actual">{a}</span>')
        else:
            links.append(f'<a class="indice-anio" href="indice-minero-{a}.html">{a}</a>')
    return "\n        ".join(links)


for archivo, anio in zip(archivos, anios):
    registros = json.load(open(archivo, encoding="utf-8"))
    registros.sort(key=lambda r: (fecha_orden(r.get("fechadoc")), numero(r.get("Repertorio"))))

    filas = []
    for r in registros:
        fojas = " ".join(x for x in [r.get("Fojas"), r.get("fojasdigito")] if x)
        # Sin la columna Nombre: incluye personas naturales y no queremos que sus nombres
        # queden buscables en Google. La vista interactiva (registro.html) sigue mostrándola.
        celdas = [r.get("fechadoc"), r.get("Registro"), fojas, r.get("Repertorio"), r.get("Materia")]
        filas.append("<tr>" + "".join(f"<td>{html.escape(str(c or ''))}</td>" for c in celdas) + "</tr>")

    url = f"{BASE}indice-minero-{anio}.html"
    titulo = f"Índice minero {anio} — Conservador de Minas de Ovalle"
    descripcion = (f"Índice de inscripciones del Conservador de Minas de Ovalle del año {anio}: "
                   f"{len(registros)} inscripciones de descubrimientos, propiedad, accionistas, hipotecas y prohibiciones.")

    pagina = f"""<!DOCTYPE html>
<html lang="es" data-tema="alternado">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{titulo} | Notaría Cabrera</title>
  <meta name="description" content="{html.escape(descripcion, quote=True)}">
  <link rel="canonical" href="{url}">
  <link rel="icon" type="image/png" href="assets/logo.png">
  <link rel="stylesheet" href="css/style.css?v=60">
  <link rel="stylesheet" href="css/registro.css?v=7">
</head>
<body>
  <!-- Página generada por scripts/generar-indices-mineros.py desde {archivo}. No editar a mano. -->
  <header class="site-header">
    <div class="container header-inner">
      <a href="index.html" class="logo">
        <img src="assets/logo.png" alt="" class="logo-icon">
        Notaría Cabrera
      </a>
    </div>
  </header>

  <main class="container registro-page">
    <a href="index.html#recursos" class="back-link">&larr; Volver a Registros y Transparencia</a>
    <h1>Índice minero {anio}</h1>
    <p class="registro-count">Conservador de Minas de Ovalle · {len(registros)} inscripciones del año {anio}, ordenadas por fecha. Para ver el nombre del titular, buscar y ordenar por columna, use la <a class="contact-link" href="registro.html?tipo=minero&amp;anio={anio}">vista interactiva</a>.</p>
    <nav class="indice-anios" aria-label="Otros años">
        {navegacion(anio)}
    </nav>

    <div class="registro-table-wrap">
      <table class="registro-table indice-tabla">
        <thead><tr><th>Fecha</th><th>Registro</th><th>Fojas</th><th>N° Repertorio</th><th>Materia</th></tr></thead>
        <tbody>
{chr(10).join('          ' + f for f in filas)}
        </tbody>
      </table>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container">
      <p>&copy; <span id="year"></span> Segunda Notaría Pública y Conservador de Minas de Ovalle. Todos los derechos reservados.</p>
    </div>
  </footer>

  <script src="js/script.js?v=46"></script>
</body>
</html>
"""
    salida = f"indice-minero-{anio}.html"
    open(salida, "w", encoding="utf-8").write(pagina)
    print(f"{salida}: {len(registros)} inscripciones")

# Mantener el sitemap al día con una entrada por año
sitemap = open("sitemap.xml", encoding="utf-8").read()
sitemap = re.sub(r"  <url><loc>[^<]*indice-minero-\d{4}\.html</loc></url>\n", "", sitemap)
nuevas = "".join(f"  <url><loc>{BASE}indice-minero-{a}.html</loc></url>\n" for a in anios)
sitemap = sitemap.replace("</urlset>", nuevas + "</urlset>")
open("sitemap.xml", "w", encoding="utf-8").write(sitemap)
print("sitemap.xml actualizado")
