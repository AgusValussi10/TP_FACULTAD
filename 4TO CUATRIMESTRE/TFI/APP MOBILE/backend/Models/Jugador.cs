namespace UnionDelSur.API.Models;

public class Jugador
{
    public int Id { get; set; }
    public string Nombre { get; set; } = string.Empty;
    public string Apellido { get; set; } = string.Empty;
    public string Dni { get; set; } = string.Empty;
    public DateTime FechaNacimiento { get; set; }
    public int CategoriaId { get; set; }
    public int? GrupoFamiliarId { get; set; }
    public bool Activo { get; set; } = true;
    public DateTime FechaAlta { get; set; } = DateTime.UtcNow;

    // Joined
    public string? CategoriaNombre { get; set; }
    public string? GrupoFamiliarContacto { get; set; }
    public string? GrupoFamiliarTelefono { get; set; }
    public string? GrupoFamiliarEmail { get; set; }
    public int CantHermanos { get; set; }
}
