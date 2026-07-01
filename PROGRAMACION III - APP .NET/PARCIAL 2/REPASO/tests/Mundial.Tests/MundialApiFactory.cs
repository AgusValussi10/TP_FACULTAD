using Microsoft.AspNetCore.Hosting;
using Microsoft.AspNetCore.Mvc.Testing;
using Microsoft.Extensions.Configuration;

namespace Mundial.Tests;

public sealed class MundialApiFactory : WebApplicationFactory<Program>
{
    protected override void ConfigureWebHost(IWebHostBuilder builder)
    {
        builder.ConfigureAppConfiguration((_, config) =>
        {
            config.AddInMemoryCollection(new Dictionary<string, string?>
            {
                ["Mundial:Credenciales:Username"] = "admin",
                ["Mundial:Credenciales:Password"] = "admin123",
                ["Jwt:SecretKey"] = "clave-super-secreta-para-tests-y-desarrollo-minimo-32-caracteres",
                ["Jwt:Issuer"] = "Mundial.Api",
                ["Jwt:Audience"] = "Mundial.Api",
                ["Jwt:ExpiresMinutes"] = "60"
            });
        });
    }
}
