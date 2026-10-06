using Dapper;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using MySqlConnector;

namespace UnionDelSur.API.Controllers;

[ApiController]
[Route("api/reportes")]
[Authorize(Roles = "admin")]
public class ReportesController : ControllerBase
{
    private readonly IConfiguration _config;
    public ReportesController(IConfiguration config) => _config = config;

    [HttpGet("dashboard")]
    public async Task<IActionResult> Dashboard()
    {
        await using var conn = new MySqlConnection(_config.GetConnectionString("Default"));

        var totalJugadores = await conn.ExecuteScalarAsync<int>(
            "SELECT COUNT(*) FROM jugadores WHERE activo = 1");

        var cuotasPendientes = await conn.ExecuteScalarAsync<int>(
            "SELECT COUNT(*) FROM cuotas WHERE estado = 'pendiente'");

        var jugadoresSinSeguro = await conn.ExecuteScalarAsync<int>(@"
            SELECT COUNT(*) FROM jugadores j WHERE j.activo = 1
            AND NOT EXISTS (
                SELECT 1 FROM seguros s WHERE s.jugador_id = j.id AND s.estado = 'vigente'
            )");

        var proximoPartido = await conn.QueryFirstOrDefaultAsync<dynamic>(@"
            SELECT p.rival, p.fecha, p.hora, p.lugar, c.nombre AS categoria
            FROM partidos p JOIN categorias c ON c.id = p.categoria_id
            WHERE p.estado = 'programado' AND p.fecha >= CURDATE()
            ORDER BY p.fecha ASC LIMIT 1");

        var morosidadPorCategoria = await conn.QueryAsync<dynamic>(@"
            SELECT c.nombre AS categoria,
                   COUNT(DISTINCT j.id) AS totalJugadores,
                   COUNT(DISTINCT CASE WHEN cu.estado = 'pendiente' THEN j.id END) AS morosos
            FROM categorias c
            JOIN jugadores j ON j.categoria_id = c.id AND j.activo = 1
            LEFT JOIN cuotas cu ON cu.jugador_id = j.id AND cu.estado = 'pendiente'
            WHERE c.activo = 1
            GROUP BY c.id, c.nombre
            ORDER BY c.nombre");

        var ultimosPartidos = await conn.QueryAsync<dynamic>(@"
            SELECT p.rival, p.resultado_local AS local, p.resultado_visitante AS visitante,
                   p.fecha, c.nombre AS categoria, p.lugar
            FROM partidos p JOIN categorias c ON c.id = p.categoria_id
            WHERE p.estado = 'jugado'
            ORDER BY p.fecha DESC LIMIT 5");

        return Ok(new
        {
            totalJugadores,
            cuotasPendientes,
            jugadoresSinSeguro,
            proximoPartido,
            morosidadPorCategoria,
            ultimosPartidos
        });
    }

    [HttpGet("morosidad")]
    public async Task<IActionResult> Morosidad()
    {
        await using var conn = new MySqlConnection(_config.GetConnectionString("Default"));
        var morosos = await conn.QueryAsync<dynamic>(@"
            SELECT CONCAT(j.nombre, ' ', j.apellido) AS jugador,
                   c.nombre AS categoria,
                   g.nombre_contacto AS contacto, g.telefono,
                   COUNT(cu.id) AS cuotasPendientes,
                   SUM(cu.monto - cu.descuento_hermanos) AS totalDeuda
            FROM jugadores j
            JOIN cuotas cu ON cu.jugador_id = j.id AND cu.estado = 'pendiente'
            JOIN categorias c ON c.id = j.categoria_id
            LEFT JOIN grupos_familiares g ON g.id = j.grupo_familiar_id
            WHERE j.activo = 1
            GROUP BY j.id, j.nombre, j.apellido, c.nombre, g.nombre_contacto, g.telefono
            ORDER BY totalDeuda DESC");
        return Ok(morosos);
    }
}
