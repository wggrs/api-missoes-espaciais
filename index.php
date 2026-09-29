<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

$registrosIniciais = [
    1 => ['id' => 1, 'nome' => 'Apollo 11', 'ano' => 1969, 'agencia' => 'NASA', 'status' => 'Concluída'],
    2 => ['id' => 2, 'nome' => 'Voyager 1', 'ano' => 1977, 'agencia' => 'NASA', 'status' => 'Em operação'],
    3 => ['id' => 3, 'nome' => 'Artemis II', 'ano' => 2026, 'agencia' => 'NASA', 'status' => 'Planejada'],
    4 => ['id' => 4, 'nome' => 'Sputnik 1', 'ano' => 1957, 'agencia' => 'URSS', 'status' => 'Concluída'],
    5 => ['id' => 5, 'nome' => 'Mars 2020', 'ano' => 2020, 'agencia' => 'NASA', 'status' => 'Em operação'],
];
$arquivoDados = __DIR__ . '/data/missoes.json';
if (is_file($arquivoDados)) {
    $salvos = json_decode((string) file_get_contents($arquivoDados), true);
    $missoes = is_array($salvos) ? array_column($salvos, null, 'id') : $registrosIniciais;
} else {
    $missoes = $registrosIniciais;
}

function salvarMissoes(string $arquivo, array $missoes): void
{
    $pasta = dirname($arquivo);
    if (!is_dir($pasta)) {
        mkdir($pasta, 0775, true);
    }
    file_put_contents($arquivo, json_encode(array_values($missoes), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
}

function json(Response $response, mixed $data, int $status = 200): Response
{
    $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus($status);
}

function validarMissao(mixed $dados): ?string
{
    if (!is_array($dados)) {
        return 'Envie um objeto JSON válido.';
    }
    foreach (['nome', 'agencia', 'status'] as $campo) {
        if (!isset($dados[$campo]) || !is_string($dados[$campo]) || trim($dados[$campo]) === '') {
            return "O campo {$campo} deve ser um texto não vazio.";
        }
    }
    if (!isset($dados['ano']) || !is_int($dados['ano']) || $dados['ano'] < 1) {
        return 'O campo ano deve ser um número inteiro positivo.';
    }
    return null;
}

function montarMissao(int $id, array $dados): array
{
    return [
        'id' => $id,
        'nome' => trim($dados['nome']),
        'ano' => $dados['ano'],
        'agencia' => trim($dados['agencia']),
        'status' => trim($dados['status']),
    ];
}

$app->get('/status', fn(Request $request, Response $response) => json($response, ['status' => 'ok']));

$app->get('/missoes', function (Request $request, Response $response) use (&$missoes): Response {
    return json($response, array_values($missoes));
});

$app->get('/missoes/{id:[0-9]+}', function (Request $request, Response $response, array $args) use (&$missoes): Response {
    $id = (int) $args['id'];
    return isset($missoes[$id])
        ? json($response, $missoes[$id])
        : json($response, ['erro' => 'Missão não encontrada.'], 404);
});

$app->post('/missoes', function (Request $request, Response $response) use (&$missoes, $arquivoDados): Response {
    $dados = $request->getParsedBody();
    if ($erro = validarMissao($dados)) {
        return json($response, ['erro' => $erro], 400);
    }
    $id = max(array_merge(array_keys($missoes), [0])) + 1;
    $missoes[$id] = montarMissao($id, $dados);
    salvarMissoes($arquivoDados, $missoes);
    return json($response->withHeader('Location', "/missoes/{$id}"), $missoes[$id], 201);
});

$app->put('/missoes/{id:[0-9]+}', function (Request $request, Response $response, array $args) use (&$missoes, $arquivoDados): Response {
    $id = (int) $args['id'];
    if (!isset($missoes[$id])) {
        return json($response, ['erro' => 'Missão não encontrada.'], 404);
    }
    $dados = $request->getParsedBody();
    if ($erro = validarMissao($dados)) {
        return json($response, ['erro' => $erro], 400);
    }
    $missoes[$id] = montarMissao($id, $dados);
    salvarMissoes($arquivoDados, $missoes);
    return json($response, $missoes[$id]);
});

$app->delete('/missoes/{id:[0-9]+}', function (Request $request, Response $response, array $args) use (&$missoes, $arquivoDados): Response {
    $id = (int) $args['id'];
    if (!isset($missoes[$id])) {
        return json($response, ['erro' => 'Missão não encontrada.'], 404);
    }
    unset($missoes[$id]);
    salvarMissoes($arquivoDados, $missoes);
    return $response->withStatus(204)->withHeader('Content-Type', 'application/json; charset=utf-8');
});

$errorMiddleware = $app->addErrorMiddleware(false, true, true);
$errorMiddleware->setDefaultErrorHandler(function (Request $request, Throwable $exception, bool $displayErrorDetails, bool $logErrors, bool $logErrorDetails) use ($app): Response {
    $status = $exception instanceof \Slim\Exception\HttpException ? $exception->getCode() : 500;
    if ($status < 400 || $status > 599) {
        $status = 500;
    }
    return json($app->getResponseFactory()->createResponse(), ['erro' => $status === 404 ? 'Rota não encontrada.' : 'Erro na requisição.'], $status);
});

$app->run();
