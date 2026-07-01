using System.Text.Json;
using Microsoft.Extensions.Options;
using Mundial.Data.Config;
using Mundial.Data.Models;

namespace Mundial.Data.Repositories;

public sealed class PartidoRepository(IOptions<MundialConfig> options) : IPartidoRepository
{
    private static readonly JsonSerializerOptions JsonOptions = new()
    {
        PropertyNameCaseInsensitive = true
    };

    private readonly MundialConfig _config = options.Value;

    public async Task<IReadOnlyList<Partido>> GetAllAsync(CancellationToken cancellationToken = default)
    {
        var path = ResolveJsonPath(_config.DatosEnJson);
        await using var stream = File.OpenRead(path);
        var document = await JsonSerializer.DeserializeAsync<MundialJsonDocument>(stream, JsonOptions, cancellationToken);

        return document?.Partidos ?? [];
    }

    private static string ResolveJsonPath(string configuredPath)
    {
        if (string.IsNullOrWhiteSpace(configuredPath))
        {
            throw new InvalidOperationException("La configuracion Mundial:DatosEnJson no puede estar vacia.");
        }

        var candidates = new List<string>
        {
            Path.IsPathRooted(configuredPath) ? configuredPath : Path.Combine(AppContext.BaseDirectory, configuredPath),
            Path.IsPathRooted(configuredPath) ? configuredPath : Path.Combine(Directory.GetCurrentDirectory(), configuredPath)
        };

        var current = new DirectoryInfo(Directory.GetCurrentDirectory());
        while (current is not null)
        {
            candidates.Add(Path.Combine(current.FullName, configuredPath));
            current = current.Parent;
        }

        foreach (var candidate in candidates.Distinct(StringComparer.OrdinalIgnoreCase))
        {
            if (File.Exists(candidate))
            {
                return candidate;
            }
        }

        throw new FileNotFoundException($"No se encontro el archivo de datos '{configuredPath}'.", configuredPath);
    }
}
