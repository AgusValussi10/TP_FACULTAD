#!/usr/bin/env python3
"""
Métricas de velocidad de entrega a partir del historial de git
(inspiradas en las métricas DORA: frecuencia de despliegue, lead time,
tasa de fallas de cambios) para el Sistema de Gestión.

Uso (desde la raíz del repo):
    python3 "3ER CUATRIMESTRE/METODOLOGIA_DE_SISTEMAS_I/metricas/metricas_git.py" --desde 2026-08-01 --json salida.json

Como Render tiene auto-deploy sobre main, cada push a main es un despliegue;
git no registra los pushes, así que se toma "día con commits en main" como
aproximación de "día con despliegue".
"""
import argparse
import json
import re
import statistics
import subprocess
from collections import Counter, defaultdict
from datetime import datetime, timedelta

# La carpeta se movió el 21/09/2026: antes vivía en la raíz del repo.
RUTAS = ["3ER CUATRIMESTRE/METODOLOGIA_DE_SISTEMAS_I", "METODOLOGIA_DE_SISTEMAS_I"]
ALIAS = {"Fabriziorios": "Fabrizio", "Fabrizio Rios": "Fabrizio",
         "Agustin": "Agustín", "Agustin Valussi": "Agustín"}


def git(*args):
    return subprocess.run(["git", *args], capture_output=True, text=True, check=True).stdout


def commits(desde, first_parent=False):
    fmt = "%H\x1f%P\x1f%an\x1f%aI\x1f%cI\x1f%s"
    args = ["log", f"--since={desde}", f"--format=\x1e{fmt}", "--numstat"]
    if first_parent:
        args.append("--first-parent")
    salida = git(*args, "--", *RUTAS)
    lista = []
    for bloque in salida.split("\x1e")[1:]:
        lineas = bloque.strip("\n").split("\n")
        h, padres, autor, fa, fc, asunto = lineas[0].split("\x1f")
        agregadas = borradas = archivos = 0
        for l in lineas[1:]:
            partes = l.split("\t")
            if len(partes) == 3:
                archivos += 1
                if partes[0] != "-":
                    agregadas += int(partes[0]); borradas += int(partes[1])
        lista.append({
            "hash": h[:7], "padres": padres.split(), "autor": ALIAS.get(autor, autor),
            "fecha_autor": datetime.fromisoformat(fa), "fecha_commit": datetime.fromisoformat(fc),
            "asunto": asunto, "archivos": archivos, "lineas": agregadas + borradas,
        })
    return lista


def tipo(asunto):
    m = re.match(r"(feat|fix|docs|refactor|test|chore|ci)\b", asunto.lower())
    if m:
        return m.group(1)
    if asunto.lower().startswith("merge"):
        return "merge"
    return "sin convención"


def lead_time_prs(desde):
    """Para cada merge: desde el primer commit propio de la rama hasta el merge."""
    resultados = []
    for c in commits(desde, first_parent=True):
        if len(c["padres"]) < 2:
            continue
        propios = git("log", "--format=%aI", f"{c['padres'][0]}..{c['padres'][1]}").split()
        if not propios:
            continue
        primero = min(datetime.fromisoformat(x) for x in propios)
        horas = (c["fecha_commit"] - primero).total_seconds() / 3600
        nro = re.search(r"#(\d+)", c["asunto"])
        resultados.append({"merge": c["hash"], "pr": f"#{nro.group(1)}" if nro else "(sin PR)",
                           "asunto": c["asunto"], "commits": len(propios), "horas": round(horas, 1)})
    return resultados


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--desde", default="2026-08-01")
    ap.add_argument("--json")
    a = ap.parse_args()

    todos = [c for c in commits(a.desde) if len(c["padres"]) < 2]  # sin merges
    main_fp = commits(a.desde, first_parent=True)
    prs = lead_time_prs(a.desde)

    por_dia = Counter(c["fecha_autor"].date().isoformat() for c in todos)
    dias_activos = sorted(por_dia)
    inicio = datetime.fromisoformat(a.desde).date()
    fin = max(c["fecha_autor"].date() for c in todos)
    semanas = max(1, ((fin - inicio).days + 1) / 7)
    dias_main = sorted({c["fecha_commit"].date().isoformat() for c in main_fp})

    tipos = Counter(tipo(c["asunto"]) for c in todos)
    lineas = [c["lineas"] for c in todos]
    archivos = [c["archivos"] for c in todos]

    # Retrabajo: fix que llega dentro de las 48 h siguientes a un feat.
    feats = [c for c in todos if tipo(c["asunto"]) == "feat"]
    fixes = [c for c in todos if tipo(c["asunto"]) == "fix"]
    fix_48 = [f for f in fixes if any(timedelta(0) < f["fecha_autor"] - ft["fecha_autor"] <= timedelta(hours=48) for ft in feats)]

    # Cuello de botella: commits empujados a main directo (sin PR / sin revisión).
    directos = [c for c in main_fp if len(c["padres"]) < 2]

    dia_pico, n_pico = por_dia.most_common(1)[0]
    res = {
        "periodo": {"desde": a.desde, "hasta": fin.isoformat(), "semanas": round(semanas, 1)},
        "commits_total": len(todos),
        "commits_por_autor": dict(Counter(c["autor"] for c in todos)),
        "dias_con_commits": len(dias_activos),
        "dias_con_cambios_en_main": len(dias_main),
        "despliegues_por_semana_aprox": round(len(dias_main) / semanas, 2),
        "dia_pico": {"fecha": dia_pico, "commits": n_pico, "porcentaje": round(100 * n_pico / len(todos), 1)},
        "commits_por_dia": dict(sorted(por_dia.items())),
        "tipos": dict(tipos),
        "tamano_commit": {"lineas_mediana": statistics.median(lineas), "lineas_max": max(lineas),
                          "archivos_mediana": statistics.median(archivos), "archivos_max": max(archivos),
                          "commits_mas_500_lineas": sum(1 for x in lineas if x > 500)},
        "retrabajo": {"feat": len(feats), "fix": len(fixes), "fix_dentro_48h_de_un_feat": len(fix_48),
                      "tasa_fix_sobre_feat": round(len(fixes) / max(1, len(feats)), 2)},
        "integracion": {"merges_en_main": len(prs), "commits_directos_a_main": len(directos),
                        "porcentaje_directo": round(100 * len(directos) / max(1, len(main_fp)), 1)},
        "prs": prs,
        "lead_time_pr_horas": {"mediana": statistics.median([p["horas"] for p in prs]) if prs else None,
                               "max": max([p["horas"] for p in prs]) if prs else None},
        "mayores_commits": sorted(({"hash": c["hash"], "asunto": c["asunto"][:70], "lineas": c["lineas"],
                                    "archivos": c["archivos"]} for c in todos), key=lambda x: -x["lineas"])[:5],
    }

    print(f"Período {res['periodo']['desde']} → {res['periodo']['hasta']} ({res['periodo']['semanas']} semanas)")
    print(f"Commits: {res['commits_total']}  por autor: {res['commits_por_autor']}")
    print(f"Días con commits: {res['dias_con_commits']}  ·  días con cambios en main (≈deploys): {res['dias_con_cambios_en_main']}"
          f"  ·  {res['despliegues_por_semana_aprox']}/semana")
    print(f"Día pico: {dia_pico} con {n_pico} commits ({res['dia_pico']['porcentaje']} % del total)")
    print(f"Tipos: {res['tipos']}")
    print(f"Tamaño de commit: mediana {res['tamano_commit']['lineas_mediana']} líneas, "
          f"máx {res['tamano_commit']['lineas_max']}; >500 líneas: {res['tamano_commit']['commits_mas_500_lineas']}")
    print(f"Retrabajo: {res['retrabajo']}")
    print(f"Integración: {res['integracion']}")
    print(f"Lead time PR (h): {res['lead_time_pr_horas']}")
    for p in prs:
        print(f"   {p['pr']:9} {p['commits']:3} commits  {p['horas']:7.1f} h  {p['asunto'][:60]}")
    if a.json:
        with open(a.json, "w", encoding="utf-8") as f:
            json.dump(res, f, ensure_ascii=False, indent=2, default=str)


if __name__ == "__main__":
    main()
