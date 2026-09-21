namespace TUP_Materias.Models;

// Materia es el objeto central de la app.
// Tiene un Profesor (objeto completo, no solo un ID) y una lista de Alumnos.
public class Materia
{
    public int Id { get; set; }
    public string Nombre { get; set; } = string.Empty;

    // Relación con Profesor: cada materia tiene UN solo docente a cargo.
    public Profesor Profesor { get; set; } = new Profesor();

    // Relación con Alumno: una materia puede tener MUCHOS alumnos.
    // Se inicializa como lista vacía para evitar errores de null.
    public List<Alumno> Alumnos { get; set; } = new List<Alumno>();
}
