using System.Net;
using System.Net.Http.Headers;
using System.Net.Http.Json;
using System.Text.Json;
using Microsoft.Extensions.DependencyInjection;
using Microsoft.Extensions.Options;
using Mundial.Data.Config;

namespace Mundial.Tests;

public sealed class MundialApiTests(MundialApiFactory factory) : IClassFixture<MundialApiFactory>
{
    [Fact]
    public void Configuracion_Mundial_SeBindeaCorrectamente()
    {
        var options = factory.Services.GetRequiredService<IOptions<MundialConfig>>();

        Assert.Equal("Mundial 2026 Fase de Grupos", options.Value.Titulo);
        Assert.Equal("mundial_2026_fase_grupos_partidos.json", options.Value.DatosEnJson);
        Assert.Equal(8, options.Value.PartidosAVisualizar);
    }

    [Fact]
    public async Task AuthToken_ConCredencialesCorrectas_DevuelveJwt()
    {
        var client = factory.CreateClient();

        var response = await client.PostAsJsonAsync("/api/auth/token", new
        {
            username = "admin",
            password = "admin123"
        });

        Assert.Equal(HttpStatusCode.OK, response.StatusCode);

        using var json = await JsonDocument.ParseAsync(await response.Content.ReadAsStreamAsync());
        Assert.False(string.IsNullOrWhiteSpace(json.RootElement.GetProperty("accessToken").GetString()));
        Assert.Equal("Bearer", json.RootElement.GetProperty("tokenType").GetString());
    }

    [Fact]
    public async Task AuthToken_ConCredencialesIncorrectas_DevuelveUnauthorized()
    {
        var client = factory.CreateClient();

        var response = await client.PostAsJsonAsync("/api/auth/token", new
        {
            username = "admin",
            password = "incorrecta"
        });

        Assert.Equal(HttpStatusCode.Unauthorized, response.StatusCode);
    }

    [Fact]
    public async Task GetPartidos_ConPageUno_DevuelveOchoItems()
    {
        var client = factory.CreateClient();
        await AuthenticateAsync(client);

        var response = await client.GetAsync("/api/partido?page=1");

        Assert.Equal(HttpStatusCode.OK, response.StatusCode);

        using var json = await JsonDocument.ParseAsync(await response.Content.ReadAsStreamAsync());
        Assert.Equal(1, json.RootElement.GetProperty("page").GetInt32());
        Assert.Equal(8, json.RootElement.GetProperty("pageSize").GetInt32());
        Assert.Equal(72, json.RootElement.GetProperty("totalItems").GetInt32());
        Assert.Equal(8, json.RootElement.GetProperty("items").GetArrayLength());
    }

    [Fact]
    public async Task GetPartidosPorPais_ConCodigoFifa_FiltraResultados()
    {
        var client = factory.CreateClient();
        await AuthenticateAsync(client);

        var response = await client.GetAsync("/api/partido/MEX");

        Assert.Equal(HttpStatusCode.OK, response.StatusCode);

        using var json = await JsonDocument.ParseAsync(await response.Content.ReadAsStreamAsync());
        var root = json.RootElement;
        Assert.Equal("MEX", root.GetProperty("pais").GetString());
        Assert.True(root.GetProperty("totalItems").GetInt32() > 0);

        foreach (var item in root.GetProperty("items").EnumerateArray())
        {
            var equipoLocalCodigo = item.GetProperty("equipoLocalCodigo").GetString();
            var equipoVisitanteCodigo = item.GetProperty("equipoVisitanteCodigo").GetString();
            Assert.True(
                string.Equals(equipoLocalCodigo, "MEX", StringComparison.OrdinalIgnoreCase) ||
                string.Equals(equipoVisitanteCodigo, "MEX", StringComparison.OrdinalIgnoreCase));
        }
    }

    private static async Task AuthenticateAsync(HttpClient client)
    {
        var response = await client.PostAsJsonAsync("/api/auth/token", new
        {
            username = "admin",
            password = "admin123"
        });

        response.EnsureSuccessStatusCode();

        using var json = await JsonDocument.ParseAsync(await response.Content.ReadAsStreamAsync());
        var token = json.RootElement.GetProperty("accessToken").GetString();
        client.DefaultRequestHeaders.Authorization = new AuthenticationHeaderValue("Bearer", token);
    }
}
