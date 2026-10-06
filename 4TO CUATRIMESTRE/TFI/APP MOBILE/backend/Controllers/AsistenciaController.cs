using Dapper;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using MySqlConnector;
using UnionDelSur.API.Models;

namespace UnionDelSur.API.Controllers;

[ApiController]
[Route("api/asistencia")]
[Authorize]
public class AsistenciaController : ControllerBase
{
    private readonly IConfiguration _config;
    public AsistenciaController(IConfiguration config) => _config = config;

    [HttpGet]
    public async Task<IActionResult> GetByFecha([FromQuery] int categoriaId, [FromQuery] string fecha)
    {
        await using var conn = new MySqlConnection(_config.GetConnectionString("Default"));

        // Get all active players in category
        var jugadores = await conn.QueryAsync<dynamic>(@"
            SELECT j.id, CONCAT(j.nombre, ' ', j.apellido) AS nombre
            FROM jugadores j WHERE j.categoria_id = @categoriaId AND j.activo = 1
            ORDER BY j.apellido, j.nombre", new { categoriaId });

        // Get existing attendance records
        var asistencias = await conn.QueryAsync<dynamic>(@"
            SELECT jugador_id AS jugadorId, presente
            FROM asistencias WHERE categoria_id = @categoriaId AND fecha = @fecha",
            new { categoriaId, fecha });

        var asistenciaMap = asistencias.ToDictionary(a => (int)a.jugadorId, a => (bool)a.presente);

        var resultado = jugadores.Select(j => new
        {
            jugadorId = (int)j.id,
            jugadorNombre = (string)j.nombre,
            presente = asistenciaMap.TryGetValue((int)j.id, out var p) ? p : (bool?)null
        });

        return Ok(resultado);
    }

    [HttpPost]
    public async Task<IActionResult> Guardar([FromBody] GuardarAsistenciaRequest req)
    {
        await using var conn = new MySqlConnection(_config.GetConnectionString("Default"));
        foreach (var r in req.Registros)
        {
            await conn.ExecuteAsync(@"
                INSERT INTO asistencias (jugador_id, categoria_id, fecha, presente)
                VALUES (@JugadorId, @CategoriaId, @Fecha, @Presente)
                ON DUPLICATE KEY UPDATE presente = @Presente",
                new { r.JugadorId, req.CategoriaId, req.Fecha, r.Presente });
        }
        return Ok(new { message = "Asistencia guardada" });
    }
}

public record RegistroAsistencia(int JugadorId, bool Presente);
public record GuardarAsistenciaRequest(int CategoriaId, string Fecha, List<RegistroAsistencia> Registros);
