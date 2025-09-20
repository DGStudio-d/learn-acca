#!/bin/bash

# Jenkins Test Runner Script
# This script runs all tests in the Jenkins CI/CD pipeline

set -e  # Exit on any error

echo "🧪 Starting Jenkins test execution..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Initialize test results
TESTS_PASSED=0
TESTS_FAILED=0

# Function to run a test and track results
run_test() {
    local test_name="$1"
    local test_command="$2"
    
    print_info "Running $test_name..."
    
    if eval "$test_command"; then
        print_status "$test_name passed"
        ((TESTS_PASSED++))
        return 0
    else
        print_error "$test_name failed"
        ((TESTS_FAILED++))
        return 1
    fi
}

# Ensure directories exist
mkdir -p test-results coverage

echo ""
echo "🔍 Running Code Quality Checks..."
echo "=================================="

# Laravel Pint Code Style Check
run_test "Laravel Pint Code Style" "vendor/bin/pint --test --config=pint.json"

# Generate Pint checkstyle report
vendor/bin/pint --test --format=checkstyle > test-results/pint-checkstyle.xml 2>/dev/null || true

echo ""
echo "🧪 Running Unit Tests..."
echo "========================"

# PHPUnit Tests with coverage
run_test "PHPUnit Tests" "vendor/bin/phpunit --configuration phpunit.xml --coverage-clover=coverage/clover.xml --coverage-html=coverage/html --log-junit=test-results/phpunit.xml --testdox-html=test-results/testdox.html"

echo ""
echo "🔗 Running Integration Tests..."
echo "==============================="

# Feature Tests
run_test "Laravel Feature Tests" "vendor/bin/phpunit --configuration phpunit.xml --testsuite=Feature --log-junit=test-results/feature-tests.xml"

# API Tests (if they exist)
if [ -d "tests/Feature/Api" ]; then
    run_test "API Integration Tests" "vendor/bin/phpunit --configuration phpunit.xml tests/Feature/Api --log-junit=test-results/api-tests.xml"
fi

echo ""
echo "🗄️  Running Database Tests..."
echo "============================="

# Database Migration Tests
print_info "Testing database migrations..."
php artisan migrate:fresh --env=testing --force
php artisan migrate:rollback --env=testing --force
php artisan migrate --env=testing --force
php artisan db:seed --env=testing --force --class=TestingSeeder
print_status "Database migration tests passed"

# Database-specific tests (if tagged)
if vendor/bin/phpunit --list-groups | grep -q "database"; then
    run_test "Database-specific Tests" "vendor/bin/phpunit --configuration phpunit.xml --group=database --log-junit=test-results/database-tests.xml"
fi

echo ""
echo "🎨 Running Frontend Tests..."
echo "============================"

# Build frontend assets
run_test "Frontend Asset Build" "npm run build"

# Frontend linting (if configured)
if grep -q '"lint"' package.json; then
    run_test "Frontend Linting" "npm run lint"
else
    print_warning "Frontend linting not configured, skipping..."
fi

# Frontend unit tests (if configured)
if grep -q '"test"' package.json && ! grep -q "echo.*not configured" package.json; then
    run_test "Frontend Unit Tests" "npm run test -- --coverage --watchAll=false"
else
    print_warning "Frontend tests not configured, skipping..."
fi

# TypeScript type checking (if configured)
if [ -f "tsconfig.json" ]; then
    run_test "TypeScript Type Check" "npx tsc --noEmit"
else
    print_info "TypeScript not configured, skipping type check..."
fi

echo ""
echo "🔒 Running Security Tests..."
echo "============================"

# Composer security audit
if composer audit --help > /dev/null 2>&1; then
    run_test "Composer Security Audit" "composer audit"
else
    print_warning "Composer audit not available, skipping..."
fi

# NPM security audit
run_test "NPM Security Audit" "npm audit --audit-level moderate"

echo ""
echo "📊 Test Results Summary"
echo "======================="
echo "Tests Passed: $TESTS_PASSED"
echo "Tests Failed: $TESTS_FAILED"
echo ""

if [ $TESTS_FAILED -eq 0 ]; then
    print_status "All tests passed! 🎉"
    echo ""
    echo "📈 Coverage Report: coverage/html/index.html"
    echo "📋 Test Results: test-results/"
    echo ""
    exit 0
else
    print_error "$TESTS_FAILED test(s) failed!"
    echo ""
    echo "📋 Check test results in: test-results/"
    echo "🔍 Review logs above for failure details"
    echo ""
    exit 1
fi