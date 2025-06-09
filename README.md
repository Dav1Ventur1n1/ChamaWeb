# chamawebrest

Este projeto demonstra uma arquitetura simples de microserviços em PHP. Os serviços comunicam-se através de APIs REST e um API Gateway centraliza o acesso externo.

## Serviços

- **gateway**: expõe as rotas públicas e encaminha as requisições para os microserviços internos.
- **tickets**: responsável pelo gerenciamento de chamados.
- **stats**: fornece estatísticas agregadas usadas na página de relatórios.
- **db**: banco de dados MySQL compartilhado entre os serviços.
- **shared/connect.php**: script único de conexão ao banco utilizado pelos serviços.

## Executando

Utilize o `docker-compose` para subir todos os serviços. O script `script_sql.sql` 
será executado automaticamente no primeiro start do banco, populando a tabela de
exemplo com um usuário administrador.

Credenciais padrão: `admin@sistema.com` / `admin123` (armazenada como hash SHA-256).

```bash
docker-compose up --build
```

O portal web pode ser acessado em `http://localhost:8080`.
O API Gateway estará em `http://localhost:8081` e fará a mediação das chamadas para os demais serviços.

## Endpoints

Ao acessar o endereço acima, você verá uma mensagem com os caminhos disponíveis.

- `http://localhost:8081/tickets` - API de gerenciamento de chamados
 - `http://localhost:8081/stats` - API de estatísticas para o relatório

## Verificando o gateway

Para acompanhar as requisições encaminhadas pelo gateway, execute:

```bash
docker-compose logs -f gateway
```

Cada requisição gera uma linha de log indicando o método, a rota recebida e o serviço interno escolhido. Você também pode acessar `http://localhost:8081/` e verificar se a mensagem JSON apresenta os caminhos `/tickets` e `/stats`.

## Segurança e Manutenção

O projeto suporta login local ou via Amazon Cognito (arquivos `cognito_login.php` e `auth_callback.php`).

Um script `backup_db.sh` está disponível para gerar backups da base MySQL. Você pode agendar sua execução diária via cron. Há também o utilitário `sla_monitor.php` que dispara notificações antes do vencimento do SLA dos chamados.

Todos os acessos e ações relevantes são registrados na tabela `logs` do banco de dados, permitindo auditoria completa.


## Kubernetes

Os manifestos em `k8s/` definem Deployments e Services para cada microserviço. Depois de construir as imagens Docker, aplique os arquivos:

```bash
kubectl apply -f k8s/
```

Isso criará as instâncias `web`, `gateway`, `tickets`, `stats`, `db` e `phpmyadmin`. O banco de dados será populado pelo script `script_sql.sql` via ConfigMap.
O portal web é exposto via NodePort. Para descobrir o endereço no Minikube, execute:

```bash
minikube service web
```

Ou encaminhe a porta manualmente:

```bash
kubectl port-forward service/web 8080:80
```

Depois acesse `http://localhost:8080`.

## Integracao continua

Este repositorio inclui um `Jenkinsfile` com um pipeline basico. Ele faz o checkout do codigo, constroi as imagens com `docker-compose`, executa um teste simples e inicia os servicos para validacao.
