# API REST de Missões Espaciais

## Identificação

- **Aluno:** Arthur Wiggers
- **Curso:** 2°info.
- **Unidade Curricular:** Desenvolver Serviços Web

## Descrição

API REST para cadastrar, listar, consultar, atualizar e remover missões espaciais. Começa com cinco registros em um array PHP. As alterações são gravadas automaticamente em `data/missoes.json`, criado na primeira modificação. Para voltar aos cinco exemplos originais, apague esse arquivo com o servidor desligado.

## Tecnologias

PHP 8.1+, Slim Framework 4, Composer e JSON. Não requer banco de dados.

## Clonar, instalar e executar

```bash
git clone https://github.com/wggrs/api-missoes-espaciais.git
cd api-missoes-espaciais
composer install
php -S localhost:8080 index.php
```

Abra `http://localhost:8080/status`. Deixe o servidor aberto durante os testes. Se a pasta `data` ainda não existir, o PHP precisa ter permissão para criá-la.

## Contrato dos endpoints

Todas as respostas com corpo usam `Content-Type: application/json; charset=utf-8`. Envie `Content-Type: application/json` em POST e PUT. O `id` é definido pela API. PUT substitui os quatro campos da missão. Um ID inexistente retorna `404`; dados inválidos retornam `400`. DELETE bem-sucedido retorna `204`, sem corpo.

| Método | URL | Objetivo | HTTP de sucesso |
| --- | --- | --- | --- |
| GET | `/status` | Verificar a API | 200 |
| GET | `/missoes` | Listar todas | 200 |
| GET | `/missoes/{id}` | Buscar por ID | 200 |
| POST | `/missoes` | Cadastrar | 201 |
| PUT | `/missoes/{id}` | Atualizar | 200 |
| DELETE | `/missoes/{id}` | Remover | 204 |

### Exemplos de requisição e resposta

```http
GET /status
```
```json
{"status":"ok"}
```

```http
GET /missoes
```
Retorna um array JSON com as cinco missões iniciais, começando por:
```json
[{"id":1,"nome":"Apollo 11","ano":1969,"agencia":"NASA","status":"Concluída"}]
```
O exemplo acima mostra o primeiro elemento; a resposta real inclui todos os registros existentes.

```http
GET /missoes/1
```
```json
{"id":1,"nome":"Apollo 11","ano":1969,"agencia":"NASA","status":"Concluída"}
```

```http
POST /missoes
Content-Type: application/json

{"nome":"Artemis III","ano":2027,"agencia":"NASA","status":"Planejada"}
```
Resposta `201 Created`, com o `id` atribuído e o cabeçalho `Location: /missoes/6` no estado inicial:
```json
{"id":6,"nome":"Artemis III","ano":2027,"agencia":"NASA","status":"Planejada"}
```

```http
PUT /missoes/6
Content-Type: application/json

{"nome":"Artemis III","ano":2027,"agencia":"NASA","status":"Em preparação"}
```
```json
{"id":6,"nome":"Artemis III","ano":2027,"agencia":"NASA","status":"Em preparação"}
```

```http
DELETE /missoes/6
```
Resposta `204 No Content`, sem corpo.

Exemplo de erro para `GET /missoes/999` (`404 Not Found`):
```json
{"erro":"Missão não encontrada."}
```

## Testes no Postman ou Insomnia

Com o servidor iniciado, execute as seis requisições acima na ordem apresentada. Configure o corpo de POST e PUT como **raw JSON**. Confira o código HTTP, o cabeçalho `Content-Type`, os dados retornados e se `GET /missoes/6` reflete o POST, depois o PUT e, após DELETE, responde `404`. Se já houver dados de testes anteriores, use o ID retornado pelo POST. Também é possível executar `bash testar-api.sh` em um terminal com `curl` para conferir automaticamente os mesmos casos.

No Postman, use **Import** e selecione `postman_collection.json`. Execute a coleção em ordem: o cadastro salva automaticamente o ID que será usado na atualização e exclusão. A aba **Actions** do GitHub também executa os testes de terminal a cada envio de código; esses testes não substituem as capturas exigidas da interface do Postman/Insomnia.

O script de terminal também requer Python 3 para extrair o ID da resposta do cadastro.

### Evidências dos testes

**Capturas de tela do Postman/Insomnia:** pendentes de realização em uma máquina com PHP e Composer. Salve imagens das seis requisições e respostas na pasta `evidencias/`, depois substitua esta linha por links para as imagens. Não apresente capturas como se fossem testes executados antes de verificá-las.
