using Dapper;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using MySqlConnector;
using System.Net.Http.Headers;
using System.Text;
using System.Text.Json;

namespace UnionDelSur.API.Controllers;

[ApiController]
[Route("api/ia")]
[Authorize(Roles = "admin")]
public class IaController : ControllerBase
{
    private readonly IConfiguration _config;
    private static readonly HttpClient _http = new();

    public IaController(IConfiguration config) => _config = config;

    [HttpPost("recordatorio")]
    public async Task<IActionResult> Recordatorio([FromBody] RecordatorioRequest req)
    {
        await using var conn = new MySqlConnection(_config.GetConnectionString("Default"));

        var jugadores = await conn.QueryAsync<dynamic>(
            "SELECT CONCAT(nombre, ' ', apellido) AS nombre FROM jugadores WHERE id = ANY(@ids) AND activo = 1",
            new { ids = req.JugadorIds.ToArray() });

        var nombresLista = string.Join(", ", jugadores.Select(j => (string)j.nombre));
        var tipoTexto = req.Tipo switch {
            "morosidad" => "cuotas mensuales pendientes de pago",
            "seguro" => "el seguro deportivo vencido o próximo a vencer",
            "carnet" => "el carnet de liga vencido o pendiente de tramitar",
            _ => "documentación pendiente"
        };

        var apiKey = _config["Anthropic:ApiKey"];
        if (!string.IsNullOrEmpty(apiKey))
        {
            try
            {
                var mensaje = await GenerarMensajeConIA(apiKey, nombresLista, tipoTexto);
                return Ok(new { mensaje });
            }
            catch { /* fallback */ }
        }

        var fallback = $"Estimada familia,\n\nLe informamos que el jugador {nombresLista} registra {tipoTexto}.\n\nLe solicitamos que se acerque a la secretaría del club para regularizar su situación a la brevedad.\n\nQuedamos a su disposición para cualquier consulta.\n\nAtentamente,\nSecretaría — Fundación Unión del Sur";
        return Ok(new { mensaje = fallback });
    }

    private static async Task<string> GenerarMensajeConIA(string apiKey, string jugadores, string motivo)
    {
        var requestBody = new
        {
            model = "claude-haiku-4-5-20251001",
            max_tokens = 400,
            messages = new[] { new {
                role = "user",
                content = $"Redactá un mensaje breve y amable en español rioplatense para enviar a las familias del club de fútbol infantil 'Unión del Sur' de Resistencia, Chaco. El motivo es: {jugadores} tiene(n) {motivo}. El mensaje debe ser corto (máx 6 líneas), formal pero cálido, y terminar con 'Gracias, Secretaría Unión del Sur'."
            }}
        };

        using var request = new HttpRequestMessage(HttpMethod.Post, "https://api.anthropic.com/v1/messages");
        request.Headers.Add("x-api-key", apiKey);
        request.Headers.Add("anthropic-version", "2023-06-01");
        request.Content = new StringContent(JsonSerializer.Serialize(requestBody), Encoding.UTF8, "application/json");

        var response = await _http.SendAsync(request);
        var json = await response.Content.ReadAsStringAsync();
        using var doc = JsonDocument.Parse(json);
        return doc.RootElement
            .GetProperty("content")[0]
            .GetProperty("text")
            .GetString() ?? "";
    }
}

public record RecordatorioRequest(string Tipo, List<int> JugadorIds);
