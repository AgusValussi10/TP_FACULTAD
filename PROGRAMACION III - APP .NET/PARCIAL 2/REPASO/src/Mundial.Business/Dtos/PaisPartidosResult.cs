namespace Mundial.Business.Dtos;

public sealed record PaisPartidosResult(
    string Pais,
    int TotalItems,
    IReadOnlyList<PartidoDto> Items);
