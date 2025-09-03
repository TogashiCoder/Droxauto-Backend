# 🐳 Manual Docker Deployment Guide

Simple manual deployment workflow for DroxStock Backend.

## 📋 Prerequisites

-   Docker and Docker Compose installed locally
-   Docker Hub account: `togashicoder`
-   EC2 instance: `13.51.174.55` with Docker installed

---

## 🏗️ Step 1: Build Docker Image Locally

```bash
# Navigate to project directory
cd droxstock

# Build the Docker image
docker build -t togashicoder/droxstock-backend:latest .

# Tag with version (optional)
docker tag togashicoder/droxstock-backend:latest togashicoder/droxstock-backend:v1.0.0
```

---

## 📤 Step 2: Push to Docker Hub

```bash
# Login to Docker Hub
docker login

# Push latest version
docker push togashicoder/droxstock-backend:latest

# Push versioned tag (optional)
docker push togashicoder/droxstock-backend:v1.0.0
```

---

## 🚀 Step 3: Deploy on EC2

### Connect to EC2:

```bash
ssh -i your-key.pem ubuntu@13.51.174.55
```

### First-time setup on EC2:

```bash
# Install Docker (if not already installed)
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker ubuntu

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/download/v2.20.0/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Clone repository
git clone https://github.com/TogashiCoder/Droxauto-Backend.git droxstock
cd droxstock

# Setup environment
cp env.ec2.example .env
```

### Deploy/Update:

```bash
# Pull latest code
git pull origin main

# Pull latest Docker image
docker pull togashicoder/droxstock-backend:latest

# Stop current containers
docker-compose down

# Start with new image
docker-compose up -d

# Run migrations (first time or when needed)
docker-compose exec app php artisan migrate --force

# Generate app key (first time only)
docker-compose exec app php artisan key:generate

# Clear cache
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

---

## 🔍 Step 4: Verify Deployment

```bash
# Check container status
docker-compose ps

# Check application logs
docker-compose logs app

# Test API health
curl http://13.51.174.55/health

# Test API documentation
curl http://13.51.174.55/docs/api
```

---

## 🗄️ Database Configuration

Your application is configured to use:

-   **RDS PostgreSQL**: `droxautodb-rds.cz262kws24oh.eu-north-1.rds.amazonaws.com`
-   **Database**: `droxautodb_aws`
-   **Redis**: Local Docker container for caching/sessions

---

## 🔄 Update Workflow

When you make changes:

1. **Local**: Build → Push to Docker Hub
2. **EC2**: Pull new image → Restart containers

```bash
# Quick update commands for EC2:
docker pull togashicoder/droxstock-backend:latest
docker-compose up -d --force-recreate app
```

---

## 📊 Key URLs

-   **API Base**: `http://13.51.174.55`
-   **Health Check**: `http://13.51.174.55/health`
-   **API Docs**: `http://13.51.174.55/docs/api`
-   **Focused Docs**: `http://13.51.174.55/docs/daparto-focused-scramble`

**Simple, manual, and in your control!** 🎯
