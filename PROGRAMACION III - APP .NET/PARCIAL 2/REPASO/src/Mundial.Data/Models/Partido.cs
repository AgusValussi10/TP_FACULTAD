using System.Text.Json.Serialization;

namespace Mundial.Data.Models;

public sealed class Partido
{
    [JsonPropertyName("id_partido")]
    public string IdPartido { get; set; } = string.Empty;

    [JsonPropertyName("grupo")]
    public string Grupo { get; set; } = string.Empty;

    [JsonPropertyName("fecha_hora_argentina")]
    public DateTimeOffset FechaHoraArgentina { get; set; }

    [JsonPropertyName("estado")]
    public string Estado { get; set; } = string.Empty;

    [JsonPropertyName("equipo_local")]
    public string EquipoLocal { get; set; } = string.Empty;

    [JsonPropertyName("equipo_local_codigo")]
    public string EquipoLocalCodigo { get; set; } = string.Empty;

    [JsonPropertyName("equipo_visitante")]
    public string EquipoVisitante { get; set; } = string.Empty;

    [JsonPropertyName("equipo_visitante_codigo")]
    public string EquipoVisitanteCodigo { get; set; } = string.Empty;

    [JsonPropertyName("goles_local")]
    public int? GolesLocal { get; set; }

    [JsonPropertyName("goles_visitante")]
    public int? GolesVisitante { get; set; }

    [JsonPropertyName("ganador")]
    public string? Ganador { get; set; }
}
