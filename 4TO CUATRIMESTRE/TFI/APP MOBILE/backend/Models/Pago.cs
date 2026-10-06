namespace UnionDelSur.API.Models;

public class Pago
{
    public int Id { get; set; }
    public int CuotaId { get; set; }
    public decimal MontoPagado { get; set; }
    public DateTime FechaPago { get; set; } = DateTime.UtcNow;
    public string MetodoPago { get; set; } = "Efectivo"; // Efectivo | Transferencia | Tarjeta
    public int? RegistradoPorId { get; set; }
    public DateTime CreatedAt { get; set; } = DateTime.UtcNow;
}
