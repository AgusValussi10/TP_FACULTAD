namespace UnionDelSur.API.Models;

public class Goleador
{
    public int Id { get; set; }
    public int PartidoId { get; set; }
    public int JugadorId { get; set; }
    public int Cantidad { get; set; }

    // Joined
    public string? JugadorNombre { get; set; }
}
