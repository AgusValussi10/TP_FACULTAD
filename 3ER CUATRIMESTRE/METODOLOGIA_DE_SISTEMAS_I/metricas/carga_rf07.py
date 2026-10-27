#!/usr/bin/env python3
"""
Prueba de carga de la funcionalidad RFG07 (consultas sobre notas).

Simula lo que hace el navegador:
  - Padre: al abrir el boletín, portal_padre.php pide EN PARALELO
    boletin_listar.php y consulta_nota_listar.php (mismo usuario, misma sesión).
  - Docente: abre gestion/consultas_notas.php → consulta_nota_listar.php.

Cada usuario virtual tiene su propia sesión. Mide la latencia de cada pedido
y el "tiempo de pantalla" del padre (hasta que llegan las dos respuestas).

Uso: python3 metricas/carga_rf07.py --url http://127.0.0.1:8080 --usuarios 1,10,25 --rondas 20 --json res.json
"""
import argparse
import json
import statistics
import threading
import time
import urllib.parse
import urllib.request
from concurrent.futures import ThreadPoolExecutor

PADRES = [("carga.padre%d" % i, "padre123") for i in range(1, 151)]
DOCENTES = [("maria.rodriguez", "docente123"), ("roberto.silva", "docente456"),
            ("patricia.aguirre", "docente789"), ("ernesto.castillo", "docente321")]


class Navegador:
    def __init__(self, base):
        self.base = base
        self.cookie = ""

    def pedir(self, ruta, datos=None):
        req = urllib.request.Request(self.base + ruta,
                                     data=urllib.parse.urlencode(datos).encode() if datos else None)
        if self.cookie:
            req.add_header("Cookie", self.cookie)
        t = time.perf_counter()
        with urllib.request.urlopen(req, timeout=120) as r:
            cuerpo = r.read()
            for h in r.headers.get_all("Set-Cookie") or []:
                if h.startswith("PHPSESSID="):
                    self.cookie = h.split(";")[0]
        return (time.perf_counter() - t) * 1000, cuerpo

    def login(self, usuario, clave, rol):
        _, cuerpo = self.pedir("/auth/login.php", {"usuario": usuario, "password": clave, "rol": rol})
        assert json.loads(cuerpo)["success"], cuerpo


def pct(xs, p):
    xs = sorted(xs)
    return xs[min(len(xs) - 1, int(round(p / 100 * (len(xs) - 1))))]


def resumen(xs):
    return {"n": len(xs), "media": round(statistics.mean(xs), 1), "p50": round(pct(xs, 50), 1),
            "p95": round(pct(xs, 95), 1), "max": round(max(xs), 1)}


def escenario(base, n_usuarios, rondas, hijos):
    """n_usuarios padres + docentes concurrentes, cada uno hace `rondas` aperturas."""
    padres, docentes = [], []
    for i in range(n_usuarios):
        u, c = PADRES[i % len(PADRES)]
        nav = Navegador(base); nav.login(u, c, "Padres / Tutores"); padres.append((nav, hijos[u]))
    for i in range(max(1, n_usuarios // 5)):  # 1 docente cada 5 padres
        u, c = DOCENTES[i % len(DOCENTES)]
        nav = Navegador(base); nav.login(u, c, "Docentes / Personal / Autoridades"); docentes.append(nav)

    lat = {"boletin": [], "consultas_padre": [], "pantalla_padre": [], "consultas_docente": []}
    lock = threading.Lock()
    pool_interno = ThreadPoolExecutor(max_workers=2 * n_usuarios + 4)

    def usuario_padre(nav, hijo):
        for _ in range(rondas):
            t = time.perf_counter()
            f1 = pool_interno.submit(nav.pedir, f"/gestion/boletin_listar.php?alumno_id={hijo}")
            f2 = pool_interno.submit(nav.pedir, f"/gestion/consulta_nota_listar.php?alumno_id={hijo}")
            a, b = f1.result()[0], f2.result()[0]
            total = (time.perf_counter() - t) * 1000
            with lock:
                lat["boletin"].append(a); lat["consultas_padre"].append(b); lat["pantalla_padre"].append(total)

    def usuario_docente(nav):
        for _ in range(rondas):
            ms, _ = nav.pedir("/gestion/consulta_nota_listar.php")
            with lock:
                lat["consultas_docente"].append(ms)

    t0 = time.perf_counter()
    with ThreadPoolExecutor(max_workers=len(padres) + len(docentes)) as ex:
        futs = [ex.submit(usuario_padre, n, h) for n, h in padres] + [ex.submit(usuario_docente, d) for d in docentes]
        for f in futs:
            f.result()
    dur = time.perf_counter() - t0
    pool_interno.shutdown()
    pedidos = sum(len(v) for k, v in lat.items() if k != "pantalla_padre")
    return {"usuarios_padre": len(padres), "usuarios_docente": len(docentes), "duracion_s": round(dur, 2),
            "pedidos": pedidos, "pedidos_por_s": round(pedidos / dur, 1),
            **{k: resumen(v) for k, v in lat.items()}}


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--url", default="http://127.0.0.1:8080")
    ap.add_argument("--usuarios", default="1,10,25")
    ap.add_argument("--rondas", type=int, default=20)
    ap.add_argument("--hijos", required=True, help="JSON {usuario_padre: alumno_id}")
    ap.add_argument("--json")
    a = ap.parse_args()
    hijos = json.load(open(a.hijos))

    res = {}
    for n in [int(x) for x in a.usuarios.split(",")]:
        r = escenario(a.url, n, a.rondas, hijos)
        res[str(n)] = r
        print(f"{n:3} padres + {r['usuarios_docente']} doc · {r['pedidos_por_s']:6.1f} ped/s · "
              f"pantalla padre p50 {r['pantalla_padre']['p50']:7.1f} ms p95 {r['pantalla_padre']['p95']:7.1f} ms · "
              f"docente p50 {r['consultas_docente']['p50']:7.1f} ms p95 {r['consultas_docente']['p95']:7.1f} ms")
    if a.json:
        json.dump(res, open(a.json, "w"), indent=2)


if __name__ == "__main__":
    main()
