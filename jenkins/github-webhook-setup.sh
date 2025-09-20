#!/bin/bash

# GitHub Webhook Setup Script for Jenkins Integration
# This script helps configure GitHub webhooks for Jenkins CI/CD pipeline

set -e

# Configuration variables
GITHUB_REPO_OWNER="${GITHUB_REPO_OWNER:-your-organization}"
GITHUB_REPO_NAME="${GITHUB_REPO_NAME:-your-laravel-app}"
JENKINS_URL="${JENKINS_URL:-https://your-jenkins.com}"
GITHUB_TOKEN="${GITHUB_TOKEN}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check required variables
check_requirements() {
    print_status "Checking requirements..."
    
    if [ -z "$GITHUB_TOKEN" ]; then
        print_error "GITHUB_TOKEN environment variable is required"
        exit 1
    fi
    
    if [ -z "$JENKINS_URL" ]; then
        print_error "JENKINS_URL environment variable is required"
        exit 1
    fi
    
    # Check if curl is available
    if ! command -v curl &> /dev/null; then
        print_error "curl is required but not installed"
        exit 1
    fi
    
    # Check if jq is available
    if ! command -v jq &> /dev/null; then
        print_error "jq is required but not installed"
        exit 1
    fi
    
    print_status "Requirements check passed"
}

# Create GitHub webhook
create_webhook() {
    print_status "Creating GitHub webhook..."
    
    local webhook_url="${JENKINS_URL}/github-webhook/"
    local api_url="https://api.github.com/repos/${GITHUB_REPO_OWNER}/${GITHUB_REPO_NAME}/hooks"
    
    # Webhook payload
    local webhook_payload=$(cat <<EOF
{
  "name": "web",
  "active": true,
  "events": [
    "push",
    "pull_request",
    "pull_request_review",
    "pull_request_review_comment",
    "issue_comment",
    "commit_comment",
    "create",
    "delete",
    "deployment",
    "deployment_status",
    "fork",
    "gollum",
    "issues",
    "member",
    "membership",
    "milestone",
    "organization",
    "page_build",
    "public",
    "release",
    "repository",
    "status",
    "team_add",
    "watch"
  ],
  "config": {
    "url": "${webhook_url}",
    "content_type": "json",
    "insecure_ssl": "0",
    "secret": ""
  }
}
EOF
)
    
    # Create the webhook
    local response=$(curl -s -w "%{http_code}" -o /tmp/webhook_response.json \
        -X POST \
        -H "Authorization: token ${GITHUB_TOKEN}" \
        -H "Accept: application/vnd.github.v3+json" \
        -d "$webhook_payload" \
        "$api_url")
    
    local http_code="${response: -3}"
    
    if [ "$http_code" = "201" ]; then
        print_status "Webhook created successfully"
        local webhook_id=$(jq -r '.id' /tmp/webhook_response.json)
        print_status "Webhook ID: $webhook_id"
    elif [ "$http_code" = "422" ]; then
        print_warning "Webhook may already exist"
        cat /tmp/webhook_response.json | jq '.'
    else
        print_error "Failed to create webhook (HTTP $http_code)"
        cat /tmp/webhook_response.json | jq '.'
        exit 1
    fi
}

# List existing webhooks
list_webhooks() {
    print_status "Listing existing webhooks..."
    
    local api_url="https://api.github.com/repos/${GITHUB_REPO_OWNER}/${GITHUB_REPO_NAME}/hooks"
    
    curl -s \
        -H "Authorization: token ${GITHUB_TOKEN}" \
        -H "Accept: application/vnd.github.v3+json" \
        "$api_url" | jq '.[] | {id: .id, name: .name, url: .config.url, active: .active}'
}

# Test webhook
test_webhook() {
    print_status "Testing webhook connectivity..."
    
    local webhook_url="${JENKINS_URL}/github-webhook/"
    
    # Test if Jenkins webhook endpoint is accessible
    local response=$(curl -s -w "%{http_code}" -o /dev/null "$webhook_url")
    
    if [ "$response" = "200" ] || [ "$response" = "405" ]; then
        print_status "Jenkins webhook endpoint is accessible"
    else
        print_error "Jenkins webhook endpoint is not accessible (HTTP $response)"
        print_error "Please check Jenkins URL and network connectivity"
        exit 1
    fi
}

# Configure branch protection rules
configure_branch_protection() {
    print_status "Configuring branch protection rules..."
    
    local api_url="https://api.github.com/repos/${GITHUB_REPO_OWNER}/${GITHUB_REPO_NAME}/branches/main/protection"
    
    local protection_payload=$(cat <<EOF
{
  "required_status_checks": {
    "strict": true,
    "contexts": [
      "jenkins/ci",
      "jenkins/pr-validation"
    ]
  },
  "enforce_admins": false,
  "required_pull_request_reviews": {
    "required_approving_review_count": 1,
    "dismiss_stale_reviews": true,
    "require_code_owner_reviews": false
  },
  "restrictions": null,
  "allow_force_pushes": false,
  "allow_deletions": false
}
EOF
)
    
    local response=$(curl -s -w "%{http_code}" -o /tmp/protection_response.json \
        -X PUT \
        -H "Authorization: token ${GITHUB_TOKEN}" \
        -H "Accept: application/vnd.github.v3+json" \
        -d "$protection_payload" \
        "$api_url")
    
    local http_code="${response: -3}"
    
    if [ "$http_code" = "200" ]; then
        print_status "Branch protection rules configured successfully"
    else
        print_warning "Failed to configure branch protection rules (HTTP $http_code)"
        cat /tmp/protection_response.json | jq '.'
    fi
}

# Main execution
main() {
    print_status "Starting GitHub webhook setup for Jenkins integration"
    print_status "Repository: ${GITHUB_REPO_OWNER}/${GITHUB_REPO_NAME}"
    print_status "Jenkins URL: ${JENKINS_URL}"
    
    check_requirements
    test_webhook
    
    case "${1:-create}" in
        "create")
            create_webhook
            configure_branch_protection
            ;;
        "list")
            list_webhooks
            ;;
        "test")
            test_webhook
            ;;
        *)
            echo "Usage: $0 [create|list|test]"
            echo "  create - Create webhook and configure branch protection (default)"
            echo "  list   - List existing webhooks"
            echo "  test   - Test webhook connectivity"
            exit 1
            ;;
    esac
    
    print_status "GitHub webhook setup completed successfully"
}

# Cleanup function
cleanup() {
    rm -f /tmp/webhook_response.json /tmp/protection_response.json
}

# Set trap for cleanup
trap cleanup EXIT

# Run main function
main "$@"