<?php
/**
 * Integração Plataforma Soma (Swagger)
 * Busca pedido por ID via API REST e retorna dados normalizados
 * no formato padrão do sistema próprio - ajuste conforme sua necessidade.
 *
 * GET /getPedido_soma.php?id=12345
 */

session_start();

// Validação de acesso
if (($_SESSION['login'] ?? false) !== true || ($_SESSION['permissao']['processar'] ?? false) !== true) {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso não autorizado.']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/../config/config.php';
$bearer  = $config['soma']['bearer'];
$baseUrl = $config['soma']['base_url'];

// Validação do parâmetro de entrada
$idPedido = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_SPECIAL_CHARS);
if (empty($idPedido)) {
    http_response_code(400);
    echo json_encode(['erro' => 'ID do pedido não foi fornecido.']);
    exit;
}

// Requisição à API da Plataforma Soma
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $baseUrl . $idPedido,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_CUSTOMREQUEST  => 'GET',
    CURLOPT_HTTPHEADER     => [
        'Accept: application/json',
        'Authorization: Bearer ' . $bearer,
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha na requisição: ' . $curlError]);
    exit;
}

// A API pode retornar JSON dentro de string — trata ambos os casos
$raw  = json_decode($response, true);
$dados = is_string($raw) ? json_decode($raw, true) : $raw;

if (!is_array($dados)) {
    http_response_code(500);
    echo json_encode(['erro' => 'Resposta inesperada da API.']);
    exit;
}

// Normalização para o formato padrão PLAC
$retorno = [
    'nome'       => $dados['cliente']['nome']                     ?? '',
    'cnpj'       => $dados['cliente']['documento']                ?? '',
    'razao'      => '',
    'tel'        => ($dados['enderecoEntrega']['celularDdd'] ?? '') . ($dados['enderecoEntrega']['celular'] ?? ''),
    'email'      => $dados['cliente']['email']                    ?? '',
    'logradouro' => $dados['enderecoEntrega']['endereco']         ?? '',
    'numero'     => $dados['enderecoEntrega']['numero']           ?? '',
    'complemento'=> $dados['enderecoEntrega']['complemento']      ?? '',
    'bairro'     => $dados['enderecoEntrega']['bairro']           ?? '',
    'cidade'     => $dados['enderecoEntrega']['cidade']           ?? '',
    'uf'         => $dados['enderecoEntrega']['estadoId']         ?? '',
    'cep'        => $dados['enderecoEntrega']['cep']              ?? '',
    'tipoEnvio'  => '',
    'chaveNF'    => '',
    'NFnf'       => '',
    'serieNF'    => '',
    'valorNF'    => $dados['valorTotal']                          ?? '',
    'dataEmissaoNF' => $dados['data']                            ?? '',
    'chaveDC'    => '',
    'DC'         => '',
    'serieDC'    => '',
    'valorDC'    => '',
    'dataEmissaoDC' => '',
    'tipoNF'     => 'NFE',
    'pedido'     => $dados['pedidoCodigo']                        ?? $idPedido,
    'valorFretePagoCliente' => $dados['valorFrete']               ?? 0,
    'produtos'   => [],
];

// Normaliza lista de produtos
foreach ($dados['produtos'] ?? [] as $item) {
    $retorno['produtos'][] = [
        'nome'       => $item['nome']       ?? '',
        'sku'        => $item['produtoId']  ?? '',
        'quantidade' => $item['quantidade'] ?? 1,
        'valor'      => $item['valorTotal'] ?? 0,
    ];
}

echo json_encode($retorno, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
