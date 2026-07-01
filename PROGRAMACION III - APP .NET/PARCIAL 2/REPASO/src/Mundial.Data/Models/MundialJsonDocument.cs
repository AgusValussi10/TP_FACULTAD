using System.Text.Json.Serialization;

namespace Mundial.Data.Models;

public sealed class MundialJsonDocument
{
    [JsonPropertyName("torneo")]
    public string Torneo { get; set; } = string.Empty;

    [JsonPropertyName("fase")]
    public string Fase { get; set; } = string.Empty;

    [JsonPropertyName("zona_horaria")]
    public string ZonaHoraria { get; set; } = string.Empty;

    [JsonPropertyName("generado_el")]
    public DateTimeOffset GeneradoEl { get; set; }

    [JsonPropertyName("cantidad_partidos")]
    public int CantidadPartidos { get; set; }

    [JsonPropertyName("partidos")]
    public List<Partido> Partidos { get; set; } = [];
}
