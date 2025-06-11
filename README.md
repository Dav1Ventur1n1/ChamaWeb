# chamawebrest

Este projeto demonstra uma arquitetura simples de microserviços em PHP. Os serviços comunicam-se através de APIs REST e um API Gateway centraliza o acesso externo.

## Serviços

- **gateway**: expõe as rotas públicas e encaminha as requisições para os microserviços internos.
- **tickets**: responsável pelo gerenciamento de chamados.
- **stats**: fornece estatísticas agregadas usadas na página de relatórios.
- **db**: banco de dados MySQL compartilhado entre os serviços.
- **shared/connect.php**: script único de conexão ao banco utilizado pelos serviços.
- **front-end**: arquivos PHP e recursos estáticos servidos na raiz do projeto.

## Executando

Utilize o `docker-compose` para subir todos os serviços. O script `script_sql.sql` 
será executado automaticamente no primeiro start do banco, populando a tabela de
exemplo com um usuário administrador.

Credenciais padrão: `admin@sistema.com` / `admin123`. A senha é gravada no banco como hash `bcrypt`, mas o login aceita também registros antigos em SHA-256 ou texto simples.

```bash
docker-compose up --build
```

O serviço **web** utiliza o diretório do projeto como DocumentRoot. O arquivo `index.php` exibe a página de login.

O portal web pode ser acessado em `http://localhost:8080`.
O API Gateway estará em `http://localhost:8081` e fará a mediação das chamadas para os demais serviços.
O contêiner `web` possui um certificado autoassinado configurado no Apache,
permitindo acesso seguro em `https://localhost:8443`. O navegador exibirá um aviso
de conexão não segura; aceite o risco para prosseguir nos testes locais.

Para fazer login utilize `http://localhost:8080/` ou `https://localhost:8443/`.

## Endpoints

 Ao acessar o endereço acima, você verá uma mensagem com os caminhos disponíveis.

 - `http://localhost:8081/tickets` - API de gerenciamento de chamados
 - `http://localhost:8081/stats` - API de estatísticas para o relatório
   (também acessíveis via HTTPS em `https://localhost:8443/api/...`)
- `api/create_ticket.php` - proxy que envia o formulario de novo chamado ao gateway, evitando problemas de CORS.

## Verificando o gateway

Para acompanhar as requisições encaminhadas pelo gateway, execute:

```bash
docker-compose logs -f gateway
```
Se estiver rodando via Kubernetes, use `kubectl logs deployment/gateway` para ver
as mesmas mensagens.

Cada requisição gera uma linha de log indicando o método, a rota recebida e o serviço interno escolhido. Você também pode acessar `http://localhost:8081/` e verificar se a mensagem JSON apresenta os caminhos `/tickets` e `/stats`.

## Segurança e Manutenção

O projeto suporta login local ou via Amazon Cognito (arquivos `cognito_login.php` e `auth_callback.php`).
Ao autenticar, um token JWT é gerado e armazenado na sessão. Esse token
é enviado no header `Authorization` para que o gateway repasse a
requisição aos microserviços.

Um script `backup_db.sh` está disponível para gerar backups da base MySQL. Você pode agendar sua execução diária via cron. Há também o utilitário `sla_monitor.php` que dispara notificações antes do vencimento do SLA dos chamados.

Todos os acessos e ações relevantes são registrados na tabela `logs` do banco de dados, permitindo auditoria completa.


## Kubernetes

Os manifestos em `k8s/` definem Deployments e Services para cada microserviço.
Antes de aplicar, crie as imagens com as tags esperadas (o `docker-compose.yml` já define essas tags):

```bash
docker build -t web:latest -f Dockerfile .
docker build -t gateway:latest -f services/gateway/Dockerfile .
docker build -t tickets:latest -f services/tickets/Dockerfile .
docker build -t stats:latest -f services/stats/Dockerfile .
# ou simplesmente
docker-compose build
```

Se estiver utilizando o Minikube, carregue-as no cluster:

```bash
minikube start
minikube image load web:latest
minikube image load gateway:latest
minikube image load tickets:latest
minikube image load stats:latest
```

Em seguida aplique os arquivos:

```bash
kubectl apply -f k8s/
```

Isso criará as instâncias `web`, `gateway`, `tickets`, `stats`, `db` e `phpmyadmin`. O banco de dados será populado pelo script `script_sql.sql` via ConfigMap.
O portal web é exposto via NodePort nas portas `30080` (HTTP) e `30443` (HTTPS). O gateway usa a porta `30081`. Para descobrir os endereços no Minikube, execute:

```bash
minikube service web
minikube service gateway
```



Ou encaminhe a porta manualmente:

```bash
kubectl port-forward service/web 8080:80
kubectl port-forward service/gateway 8081:80
kubectl port-forward service/web 8443:443
```
Depois acesse `http://localhost:8080` para o portal web, `http://localhost:8081` para o gateway ou `https://localhost:8443` para conexão segura.

Se algum pod ficar em `ImagePullBackOff`, verifique se as imagens estão disponíveis no Minikube com `minikube image ls`. Os manifestos definem `imagePullPolicy: Never` justamente para usar as imagens locais. Caso faltem, execute novamente `docker-compose build` e `minikube image load <nome>:latest` para cada serviço.


## Jenkins

Deve-se subir um docker separado para o Jenkins funcionar de maneira correta.

Para subir o docker do Jenkins. O comando utilizado para rodar o docker do jenkins inclui o comando para que seja possivel criar um container "dentro" do container do jenkins, na verdade o comando permite que o jenkins acesse o docker do host, crindo o container a partir dali

```bash
docker build -t jenkins-chamaweb -f jenkins/Dockerfile .
docker run -d --name jenkins `
  -p 8090:8090 `
  -v //var/run/docker.sock:/var/run/docker.sock `
  -v "$HOME/.kube:/home/jenkins/.kube" `
  -v "$HOME/.minikube:/home/jenkins/.minikube" `
  -v jenkins_home:/var/jenkins_home `
  --group-add 0 `
  jenkins-chamaweb

  ```

Acesse o container do jenkins como root através do comando abaixo e instale o docker manualmente
```bash
docker exec -u 0 -it jenkins bash
apt update
apt install -y docker.io
  ```

Para realizar a instalação do jenkins é necessário colocar a senha iniciar, para isso acesse a maquina e rode o comando cat na pasta indicada pelo jenkins, normlamente (/home/jenkins/secrets/initialAdminPassword)
```bash
docker exec -it jenkins bash
cat /caminho/para/a/senha
  ```

Para voltar a rodar o Jenkins quando o container for desligado
```bash
docker start jenkins
  ```


