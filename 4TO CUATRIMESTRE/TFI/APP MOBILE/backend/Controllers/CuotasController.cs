using Dapper;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using MySqlConnector;
using System.Security.Claims;
using UnionDelSur.API.Models;

namespace UnionDelSur.API.Controllers;

[ApiController]
[Route("api/cuotas")]
[Authorize]
public class CuotasController : ControllerBase
{
    private readonly IConfiguration _config;
    public CuotasController(IConfiguration config) => _config = config;

    [HttpGet]
    public async Task<IActionResult> GetAll([FromQuery] int? mes, [FromQuery] int? anio, [FromQuery] int? categoriaId)
    {
        await using var conn = new MySqlConnection(_config.GetConnectionString("Default"));
        var sql = @"
            SELECT cu.id, cu.jugador_id AS JugadorId, cu.periodo_mes AS PeriodoMes,
                   cu.periodo_anio AS PeriodoAnio, cu.monto, cu.descuento_hermanos AS DescuentoHermanos,
                   cu.estado, cu.fecha_vencimiento AS FechaVencimiento,
                   CONCAT(j.nombre, ' ', j.apellido) AS JugadorNombre, c.nombre AS CategoriaNombre
            FROM cuotas cu
            JOIN jugadores j ON j.id = cu.jugador_id
            JOIN categorias c ON c.id = j.categoria_id
            WHERE 1=1";
        if (mes.HasValue) sql += " AND cu.periodo_mes = @mes";
        if (anio.HasValue) sql += " AND cu.periodo_anio = @anio";
        if (categoriaId.HasValue) sql += " AND j.categoria_id = @categoriaId";
        sql += " ORDER BY j.apellido, j.nombre";

        var cuotas = await conn.QueryAsync<Cuota>(sql, new { mes, anio, categoriaId });
        return Ok(cuotas);
    }

    [HttpPost("generar")]
    [Authorize(Roles = "admin")]
    public async Task<IActionResult> Generar([FromBody] GenerarCuotasRequest req)
    {
        await using var conn = new MySqlConnection(_config.GetConnectionString("Default"));
        var jugadores = await conn.QueryAsync<dynamic>(
            "SELECT id, grupo_familiar_id AS GrupoFamiliarId FROM jugadores WHERE activo = 1");

        int generadas = 0;
        foreach (var j in jugadores)
        {
            // Check hermanos discount (10% if group has >1 active player)
            decimal descuento = 0;
            if (j.GrupoFamiliarId != null)
            {
                var hermanos = await conn.ExecuteScalarAsync<int>(
                    "SELECT COUNT(*) FROM jugadores WHERE grupo_familiar_id = @gf AND activo = 1 AND id != @jid",
                    new { gf = j.GrupoFamiliarId, jid = j.id });
                if (hermanos > 0) descuento = req.Monto * 0.10m;
            }

            var fechaVenc = new DateTime(req.Anio, req.Mes, 10);
            try
            {
                await conn.ExecuteAsync(@"
                    INSERT IGNORE INTO cuotas (jugador_id, periodo_mes, periodo_anio, monto, descuento_hermanos, fecha_vencimiento)
                    VALUES (@JugadorId, @Mes, @Anio, @Monto, @Descuento, @FechaVenc)",
                    new { JugadorId = j.id, req.Mes, req.Anio, req.Monto, Descuento = descuento, FechaVenc = fechaVenc });
                generadas++;
            }
            catch { /* skip duplicates */ }
        }

        return Ok(new { generadas, message = $"Se generaron {generadas} cuotas para {req.Mes}/{req.Anio}" });
    }

    [HttpPost("{id}/pagar")]
    [Authorize(Roles = "admin")]
    public async Task<IActionResult> Pagar(int id, [FromBody] PagarCuotaRequest req)
    {
        await using var conn = new MySqlConnection(_config.GetConnectionString("Default"));
        var cuota = await conn.QueryFirstOrDefaultAsync<Cuota>(
            "SELECT id, estado FROM cuotas WHERE id = @id", new { id });
        if (cuota == null) return NotFound();
        if (cuota.Estado == "pagada") return BadRequest(new { message = "La cuota ya fue pagada" });

        var userId = int.TryParse(User.FindFirstValue(ClaimTypes.NameIdentifier), out var uid) ? (int?)uid : null;

        await conn.ExecuteAsync(@"
            INSERT INTO pagos (cuota_id, monto_pagado, metodo_pago, registrado_por_id)
            VALUES (@id, @Monto, @Metodo, @UserId)",
            new { id, req.Monto, req.Metodo, UserId = userId });

        await conn.ExecuteAsync(
            "UPDATE cuotas SET estado = 'pagada' WHERE id = @id", new { id });

        return Ok(new { message = "Pago registrado correctamente" });
    }
}

public record GenerarCuotasRequest(int Mes, int Anio, decimal Monto);
public record PagarCuotaRequest(decimal Monto, string Metodo);
