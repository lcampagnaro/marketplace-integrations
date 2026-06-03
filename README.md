# Marketplace Integrations — PHP

Integrações backend desenvolvidas para o sistema **PLAC** (plataforma logística interna), responsável por buscar pedidos de diferentes marketplaces e ERPs, normalizar os dados e alimentar o fluxo de expedição.

Cada integração consome uma API externa, mapeia os campos para um formato padrão e persiste ou retorna os dados para processamento logístico.

---

## Integrações disponíveis

| Arquivo | Plataforma | Método | Descrição |
|---|---|---|---|
| `getPedido_tiny.php` | Tiny ERP | POST | Busca pedido por número, obtém detalhes e normaliza |
| `getPedido_soma.php` | Plataforma Soma | GET | Busca pedido por ID via Bearer Token |
| `getPedido_vnda.php` | VNDA | GET | Busca pedido e persiste na tabela `preEnvio` |

Todas retornam (ou persistem) os dados no formato padrão PLAC:

```json
{
  "nome": "...",
  "cpf": "...",
  "cnpj": "...",
  "logradouro": "...",
  "numero": "...",
  "bairro": "...",
  "cidade": "...",
  "uf": "...",
  "cep": "...",
  "pedido": "...",
  "valorFretePagoCliente": 0.00,
  "chaveNF": "...",
  "NF": "...",
  "valorNF": 0.00,
  "produtos": []
}
```

---

## Stack

- **PHP 8+**
- **MySQL** com MySQLi e prepared statements
- **cURL** para requisições HTTP
- APIs REST com autenticação Bearer Token

---

## Configuração

1. Clone o repositório
2. Copie o arquivo de exemplo e configure suas credenciais:

```bash
cp .env.example .env
```

3. Edite o `.env` com seus tokens e configurações de banco:

```env
TINY_TOKEN=seu_token
SOMA_BEARER_TOKEN=seu_token
VNDA_BEARER_TOKEN=seu_token
DB_HOST=localhost
DB_NAME=plac
DB_USER=usuario
DB_PASS=senha
```

> O arquivo `.env` está no `.gitignore` e **nunca deve ser versionado**.

---

## Estrutura

```
marketplace-integrations/
├── config/
│   └── config.php          # Leitura centralizada de variáveis de ambiente
├── src/
│   ├── getPedido_tiny.php  # Integração Tiny ERP
│   ├── getPedido_soma.php  # Integração Plataforma Soma
│   └── getPedido_vnda.php  # Integração VNDA + persistência MySQL
├── .env.example            # Modelo de configuração
├── .gitignore
└── README.md
```

---

## Contexto

Estas integrações fazem parte de um sistema logístico que processa **mais de 2.000 pedidos/dia**, conectando múltiplas plataformas de e-commerce ao fluxo de expedição e rastreamento de pacotes.

---

## Autor

**Leandro Campagnaro**  
Desenvolvedor Backend | PHP · Laravel · Python · MySQL · APIs REST  
[linkedin.com/in/leandro-campagnaro](https://linkedin.com/in/leandro-campagnaro) · lcampagnaro@gmail.com
