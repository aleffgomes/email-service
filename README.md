# Visão Geral

Este projeto é um serviço de envio de e-mails que consome mensagens de uma fila RabbitMQ e envia e-mails usando o PHPMailer, desenvolvido em PHP utilizando o Swoole para corrotinas

## Estrutura do Projeto

- `Dockerfile`: Configuração do ambiente Docker.
- `docker-compose.yml`: Configuração para executar o RabbitMQ e o serviço de e-mail no ambiente de desenvolvimento.
- `server.php`: Script principal para inicializar o consumidor de e-mails.
- `entrypoint.sh`: Script de entrada do Docker para definir o ambiente de desenvolvimento ou produção.
- `.env-example`: Arquivo de exemplo para configurar variáveis de ambiente.
- `phpunit.xml`: Configuração de testes.
- `src/`: Códigos do projeto.
- `logs/`: Diretório onde os logs diários são armazenados.
- `tests/`: Testes unitários.

## Estrutura do Código

- `server.php`: Inicializa o consumidor de e-mails com as configurações apropriadas.
- `src/Application/`:
  - `EmailConsumer`: Classe que usa RabbitMQ para consumir os dados da fila e envia para EmailService enviar o e-mail.
- `src/Services/`:
  - `MessageValidatorService`: Classe validadora dos dados e estrutura consumidos da fila.
  - `EmailService`: Classe de serviço que envia o e-mail usando o PHPMailer.
- `src/Config/`:
  - `EmailConfig` e `RabbitMQConfig`: Classes que importam as variáveis de ambiente definidas no .env.
- `src/Adapters/`:
  - `FileDownloaderAdapter`: Faz download de arquivos em base64 ou de links públicos para o diretório temporário e depois retorna o mesmo para anexo do EmailService.
  - `PHPMailerAdapter`: Adapta os métodos do PHPMailer, visando desacoplar a dependência, facilitando a troca da biblioteca e/ou testes unitários.
- `src/Interfaces/`: Interfaces dos adapters.

## Dependências

- PHP 8.1
- PHPMailer
- RabbitMQ
- Swoole
- Composer
- Monolog
- League\CLImate

## Requisitos

- Docker
- Docker Compose

## Instalação

1. Clone o repositório:

```bash
  git clone <https://github.com/seu-repositorio/email-service.git>
  cd email-service
```

2. Configure o arquivo .env:

- Copie o conteúdo do .env-example para um novo arquivo .env:

```bash
  cp .env.example .env
```

- Edite o arquivo .env com suas configurações.

## Uso

### Ambiente de Desenvolvimento

Para iniciar o servidor em ambiente de desenvolvimento, o entrypoint está configurado para iniciar o modo interativo do PHP:

> Certifique-se de que o APP_ENV esteja configurado como `development` no arquivo .env.

```bash
  docker-compose --profile dev up -d --build
  docker exec -it email-service bash
  php server.php
```

Para acessar a interface do RabbitMQ, acesse `http://localhost:15672`

### Ambiente de Produção

No ambiente de produção, o servidor será iniciado automaticamente:

> Certifique-se de que o APP_ENV esteja configurado como `production` no arquivo .env.

```bash
  docker-compose --profile prod up -d --build
```

## Estrutura de Mensagens

As mensagens enviadas para a fila RabbitMQ devem seguir o seguinte formato:

```json
  {
    "to": "informativo@teste.com.br", 
    "subject": "Teste", 
    "body": "Testando o envio de email.", 
    "bcc": ["teste@teste.com.br"],
    "attachments": ["https://","data:image/jpeg;base64"]
  }
```

- `to`: Destinatário | string ou array de strings (obrigatório)
- `subject`: Título | string (obrigatório)
- `body`: Corpo | string ou html (obrigatório)
- `bcc`: Destinatários ocultos | string ou array de strings (opcional)
- `attachments`: Anexos | string ou array de strings (opcional) | Deve ser um link de arquivo público ou um arquivo codificado em base64

## Exemplo de Uso

### Enviando uma Mensagem

1. Enfileire uma mensagem no RabbitMQ:

```json
  {
    "to": ["recipient@example.com"],
    "subject": "Hello World",
    "body": "This is a test email.",
    "bcc": ["bcc@example.com"],
    "attachments": ["https://example.com/file.jpg"]
  }
```

2. O serviço consumirá a mensagem, validará os dados e tentará enviar o e-mail.

### Logs e Monitoramento

Os logs serão salvos na pasta `logs` na raiz do projeto, com um arquivo de log para cada dia. O CLImate será usado para exibir estatísticas em tempo real no terminal.

## Testes

Para executar os testes unitários, utilize o PHPUnit. Acesse o container do serviço e execute o comando:

```bash
  docker exec -it email-service bash
  ./vendor/bin/phpunit
```

### Tratamento de Falhas

- Falhas de Envio: A mensagem será retornada à fila.
- Mensagens Mal Formadas: A mensagem será descartada e registrada nos logs.

## Qualidade do código

O comando a seguir pode ser usado para rodar o PHPMD ao projeto localmente:

```bash
  docker run -it --rm -v $(pwd):/project -w /project jakzal/phpqa phpmd app text cleancode,codesize,controversial,design,naming,unusedcode
```
