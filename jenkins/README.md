# Jenkins Pipeline Configuration

This directory contains the Jenkins pipeline configuration files for the Laravel application CI/CD system.

## Files Overview

### Pipeline Files
- `Jenkinsfile` - Main deployment pipeline for production deployments
- `Jenkinsfile.pr` - Pull request validation pipeline
- `multibranch-pipeline-config.xml` - Jenkins multibranch pipeline configuration
- `job-dsl-config.groovy` - Job DSL script for programmatic pipeline setup
- `github-webhook-setup.sh` - Script to configure GitHub webhooks

## Setup Instructions

### 1. Jenkins Prerequisites

Ensure the following Jenkins plugins are installed:
- Pipeline: Multibranch
- GitHub Branch Source
- GitHub Integration Plugin
- Docker Pipeline
- Kubernetes CLI
- Slack Notification
- Blue Ocean (optional, for better UI)

### 2. Credentials Configuration

Configure the following credentials in Jenkins:

#### GitHub Credentials
- **ID**: `github-credentials`
- **Type**: Username with password or GitHub App
- **Username**: Your GitHub username or App ID
- **Password**: GitHub personal access token or App private key

#### Docker Registry Credentials
- **ID**: `docker-registry-credentials`
- **Type**: Username with password
- **Username**: Docker registry username
- **Password**: Docker registry password

#### Docker Registry URL
- **ID**: `docker-registry-url`
- **Type**: Secret text
- **Secret**: Docker registry URL (e.g., `docker.io`, `gcr.io`)

### 3. Pipeline Setup

#### Option A: Manual Setup
1. Create a new Multibranch Pipeline job in Jenkins
2. Configure GitHub branch source with your repository
3. Set the script path to `Jenkinsfile`
4. Configure webhook triggers

#### Option B: Automated Setup using Job DSL
1. Install the Job DSL plugin
2. Create a new Freestyle job
3. Add a "Process Job DSLs" build step
4. Use the script from `job-dsl-config.groovy`
5. Update the repository details in the script

#### Option C: Configuration as Code
1. Use the `multibranch-pipeline-config.xml` with Jenkins Configuration as Code
2. Update the repository and credential references
3. Apply the configuration

### 4. GitHub Webhook Configuration

#### Automated Setup
```bash
# Set environment variables
export GITHUB_TOKEN="your_github_token"
export GITHUB_REPO_OWNER="your-organization"
export GITHUB_REPO_NAME="your-laravel-app"
export JENKINS_URL="https://your-jenkins.com"

# Run the setup script
./jenkins/github-webhook-setup.sh create
```

#### Manual Setup
1. Go to your GitHub repository settings
2. Navigate to "Webhooks"
3. Add a new webhook with:
   - **Payload URL**: `https://your-jenkins.com/github-webhook/`
   - **Content type**: `application/json`
   - **Events**: Select "Let me select individual events" and choose:
     - Push
     - Pull requests
     - Pull request reviews
     - Issue comments

### 5. Environment Configuration

Update the following variables in the Jenkinsfile:
- `DOCKER_REGISTRY`: Your Docker registry URL
- `APP_NAME`: Your application name
- `STAGING_NAMESPACE`: Kubernetes namespace for staging
- `PRODUCTION_NAMESPACE`: Kubernetes namespace for production

### 6. Branch Protection Rules

Configure branch protection for the `main` branch:
- Require status checks to pass before merging
- Require branches to be up to date before merging
- Required status checks:
  - `jenkins/ci`
  - `jenkins/pr-validation`

## Pipeline Behavior

### Main Pipeline (Jenkinsfile)
Triggered on pushes to `main` branch:
1. **Checkout** - Get source code
2. **Test** - Run PHP tests, code quality checks, frontend tests
3. **Security Scan** - Dependency and static analysis security checks
4. **Build Docker Image** - Create and push container image
5. **Deploy to Staging** - Automatic staging deployment
6. **Staging Tests** - Smoke tests on staging environment
7. **Production Approval** - Manual approval gate (24-hour timeout)
8. **Deploy to Production** - Blue-green deployment with health checks
9. **Production Health Check** - Verify deployment success

### PR Pipeline (Jenkinsfile.pr)
Triggered on pull request events:
1. **Checkout** - Get PR source code
2. **Setup Environment** - Install dependencies
3. **Code Quality Checks** - Style, linting, static analysis
4. **Security Checks** - Vulnerability scanning
5. **Unit Tests** - PHP and frontend unit tests with coverage
6. **Integration Tests** - Feature tests with test database
7. **Build Validation** - Verify application can be built
8. **Performance Tests** - Basic performance validation
9. **Generate PR Report** - Summary and GitHub status updates

## Monitoring and Notifications

### Slack Integration
Configure Slack notifications by:
1. Installing the Slack Notification plugin
2. Adding Slack workspace integration
3. Updating the `#deployments` channel in the Jenkinsfile

### GitHub Status Updates
The pipeline automatically updates GitHub with:
- Build status on commits
- PR validation results
- Deployment status

## Troubleshooting

### Common Issues

#### Webhook Not Triggering
- Verify webhook URL is accessible from GitHub
- Check Jenkins GitHub plugin configuration
- Ensure credentials are correctly configured

#### Build Failures
- Check Jenkins agent has required tools (Docker, kubectl, helm)
- Verify credentials and registry access
- Review build logs for specific error messages

#### Deployment Issues
- Ensure Kubernetes cluster is accessible from Jenkins
- Verify namespace permissions and resource quotas
- Check Helm chart configuration and values

### Debug Commands

```bash
# Test webhook connectivity
curl -X POST https://your-jenkins.com/github-webhook/

# List existing webhooks
./jenkins/github-webhook-setup.sh list

# Test Jenkins connectivity
curl -I https://your-jenkins.com/
```

## Security Considerations

1. **Credentials Management**: Use Jenkins Credentials Store for all secrets
2. **Network Security**: Ensure Jenkins is accessible only from GitHub IPs
3. **Access Control**: Configure proper Jenkins user permissions
4. **Audit Logging**: Enable Jenkins audit logging for compliance
5. **Container Security**: Regular security scanning of Docker images

## Maintenance

### Regular Tasks
- Update Jenkins plugins monthly
- Review and rotate credentials quarterly
- Monitor build performance and optimize as needed
- Update pipeline scripts based on application changes

### Backup
- Backup Jenkins configuration and job definitions
- Store pipeline scripts in version control
- Document any manual configuration changes