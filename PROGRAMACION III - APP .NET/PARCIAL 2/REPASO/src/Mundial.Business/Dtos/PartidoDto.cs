namespace Mundial.Business.Dtos;

public sealed record PartidoDto(
    string IdPartido,
    string Grupo,
    DateTimeOffset FechaHoraArgentina,
    string Estado,
    string EquipoLocal,
    string EquipoLocalCodigo,
    string EquipoVisitante,
    string EquipoVisitanteCodigo,
    int? GolesLocal,
    int? GolesVisitante,
    string? Ganador);
