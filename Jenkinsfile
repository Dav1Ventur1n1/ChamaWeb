pipeline {
    agent any

    stages {
        stage('Checkout') {
            steps {
                git branch: 'main', url: env.GIT_URL
            }
        }

        stage('Build images') {
            steps {
                sh '''
                docker build -t web:latest -f Dockerfile .
                docker build -t gateway:latest -f services/gateway/Dockerfile .
                docker build -t tickets:latest -f services/tickets/Dockerfile .
                docker build -t stats:latest -f services/stats/Dockerfile .
                docker build -t nginx-proxy:latest -f nginx/Dockerfile nginx/
                '''
            }
        }

        stage('Test images') {
            steps {
                sh 'docker run --rm web:latest php -v'
            }
        }

        stage('Load images') {
            steps {
                sh '''
                minikube image load web:latest
                minikube image load gateway:latest
                minikube image load tickets:latest
                minikube image load stats:latest
                minikube image load nginx-proxy:latest
                '''
            }
        }

        stage('Deploy to Kubernetes') {
            steps {
                sh 'kubectl apply -f k8s/'
            }
        }
    }

    post {
        always {
            sh 'kubectl get pods'
        }
        cleanup {
            sh 'kubectl delete -f k8s/ || true'
        }
    }
}
