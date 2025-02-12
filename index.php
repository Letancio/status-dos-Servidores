<?php

class VerificarServer
{
    public function serverInfo($uri_site)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $uri_site);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true); // Remove o corpo da resposta
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $head = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $port = curl_getinfo($ch, CURLINFO_PRIMARY_PORT);
        $ip = gethostbyname(parse_url($uri_site, PHP_URL_HOST));
        $geo_info = $this->getGeoInfo($ip);

        curl_close($ch);

        $ssl_info = $this->getSSLInfo($uri_site);

        return [
            'status' => ($httpCode < 400) ? 'Ativo' : 'Inativo',
            'http_code' => $httpCode,
            'porta' => $port,
            'ip' => $ip,
            'geo_localizacao' => $geo_info,
            'ssl' => $ssl_info
        ];
    }

    private function getSSLInfo($url)
    {
        $stream = @stream_context_create(["ssl" => ["capture_peer_cert" => true]]);
        $socket = @stream_socket_client("ssl://" . parse_url($url, PHP_URL_HOST) . ":443", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $stream);

        if (!$socket) {
            return ['status' => 'Não possui SSL válido'];
        }

        $context = stream_context_get_params($socket);
        $cert = openssl_x509_parse($context['options']['ssl']['peer_certificate']);

        return [
            'emitido_para' => $cert['subject']['CN'] ?? 'Desconhecido',
            'emitido_por' => $cert['issuer']['CN'] ?? 'Desconhecido',
            'validade_inicio' => date('Y-m-d', $cert['validFrom_time_t']),
            'validade_fim' => date('Y-m-d', $cert['validTo_time_t'])
        ];
    }

    private function getGeoInfo($ip)
    {
        $url = "http://ip-api.com/json/" . $ip;
        $response = @file_get_contents($url);
        return $response ? json_decode($response, true) : ['status' => 'Não foi possível obter informações geográficas'];
    }
}

$sites = [
    "https://antoniogoncalves.ba.gov.br",
    "https://brejolandia.ba.gov.br",
    "https://caldeiraogrande.ba.gov.br",
    "https://camaraacajutiba.ba.gov.br",
    "https://camaraalcobaca.ba.gov.br",
    "https://camaraantoniogoncalves.ba.gov.br",
    "https://camarabrejoes.ba.gov.br",
    "https://camaracaldeiraogrande.ba.gov.br",
    "https://camaracasanova.ba.gov.br",
    "https://camaradearamari.ba.gov.br",
    "https://camaraibiquera.ba.gov.br",
    "https://camaraipira.ba.gov.br",
    "https://camarajacobina.ba.gov.br",
    "https://camaraolindina.ba.gov.br",
    "https://camaranovasoure.ba.gov.br",
    "https://camaraouricangas.ba.gov.br",
    "https://camarapintadas.ba.gov.br",
    "https://camaradesaude.ba.gov.br",
    "https://camarasobradinho.ba.gov.br",
    "https://candeal.ba.gov.br",
    "https://consorciosaudesrdobonfim.ba.gov.br",
    "https://constesf.ba.gov.br",
    "https://cxprevantoniogoncalves.ba.gov.br",
    "https://imcompras.org",
    "https://impublicacoes.org",
    "https://jussara.ba.gov.br",
    "https://muquemdosaofrancisco.ba.gov.br",
    "https://oliveiradosbrejinhos.ba.gov.br",
    "https://olindina.ba.gov.br",
    "https://saaeburitirama.ba.gov.br",
    "https://saaecasanova.ba.gov.br",
    "https://saaepilaoarcado.ba.gov.br",
    "https://saaesobradinho.ba.gov.br",
    "https://smttalagoinhas.ba.gov.br",
    "https://sobradinho.ba.gov.br",
    "https://www.pindobacu.ba.gov.br"
];

$serverChecker = new VerificarServer();
$siteData = [];

foreach ($sites as $site) {
    $siteData[$site] = $serverChecker->serverInfo($site);
}
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status dos Servidores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-4">
        <h2 class="mb-4">Status dos Servidores</h2>
        <div class="row">
            <?php foreach ($siteData as $site => $data) : ?>
                <div class="col-md-4 mb-4">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <?= htmlspecialchars($site) ?>
                        </div>
                        <div class="card-body">
                            <pre><?= json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?></pre>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>

</html>