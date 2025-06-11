FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive
ENV JENKINS_HOME=/home/jenkins
ENV KUBECTL_VERSION=v1.30.0
ENV MINIKUBE_VERSION=v1.33.1

# Instala Java, Docker CLI, Docker Compose, kubectl, Minikube e utilitários
RUN apt update && apt install -y \
    openjdk-17-jdk \
    curl \
    wget \
    git \
    sudo \
    unzip \
    ca-certificates \
    gnupg2 \
    docker.io \
    conntrack \
    socat \
    apt-transport-https \
    lsb-release && \
    rm -rf /var/lib/apt/lists/*

# Docker Compose
RUN curl -L "https://github.com/docker/compose/releases/download/v2.24.6/docker-compose-linux-x86_64" -o /usr/local/bin/docker-compose && \
    chmod +x /usr/local/bin/docker-compose

# Cria grupo docker (se não existir) e usuário Jenkins
RUN getent group docker || groupadd docker && \
    useradd -m -s /bin/bash -G sudo,docker jenkins && \
    echo "jenkins ALL=(ALL) NOPASSWD:ALL" >> /etc/sudoers

# Jenkins WAR
RUN wget https://get.jenkins.io/war-stable/latest/jenkins.war -O /home/jenkins/jenkins.war

# kubectl
RUN curl -LO https://dl.k8s.io/release/${KUBECTL_VERSION}/bin/linux/amd64/kubectl && \
    install -o root -g root -m 0755 kubectl /usr/local/bin/kubectl && \
    rm kubectl

# minikube
RUN curl -LO https://storage.googleapis.com/minikube/releases/${MINIKUBE_VERSION}/minikube-linux-amd64 && \
    install minikube-linux-amd64 /usr/local/bin/minikube && \
    rm minikube-linux-amd64

# Ajusta permissões
RUN mkdir -p /var/jenkins_home && \
    mkdir -p /home/jenkins/.kube /home/jenkins/.minikube && \
    chown -R jenkins:jenkins /var/jenkins_home /home/jenkins

USER jenkins
WORKDIR /home/jenkins

EXPOSE 8090
CMD ["java", "-jar", "/home/jenkins/jenkins.war", "--httpPort=8090"]
