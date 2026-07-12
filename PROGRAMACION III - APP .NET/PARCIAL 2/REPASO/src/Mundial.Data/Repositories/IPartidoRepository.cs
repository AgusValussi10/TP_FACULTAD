using Mundial.Data.Models;

namespace Mundial.Data.Repositories;

public interface IPartidoRepository
{
    Task<IReadOnlyList<Partido>> GetAllAsync(CancellationToken cancellationToken = default);
}
