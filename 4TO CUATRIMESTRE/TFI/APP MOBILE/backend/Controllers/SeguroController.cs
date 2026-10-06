using Dapper;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using MySqlConnector;
using UnionDelSur.API.Models;

namespace UnionDelSur.API.Controllers;

[ApiController]
[Route("api/seguros")]
[Authorize]
public class SeguroController : ControllerBase
{
    private readonly IConfiguration _config;
    public SeguroController(IConfiguration config) => _config = config;

    [HttpGet]
    public async Task<IActionResult> GetAll([FromQuery] bool? vencimientoProximo)
    {
        await using var conn = new MySqlConnection(_config.GetConnectionString("Default"));
        var sql = @"
            SELECT s.id, s.jugador_id AS JugadorId, s.numero_poliza AS NumeroPoliza,
                   s.vigente_desde AS VigenteDesde, s.vigente_hasta AS VigenteHasta, s.estado,
                   CONCAT(j.nombre, ' ', j.apellido) AS JugadorNombre, c.nombre AS CategoriaNombre
            FROM seguros s
            JOIN jugadores j ON j.id = s.jugador_id
            JOIN categorias c ON c.id = j.categoria_id
            WHERE 1=1";

        if (vencimientoProximo == true)
            sql += " AND s.vigente_hasta BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 15 DAY)";

        sql += " ORDER BY s.vigente_hasta ASC";
        var seguros = await conn.QueryAsync<Seguro>(sql);
        return Ok(seguros);
    }

    [HttpPost]
    [Authorize(Roles = "admin")]
    public async Task<IActionResult> Create([FromBody] SeguroRequest req)
    {
        await using var conn = new MySqlConnection(_config.GetConnectionString("Default"));
        var id = await conn.ExecuteScalarAsync<int>(@"
            INSERT INTO seguros (jugador_id, numero_poliza, vigente_desde, vigente_hasta, estado)
            VALUES (@JugadorId, @NumeroPoliza, @VigenteDesde, @VigenteHasta, 'vigente');
            SELECT LAST_INSERT_ID();",
            new { req.JugadorId, req.NumeroPoliza, req.VigenteDesde, req.VigenteHasta });
        return CreatedAtAction(null, new { id }, new { id });
    }

    [HttpPut("{id}")]
    [Authorize(Roles = "admin")]
    public async Task<IActionResult> Update(int id, [FromBody] SeguroRequest req)
    {
        await using var conn = new MySqlConnection(_config.GetConnectionString("Default"));
        var estado = req.VigenteHasta < DateTime.Today ? "vencido" : "vigente";
        var rows = await conn.ExecuteAsync(@"
            UPDATE seguros SET numero_poliza = @NumeroPoliza, vigente_desde = @VigenteDesde,
            vigente_hasta = @VigenteHasta, estado = @estado WHERE id = @id",
            new { req.NumeroPoliza, req.VigenteDesde, req.VigenteHasta, estado, id });
        if (rows == 0) return NotFound();
        return NoContent();
    }
}

public record SeguroRequest(int JugadorId, string NumeroPoliza, DateTime VigenteDesde, DateTime VigenteHasta);
