using Dapper;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using MySqlConnector;
using UnionDelSur.API.Models;

namespace UnionDelSur.API.Controllers;

[ApiController]
[Route("api/carnets")]
[Authorize]
public class CarnetController : ControllerBase
{
    private readonly IConfiguration _config;
    public CarnetController(IConfiguration config) => _config = config;

    [HttpGet]
    public async Task<IActionResult> GetAll([FromQuery] string? temporada)
    {
        await using var conn = new MySqlConnection(_config.GetConnectionString("Default"));
        var sql = @"
            SELECT cl.id, cl.jugador_id AS JugadorId, cl.numero_carnet AS NumeroCarnet,
                   cl.temporada, cl.estado, cl.fecha_emision AS FechaEmision,
                   cl.fecha_vencimiento AS FechaVencimiento,
                   CONCAT(j.nombre, ' ', j.apellido) AS JugadorNombre, c.nombre AS CategoriaNombre
            FROM carnets_liga cl
            JOIN jugadores j ON j.id = cl.jugador_id
            JOIN categorias c ON c.id = j.categoria_id
            WHERE 1=1";
        if (!string.IsNullOrEmpty(temporada)) sql += " AND cl.temporada = @temporada";
        sql += " ORDER BY j.apellido, j.nombre";

        var carnets = await conn.QueryAsync<CarnetLiga>(sql, new { temporada });
        return Ok(carnets);
    }

    [HttpPost]
    [Authorize(Roles = "admin")]
    public async Task<IActionResult> Create([FromBody] CarnetRequest req)
    {
        await using var conn = new MySqlConnection(_config.GetConnectionString("Default"));
        var id = await conn.ExecuteScalarAsync<int>(@"
            INSERT INTO carnets_liga (jugador_id, numero_carnet, temporada, estado, fecha_emision, fecha_vencimiento)
            VALUES (@JugadorId, @NumeroCarnet, @Temporada, @Estado, @FechaEmision, @FechaVencimiento);
            SELECT LAST_INSERT_ID();", req);
        return CreatedAtAction(null, new { id }, new { id });
    }

    [HttpPut("{id}")]
    [Authorize(Roles = "admin")]
    public async Task<IActionResult> Update(int id, [FromBody] CarnetRequest req)
    {
        await using var conn = new MySqlConnection(_config.GetConnectionString("Default"));
        var rows = await conn.ExecuteAsync(@"
            UPDATE carnets_liga SET numero_carnet = @NumeroCarnet, temporada = @Temporada,
            estado = @Estado, fecha_emision = @FechaEmision, fecha_vencimiento = @FechaVencimiento
            WHERE id = @id",
            new { req.NumeroCarnet, req.Temporada, req.Estado, req.FechaEmision, req.FechaVencimiento, id });
        if (rows == 0) return NotFound();
        return NoContent();
    }
}

public record CarnetRequest(int JugadorId, string NumeroCarnet, string Temporada, string Estado,
    DateTime? FechaEmision, DateTime? FechaVencimiento);
