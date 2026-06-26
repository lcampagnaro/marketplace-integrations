<?php
/**
 * Integração Tiny ERP
 * Busca pedido pelo número, obtém detalhes completos e retorna
 * no formato padrão do sistema próprio - ajuste conforme sua necessidade.
 *
 * GET /getPedido.php?numero=12345
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
$token  = $config['tiny']['token'];
$baseUrl = $config['tiny']['base_url'];

// Validação do parâmetro de entrada
$numero = filter_input(INPUT_GET, 'numero', FILTER_SANITIZE_SPECIAL_CHARS);
if (empty($numero)) {
    http_response_code(400);
    echo json_encode(['erro' => 'Número do pedido não informado.']);
    exit;
}

// Busca o ID do pedido pelo número
$resposta = enviarREST("$baseUrl/pedidos.pesquisa.php", "token=$token&numero=$numero&formato=JSON");
$dadosPedido = json_decode($resposta, true);

if (!isset($dadosPedido['retorno']['pedidos'][0]['pedido']['id'])) {
    http_response_code(404);
    echo json_encode(['erro' => 'Pedido não encontrado ou resposta inválida.']);
    exit;
}

$id = $dadosPedido['retorno']['pedidos'][0]['pedido']['id'];

// Obtém detalhes completos do pedido
$respostaDetalhes = enviarREST("$baseUrl/pedido.obter.php", "token=$token&id=$id&formato=JSON");
$dadosDetalhados  = json_decode($respostaDetalhes, true);

if (!isset($dadosDetalhados['retorno']['pedido'])) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao obter detalhes do pedido.']);
    exit;
}

$pedido  = $dadosDetalhados['retorno']['pedido'];
$cliente = $pedido['cliente'];
$nota    = $pedido['notaFiscal'][0] ?? [];

// Normalização para o formato padrão PLAC
$retorno = [
    'nome'                  => $cliente['nome']          ?? '',
    'cpf'                   => $cliente['cpf']           ?? '',
    'cnpj'                  => $cliente['cnpj']          ?? '',
    'razao'                 => $cliente['razaoSocial']   ?? '',
    'tel'                   => $cliente['fone']          ?? '',
    'email'                 => $cliente['email']         ?? '',
    'logradouro'            => $cliente['endereco']      ?? '',
    'numero'                => $cliente['numero']        ?? '',
    'complemento'           => $cliente['complemento']   ?? '',
    'bairro'                => $cliente['bairro']        ?? '',
    'cidade'                => $cliente['cidade']        ?? '',
    'uf'                    => $cliente['uf']            ?? '',
    'cep'                   => $cliente['cep']           ?? '',
    'tipoEnvio'             => $pedido['formaEnvio']     ?? '',
    'chaveNF'               => $nota['chaveAcesso']      ?? '',
    'NF'                    => $nota['numero']           ?? '',
    'serieNF'               => $nota['serie']            ?? '',
    'valorNF'               => $nota['valor']            ?? '',
    'dataEmissaoNF'         => $nota['dataEmissao']      ?? '',
    'chaveDC'               => '',
    'DC'                    => '',
    'serieDC'               => '',
    'valorDC'               => '',
    'dataEmissaoDC'         => date('Y-m-d'),
    'tipoNF'                => 'NFE',
    'pedido'                => $pedido['numero']         ?? '',
    'valorFretePagoCliente' => $pedido['valorFrete']     ?? '',
    'produtos'              => $pedido['itens']          ?? [],
];

echo json_encode($retorno, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

/**
 * Envia requisição POST para APIs REST estilo Tiny (application/x-www-form-urlencoded)
 */
function enviarREST(string $url, string $data): string {
    $params = [
        'http' => [
            'method'  => 'POST',
            'content' => $data,
            'header'  => 'Content-Type: application/x-www-form-urlencoded',
        ]
    ];

    $ctx      = stream_context_create($params);
    $fp       = @fopen($url, 'rb', false, $ctx);
    $response = $fp ? @stream_get_contents($fp) : false;

    if ($response === false) {
        throw new RuntimeException("Erro ao acessar: $url");
    }

    return $response;
}
