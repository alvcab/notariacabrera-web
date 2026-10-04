"""Agrega a cada documento de los registros mineros el nombre de la concesión (Materia).

Los botones de descubrimientos.html, propiedad.html, etc. decían solo "N° 1 — Fojas 1 vta.".
Este script busca cada documento en el índice minero del año (por registro + fojas) y agrega
debajo la materia, por ejemplo "ACTA DE MENSURA Y SENTENCIA CONSTITUTIVA VIENTO 16 1/200".
No agrega nombres de personas. Se puede correr varias veces: reemplaza lo que había.

Uso (desde la raíz del proyecto):
    python3 scripts/agregar-materia-registros.py
"""
import html
import json
import re

PAGINAS = {
    "descubrimientos.html": "DESCUBRIMIENTOS",
    "propiedad.html": "PROPIEDAD",
    "accionistas.html": "ACCIONISTAS",
    "hipotecas.html": "HIPOTECAS Y GRAVÁMENES",
    "prohibiciones.html": "PROHIBICIONES E INTERDICCIONES",
}

ENLACE = re.compile(
    r'(<a href="assets/documents/[^/]+/n(\d+)-fs(\d+)(-vta)?\.pdf"[^>]*class="resource-link[^"]*">)'
    r'(N° \d+ — Fojas [^<]*?)(?:<span class="doc-materia">.*?</span>)?(</a>)'
)


# El N° del documento es el número de repertorio; se une por repertorio + fojas, porque
# hay inscripciones distintas en las mismas fojas.
def clave(repertorio, fojas, vuelta):
    return (str(int(repertorio)), str(int(fojas)), bool(vuelta))


for pagina, registro in PAGINAS.items():
    texto = open(pagina, encoding="utf-8").read()
    anio = re.search(r"año (\d{4})", texto).group(1)
    indice = json.load(open(f"assets/data/minero-{anio}.json", encoding="utf-8"))

    materias = {}
    for r in indice:
        if r.get("Registro") != registro or not str(r.get("Fojas", "")).isdigit() or not str(r.get("Repertorio", "")).isdigit():
            continue
        k = clave(r["Repertorio"], r["Fojas"], (r.get("fojasdigito") or "").upper().startswith("V"))
        materias.setdefault(k, r.get("Materia", "").strip())

    encontrados = faltantes = 0

    def reemplazar(m):
        global encontrados, faltantes
        materia = materias.get(clave(m.group(2), m.group(3), m.group(4)))
        if materia:
            encontrados += 1
            extra = f'<span class="doc-materia">{html.escape(materia)}</span>'
        else:
            faltantes += 1
            extra = ""
        return f"{m.group(1)}{m.group(5)}{extra}{m.group(6)}"

    texto = ENLACE.sub(reemplazar, texto)
    open(pagina, "w", encoding="utf-8").write(texto)
    print(f"{pagina}: {encontrados} con materia, {faltantes} sin coincidencia en el índice {anio}")
