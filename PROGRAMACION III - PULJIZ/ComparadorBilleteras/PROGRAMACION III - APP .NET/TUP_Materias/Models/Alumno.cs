namespace TUP_Materias.Models;

// Un modelo es una clase que representa un objeto del mundo real.
// Acá definimos qué datos tiene un Alumno dentro de la aplicación.
public class Alumno
{
    public int Id { get; set; }
    public string Nombre { get; set; } = string.Empty;
    public string Apellido { get; set; } = string.Empty;

    // Propiedad calculada: junta Nombre y Apellido en un solo texto.
    // No se almacena en ningún lado, se genera al momento de leerla.
    public string NombreCompleto => $"{Nombre} {Apellido}";
}
