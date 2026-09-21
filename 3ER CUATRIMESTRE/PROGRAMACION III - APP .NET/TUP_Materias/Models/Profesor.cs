namespace TUP_Materias.Models;

// Igual que Alumno, pero representa al docente a cargo de una materia.
public class Profesor
{
    public int Id { get; set; }
    public string Nombre { get; set; } = string.Empty;
    public string Apellido { get; set; } = string.Empty;

    public string NombreCompleto => $"{Nombre} {Apellido}";
}
