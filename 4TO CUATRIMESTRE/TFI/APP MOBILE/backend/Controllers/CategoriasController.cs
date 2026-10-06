using Dapper;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using MySqlConnector;
using UnionDelSur.API.Models;

namespace UnionDelSur.API.Controllers;

[ApiController]
[Route("api/categorias")]
[Authorize]
public class CategoriasController : ControllerBase
{
    private readonly IConfiguration _config;
    public CategoriasController(IConfiguration config) => _config = config;

    [HttpGet]
    public async Task<IActionResult> GetAll()
    {
        await using var conn = new MySqlConnection(_config.GetConnectionString("Default"));
        var categorias = await conn.QueryAsync<Categoria>(@"
            SELECT c.id, c.nombre, c.anio_nacimiento_desde AS AnioNacimientoDesde,
                   c.anio_nacimiento_hasta AS AnioNacimientoHasta, c.entrenador_id AS EntrenadorId,
                   c.activo, c.created_at AS CreatedAt,
                   u.nombre AS EntrenadorNombre,
                   (SELECT COUNT(*) FROM jugadores j WHERE j.categoria_id = c.id AND j.activo = 1) AS CantJugadores
            FROM categorias c
            LEFT JOIN usuarios u ON u.id = c.entrenador_id
            WHERE c.activo = 1
            ORDER BY c.nombre");
        return Ok(categorias);
    }

    [HttpGet("{id}")]
    public async Task<IActionResult> GetById(int id)
    {
        await using var conn = new MySqlConnection(_config.GetConnectionString("Default"));
        var categoria = await conn.QueryFirstOrDefaultAsync<Categoria>(@"
            SELECT c.id, c.nombre, c.anio_nacimiento_desde AS AnioNacimientoDesde,
                   c.anio_nacimiento_hasta AS AnioNacimientoHasta, c.entrenador_id AS EntrenadorId,
                   c.activo, u.nombre AS EntrenadorNombre,
                   (SELECT COUNT(*) FROM jugadores j WHERE j.categoria_id = c.id AND j.activo = 1) AS CantJugadores
            FROM categorias c
            LEFT JOIN usuarios u ON u.id = c.entrenador_id
            WHERE c.id = @id", new { id });
        if (categoria == null) return NotFound();
        return Ok(categoria);
    }

    [HttpPost]
    [Authorize(Roles = "admin")]
    public async Task<IActionResult> Create([FromBody] CategoriaRequest req)
    {
        await using var conn = new MySqlConnection(_config.GetConnectionString("Default"));
        var id = await conn.ExecuteScalarAsync<int>(@"
            INSERT INTO categorias (nombre, anio_nacimiento_desde, anio_nacimiento_hasta, entrenador_id)
            VALUES (@Nombre, @AnioDesde, @AnioHasta, @EntrenadorId);
            SELECT LAST_INSERT_ID();",
            new { req.Nombre, req.AnioDesde, req.AnioHasta, req.EntrenadorId });
        return CreatedAtAction(nameof(GetById), new { id }, new { id });
    }

    [HttpPut("{id}")]
    [Authorize(Roles = "admin")]
    public async Task<IActionResult> Update(int id, [FromBody] CategoriaRequest req)
    {
        await using var conn = new MySqlConnection(_config.GetConnectionString("Default"));
        var rows = await conn.ExecuteAsync(@"
            UPDATE categorias SET nombre = @Nombre, anio_nacimiento_desde = @AnioDesde,
            anio_nacimiento_hasta = @AnioHasta, entrenador_id = @EntrenadorId WHERE id = @id",
            new { req.Nombre, req.AnioDesde, req.AnioHasta, req.EntrenadorId, id });
        if (rows == 0) return NotFound();
        return NoContent();
    }

    [HttpDelete("{id}")]
    [Authorize(Roles = "admin")]
    public async Task<IActionResult> Delete(int id)
    {
        await using var conn = new MySqlConnection(_config.GetConnectionString("Default"));
        await conn.ExecuteAsync("UPDATE categorias SET activo = 0 WHERE id = @id", new { id });
        return NoContent();
    }
}

public record CategoriaRequest(string Nombre, int AnioDesde, int AnioHasta, int? EntrenadorId);
