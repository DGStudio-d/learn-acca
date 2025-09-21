pipeline {
    agent any

    environment {
        APP_NAME = "laravel-app"
        // GITHUB_CREDENTIALS = credentials('github-ssh-key')  // Jenkins SSH key or token
        DEPLOY_BRANCH = "deploy"  // branch Hostinger pulls
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Install & Build') {
            steps {
                sh '''
                    composer install --no-dev --optimize-autoloader
                    npm ci
                    npm run build
                    php artisan config:clear
                    php artisan route:clear
                    php artisan view:clear
                '''
            }
        }

        stage('Run Tests') {
            steps {
                sh '''
                    php artisan migrate:fresh --env=testing --force
                    php artisan test
                '''
            }
        }

        stage('Push to GitHub Deploy Branch') {
            steps {
                sh '''
                    git config user.name "DGStudio-d "\r
                    git config user.email "AliTafni@proton.me"\r

                    # Checkout deploy branch (create if not exists)
                    git checkout -B ${DEPLOY_BRANCH}

                    # Add built assets & vendor (if Hostinger needs them)
                    git add -f public/build
                    git add -f vendor

                    # Commit changes
                    git commit -m "Deploy build ${BUILD_NUMBER}" || echo "No changes to commit"

                    # Push with credentials
                    git push --force git@github.com:youruser/yourrepo.git ${DEPLOY_BRANCH}
                '''
            }
        }
    }

    post {
        success {
            echo "✅ Build & push to GitHub successful. Hostinger will auto-deploy."
        }
        failure {
            echo "❌ Build failed. Nothing pushed."
        }
    }
}
