namespace UnionDelSur.API.Models;

public class CarnetLiga
{
    public int Id { get; set; }
    public int JugadorId { get; set; }
    public string NumeroCarnet { get; set; } = string.Empty;
    public string Temporada { get; set; } = string.Empty;
    public string Estado { get; set; } = "pendiente"; // activo | vencido | pendiente
    public DateTime? FechaEmision { get; set; }
    public DateTime? FechaVencimiento { get; set; }
    public DateTime CreatedAt { get; set; } = DateTime.UtcNow;

    // Joined
    public string? JugadorNombre { get; set; }
    public string? CategoriaNombre { get; set; }
}
