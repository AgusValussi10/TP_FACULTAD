<?php

/**
 * Cliente HTTP mínimo con cookies propias: cada instancia es "un navegador"
 * distinto, con su propia sesión PHP.
 */
class HttpClient
{
    private string $cookieJar;

    public function __construct()
    {
        $this->cookieJar = tempnam(sys_get_temp_dir(), 'cookies_');
    }

    public function __destruct()
    {
        @unlink($this->cookieJar);
    }

    /** Inicia sesión igual que el modal de la landing. Devuelve el JSON de login.php. */
    public function login(string $usuario, string $password, string $opcionRol): array
    {
        return $this->post('/auth/login.php', [
            'usuario'  => $usuario,
            'password' => $password,
            'rol'      => $opcionRol,
        ])['json'];
    }

    /** @return array{status:int, json:?array, body:string} */
    public function get(string $ruta): array
    {
        return $this->enviar('GET', $ruta);
    }

    /** @return array{status:int, json:?array, body:string} */
    public function post(string $ruta, array $datos): array
    {
        return $this->enviar('POST', $ruta, $datos);
    }

    private function enviar(string $metodo, string $ruta, array $datos = []): array
    {
        $ch = curl_init(TEST_BASE_URL . $ruta);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR      => $this->cookieJar,
            CURLOPT_COOKIEFILE     => $this->cookieJar,
            CURLOPT_TIMEOUT        => 15,
        ]);
        if ($metodo === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($datos));
        }
        $body   = (string) curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        $json = json_decode($body, true);
        return ['status' => $status, 'json' => is_array($json) ? $json : null, 'body' => $body];
    }
}
