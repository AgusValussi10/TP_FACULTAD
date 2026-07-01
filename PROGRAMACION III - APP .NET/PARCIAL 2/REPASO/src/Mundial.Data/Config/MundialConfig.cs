namespace Mundial.Data.Config;

public sealed class MundialConfig
{
    public string Titulo { get; set; } = string.Empty;
    public string DatosEnJson { get; set; } = string.Empty;
    public int PartidosAVisualizar { get; set; }
    public MundialCredencialesConfig Credenciales { get; set; } = new();
}

public sealed class MundialCredencialesConfig
{
    public string Username { get; set; } = string.Empty;
    public string Password { get; set; } = string.Empty;
}