#!/bin/bash

# EC2 Initial Setup Script for DroxStock
set -e

echo "🔧 Setting up EC2 instance for DroxStock..."

# Update system
sudo apt-get update
sudo apt-get upgrade -y

# Install required packages
sudo apt-get install -y \
    apt-transport-https \
    ca-certificates \
    curl \
    gnupg \
    lsb-release \
    git \
    ufw \
    fail2ban \
    htop \
    vim

# Install Docker
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
echo "deb [arch=amd64 signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
sudo apt-get update
sudo apt-get install -y docker-ce docker-ce-cli containerd.io

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Add ubuntu user to docker group
sudo usermod -aG docker ubuntu

# Setup firewall
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw --force enable

# Configure fail2ban
sudo systemctl enable fail2ban
sudo systemctl start fail2ban

# Create application directory
mkdir -p /home/ubuntu/droxstock
mkdir -p /home/ubuntu/backups
mkdir -p /home/ubuntu/ssl

# Clone repository
cd /home/ubuntu
git clone https://github.com/YOUR_USERNAME/droxstock.git

# Set permissions
sudo chown -R ubuntu:ubuntu /home/ubuntu/droxstock
sudo chown -R ubuntu:ubuntu /home/ubuntu/backups

# Create swap file (2GB)
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab

# Setup automatic security updates
sudo apt-get install -y unattended-upgrades
sudo dpkg-reconfigure -plow unattended-upgrades

# Install Certbot for SSL
sudo snap install --classic certbot
sudo ln -s /snap/bin/certbot /usr/bin/certbot

# Create systemd service for auto-start
sudo tee /etc/systemd/system/droxstock.service > /dev/null <<EOF
[Unit]
Description=DroxStock Docker Compose Application
Requires=docker.service
After=docker.service

[Service]
Type=oneshot
RemainAfterExit=yes
User=ubuntu
Group=ubuntu
WorkingDirectory=/home/ubuntu/droxstock
ExecStart=/usr/local/bin/docker-compose up -d
ExecStop=/usr/local/bin/docker-compose down
ExecReload=/usr/local/bin/docker-compose restart

[Install]
WantedBy=multi-user.target
EOF

# Enable the service
sudo systemctl daemon-reload
sudo systemctl enable droxstock.service

# Setup log rotation
sudo tee /etc/logrotate.d/droxstock > /dev/null <<EOF
/home/ubuntu/droxstock/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
    postrotate
        docker-compose -f /home/ubuntu/droxstock/docker-compose.yml exec -T app php artisan cache:clear
    endscript
}
EOF

# Create monitoring script
cat > /home/ubuntu/monitor.sh <<'EOF'
#!/bin/bash
# Simple monitoring script
HEALTH_CHECK=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/health)
if [ "$HEALTH_CHECK" != "200" ]; then
    echo "Health check failed at $(date)" >> /home/ubuntu/monitoring.log
    cd /home/ubuntu/droxstock && docker-compose restart
fi
EOF

chmod +x /home/ubuntu/monitor.sh

# Add monitoring to crontab
(crontab -l 2>/dev/null; echo "*/5 * * * * /home/ubuntu/monitor.sh") | crontab -

echo "✅ EC2 setup completed!"
echo ""
echo "Next steps:"
echo "1. Copy your .env.production file to /home/ubuntu/droxstock/.env"
echo "2. Run: cd /home/ubuntu/droxstock && ./scripts/deploy.sh"
echo "3. Setup SSL: sudo certbot --nginx -d yourdomain.com"
echo ""
echo "Remember to configure these GitHub secrets:"
echo "- AWS_ACCESS_KEY_ID"
echo "- AWS_SECRET_ACCESS_KEY"
echo "- AWS_REGION"
echo "- EC2_HOST"
echo "- EC2_USERNAME"
echo "- EC2_PRIVATE_KEY"
echo "- DOCKER_USERNAME"
echo "- DOCKER_PASSWORD"
echo "- ECR_REGISTRY"
echo "- SLACK_WEBHOOK (optional)"
