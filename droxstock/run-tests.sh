#!/bin/bash

# Comprehensive Test Runner Script for Droxstock API
# This script provides easy access to various testing scenarios

echo "🚀 Droxstock API Test Suite Runner"
echo "=================================="

# Function to display help
show_help() {
    echo ""
    echo "Usage: ./run-tests.sh [OPTION]"
    echo ""
    echo "Options:"
    echo "  all              Run all tests"
    echo "  auth             Run authentication tests only"
    echo "  admin            Run admin user management tests only"
    echo "  coverage         Run tests with coverage report"
    echo "  parallel         Run tests in parallel"
    echo "  verbose          Run tests with verbose output"
    echo "  help             Show this help message"
    echo ""
    echo "Examples:"
    echo "  ./run-tests.sh all"
    echo "  ./run-tests.sh auth"
    echo "  ./run-tests.sh coverage"
    echo ""
}

# Function to run tests
run_tests() {
    local command="$1"
    echo "🧪 Running: $command"
    echo "⏱️  Started at: $(date)"
    echo ""

    eval "$command"

    echo ""
    echo "✅ Completed at: $(date)"
}

# Check if Pest is available
if ! command -v ./vendor/bin/pest &> /dev/null; then
    echo "❌ Error: Pest PHP not found. Please install dependencies first:"
    echo "   composer install"
    exit 1
fi

# Main script logic
case "${1:-help}" in
    "all")
        run_tests "./vendor/bin/pest"
        ;;
    "auth")
        run_tests "./vendor/bin/pest tests/Feature/Auth/"
        ;;
    "admin")
        run_tests "./vendor/bin/pest tests/Feature/Admin/"
        ;;
    "coverage")
        run_tests "./vendor/bin/pest --coverage --min=90"
        ;;
    "parallel")
        run_tests "./vendor/bin/pest --parallel"
        ;;
    "verbose")
        run_tests "./vendor/bin/pest --verbose"
        ;;
    "help"|*)
        show_help
        ;;
esac
