namespace UnionDelSur.API.Models;

public class Partido
{
    public int Id { get; set; }
    public int CategoriaId { get; set; }
    public string Rival { get; set; } = string.Empty;
    public DateTime Fecha { get; set; }
    public string Hora { get; set; } = string.Empty;
    public string Lugar { get; set; } = "local"; // local | visitante
    public int? ResultadoLocal { get; set; }
    public int? ResultadoVisitante { get; set; }
    public string Estado { get; set; } = "programado"; // programado | jugado | suspendido
    public DateTime CreatedAt { get; set; } = DateTime.UtcNow;

    // Joined
    public string? CategoriaNombre { get; set; }
    public List<Goleador> Goleadores { get; set; } = [];
}
