namespace UnionDelSur.API.Models;

public class Seguro
{
    public int Id { get; set; }
    public int JugadorId { get; set; }
    public string NumeroPoliza { get; set; } = string.Empty;
    public DateTime VigenteDesde { get; set; }
    public DateTime VigenteHasta { get; set; }
    public string Estado { get; set; } = "vigente"; // vigente | vencido | pendiente

    // Joined
    public string? JugadorNombre { get; set; }
    public string? CategoriaNombre { get; set; }
}
