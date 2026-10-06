namespace UnionDelSur.API.Models;

public class Cuota
{
    public int Id { get; set; }
    public int JugadorId { get; set; }
    public int PeriodoMes { get; set; }
    public int PeriodoAnio { get; set; }
    public decimal Monto { get; set; }
    public decimal DescuentoHermanos { get; set; } = 0;
    public string Estado { get; set; } = "pendiente"; // pendiente | pagada | vencida
    public DateTime FechaVencimiento { get; set; }
    public DateTime CreatedAt { get; set; } = DateTime.UtcNow;

    // Joined
    public string? JugadorNombre { get; set; }
    public string? CategoriaNombre { get; set; }
}
