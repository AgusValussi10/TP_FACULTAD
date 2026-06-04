# CLAUDE.md — Notas internas del repo (solo para Claude)

Repositorio de la materia **Programacion 3 Web TUP 2026** del profe Fernandez.
Versión del repo: v7 | Target: .NET 10.0 | Lenguajes: C#, HTML, CSS, JS, Astro, TypeScript

---

## Estructura y propósito de cada carpeta

| Carpeta | Qué es |
|---|---|
| `001-git` | Ejercicio intro a Git. Solo tiene `tarea1.c` de ejemplo |
| `002-net-console` | Console App .NET 10 "Hello World v2" |
| `003-net-console` | Console App .NET 10 "Hello World v3" (estructura actualizada) |
| `004-poo-net` | POO en C#: clase `Alumno` con propiedad calculada `Edad`. Tiene tests xUnit |
| `005-web-simple` | ASP.NET Minimal API, solo un `MapGet("/home")` que retorna HTML |
| `006-html` | Frontend vanilla: HTML semántico, CSS, JS puro. Tiene imagen Star Wars |
| `007-net-http` | Minimal API con MapGet/MapPost/MapDelete. Sirve estáticos desde wwwroot |
| `009-todo-solucion` | Arquitectura de capas completa (TODO App): Entidades, Datos, Negocio, ConsoleApp, WebApi |
| `010-ado-simple` | ADO.NET puro: SqlConnection + SqlCommand + DataReader contra tabla Jedi |
| `011-api-db` | App completa Star Wars con 4 frontends: HTML puro, Astro, Next.js (vacío), y API ASP.NET con CORS |
| `012-auth` | Autenticación JWT: login con validación inversa de password, endpoints públicos y securizados |
| `013-aspnet-mvc` | ASP.NET MVC tradicional: HomeController, JediController, Views Razor, Layout Bootstrap |
| `014-demo` | Proyecto más complejo: Mundial FIFA 2026 con EF Core, ADO.NET, MVC y Blazor |
| `015-appSettings` | (vacío o de configuración) |
| `demo` | Archivos varios de ejemplo |

---

## Arquitectura de capas (patrón repetido en 009, 011, 014)

```
Entidades  →  Datos (Repository)  →  Negocio  →  Presentación (API / MVC / Blazor / Console)
```

- **Entidades**: Modelos puros (`Item`, `Jedi`, `Equipo`, `Partido`)
- **Datos**: `ItemRepository`, `JediRepository`, `PartidoRepository` — acceso a BD o mock
- **Negocio**: `ItemNegocio`, `JediBusiness`, `PartidoNegocio` — orquesta repos
- **Presentación**: Múltiples apps que consumen Negocio

---

## Tecnologías usadas

### Backend
- .NET 10.0
- ASP.NET Core (Minimal APIs y MVC)
- Entity Framework Core (DbContext, DbSet, Include, LINQ)
- ADO.NET (SqlConnection, SqlCommand, DataReader)
- JWT (SymmetricSecurityKey, JwtSecurityToken, Claims, Bearer scheme)
- Blazor (AddRazorComponents, AddInteractiveServerComponents)
- xUnit + Moq para tests

### Frontend
- HTML/CSS/JavaScript vanilla (Fetch API, DOM API, event listeners)
- Razor (.cshtml): `@model`, `@foreach`, `@if`, `@RenderBody()`
- Astro v6.1.10 (`.astro` con frontmatter)
- Next.js (solo esqueleto, package.json)
- Bootstrap (en MVC layouts)

---

## Detalles clave por carpeta

### 004-poo-net
```csharp
// Alumno.cs
public int Edad { get { return CalcularEdad(); } }  // calculada, no almacenada
private int CalcularEdad() { /* lógica con DateTime.Today */ }
```
Tests en `ConsoleApp4.Test/AlumnoTest.cs` con [Fact] y Assert.Equal

### 007-net-http
Usa string verbatim (`@"..."`) para HTML embebido en C#. Archivos de test: `test.http`

### 009-todo-solucion
- `Item.cs`: tiene `_estado` privado con getter/setter y `ToString()` que muestra "Completo" o "Pendiente"
- 8 tests xUnit en `ToDo.Entidades.Tests/ItemTest.cs`
- API expone `GET /api/item` → retorna lista desde ItemNegocio
- Datos son mock (no BD real)

### 010-ado-simple
```csharp
reader.GetString(reader.GetOrdinal("Nombre"))  // 3 formas de leer: índice, campo, GetOrdinal
```
ConnectionString apunta a SQL Server local, tabla `dbo.Jedi`

### 011-api-db
- API en `Starwars.Apps.WebApiApp` → `GET /api/jedi` con CORS AllowAll
- HTML+JS en `Starwars.Apps.WebHtmlSimpleApp/app.js` — usa fetch con .then/.catch/.finally + spinner
- Astro app: `npm run dev` en `Starwars.Apps.WebAstroApp/`
- `doc.md` en WebHtmlSimpleApp explica fetch, promises, DOM
- API URL hardcodeada: `https://localhost:7213/api/jedi`

### 012-auth
- Login: `POST /api/auth/token` con `{ username, password }`
- Validación: `password == reverse(username)` (ej: luke/ekul, leia/aiel, yoda/adoy)
- Endpoint público: `GET /api/time`
- Endpoint seguro: `GET /api/time/secure` (requiere Bearer token)
- Config JWT en appsettings.json: Key, Issuer, Audience, ExpiresInMinutes (60)
- Tests en `Time1-Tests.http`

### 013-aspnet-mvc
- `JediController.Index()` → pasa `List<Jedi>` a `Views/Jedi/Index.cshtml`
- `JediController.Detail(int id)` → detalle de un jedi
- Layout compartido: `Views/Shared/_Layout.cshtml` (navbar Bootstrap, @RenderBody)
- net10.0, sin base de datos (datos en memoria)

### 014-demo (el más complejo)
**Entidades con DataAnnotations**:
```csharp
[Table("Partido")]
public class Partido {
    [Key] public int PartidoId { get; set; }
    [ForeignKey("EquipoIdLocal")] public Equipo Local { get; set; }
    [ForeignKey("EquipoIdVisitante")] public Equipo Visitante { get; set; }
    public DateTime Fecha { get; set; }
    public string Ciudad { get; set; }
    public string Estadio { get; set; }
}
```

**EF con LINQ**:
```csharp
context.Partidos
    .Where(p => p.Local.Nombre.Contains(filtro.TextoABuscar))
    .Include(p => p.Local)
    .Include(p => p.Visitante)
    .ToList();
```

**PartidoNegocio** permite cambiar entre ADO.NET y EF sin tocar controllers:
```csharp
public List<Partido> ObtenerListadoEF(filtro) => new PartidoRepository().ObtenerListado(filtro);
public List<Partido> ObtenerListadoADONET() => new TUP.Mundial.Datos.PartidoRepository().ObtenerListado();
```

**MVC**: `PartidoController` con rutas amigables (`/ticket/grupo/argentina-vs-brasil`)

**Blazor**: AddRazorComponents + AddInteractiveServerComponents (Blazor Server)

---

## Convenciones del proyecto

- **Nombres**: PascalCase clases/métodos/propiedades, `_camelCase` privados, camelCase locales
- **Estructura**: cada "0XX-nombre/" es un ejercicio o demo independiente con su propio `.csproj`/`.slnx`
- **Tests**: siempre en proyectos separados `*.Tests` o `*.Test`
- **Archivos .http**: tests de endpoints directamente desde el IDE
- **CORS**: siempre AllowAll en demos de desarrollo
- **Sin DI formal**: mayoría instancia repos y negocio directamente (sin constructor injection)

---

## Base de datos

- Motor: **SQL Server** local
- Connection strings: dentro de los archivos `.cs` directamente (no en appsettings en los demos ADO)
- BD usada en 010, 011 (tabla `dbo.Jedi`), 014 (`MundialFIFA2026`)

---

## Solución global

`WebAppMvc.slnx` en la raíz agrupa varios proyectos. Cada subcarpeta tiene su propio `.slnx` también.

---

# RESUMEN COMPLETO PARA EL PARCIAL (del PDF)
> Fuente: "PDF a Markdown.pdf" — Resumen Completo Programación III · TUP UTN FRRe · Junio 2026

---

## 1. C# y .NET — Conceptos Básicos (Módulos 002 y 003)

```csharp
// Program.cs — Top-level statements (sin class ni Main)
Console.WriteLine("Hello, World!");
Console.ReadKey();
```

| Concepto | Descripción |
|---|---|
| `Console.WriteLine` | Imprime texto con salto de línea |
| `Console.ReadKey()` | Espera a que el usuario presione una tecla |
| `Console.ReadLine()` | Lee una línea de texto del usuario |
| `var` | Inferencia de tipo: el compilador deduce el tipo |
| `.csproj` | Archivo de proyecto C# (configuración y dependencias) |
| `.slnx` | Archivo de solución (agrupa varios proyectos) |

```csharp
// Tipos básicos
int numero = 42;
double decimal1 = 3.14;
string texto = "Programacion III";
bool activo = true;
char letra = 'A';

// Inferencia de tipo
var nombre = "Luke";   // string inferido
var edad = 22;         // int inferido

// Interpolación de strings
Console.WriteLine($"Nombre: {nombre}, Edad: {edad}");

// Null coalescing — devuelve izquierda si no es null, derecha si es null
string saludo = nombre ?? "Desconocido";
```

---

## 2. Programación Orientada a Objetos (Módulo 004)

```csharp
public class Alumno
{
    private string _nombre;

    public string Nombre
    {
        get { return _nombre; }
        set { _nombre = value; }
    }

    public string Apellido { get; set; }  // Auto-propiedad

    public int Edad { get { return CalcularEdad(); } }  // Solo lectura, calculada

    public DateTime FechaNacimiento { get; set; }

    private int CalcularEdad()
    {
        var hoy = DateTime.Today;
        var edad = hoy.Year - FechaNacimiento.Year;
        if (FechaNacimiento.Date > hoy.AddYears(-edad)) edad--;
        return edad;
    }
}
```

```csharp
// Instanciar y usar
var alumno = new Alumno();
alumno.Nombre = "Programacion III";
alumno.FechaNacimiento = new DateTime(2004, 1, 1);

// Object initializer
var alumno2 = new Alumno
{
    Nombre = "Juan",
    Apellido = "Perez",
    FechaNacimiento = new DateTime(2002, 5, 20)
};
Console.WriteLine(alumno2.Edad);
```

| Concepto | Descripción |
|---|---|
| Encapsulamiento | Campo `private` expuesto a través de propiedad `public` |
| `get / set` | get = leer, set = escribir |
| Auto-propiedad | `public string Apellido { get; set; }` — campo privado automático |
| Prop. calculada | Sin `set`; valor se obtiene ejecutando un método |
| `new DateTime(año, mes, día)` | Crea un objeto fecha |

### Tests Unitarios con xUnit

```csharp
// AlumnoTest.cs — proyecto ConsoleApp4.Test
public class AlumnoTest
{
    [Fact]
    public void DebeCrearUnAlumnoConNombre()
    {
        // Arrange
        var alumno = new Alumno();
        // Act
        alumno.Nombre = "Programacion III";
        // Assert
        Assert.Equal("Programacion III", alumno.Nombre);
    }

    [Fact]
    public void DebeCrearUnAlumnoDe22Anios()
    {
        var alumno = new Alumno();
        alumno.FechaNacimiento = new DateTime(2004, 1, 1);
        Assert.Equal(22, alumno.Edad);
    }
}
```

**Patrón AAA**: Arrange (preparar) → Act (ejecutar) → Assert (verificar). Todo test debe seguirlo.

---

## 3. HTML, CSS y JavaScript (Módulo 006)

```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title>Programacion III</title>
</head>
<body>
    <h1 class="titulo" id="titulo1">Programacion III</h1>
    <img id="miimagen" src="imagen.jpg" />
    <script src="scripts.js"></script>  <!-- al final del body -->
</body>
</html>
```

| Concepto | Descripción |
|---|---|
| `DOCTYPE` | Declara HTML5 |
| `charset UTF-8` | Soporta ñ, acentos, etc. |
| `viewport` | Hace la página responsive |
| `class / id` | class: múltiples elementos; id: único en la página |
| `script al final` | Para que el HTML cargue primero |

```javascript
// DOM y Eventos
var titulo1 = document.getElementById("titulo1");
var miimagen = document.getElementById("miimagen");

// Forma NO intrusiva (recomendada)
miimagen.addEventListener("click", function() { accion1(); });
miimagen.addEventListener("mousemove", function() { accion1(); });

function accion1() {
    var dateTime = new Date();
    titulo1.innerHTML = "Hora: " + dateTime.getHours() + ":" + dateTime.getMinutes() + ":" + dateTime.getSeconds();
}
```

| Método | Descripción |
|---|---|
| `getElementById` | Obtiene elemento por id |
| `addEventListener` | Registra función para un evento sin mezclar HTML con JS |
| `innerHTML` | Lee o modifica el contenido HTML interno |
| `textContent` | Igual que innerHTML pero solo texto plano |
| `new Date()` | Objeto fecha/hora de JavaScript |

---

## 4. API Mínima de ASP.NET (Módulos 005 y 007)

```csharp
// Forma más simple (WebApplication.Create)
var app = WebApplication.Create(args);
app.MapGet("/home", () => Results.Content("<h1>Programacion III</h1>", "text/html"));
app.Run();
```

```csharp
// Con builder y más opciones
var builder = WebApplication.CreateBuilder(args);
var app = builder.Build();
app.UseHttpsRedirection();
app.MapStaticAssets();  // sirve archivos de wwwroot

app.MapGet("/", () => "Hello World! GET");
app.MapPost("/", () => "Hello World! POST");
app.MapDelete("/", () => "Hello World! DELETE");
app.MapGet("/index.html", () => Results.Content("<html><body><h1>Hola</h1></body></html>", "text/html"));
app.Run();
```

| Concepto | Descripción |
|---|---|
| `WebApplication.Create` | Forma rápida sin builder |
| `WebApplicationBuilder` | Configura servicios ANTES de `Build()` |
| `app.Build()` | Crea la app con todos los servicios |
| `MapGet/Post/Delete/Put` | Define rutas para cada verbo HTTP |
| `UseHttpsRedirection` | Redirige HTTP → HTTPS |
| `MapStaticAssets()` | Sirve archivos de `wwwroot` |
| `Results.Content(html, mediaType)` | Devuelve contenido con Content-Type específico |

> **IMPORTANTE**: El orden de `builder.Services.*` siempre va ANTES de `builder.Build()`. El orden del middleware importa: `UseHttpsRedirection` → `UseCors` → `UseAuthentication` → `UseAuthorization` → `MapControllers`.

---

## 5. HTTP: Métodos, Códigos de estado y REST

### Métodos HTTP

| Método | Uso | Ejemplo |
|---|---|---|
| GET | Obtener datos (no modifica) | `GET /api/jedi` |
| POST | Crear un nuevo recurso | `POST /api/jedi` |
| PUT | Reemplazar un recurso completo | `PUT /api/jedi/{id}` |
| PATCH | Modificar parcialmente | `PATCH /api/jedi/{id}` |
| DELETE | Eliminar un recurso | `DELETE /api/jedi/{id}` |

### Códigos de estado

| Código | Nombre | Cuándo usarlo |
|---|---|---|
| 200 | OK | Respuesta exitosa (GET) |
| 201 | Created | Recurso creado (POST) |
| 204 | No Content | Éxito sin cuerpo (DELETE) |
| 400 | Bad Request | Petición malformada |
| 401 | Unauthorized | No autenticado, falta token JWT |
| 403 | Forbidden | Autenticado pero sin permiso |
| 404 | Not Found | El recurso no existe |
| 500 | Internal Server Error | Error no controlado del servidor |

```csharp
// Resultados en API mínima
app.MapGet("/api/jedi/{id}", (int id) =>
{
    if (id <= 0) return Results.BadRequest("Id inválido");
    var jedi = ...;
    if (jedi == null) return Results.NotFound();
    return Results.Ok(jedi);  // 200 + JSON
});
app.MapPost("/api/jedi", (Jedi jedi) => Results.Created($"/api/jedi/{jedi.Id}", jedi));  // 201
app.MapDelete("/api/jedi/{id}", (int id) => Results.NoContent());  // 204
```

---

## 6. Arquitectura en Capas (Módulo 009 — ToDo App)

```
Presentación (API/Console) → Negocio → Datos → [BD]
                                         ↑
                               Entidades (compartidas por todas)
```

### Item.cs (Entidades)
```csharp
public class Item
{
    public string Titulo { get; set; }
    private bool _estado;
    public bool Estado { get { return _estado; } set { _estado = value; } }
    public string Color { get; set; }
    public override string ToString() => $"{Titulo} ({(Estado ? "Completo" : "Pendiente")})";
}
```

### ItemRepository.cs (Datos)
```csharp
public class ItemRepository
{
    public List<Item> ObtenerTodos()
    {
        return new List<Item>
        {
            new Item { Titulo = "Comprar leche", Estado = false, Color = "Rojo" },
            new Item { Titulo = "Llamar a mamá", Estado = true,  Color = "Verde" },
            new Item { Titulo = "Pagar facturas", Estado = false, Color = "Azul" },
            new Item { Titulo = "Hacer ejercicio", Estado = true, Color = "Amarillo" }
        };
    }
}
```

### ItemNegocio.cs (Negocio)
```csharp
public class ItemNegocio
{
    public List<Item> ObtenerTodos()
    {
        var repo = new ItemRepository();
        return repo.ObtenerTodos();
    }
}
```

### Program.cs (API)
```csharp
app.MapGet("/api/item", () =>
{
    var itemNegocio = new ToDo.Negocio.ItemNegocio();
    var items = itemNegocio.ObtenerTodos();
    return items;  // .NET serializa a JSON automáticamente
});
```

> **IMPORTANTE**: Presentación llama a Negocio. Negocio llama a Datos. Datos llama a la BD. **Nunca la API habla directamente con la BD.** Las Entidades las conocen todas las capas.

---

## 7. ADO.NET — Acceso a Datos con SQL Server (Módulos 010 y 011)

| Clase | Descripción |
|---|---|
| `SqlConnection` | Representa la conexión. Necesita connection string |
| `SqlCommand` | Representa una consulta SQL |
| `SqlDataReader` | Lee resultados de SELECT fila por fila |
| `ExecuteReader()` | Ejecuta SELECT y devuelve DataReader |
| `ExecuteNonQuery()` | Ejecuta INSERT, UPDATE o DELETE |
| `reader.Read()` | Avanza al siguiente registro. False cuando no hay más |

```csharp
// Flujo completo ADO.NET
var connectionString = "Server=localhost;Database=StarwarsGalaxy;Integrated Security=True;TrustServerCertificate=True;";

var conn = new SqlConnection(connectionString);
var cmd = new SqlCommand();
cmd.CommandText = "SELECT JediId, Nombre FROM dbo.Jedi";
cmd.Connection = conn;
cmd.CommandType = System.Data.CommandType.Text;

conn.Open();
var reader = cmd.ExecuteReader();

while (reader.Read())
{
    var nombre1 = reader.GetString(1);                             // por índice
    var nombre2 = reader["Nombre"].ToString();                     // por nombre
    var nombre3 = reader.GetString(reader.GetOrdinal("Nombre"));  // más seguro
    Console.WriteLine($"Jedi: {nombre3}");
}

reader.Close();
conn.Close();
// O usar: using (var conn = new SqlConnection(cs)) { ... }
```

### JediRepository (Módulo 011)
```csharp
public List<Jedi> GetAll()
{
    var jedis = new List<Jedi>();
    var conn = new SqlConnection(CONN);
    var cmd = new SqlCommand("SELECT JediId, Nombre FROM dbo.Jedi", conn);
    conn.Open();
    var reader = cmd.ExecuteReader();
    while (reader.Read())
    {
        jedis.Add(new Jedi
        {
            JediId = reader.GetInt32(reader.GetOrdinal("JediId")),
            Nombre = reader.GetString(reader.GetOrdinal("Nombre"))
        });
    }
    reader.Close();
    conn.Close();
    return jedis;
}
```

---

## 8. API REST con BD y Cliente Web (Módulo 011 — CORS + fetch)

### CORS en el servidor
```csharp
builder.Services.AddCors(options =>
{
    options.AddPolicy("AllowAll", policy =>
    {
        policy.AllowAnyOrigin().AllowAnyMethod().AllowAnyHeader();
    });
});
var app = builder.Build();
app.UseCors("AllowAll");  // ANTES de mapear rutas
app.MapGet("/api/jedi", () => new JediBusiness().GetAll());
```

### Cliente HTML con fetch()
```javascript
const API_URL = 'https://localhost:7213/api/jedi';

document.getElementById('btn-cargar').addEventListener('click', function () {
    mostrarLoader();
    fetch(API_URL)
        .then(function (response) {
            if (!response.ok) throw new Error('Error: ' + response.status);
            return response.json();
        })
        .then(function (jedis) {
            jedis.forEach(function (jedi) {
                var card = document.createElement('div');
                card.className = 'jedi-card';
                card.textContent = '#' + jedi.jediId + ' ' + jedi.nombre;
                document.getElementById('lista-jedis').appendChild(card);
            });
        })
        .catch(function (error) {
            document.getElementById('error-msg').textContent = error.message;
        })
        .finally(function () {
            ocultarLoader();
        });
});
```

| Método | Descripción |
|---|---|
| `fetch(url)` | Petición HTTP asíncrona (devuelve Promise) |
| `.then(fn)` | Callback cuando la Promise se resuelve (éxito) |
| `.catch(fn)` | Callback cuando hay error |
| `.finally(fn)` | Siempre se ejecuta, haya error o no |
| `response.json()` | Parsea el cuerpo como JSON (también es Promise) |
| `response.ok` | True si código de estado es 200-299 |
| `createElement` | Crea un nuevo elemento HTML |
| `appendChild` | Agrega un elemento como hijo de otro |

---

## 9. Autenticación JWT (Módulo 012)

**JWT = JSON Web Token**: token firmado digitalmente. El servidor lo genera al hacer login y el cliente lo envía en cada petición en el header `Authorization: Bearer <token>`.

| Parte | Descripción |
|---|---|
| Header | Tipo de token y algoritmo (ej: HS256) |
| Payload (Claims) | Datos del usuario: nombre, rol, expiración |
| Signature | Firma HMAC-SHA256 que garantiza integridad |
| `[Authorize]` | Atributo que requiere JWT válido (controllers) |
| `RequireAuthorization()` | Igual que `[Authorize]` pero en Minimal API |

### Configuración JWT en Program.cs
```csharp
var jwtKey    = builder.Configuration["Jwt:Key"]!;
var jwtIssuer = builder.Configuration["Jwt:Issuer"]!;
var jwtAud    = builder.Configuration["Jwt:Audience"]!;

builder.Services
    .AddAuthentication(JwtBearerDefaults.AuthenticationScheme)
    .AddJwtBearer(options =>
    {
        options.TokenValidationParameters = new TokenValidationParameters
        {
            ValidateIssuer = true,
            ValidateAudience = true,
            ValidateLifetime = true,
            ValidateIssuerSigningKey = true,
            ValidIssuer = jwtIssuer,
            ValidAudience = jwtAud,
            IssuerSigningKey = new SymmetricSecurityKey(Encoding.UTF8.GetBytes(jwtKey))
        };
    });
builder.Services.AddAuthorization();

// Orden del middleware:
app.UseAuthentication();  // primero
app.UseAuthorization();   // luego
```

### Generación del Token (login)
```csharp
app.MapPost("/api/auth/token", (LoginRequest req) =>
{
    // Validar credenciales (regla del ejemplo: pass == reverse(user))
    var reversed = new string(req.Username.ToLower().Reverse().ToArray());
    if (!req.Password.Equals(reversed, StringComparison.OrdinalIgnoreCase))
        return Results.Unauthorized();

    var claims = new[]
    {
        new Claim(ClaimTypes.Name, req.Username),
        new Claim(ClaimTypes.Role, "User"),
        new Claim(JwtRegisteredClaimNames.Jti, Guid.NewGuid().ToString())
    };

    var key   = new SymmetricSecurityKey(Encoding.UTF8.GetBytes(jwtKey));
    var creds = new SigningCredentials(key, SecurityAlgorithms.HmacSha256);
    var token = new JwtSecurityToken(jwtIssuer, jwtAud, claims,
        expires: DateTime.UtcNow.AddMinutes(jwtExpMin),
        signingCredentials: creds);

    return Results.Ok(new {
        token   = new JwtSecurityTokenHandler().WriteToken(token),
        expires = token.ValidTo
    });
});
```

### Endpoints públicos vs protegidos
```csharp
// Público
app.MapGet("/api/time", () => Results.Ok(new { time = DateTime.Now.ToString(), secured = false }));

// Protegido — requiere Authorization: Bearer <token>
app.MapGet("/api/time/secure", (ClaimsPrincipal user) =>
    Results.Ok(new { time = DateTime.Now.ToString(), secured = true, user = user.Identity?.Name }))
    .RequireAuthorization();
```

**Usuarios de prueba del módulo**: luke/ekul · leia/aiel · yoda/adoy

---

## 10. ASP.NET MVC (Módulo 013)

| Parte | Descripción |
|---|---|
| Model | Clases de datos / entidades |
| View | Archivos `.cshtml` (Razor). Generan el HTML |
| Controller | Clases C#. Reciben petición, llaman al modelo, seleccionan vista |
| Routing | Por defecto: `{controller}/{action}/{id?}` → JediController + Index = `/Jedi/Index` |

### Program.cs
```csharp
builder.Services.AddControllersWithViews();
var app = builder.Build();
app.UseHttpsRedirection();
app.UseRouting();
app.UseAuthorization();
app.MapStaticAssets();
app.MapControllerRoute(name: "default", pattern: "{controller=Home}/{action=Index}/{id?}")
   .WithStaticAssets();
```

### JediController.cs
```csharp
public class JediController : Controller
{
    [HttpGet]
    public IActionResult Index()
    {
        var jedis = new List<Jedi>();
        for (int i = 0; i < 10; i++)
            jedis.Add(new Jedi { Id = i, Name = "Luke Skywalker", LightSaberColor = "Green" });
        return View(jedis);
    }

    [HttpGet]
    public IActionResult Detail(int id) => View(new Jedi { Id = id, Name = "Luke Skywalker", LightSaberColor = "Green" });
}
```

### Vista Razor — Views/Jedi/Index.cshtml
```html
@model IEnumerable<WebAppMvc.Models.Jedi>
@{ ViewData["Title"] = "Jedis"; }

<h2 class="display-4">Jedis!</h2>

@{ var suma = 1 + 2; }
@if (suma > 2) { <p>La suma es mayor a 2</p> }

<table class="table">
    <thead><tr><th>Id</th><th>Name</th><th>LightSaber</th></tr></thead>
    <tbody>
        @foreach (var item in Model)
        {
            <tr>
                <td>@item.Id</td>
                <td>@item.Name</td>
                <td>@item.LightSaberColor</td>
            </tr>
        }
    </tbody>
</table>
```

| Sintaxis Razor | Descripción |
|---|---|
| `@model` | Declara el tipo del modelo que recibe la vista |
| `@{ }` | Bloque de código C# |
| `@variable` | Imprime valor de variable C# en HTML |
| `@if / @foreach` | Control de flujo C# en Razor |
| `ViewData["Title"]` | Diccionario para datos adicionales a la vista |
| `return View(modelo)` | Controller le pasa el modelo a la vista |

---

## 11. Entity Framework Core y LINQ (Módulo 014)

**EF Core = ORM**: mapea clases C# a tablas de BD. Se escribe LINQ y EF lo traduce a SQL.

| Concepto | Descripción |
|---|---|
| `DbContext` | Clase central. Representa la sesión con la BD. Tiene `DbSet` por cada tabla |
| `DbSet` | Colección que representa una tabla. Permite consultas LINQ |
| `[Table("...")]` | Nombre de la tabla en la BD |
| `[Key]` | Clave primaria |
| `[ForeignKey("...")]` | Clave foránea |
| `.Include(p => p.Local)` | Carga una relación (eager loading) |
| `.Where(condicion)` | Filtra registros (= WHERE en SQL) |
| `.ToList()` | Ejecuta la consulta y trae resultados |

### Entidades
```csharp
[Table("Partido")]
public class Partido
{
    [Key] public int PartidoId { get; set; }
    public DateTime Fecha { get; set; }
    public string Ciudad { get; set; }
    public string Estadio { get; set; }
    [ForeignKey("EquipoIdLocal")]    public Equipo Local { get; set; }
    [ForeignKey("EquipoIdVisitante")] public Equipo Visitante { get; set; }
}

[Table("Equipo")]
public class Equipo
{
    [Key] public int EquipoId { get; set; }
    public string Nombre { get; set; }
}
```

### DbContext
```csharp
internal class MundialFIFA2026Context : DbContext
{
    public DbSet<Equipo>  Equipos  { get; set; }
    public DbSet<Partido> Partidos { get; set; }

    protected override void OnConfiguring(DbContextOptionsBuilder opt)
    {
        opt.UseSqlServer("Server=localhost;Database=MundialFIFA2026;Integrated Security=True;TrustServerCertificate=True;");
        base.OnConfiguring(opt);
    }
}
```

### Consultas LINQ
```csharp
using (var context = new MundialFIFA2026Context())
{
    // Method Syntax
    var query = context.Partidos
        .Where(p => p.Local.Nombre.Contains(filtro.TextoABuscar)
                 || p.Visitante.Nombre.Contains(filtro.TextoABuscar))
        .Include(p => p.Local)
        .Include(p => p.Visitante);

    // Query Syntax (similar a SQL)
    var query2 = from p in context.Partidos
                 where p.Local.Nombre.Contains(filtro.TextoABuscar)
                    || p.Ciudad.Contains(filtro.TextoABuscar)
                 select p.Local;

    return query.ToList();  // ejecuta en la BD

    // COUNT
    return context.Partidos
        .Where(p => p.Local.Nombre.Contains(filtro.TextoABuscar))
        .Count();
}
```

> **ADO.NET vs EF Core**: ADO.NET = SQL manual (más control, más código). EF Core = LINQ automático (más rápido, menos control). El módulo 014 muestra AMBAS implementaciones en paralelo.

---

## 12. Blazor (Módulo 014)

Framework de Microsoft para crear interfaces web con C# en lugar de JavaScript. Componentes son archivos `.razor`.

| Concepto | Descripción |
|---|---|
| `.razor` | Archivo de componente. Mezcla HTML y C# con `@code { }` |
| `@page "/ruta"` | Hace que el componente sea una página navegable |
| `@code { }` | Bloque de lógica C# del componente |
| `@using` | Importa namespaces |
| `@inject` | Inyecta servicios en el componente |

```razor
@* Partidos.razor *@
@using TUP.Mundial.WebBlazor.Components.Comunes
@page "/partidos"
<PageTitle>Partidos fase de Grupo</PageTitle>
<PartidosComponent />

@* PartidosComponent.razor *@
<h3>Lista de Partidos</h3>
@if (partidos == null)
{
    <p>Cargando...</p>
}
else
{
    @foreach (var p in partidos)
    {
        <div>@p.Local.Nombre vs @p.Visitante.Nombre</div>
    }
}
@code {
    List<Partido> partidos;
    protected override async Task OnInitializedAsync()
    {
        var negocio = new PartidoNegocio();
        partidos = negocio.ObtenerListado();
    }
}
```

---

## 13. Configuración — appsettings.json (Módulo 015)

```json
{
    "TUP": {
        "Titulo": "Programacion III",
        "Habilitado": true
    },
    "ConnectionStrings": {
        "Conexion1": "Server=localhost;Database=DB1;...",
        "Conexion2": "Server=localhost;Database=DB2;..."
    }
}
```

```csharp
var configuration = builder.Configuration;

// Cargar archivos
configuration
    .AddJsonFile("appsettings.json", optional: false, reloadOnChange: true)
    .AddJsonFile($"appsettings.{builder.Environment.EnvironmentName}.json", optional: true, reloadOnChange: true)
    .AddEnvironmentVariables();

// 1) Valor directo
string titulo = configuration["TUP:Titulo"];

// 2) Connection string
string conn1 = configuration.GetConnectionString("Conexion1");
string conn2 = configuration["ConnectionStrings:Conexion2"];

// 3) Options Pattern — bind a clase tipada
var tupOptions = configuration.GetSection("TUP").Get<TUPOptions>();
Console.WriteLine(tupOptions.Titulo);
```

```csharp
// TUPOptions.cs
public class TUPOptions
{
    public string Titulo { get; set; }
    public bool Habilitado { get; set; }
}
```

| Concepto | Descripción |
|---|---|
| `appsettings.json` | Config base, siempre se carga |
| `appsettings.Development.json` | Sobreescribe para desarrollo local |
| `appsettings.Production.json` | Sobreescribe para producción |
| `configuration["Clave"]` | Acceso directo (`:` para secciones anidadas) |
| `GetConnectionString("x")` | Atajo para `ConnectionStrings:x` |
| `GetSection("TUP").Get<T>()` | Options Pattern: mapea sección a clase tipada |
| `reloadOnChange: true` | Recarga si el archivo cambia sin reiniciar |

---

## 14. Cheat Sheet — Lo más probable en el parcial

```csharp
// Minimal API mínima
var app = WebApplication.Create(args);
app.MapGet("/ruta", () => "Respuesta");
app.Run();

// Con Builder y servicios
var builder = WebApplication.CreateBuilder(args);
builder.Services.AddCors(...);
builder.Services.AddControllers();
var app = builder.Build();
app.UseCors(); app.UseAuthentication(); app.UseAuthorization(); app.MapControllers();
app.Run();

// ADO.NET
var conn = new SqlConnection(connStr);
var cmd = new SqlCommand("SELECT ...", conn);
conn.Open();
var reader = cmd.ExecuteReader();
while (reader.Read()) { var nombre = reader.GetString(reader.GetOrdinal("Nombre")); }
reader.Close(); conn.Close();

// EF Core LINQ
using (var ctx = new MiContext()) {
    var lista = ctx.Partidos.Where(p => p.Ciudad == "Resis").Include(p => p.Local).ToList();
}

// JWT — generar token
var claims = new[] { new Claim(ClaimTypes.Name, user), new Claim(ClaimTypes.Role, "User") };
var key   = new SymmetricSecurityKey(Encoding.UTF8.GetBytes(jwtKey));
var creds = new SigningCredentials(key, SecurityAlgorithms.HmacSha256);
var token = new JwtSecurityToken(issuer, audience, claims, expires: DateTime.UtcNow.AddMinutes(30), signingCredentials: creds);
return Results.Ok(new { token = new JwtSecurityTokenHandler().WriteToken(token) });

// MVC — Controller básico
public class JediController : Controller {
    [HttpGet]
    public IActionResult Index() { var jedis = negocio.GetAll(); return View(jedis); }
}

// Razor View
@model IEnumerable<Jedi>
@foreach (var j in Model) { <tr><td>@j.Id</td><td>@j.Name</td></tr> }

// fetch() con JWT
fetch('/api/time/secure', { headers: { 'Authorization': 'Bearer ' + token } })
    .then(r => r.json()).then(data => console.log(data)).catch(e => console.error(e));
```

### Lista de verificación del parcial

- [ ] **C# básico** — `var`, tipos, interpolación de strings, operador `??`
- [ ] **POO** — Clases, propiedades (get/set, auto-prop, calculada), constructores, `override ToString`
- [ ] **xUnit** — `[Fact]`, patrón Arrange-Act-Assert, `Assert.Equal`
- [ ] **HTML/JS** — Estructura HTML5, `addEventListener`, `fetch()`, manipulación del DOM
- [ ] **API mínima** — `WebApplication.Create` vs Builder, `MapGet/Post/Delete`, `Results.Ok/NotFound/Created`
- [ ] **Arquitectura capas** — Entidades → Datos → Negocio → API. Cada capa solo habla con la inferior
- [ ] **ADO.NET** — `SqlConnection` → `SqlCommand` → `Open()` → `ExecuteReader()` → `Read()` → `Close()`
- [ ] **CORS** — `AddCors` + `AddPolicy` + `UseCors`. Necesario cuando cliente y API son distintos orígenes
- [ ] **JWT** — `AddAuthentication` + `AddJwtBearer` + `TokenValidationParameters`. Claims. `[Authorize]` / `RequireAuthorization()`
- [ ] **MVC** — `Controller : Controller`, `IActionResult`, `return View(modelo)`. Razor: `@model`, `@foreach`, `@if`
- [ ] **EF Core** — `DbContext`, `DbSet`, `[Table]`, `[Key]`, `[ForeignKey]`. LINQ: `.Where`, `.Include`, `.ToList`
- [ ] **Configuración** — `appsettings.json`, `configuration["Clave"]`, `GetConnectionString`, Options Pattern
