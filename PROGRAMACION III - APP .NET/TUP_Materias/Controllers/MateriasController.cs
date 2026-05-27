using Microsoft.AspNetCore.Mvc;
using MySql.Data.MySqlClient;
using TUP_Materias.Models;

namespace TUP_Materias.Controllers;

public class MateriasController : Controller
{
    // IConfiguration permite leer valores de appsettings.json.
    // ASP.NET lo inyecta automáticamente en el constructor (esto se llama "inyección de dependencias").
    private readonly string _connectionString;

    public MateriasController(IConfiguration configuration)
    {
        // Leemos el string de conexión que definimos en appsettings.json
        _connectionString = configuration.GetConnectionString("DefaultConnection")!;
    }

    // Acción Index: consulta todas las materias con su profesor desde la BD
    public IActionResult Index()
    {
        List<Materia> materias = new List<Materia>();

        // "using" garantiza que la conexión se cierre aunque ocurra un error.
        using (MySqlConnection conexion = new MySqlConnection(_connectionString))
        {
            conexion.Open();

            // JOIN para traer el nombre del profesor junto con cada materia en una sola consulta.
            string sql = @"
                SELECT m.id, m.nombre, p.id AS profesor_id, p.nombre AS prof_nombre, p.apellido AS prof_apellido
                FROM materias m
                INNER JOIN profesores p ON m.profesor_id = p.id";

            using MySqlCommand cmd = new MySqlCommand(sql, conexion);
            using MySqlDataReader reader = cmd.ExecuteReader();

            // ExecuteReader devuelve las filas una por una. Read() avanza a la siguiente.
            while (reader.Read())
            {
                materias.Add(new Materia
                {
                    Id     = reader.GetInt32("id"),
                    Nombre = reader.GetString("nombre"),
                    Profesor = new Profesor
                    {
                        Id       = reader.GetInt32("profesor_id"),
                        Nombre   = reader.GetString("prof_nombre"),
                        Apellido = reader.GetString("prof_apellido"),
                    }
                });
            }
        }

        // Contamos cuántos alumnos tiene cada materia en consultas separadas.
        // (En el Detalle vamos a traer la lista completa)
        foreach (Materia materia in materias)
        {
            materia.Alumnos = ObtenerAlumnosDeMateria(materia.Id);
        }

        return View(materias);
    }

    // Acción Detalle: consulta UNA materia completa con su lista de alumnos
    public IActionResult Detalle(int id)
    {
        Materia? materia = null;

        using (MySqlConnection conexion = new MySqlConnection(_connectionString))
        {
            conexion.Open();

            string sql = @"
                SELECT m.id, m.nombre, p.id AS profesor_id, p.nombre AS prof_nombre, p.apellido AS prof_apellido
                FROM materias m
                INNER JOIN profesores p ON m.profesor_id = p.id
                WHERE m.id = @id";

            using MySqlCommand cmd = new MySqlCommand(sql, conexion);

            // @id es un parámetro — evita inyección SQL. Nunca concatenes valores del usuario en el SQL.
            cmd.Parameters.AddWithValue("@id", id);

            using MySqlDataReader reader = cmd.ExecuteReader();

            if (reader.Read())
            {
                materia = new Materia
                {
                    Id     = reader.GetInt32("id"),
                    Nombre = reader.GetString("nombre"),
                    Profesor = new Profesor
                    {
                        Id       = reader.GetInt32("profesor_id"),
                        Nombre   = reader.GetString("prof_nombre"),
                        Apellido = reader.GetString("prof_apellido"),
                    }
                };
            }
        }

        if (materia == null)
            return NotFound();

        materia.Alumnos = ObtenerAlumnosDeMateria(id);

        return View(materia);
    }

    // Método auxiliar que trae los alumnos de una materia usando la tabla intermedia materia_alumno
    private List<Alumno> ObtenerAlumnosDeMateria(int materiaId)
    {
        List<Alumno> alumnos = new List<Alumno>();

        using MySqlConnection conexion = new MySqlConnection(_connectionString);
        conexion.Open();

        string sql = @"
            SELECT a.id, a.nombre, a.apellido
            FROM alumnos a
            INNER JOIN materia_alumno ma ON a.id = ma.alumno_id
            WHERE ma.materia_id = @materiaId";

        using MySqlCommand cmd = new MySqlCommand(sql, conexion);
        cmd.Parameters.AddWithValue("@materiaId", materiaId);

        using MySqlDataReader reader = cmd.ExecuteReader();

        while (reader.Read())
        {
            alumnos.Add(new Alumno
            {
                Id       = reader.GetInt32("id"),
                Nombre   = reader.GetString("nombre"),
                Apellido = reader.GetString("apellido"),
            });
        }

        return alumnos;
    }
}
