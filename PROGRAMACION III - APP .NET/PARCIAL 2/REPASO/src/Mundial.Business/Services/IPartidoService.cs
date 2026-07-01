using Mundial.Business.Dtos;

namespace Mundial.Business.Services;

public interface IPartidoService
{
    Task<PagedResult<PartidoDto>> GetPagedAsync(int page, CancellationToken cancellationToken = default);

    Task<PaisPartidosResult> GetByPaisAsync(string pais, CancellationToken cancellationToken = default);
}
