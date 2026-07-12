using Microsoft.Extensions.Options;
using Mundial.Business.Dtos;
using Mundial.Data.Config;
using Mundial.Data.Models;
using Mundial.Data.Repositories;

namespace Mundial.Business.Services;

public sealed class PartidoService(
    IPartidoRepository repository,
    IOptions<MundialConfig> options) : IPartidoService
{
    private readonly MundialConfig _config = options.Value;

    public async Task<PagedResult<PartidoDto>> GetPagedAsync(int page, CancellationToken cancellationToken = default)
    {
        if (page < 1)
        {
            throw new ArgumentOutOfRangeException(nameof(page), "La pagina debe ser mayor o igual a 1.");
        }

        var allPartidos = await repository.GetAllAsync(cancellationToken);
        var pageSize = _config.PartidosAVisualizar;
        var totalItems = allPartidos.Count;
        var totalPages = (int)Math.Ceiling((double)totalItems / pageSize);
        var items = allPartidos
            .Skip((page - 1) * pageSize)
            .Take(pageSize)
            .Select(ToDto)
            .ToList();
        return new PagedResult<PartidoDto>(page, pageSize, totalItems, totalPages, items);
    }


    public async Task<PaisPartidosResult> GetByPaisAsync(string pais, CancellationToken cancellationToken = default)
    {
        if (string.IsNullOrWhiteSpace(pais))
        {
            throw new ArgumentException("El pais no puede estar vacio.", nameof(pais));
        }

        var partidos = await repository.GetAllAsync(cancellationToken);

        var items = partidos
            .Where(p => string.Equals(p.EquipoLocalCodigo, pais, StringComparison.OrdinalIgnoreCase) ||
                string.Equals(p.EquipoVisitanteCodigo, pais, StringComparison.OrdinalIgnoreCase))
            .Select(ToDto)
            .ToList();

        return new PaisPartidosResult(pais, items.Count, items);
    }

    private static PartidoDto ToDto(Partido partido)
    {
        return new PartidoDto(
            partido.IdPartido,
            partido.Grupo,
            partido.FechaHoraArgentina,
            partido.Estado,
            partido.EquipoLocal,
            partido.EquipoLocalCodigo,
            partido.EquipoVisitante,
            partido.EquipoVisitanteCodigo,
            partido.GolesLocal,
            partido.GolesVisitante,
            partido.Ganador);
    }
}
