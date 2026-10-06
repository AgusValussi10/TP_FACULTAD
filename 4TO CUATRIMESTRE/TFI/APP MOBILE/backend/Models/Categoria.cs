namespace UnionDelSur.API.Models;

public class Categoria
{
    public int Id { get; set; }
    public string Nombre { get; set; } = string.Empty;
    public int AnioNacimientoDesde { get; set; }
    public int AnioNacimientoHasta { get; set; }
    public int? EntrenadorId { get; set; }
    public bool Activo { get; set; } = true;
    public DateTime CreatedAt { get; set; } = DateTime.UtcNow;

    // Joined (not persisted)
    public string? EntrenadorNombre { get; set; }
    public int CantJugadores { get; set; }
}
