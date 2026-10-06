namespace UnionDelSur.API.Models;

public class Asistencia
{
    public int Id { get; set; }
    public int JugadorId { get; set; }
    public int CategoriaId { get; set; }
    public DateTime Fecha { get; set; }
    public bool Presente { get; set; }
    public string? Observaciones { get; set; }

    // Joined
    public string? JugadorNombre { get; set; }
}
