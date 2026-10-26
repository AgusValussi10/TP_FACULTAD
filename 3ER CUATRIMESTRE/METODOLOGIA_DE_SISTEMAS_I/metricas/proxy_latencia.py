#!/usr/bin/env python3
"""
Proxy TCP que agrega una demora fija en cada sentido, para simular que la base
de datos está en otro proveedor (como Filess.io respecto de Render).
Uso: python3 metricas/proxy_latencia.py --escuchar 3307 --destino 127.0.0.1:3306 --ms 10
     (10 ms por sentido ≈ 20 ms de ida y vuelta)
"""
import argparse
import asyncio


async def tubo(lector, escritor, demora):
    try:
        while True:
            datos = await lector.read(65536)
            if not datos:
                break
            await asyncio.sleep(demora)
            escritor.write(datos)
            await escritor.drain()
    except (ConnectionResetError, BrokenPipeError):
        pass
    finally:
        escritor.close()


async def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--escuchar", type=int, default=3307)
    ap.add_argument("--destino", default="127.0.0.1:3306")
    ap.add_argument("--ms", type=float, default=10)
    a = ap.parse_args()
    host, puerto = a.destino.split(":")
    demora = a.ms / 1000

    async def cliente(lc, ec):
        ls, es = await asyncio.open_connection(host, int(puerto))
        await asyncio.gather(tubo(lc, es, demora), tubo(ls, ec, demora))

    srv = await asyncio.start_server(cliente, "127.0.0.1", a.escuchar)
    async with srv:
        await srv.serve_forever()


if __name__ == "__main__":
    asyncio.run(main())
