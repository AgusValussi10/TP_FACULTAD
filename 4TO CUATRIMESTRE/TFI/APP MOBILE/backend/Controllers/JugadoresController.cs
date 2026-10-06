using Dapper;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using MySqlConnector;
using UnionDelSur.API.Models;

namespace UnionDelSur.API.Controllers;

[ApiController]
[Route("api/jugadores")]
[Authorize]
public class JugadoresController : ControllerBase
{
    private readonly IConfiguration _config;
    public JugadoresController(IConfiguration config) => _config = config;

    [HttpGet]
    public async Task<IActionResult> GetAll([FromQuery] int? categoriaId, [FromQuery] string? busqueda)
    {
        await using var conn = new MySqlConnection(_config.GetConnectionString("Default"));
        var sql = @"
            SELECT j.id, j.nombre, j.apellido, j.dni, j.fecha_nacimiento AS FechaNacimiento,
                   j.categoria_id AS CategoriaId, j.grupo_familiar_id AS GrupoFamiliarId,
                   j.activo, j.fecha_alta AS FechaAlta,
                   c.nombre AS CategoriaNombre,
                   g.nombre_contacto AS GrupoFamiliarContacto,
                   g.telefono AS GrupoFamiliarTelefono, g.email AS GrupoFamiliarEmail,
                   (SELECT COUNT(*) FROM jugadores h WHERE h.grupo_familiar_id = j.grupo_familiar_id
                      AND h.id <> j.id AND h.activo = 1) AS CantHermanos
            FROM jugadores j
            LEFT JOIN categorias c ON c.id = j.categoria_id
            LEFT JOIN grupos_familiares g ON g.id = j.grupo_familiar_id
            WHERE j.activo = 1";

        if (categoriaId.HasValue) sql += " AND j.categoria_id = @categoriaId";
        if (!string.IsNullOrWhiteSpace(busqueda))
            sql += " AND (j.nombre LIKE @busq OR j.apellido LIKE @busq OR j.dni LIKE @busq)";
        sql += " ORDER BY j.apellido, j.nombre";

        var jugadores = await conn.QueryAsync<Jugador>(sql,
            new { categoriaId, busq = $"%{busqueda}%" });
        return Ok(jugadores);
    }

    [HttpGet("{id}")]
    public async Task<IActionResult> GetById(int id)
    {
        await using var conn = new MySqlConnection(_config.GetConnectionString("Default"));

        var jugador = await conn.QueryFirstOrDefaultAsync<Jugador>(@"
            SELECT j.id, j.nombre, j.apellido, j.dni, j.fecha_nacimiento AS FechaNacimiento,
                   j.categoria_id AS CategoriaId, j.grupo_familiar_id AS GrupoFamiliarId,
                   j.activo, j.fecha_alta AS FechaAlta,
                   c.nombre AS CategoriaNombre,
                   g.nombre_contacto AS GrupoFamiliarContacto,
                   g.telefono AS GrupoFamiliarTelefono, g.email AS GrupoFamiliarEmail
            FROM jugadores j
            LEFT JOIN categorias c ON c.id = j.categoria_id
            LEFT JOIN grupos_familiares g ON g.id = j.grupo_familiar_id
            WHERE j.id = @id", new { id });

        if (jugador == null) return NotFound();

        var cuotas = await conn.QueryAsync<Cuota>(@"
            SELECT id, jugador_id AS JugadorId, periodo_mes AS PeriodoMes, periodo_anio AS PeriodoAnio,
                   monto, descuento_hermanos AS DescuentoHermanos, estado, fecha_vencimiento AS FechaVencimiento
            FROM cuotas WHERE jugador_id = @id ORDER BY periodo_anio DESC, periodo_mes DESC LIMIT 12", new { id });

        var seguro = await conn.QueryFirstOrDefaultAsync<Seguro>(@"
            SELECT id, jugador_id AS JugadorId, numero_poliza AS NumeroPoliza,
                   vigente_desde AS VigenteDesde, vigente_hasta AS VigenteHasta, estado
            FROM seguros WHERE jugador_id = @id ORDER BY id DESC LIMIT 1", new { id });

        var carnet = await conn.QueryFirstOrDefaultAsync<CarnetLiga>(@"
            SELECT id, jugador_id AS JugadorId, numero_carnet AS NumeroCarnet, temporada,
                   estado, fecha_emision AS FechaEmision, fecha_vencimiento AS FechaVencimiento
            FROM carnets_liga WHERE jugador_id = @id ORDER BY id DESC LIMIT 1", new { id });

        var asistencias = await conn.QueryAsync<Asistencia>(@"
            SELECT id, jugador_id AS JugadorId, categoria_id AS CategoriaId,
                   fecha, presente, observaciones
            FROM asistencias WHERE jugador_id = @id ORDER BY fecha DESC LIMIT 5", new { id });

        var hermanos = jugador.GrupoFamiliarId == null
            ? Enumerable.Empty<Jugador>()
            : await conn.QueryAsync<Jugador>(@"
                SELECT j.id, j.nombre, j.apellido, j.dni, j.fecha_nacimiento AS FechaNacimiento,
                       j.categoria_id AS CategoriaId, c.nombre AS CategoriaNombre
                FROM jugadores j LEFT JOIN categorias c ON c.id = j.categoria_id
                WHERE j.grupo_familiar_id = @g AND j.id <> @id AND j.activo = 1
                ORDER BY j.fecha_nacimiento", new { g = jugador.GrupoFamiliarId, id });

        return Ok(new { jugador, cuotas, seguro, carnet, asistencias, hermanos });
    }

    // Grupos familiares existentes con sus integrantes, para vincular un hermano nuevo.
    [HttpGet("grupos")]
    public async Task<IActionResult> GetGrupos([FromQuery] string? busqueda)
    {
        await using var conn = new MySqlConnection(_config.GetConnectionString("Default"));
        var rows = await conn.QueryAsync<GrupoFamiliarResumen>(@"
            SELECT g.id AS GrupoId, g.nombre_contacto AS NombreContacto, g.telefono AS Telefono, g.email AS Email,
                   GROUP_CONCAT(CONCAT(j.apellido, ', ', j.nombre) ORDER BY j.fecha_nacimiento SEPARATOR ' · ') AS Integrantes
            FROM grupos_familiares g
            JOIN jugadores j ON j.grupo_familiar_id = g.id AND j.activo = 1
            WHERE (@b IS NULL OR g.nombre_contacto LIKE @like OR j.apellido LIKE @like OR j.nombre LIKE @like OR j.dni LIKE @like)
            GROUP BY g.id, g.nombre_contacto, g.telefono, g.email
            ORDER BY g.nombre_contacto LIMIT 30",
            new { b = string.IsNullOrWhiteSpace(busqueda) ? null : busqueda, like = $"%{busqueda}%" });
        return Ok(rows);
    }

    [HttpPost]
    [Authorize(Roles = "admin")]
    public async Task<IActionResult> Create([FromBody] JugadorCreateRequest req)
    {
        await using var conn = new MySqlConnection(_config.GetConnectionString("Default"));
        await conn.OpenAsync();

        var errorEdad = await ValidarEdadCategoria(conn, req.CategoriaId, req.FechaNacimiento);
        if (errorEdad != null) return BadRequest(new { message = errorEdad });

        await using var tx = await conn.BeginTransactionAsync();

        try
        {
            int? grupoId = req.GrupoFamiliarId;
            if (grupoId == null && req.GrupoFamiliar != null)
            {
                grupoId = await conn.ExecuteScalarAsync<int>(@"
                    INSERT INTO grupos_familiares (nombre_contacto, telefono, email)
                    VALUES (@NombreContacto, @Telefono, @Email);
                    SELECT LAST_INSERT_ID();",
                    req.GrupoFamiliar, transaction: tx);
            }

            var jugadorId = await conn.ExecuteScalarAsync<int>(@"
                INSERT INTO jugadores (nombre, apellido, dni, fecha_nacimiento, categoria_id, grupo_familiar_id)
                VALUES (@Nombre, @Apellido, @Dni, @FechaNacimiento, @CategoriaId, @GrupoId);
                SELECT LAST_INSERT_ID();",
                new { req.Nombre, req.Apellido, req.Dni, req.FechaNacimiento, req.CategoriaId, GrupoId = grupoId },
                transaction: tx);

            await tx.CommitAsync();
            return CreatedAtAction(nameof(GetById), new { id = jugadorId }, new { id = jugadorId });
        }
        catch (MySqlException ex) when (ex.ErrorCode == MySqlErrorCode.DuplicateKeyEntry)
        {
            await tx.RollbackAsync();
            return BadRequest(new { message = "Ya existe un jugador con ese DNI" });
        }
        catch
        {
            await tx.RollbackAsync();
            return StatusCode(500, new { message = "Error al crear el jugador" });
        }
    }

    [HttpPut("{id}")]
    [Authorize(Roles = "admin")]
    public async Task<IActionResult> Update(int id, [FromBody] JugadorUpdateRequest req)
    {
        await using var conn = new MySqlConnection(_config.GetConnectionString("Default"));

        var errorEdad = await ValidarEdadCategoria(conn, req.CategoriaId, req.FechaNacimiento);
        if (errorEdad != null) return BadRequest(new { message = errorEdad });

        await conn.OpenAsync();
        await using var tx = await conn.BeginTransactionAsync();
        try
        {
            int? grupoId = req.GrupoFamiliarId;
            if (grupoId == null && req.GrupoFamiliar != null)
            {
                grupoId = await conn.ExecuteScalarAsync<int>(@"
                    INSERT INTO grupos_familiares (nombre_contacto, telefono, email)
                    VALUES (@NombreContacto, @Telefono, @Email);
                    SELECT LAST_INSERT_ID();", req.GrupoFamiliar, transaction: tx);
            }

            var rows = await conn.ExecuteAsync(@"
                UPDATE jugadores SET nombre = @Nombre, apellido = @Apellido, dni = @Dni,
                fecha_nacimiento = @FechaNacimiento, categoria_id = @CategoriaId,
                grupo_familiar_id = COALESCE(@GrupoId, grupo_familiar_id) WHERE id = @id",
                new { req.Nombre, req.Apellido, req.Dni, req.FechaNacimiento, req.CategoriaId, GrupoId = grupoId, id },
                transaction: tx);
            if (rows == 0) { await tx.RollbackAsync(); return NotFound(); }
            await tx.CommitAsync();
            return NoContent();
        }
        catch (MySqlException ex) when (ex.ErrorCode == MySqlErrorCode.DuplicateKeyEntry)
        {
            await tx.RollbackAsync();
            return BadRequest(new { message = "Ya existe un jugador con ese DNI" });
        }
        catch
        {
            await tx.RollbackAsync();
            return StatusCode(500, new { message = "Error al actualizar el jugador" });
        }
    }

    // Devuelve un mensaje de error si el año de nacimiento no entra en el rango de la categoría.
    private static async Task<string?> ValidarEdadCategoria(MySqlConnection conn, int categoriaId, DateTime fechaNacimiento)
    {
        var cat = await conn.QueryFirstOrDefaultAsync<Categoria>(@"
            SELECT id, nombre, anio_nacimiento_desde AS AnioNacimientoDesde,
                   anio_nacimiento_hasta AS AnioNacimientoHasta
            FROM categorias WHERE id = @categoriaId AND activo = 1", new { categoriaId });

        if (cat == null) return "La categoría seleccionada no existe";

        var anio = fechaNacimiento.Year;
        if (anio < cat.AnioNacimientoDesde || anio > cat.AnioNacimientoHasta)
            return $"El jugador nació en {anio} y la categoría {cat.Nombre} admite nacidos entre {cat.AnioNacimientoDesde} y {cat.AnioNacimientoHasta}";

        return null;
    }

    [HttpDelete("{id}")]
    [Authorize(Roles = "admin")]
    public async Task<IActionResult> Delete(int id)
    {
        await using var conn = new MySqlConnection(_config.GetConnectionString("Default"));
        await conn.ExecuteAsync("UPDATE jugadores SET activo = 0 WHERE id = @id", new { id });
        return NoContent();
    }
}

public record GrupoFamiliarRequest(string NombreContacto, string Telefono, string Email);
public record JugadorCreateRequest(string Nombre, string Apellido, string Dni, DateTime FechaNacimiento,
    int CategoriaId, GrupoFamiliarRequest? GrupoFamiliar, int? GrupoFamiliarId = null);
public record JugadorUpdateRequest(string Nombre, string Apellido, string Dni, DateTime FechaNacimiento, int CategoriaId,
    GrupoFamiliarRequest? GrupoFamiliar = null, int? GrupoFamiliarId = null);
public record GrupoFamiliarResumen(int GrupoId, string NombreContacto, string? Telefono, string? Email, string Integrantes);
