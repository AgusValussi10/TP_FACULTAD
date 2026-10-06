namespace UnionDelSur.API.Models;

public class GrupoFamiliar
{
    public int Id { get; set; }
    public string NombreContacto { get; set; } = string.Empty;
    public string Telefono { get; set; } = string.Empty;
    public string Email { get; set; } = string.Empty;
    public bool Activo { get; set; } = true;
    public DateTime CreatedAt { get; set; } = DateTime.UtcNow;
}
