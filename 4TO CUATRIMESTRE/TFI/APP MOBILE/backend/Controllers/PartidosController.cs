using Dapper;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using MySqlConnector;
using UnionDelSur.API.Models;

namespace UnionDelSur.API.Controllers;

[ApiController]
[Route("api/partidos")]
[Authorize]
public class PartidosController : ControllerBase
{
    private readonly IConfiguration _config;
    public PartidosController(IConfiguration config) => _config = config;

    [HttpGet]
    public async Task<IActionResult> GetAll([FromQuery] int? categoriaId, [FromQuery] string? estado)
    {
        await using var conn = new MySqlConnection(_config.GetConnectionString("Default"));
        var sql = @"
            SELECT p.id, p.categoria_id AS CategoriaId, p.rival, p.fecha, p.hora,
                   p.lugar, p.resultado_local AS ResultadoLocal,
                   p.resultado_visitante AS ResultadoVisitante, p.estado,
                   c.nombre AS CategoriaNombre
            FROM partidos p
            JOIN categorias c ON c.id = p.categoria_id
            WHERE 1=1";
        if (categoriaId.HasValue) sql += " AND p.categoria_id = @categoriaId";
        if (!string.IsNullOrEmpty(estado)) sql += " AND p.estado = @estado";
        sql += " ORDER BY p.fecha DESC";

        var partidos = await conn.QueryAsync<Partido>(sql, new { categoriaId, estado });
        return Ok(partidos);
    }

    [HttpPost]
    [Authorize(Roles = "admin,entrenador")]
    public async Task<IActionResult> Create([FromBody] PartidoRequest req)
    {
        await using var conn = new MySqlConnection(_config.GetConnectionString("Default"));
        var id = await conn.ExecuteScalarAsync<int>(@"
            INSERT INTO partidos (categoria_id, rival, fecha, hora, lugar)
            VALUES (@CategoriaId, @Rival, @Fecha, @Hora, @Lugar);
            SELECT LAST_INSERT_ID();", req);
        return CreatedAtAction(nameof(GetAll), new { }, new { id });
    }

    [HttpPut("{id}/resultado")]
    [Authorize(Roles = "admin,entrenador")]
    public async Task<IActionResult> CargarResultado(int id, [FromBody] ResultadoRequest req)
    {
        await using var conn = new MySqlConnection(_config.GetConnectionString("Default"));
        var rows = await conn.ExecuteAsync(@"
            UPDATE partidos SET resultado_local = @Local, resultado_visitante = @Visitante,
            estado = 'jugado' WHERE id = @id",
            new { req.Local, req.Visitante, id });
        if (rows == 0) return NotFound();

        if (req.Goleadores?.Count > 0)
        {
            await conn.ExecuteAsync("DELETE FROM goleadores WHERE partido_id = @id", new { id });
            foreach (var g in req.Goleadores)
            {
                await conn.ExecuteAsync(
                    "INSERT INTO goleadores (partido_id, jugador_id, cantidad) VALUES (@id, @JugadorId, @Cantidad)",
                    new { id, g.JugadorId, g.Cantidad });
            }
        }

        return NoContent();
    }
}

public record PartidoRequest(int CategoriaId, string Rival, DateTime Fecha, string Hora, string Lugar);
public record GoleadorRequest(int JugadorId, int Cantidad);
public record ResultadoRequest(int Local, int Visitante, List<GoleadorRequest>? Goleadores);
