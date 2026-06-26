<?php
/**
 * Integração VNDA
 * Busca pedido por ID, normaliza os dados e persiste na tabela
 * preEnvio para processamento logístico.
 * no formato padrão do sistema próprio - ajuste conforme sua necessidade.
 *
 * GET /getPedido_vnda.php?id=12345
 */

session_start();

// Validação de acesso
if (($_SESSION['login'] ?? false) !== true || ($_SESSION['permissao']['processar'] ?? false) !== true) {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso não autorizado.']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$config  = require __DIR__ . '/../config/config.php';
$bearer  = $config['vnda']['bearer'];
$baseUrl = $config['vnda']['base_url'];
$dbCfg   = $config['db'];

// Validação do parâmetro de entrada
$pedidoId = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_SPECIAL_CHARS);
if (empty($pedidoId)) {
    http_response_code(400);
    echo json_encode(['erro' => 'ID do pedido não informado.']);
    exit;
}

// Detecta ambiente (dev ou produção) para definir o shop host
$isDev    = str_contains($_SERVER['HTTP_HOST'] ?? '', 'dev.');
$shopHost = $isDev ? 'homolog.suaplataforma.com.br' : 'www.suaplataforma.com.br';

// Requisição à API VNDA
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => "$baseUrl/$pedidoId",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST  => 'GET',
    CURLOPT_HTTPHEADER     => [
        "X-Shop-Host: $shopHost",
        'Accept: application/json',
        'Authorization: Bearer ' . $bearer,
    ],
]);

$response  = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha na requisição: ' . $curlError]);
    exit;
}

$dados = json_decode($response, true);
if (!is_array($dados)) {
    http_response_code(500);
    echo json_encode(['erro' => 'Resposta inesperada da API VNDA.']);
    exit;
}

// Normalização dos dados recebidos
$preEnvio = [
    'id_cliente'       => $_SESSION['id_cliente'] ?? 1,
    'dataHora'         => date('Y-m-d H:i:s'),
    'destNome'         => $dados['client']['name']              ?? '',
    'destCPF'          => $dados['client']['cpf']               ?? '',
    'destCNPJ'         => $dados['client']['cnpj']              ?? '',
    'destTel'          => $dados['client']['phone']             ?? '',
    'destEmail'        => $dados['client']['email']             ?? '',
    'destCEP'          => $dados['address']['zip_code']         ?? '',
    'destLogradouro'   => $dados['address']['street']           ?? '',
    'destNumero'       => $dados['address']['number']           ?? '',
    'destBairro'       => $dados['address']['neighborhood']     ?? '',
    'destCidade'       => $dados['address']['city']             ?? '',
    'destUF'           => $dados['address']['state']            ?? '',
    'pedido'           => $pedidoId,
    'NFchave'          => $dados['invoice']['key']              ?? '',
    'NFnum'            => $dados['invoice']['number']           ?? 0,
    'NFvalor'          => $dados['total']                       ?? 0.00,
    'envioValor'       => $dados['shipping_price']              ?? 0.00,
    'envioNomeServico' => $dados['shipping_method']             ?? 'N/A',
];

// Persistência com prepared statement (previne SQL injection)
$mysqli = new mysqli($dbCfg['host'], $dbCfg['user'], $dbCfg['pass'], $dbCfg['name']);

if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha na conexão com o banco de dados.']);
    exit;
}

$sql = "INSERT INTO preEnvio (
            id_cliente, dataHora, destNome, destCPF, destCNPJ, destTel, destEmail,
            destLogradouro, destNumero, destBairro, destCidade, destUF, destCEP,
            pedido, NFchave, NFnum, NFvalor, envioValor, envioNomeServico
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $mysqli->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao preparar query.']);
    exit;
}

$stmt->bind_param(
    'issssssssssssssidds',
    $preEnvio['id_cliente'],
    $preEnvio['dataHora'],
    $preEnvio['destNome'],
    $preEnvio['destCPF'],
    $preEnvio['destCNPJ'],
    $preEnvio['destTel'],
    $preEnvio['destEmail'],
    $preEnvio['destLogradouro'],
    $preEnvio['destNumero'],
    $preEnvio['destBairro'],
    $preEnvio['destCidade'],
    $preEnvio['destUF'],
    $preEnvio['destCEP'],
    $preEnvio['pedido'],
    $preEnvio['NFchave'],
    $preEnvio['NFnum'],
    $preEnvio['NFvalor'],
    $preEnvio['envioValor'],
    $preEnvio['envioNomeServico']
);

if ($stmt->execute()) {
    echo json_encode(['status' => 'sucesso', 'id_pre_envio' => $stmt->insert_id]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'erro', 'msg' => 'Erro ao salvar no banco.']);
}

$stmt->close();
$mysqli->close();
