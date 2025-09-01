# DroxStock Docker Deployment Guide

## 🚀 Quick Start

### Prerequisites
- Docker & Docker Compose installed
- Git
- Make (optional, for easier commands)

### Local Development Setup

1. **Clone the repository**
```bash
git clone https://github.com/your-username/droxstock.git
cd droxstock
```

2. **Setup environment**
```bash
cp env.production.example .env
# Edit .env with your configuration
```

3. **Build and start containers**
```bash
make setup
# OR without Make:
docker-compose up -d --build
```

4. **Access the application**
- API: http://localhost
- Health Check: http://localhost/api/health
- API Docs: http://localhost/docs/api

## 📦 Docker Architecture

### Services
- **app**: PHP-FPM with Laravel application
- **nginx**: Web server
- **db**: PostgreSQL database
- **redis**: Cache and queue backend
- **queue**: Queue worker
- **scheduler**: Laravel scheduler

### Key Features
- Multi-stage Docker build for optimized images
- Production-ready PHP and Nginx configurations
- Health checks for all services
- Automatic queue workers and scheduler
- Log rotation and monitoring
- Security hardening

## 🔧 Development Commands

### Using Make (Recommended)
```bash
make help          # Show all available commands
make up            # Start containers
make down          # Stop containers
make logs          # View logs
make shell         # Access app container
make test          # Run tests
make fresh         # Fresh migration with seeding
make backup        # Backup database
```

### Using Docker Compose
```bash
docker-compose up -d                    # Start containers
docker-compose down                     # Stop containers
docker-compose logs -f                  # View logs
docker-compose exec app sh              # Access container
docker-compose exec app php artisan tinker  # Laravel tinker
```

## 🚢 Production Deployment on EC2

### 1. Initial EC2 Setup

SSH into your EC2 instance and run:
```bash
curl -O https://raw.githubusercontent.com/your-username/droxstock/main/scripts/setup-ec2.sh
chmod +x setup-ec2.sh
./setup-ec2.sh
```

This script will:
- Install Docker & Docker Compose
- Setup firewall rules
- Configure fail2ban for security
- Create necessary directories
- Setup automatic updates
- Configure systemd service

### 2. Environment Configuration

Create production environment file:
```bash
nano /home/ubuntu/droxstock/.env
```

Add your production configuration (see env.production.example).

### 3. Deploy Application

```bash
cd /home/ubuntu/droxstock
./scripts/deploy.sh
```

### 4. Setup SSL Certificate

```bash
sudo certbot certonly --standalone -d api.droxstock.com
```

Update nginx configuration to use SSL certificates.

## 🔄 CI/CD Pipeline

### GitHub Actions Setup

1. **Add GitHub Secrets**:
   - `AWS_ACCESS_KEY_ID`
   - `AWS_SECRET_ACCESS_KEY`
   - `AWS_REGION`
   - `EC2_HOST`
   - `EC2_USERNAME`
   - `EC2_PRIVATE_KEY`
   - `DOCKER_USERNAME`
   - `DOCKER_PASSWORD`
   - `ECR_REGISTRY`
   - `SLACK_WEBHOOK` (optional)

2. **Pipeline Stages**:
   - **Test**: Runs tests, linting, and static analysis
   - **Build**: Creates Docker images and pushes to registry
   - **Deploy**: Deploys to EC2 instance

3. **Automatic Deployment**:
   - Push to `main` branch triggers deployment
   - Pull requests run tests only

## 📊 Monitoring

### Health Check Endpoints
- `/api/health` - Detailed health status
- `/api/ping` - Simple ping for load balancers

### Health Check Response
```json
{
  "status": "healthy",
  "timestamp": "2024-01-01T00:00:00Z",
  "services": {
    "database": {
      "status": "up",
      "response_time": 1.23
    },
    "redis": {
      "status": "up",
      "response_time": 0.45
    },
    "cache": {
      "status": "up",
      "response_time": 0.12
    },
    "disk": {
      "status": "up",
      "usage_percent": 45.67,
      "free_gb": 50.12,
      "total_gb": 100.00
    },
    "memory": {
      "status": "up",
      "current_mb": 128.45,
      "peak_mb": 256.78
    }
  },
  "application": {
    "name": "DroxStock",
    "environment": "production",
    "version": "1.0.0"
  }
}
```

### Monitoring Script
A monitoring script runs every 5 minutes via cron:
```bash
*/5 * * * * /home/ubuntu/monitor.sh
```

## 🔐 Security

### Implemented Security Measures
- Firewall (UFW) configured
- Fail2ban for brute force protection
- Rate limiting on API endpoints
- Security headers in Nginx
- Non-root container user
- Secrets management via environment variables
- Automatic security updates
- Log rotation

### SSL/TLS Configuration
- Use Certbot for free Let's Encrypt certificates
- Auto-renewal configured via cron
- Strong cipher suites in Nginx

## 🔧 Troubleshooting

### View Logs
```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f app
docker-compose logs -f nginx

# Laravel logs
docker-compose exec app tail -f storage/logs/laravel.log
```

### Common Issues

1. **Container won't start**
```bash
docker-compose down -v
docker system prune -af
docker-compose up -d --build
```

2. **Permission issues**
```bash
docker-compose exec app chown -R www:www storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

3. **Database connection issues**
```bash
docker-compose exec db psql -U droxstock -d droxstock
# Check if database exists and is accessible
```

4. **Queue not processing**
```bash
docker-compose restart queue
docker-compose exec app php artisan queue:restart
```

## 🔄 Backup & Restore

### Backup Database
```bash
make backup
# OR
./scripts/backup.sh
```

### Restore Database
```bash
make restore
# Then enter backup filename when prompted
```

### Automated Backups
Daily backups are configured via cron:
```bash
0 2 * * * /home/ubuntu/droxstock/scripts/backup.sh
```

## 📈 Scaling

### Horizontal Scaling
1. Use AWS Auto Scaling Groups
2. Put application behind Load Balancer
3. Use RDS for database
4. Use ElastiCache for Redis

### Vertical Scaling
1. Increase EC2 instance size
2. Adjust Docker resource limits
3. Tune PHP-FPM workers
4. Optimize Nginx configuration

## 📝 Maintenance

### Update Application
```bash
cd /home/ubuntu/droxstock
git pull origin main
./scripts/deploy.sh
```

### Rollback
```bash
./scripts/rollback.sh
```

### Clean Up
```bash
# Remove old Docker images
docker system prune -af

# Clean old logs
find storage/logs -name "*.log" -mtime +30 -delete

# Clean old backups
find /home/ubuntu/backups -name "*.sql.gz" -mtime +7 -delete
```

## 🆘 Support

For issues or questions:
1. Check application logs
2. Review health check endpoint
3. Check GitHub Issues
4. Contact DevOps team

## 📚 Additional Resources

- [Docker Documentation](https://docs.docker.com/)
- [Laravel Deployment](https://laravel.com/docs/deployment)
- [AWS EC2 Best Practices](https://docs.aws.amazon.com/ec2/index.html)
- [Nginx Configuration](https://nginx.org/en/docs/)
