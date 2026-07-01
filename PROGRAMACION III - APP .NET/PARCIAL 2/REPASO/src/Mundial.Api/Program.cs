using System.IdentityModel.Tokens.Jwt;
using System.Security.Claims;
using System.Text;
using Microsoft.AspNetCore.Authentication.JwtBearer;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.Extensions.Options;
using Microsoft.IdentityModel.Tokens;
using Mundial.Api.Security;
using Mundial.Business.Services;
using Mundial.Data.Config;
using Mundial.Data.Repositories;

var builder = WebApplication.CreateBuilder(args);

builder.Services.AddOpenApi();

builder.Services
    .AddOptions<MundialConfig>()
    .Bind(builder.Configuration.GetSection("XXX"))
    .ValidateOnStart();

builder.Services
    .AddOptions<JwtConfig>()
    .Bind(builder.Configuration.GetSection("YYY"))
    .ValidateOnStart();

var jwtConfig = builder.Configuration.GetSection("YYY").Get<JwtConfig>() ?? new JwtConfig();
var signingKey = new SymmetricSecurityKey(Encoding.UTF8.GetBytes(jwtConfig.SecretKey));

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
            ValidIssuer = jwtConfig.Issuer,
            ValidAudience = jwtConfig.Audience,
            IssuerSigningKey = signingKey,
            ClockSkew = TimeSpan.FromMinutes(1)
        };
    });

builder.Services.AddAuthorization();
builder.Services.AddSingleton<IPartidoRepository, PartidoRepository>();
builder.Services.AddScoped<IPartidoService, PartidoService>();

var app = builder.Build();

if (app.Environment.IsDevelopment())
{
    app.MapOpenApi();
}

app.UseHttpsRedirection();
app.UseAuthentication();
app.UseAuthorization();

app.MapPost("/api/auth/token", (
    [FromBody] TokenRequest request,
    IOptions<MundialConfig> mundialOptions,
    IOptions<JwtConfig> jwtOptions) =>
{
    var credentials = mundialOptions.Value.Credenciales;
    if (!string.Equals(request.Username, credentials.Username, StringComparison.Ordinal) ||
        !string.Equals(request.Password, credentials.Password, StringComparison.Ordinal))
    {
        return Results.Unauthorized();
    }

    var jwt = jwtOptions.Value;
    var expires = DateTime.UtcNow.AddMinutes(jwt.ExpiresMinutes);
    var claims = new[]
    {
        new Claim(JwtRegisteredClaimNames.Sub, request.Username),
        new Claim(JwtRegisteredClaimNames.Jti, Guid.NewGuid().ToString("N"))
    };

    var token = new JwtSecurityToken(
        issuer: jwt.Issuer,
        audience: jwt.Audience,
        claims: claims,
        expires: expires,
        signingCredentials: new SigningCredentials(
            new SymmetricSecurityKey(Encoding.UTF8.GetBytes(jwt.SecretKey)),
            SecurityAlgorithms.HmacSha256));

    var accessToken = new JwtSecurityTokenHandler().WriteToken(token);
    return Results.Ok(new TokenResponse(accessToken, "Bearer", jwt.ExpiresMinutes * 60));
})
.AllowAnonymous()
.WithName("CreateToken");



app.MapGet("/api/partido", async (
    [FromQuery] int? page,
    IPartidoService partidoService,
    CancellationToken cancellationToken) =>
{
    var currentPage = page ?? 1;
    if (currentPage < 1)
    {
        return Results.BadRequest(new { error = "La pagina debe ser mayor o igual a 1." });
    }

    var result = await partidoService.GetPagedAsync(currentPage, cancellationToken);

    return Results.Ok(result);
})
.WithName("GetPartidos");




app.MapPost("/api/partido/{pais}", async (
    string pais,
    IPartidoService partidoService,
    CancellationToken cancellationToken) =>
{
    if (string.IsNullOrWhiteSpace(pais))
    {
        return Results.BadRequest(new { error = "El pais es requerido." });
    }

    var result = await partidoService.GetByPaisAsync(pais, cancellationToken);

    return Results.Ok(result);
})
.WithName("GetPartidosPorPais");

app.Run();

public sealed record TokenRequest(string Username, string Password);

public sealed record TokenResponse(string AccessToken, string TokenType, int ExpiresIn);

public partial class Program;
