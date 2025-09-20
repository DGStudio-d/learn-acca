pipeline {
    agent any
    
    environment {
        DOCKER_REGISTRY = credentials('docker-registry-url')
        DOCKER_CREDENTIALS = credentials('docker-registry-credentials')
        APP_NAME = 'laravel-app'
        STAGING_NAMESPACE = 'staging'
        PRODUCTION_NAMESPACE = 'production'
    }
    
    options {
        buildDiscarder(logRotator(numToKeepStr: '10'))
        timeout(time: 60, unit: 'MINUTES')
        skipStagesAfterUnstable()
    }
    
    stages {
        stage('Checkout') {
            steps {
                checkout scm
                script {
                    env.GIT_COMMIT_SHORT = sh(
                        script: 'git rev-parse --short HEAD',
                        returnStdout: true
                    ).trim()
                    env.BUILD_TAG = "${env.BUILD_NUMBER}-${env.GIT_COMMIT_SHORT}"
                }
            }
        }
        
        stage('Setup Test Environment') {
            steps {
                sh '''
                    # Copy environment configuration for testing
                    cp .env.example .env.testing
                    
                    # Install PHP dependencies with dev packages for testing
                    composer install --optimize-autoloader
                    
                    # Generate application key for testing environment
                    php artisan key:generate --env=testing
                    
                    # Install Node.js dependencies
                    npm ci
                    
                    # Clear any cached configuration
                    php artisan config:clear --env=testing
                    php artisan cache:clear --env=testing
                    php artisan route:clear --env=testing
                    php artisan view:clear --env=testing
                '''
            }
        }
        
        stage('Database Setup') {
            steps {
                sh '''
                    # Create test database (SQLite in-memory for CI)
                    touch database/testing.sqlite
                    
                    # Run migrations for testing environment
                    php artisan migrate:fresh --env=testing --force
                    
                    # Seed test database with required data
                    php artisan db:seed --env=testing --force
                '''
            }
        }
        
        stage('Test') {
            parallel {
                stage('PHPUnit Tests') {
                    steps {
                        sh '''
                            # Cache configuration for better performance
                            php artisan config:cache --env=testing
                            
                            # Run PHPUnit tests with coverage and JUnit output
                            vendor/bin/phpunit \\
                                --configuration phpunit.xml \\
                                --coverage-clover=coverage/clover.xml \\
                                --coverage-html=coverage/html \\
                                --log-junit=test-results/phpunit.xml \\
                                --testdox-html=test-results/testdox.html \\
                                --stop-on-failure
                        '''
                    }
                    post {
                        always {
                            // Publish test results
                            publishTestResults testResultsPattern: 'test-results/phpunit.xml'
                            
                            // Publish code coverage
                            publishCoverage adapters: [
                                cloverAdapter('coverage/clover.xml')
                            ], sourceFileResolver: sourceFiles('STORE_LAST_BUILD')
                            
                            // Archive coverage reports
                            archiveArtifacts artifacts: 'coverage/**, test-results/**', allowEmptyArchive: true
                        }
                    }
                }
                
                stage('Laravel Pint Code Style') {
                    steps {
                        sh '''
                            # Run Laravel Pint for code style checking
                            vendor/bin/pint --test --verbose --config=pint.json
                            
                            # Generate Pint report in checkstyle format for Jenkins
                            vendor/bin/pint --test --format=checkstyle > test-results/pint-checkstyle.xml || true
                        '''
                    }
                    post {
                        always {
                            // Publish code style results
                            publishCheckStyleResults pattern: 'test-results/pint-checkstyle.xml'
                        }
                        failure {
                            sh '''
                                echo "Code style violations found. Run 'vendor/bin/pint' to fix automatically."
                                vendor/bin/pint --test --verbose || true
                            '''
                        }
                    }
                }
                
                stage('Frontend Asset Tests') {
                    steps {
                        sh '''
                            # Build frontend assets
                            npm run build
                            
                            # Run frontend linting if available
                            if [ -f "eslint.config.js" ] || [ -f ".eslintrc.js" ]; then
                                npm run lint || echo "Linting not configured, skipping..."
                            fi
                            
                            # Run frontend unit tests if available
                            if grep -q "test" package.json; then
                                npm run test -- --coverage --watchAll=false --testResultsProcessor=jest-junit
                            else
                                echo "Frontend tests not configured, skipping..."
                            fi
                            
                            # Validate that assets were built successfully
                            if [ ! -d "public/build" ]; then
                                echo "Error: Frontend assets not built successfully"
                                exit 1
                            fi
                            
                            # Check for TypeScript compilation errors if TypeScript is used
                            if [ -f "tsconfig.json" ]; then
                                npx tsc --noEmit || echo "TypeScript check completed with warnings"
                            fi
                        '''
                    }
                    post {
                        always {
                            // Archive built assets for verification
                            archiveArtifacts artifacts: 'public/build/**', allowEmptyArchive: true
                            
                            // Publish frontend test results if they exist
                            publishTestResults testResultsPattern: 'junit.xml', allowEmptyResults: true
                        }
                    }
                }
                
                stage('Database Tests') {
                    steps {
                        sh '''
                            # Test database migrations (up and down)
                            php artisan migrate:fresh --env=testing --force
                            php artisan migrate:rollback --env=testing --force
                            php artisan migrate --env=testing --force
                            
                            # Test database seeding
                            php artisan db:seed --env=testing --force
                            
                            # Run database-specific tests
                            vendor/bin/phpunit --configuration phpunit.xml --group=database --log-junit=test-results/database-tests.xml
                        '''
                    }
                    post {
                        always {
                            publishTestResults testResultsPattern: 'test-results/database-tests.xml', allowEmptyResults: true
                        }
                    }
                }
            }
        }
        
        stage('Security Scan') {
            parallel {
                stage('Dependency Check') {
                    steps {
                        sh '''
                            composer audit
                            npm audit --audit-level moderate
                        '''
                    }
                }
                
                stage('Static Analysis') {
                    steps {
                        sh '''
                            vendor/bin/phpstan analyse --memory-limit=2G
                            vendor/bin/psalm --show-info=true
                        '''
                    }
                }
            }
        }
        
        stage('Build Docker Image') {
            steps {
                script {
                    env.IMAGE_TAG = "${env.DOCKER_REGISTRY}/${env.APP_NAME}:${env.BUILD_TAG}"
                    env.LATEST_TAG = "${env.DOCKER_REGISTRY}/${env.APP_NAME}:latest"
                }
                sh '''
                    docker build -t ${IMAGE_TAG} -t ${LATEST_TAG} .
                    echo ${DOCKER_CREDENTIALS_PSW} | docker login ${DOCKER_REGISTRY} -u ${DOCKER_CREDENTIALS_USR} --password-stdin
                    docker push ${IMAGE_TAG}
                    docker push ${LATEST_TAG}
                '''
            }
            post {
                always {
                    sh 'docker logout ${DOCKER_REGISTRY}'
                }
            }
        }
        
        stage('Deploy to Staging') {
            steps {
                script {
                    sh '''
                        helm upgrade --install ${APP_NAME}-staging ./helm-chart \\
                            --namespace ${STAGING_NAMESPACE} \\
                            --set image.tag=${BUILD_TAG} \\
                            --set environment=staging \\
                            --wait --timeout=10m
                    '''
                }
            }
        }
        
        stage('Staging Tests') {
            steps {
                sh '''
                    # Wait for staging deployment to be ready
                    kubectl wait --for=condition=available --timeout=300s deployment/${APP_NAME} -n ${STAGING_NAMESPACE}
                    
                    # Run smoke tests against staging
                    php artisan test:smoke --env=staging
                '''
            }
        }
        
        stage('Production Approval') {
            when {
                branch 'main'
            }
            steps {
                script {
                    def deploymentApproved = false
                    try {
                        timeout(time: 24, unit: 'HOURS') {
                            deploymentApproved = input(
                                message: 'Deploy to Production?',
                                ok: 'Deploy',
                                parameters: [
                                    choice(
                                        name: 'DEPLOYMENT_STRATEGY',
                                        choices: ['blue-green', 'rolling'],
                                        description: 'Select deployment strategy'
                                    )
                                ]
                            )
                        }
                    } catch (err) {
                        deploymentApproved = false
                        echo "Deployment approval timeout or cancelled: ${err}"
                    }
                    
                    if (!deploymentApproved) {
                        error("Production deployment not approved")
                    }
                }
            }
        }
        
        stage('Deploy to Production') {
            when {
                branch 'main'
            }
            steps {
                script {
                    sh '''
                        # Create backup of current production state
                        kubectl get deployment ${APP_NAME} -n ${PRODUCTION_NAMESPACE} -o yaml > backup-deployment-${BUILD_NUMBER}.yaml
                        
                        # Deploy to production with blue-green strategy
                        helm upgrade --install ${APP_NAME}-production ./helm-chart \\
                            --namespace ${PRODUCTION_NAMESPACE} \\
                            --set image.tag=${BUILD_TAG} \\
                            --set environment=production \\
                            --set deployment.strategy=${DEPLOYMENT_STRATEGY} \\
                            --wait --timeout=15m
                    '''
                }
            }
        }
        
        stage('Production Health Check') {
            when {
                branch 'main'
            }
            steps {
                script {
                    sh '''
                        # Wait for production deployment to be ready
                        kubectl wait --for=condition=available --timeout=600s deployment/${APP_NAME} -n ${PRODUCTION_NAMESPACE}
                        
                        # Run production health checks
                        php artisan test:health --env=production
                        
                        # Verify application endpoints
                        curl -f -s -o /dev/null --retry 5 --retry-delay 10 https://your-app.com/health
                    '''
                }
            }
            post {
                failure {
                    script {
                        echo "Production health check failed, initiating rollback"
                        sh '''
                            kubectl apply -f backup-deployment-${BUILD_NUMBER}.yaml
                            kubectl rollout status deployment/${APP_NAME} -n ${PRODUCTION_NAMESPACE} --timeout=300s
                        '''
                    }
                }
            }
        }
    }
    
    post {
        always {
            cleanWs()
        }
        success {
            script {
                if (env.BRANCH_NAME == 'main') {
                    slackSend(
                        channel: '#deployments',
                        color: 'good',
                        message: "✅ Production deployment successful: ${env.APP_NAME} v${env.BUILD_TAG}"
                    )
                }
            }
        }
        failure {
            slackSend(
                channel: '#deployments',
                color: 'danger',
                message: "❌ Deployment failed: ${env.APP_NAME} - ${env.BUILD_URL}"
            )
        }
        unstable {
            slackSend(
                channel: '#deployments',
                color: 'warning',
                message: "⚠️ Deployment unstable: ${env.APP_NAME} - ${env.BUILD_URL}"
            )
        }
    }
}