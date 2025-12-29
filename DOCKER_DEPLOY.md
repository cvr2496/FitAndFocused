# Docker Deployment Guide

## Quick Start

This is a one-time setup per VM. After this, deployments are just `./docker-deploy.sh`!

### 1. Initial VM Setup (One Time)

```bash
# SSH into your VM
ssh golf-scarlet.exe.xyz

# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Log out and back in for group changes to take effect
exit
ssh golf-scarlet.exe.xyz

# Verify Docker works
docker --version
```

### 2. Clone and Configure

```bash
# Clone the repo
git clone https://github.com/cvr2496/FitAndFocused.git
cd FitAndFocused

# Create .env file
cp .env.example .env
nano .env  # Add your ANTHROPIC_API_KEY

# Generate app key
docker run --rm -v $(pwd):/app -w /app php:8.2-cli php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;" > /tmp/key.txt
# Copy the key and add to .env as APP_KEY=
```

### 3. Deploy

```bash
# Make deploy script executable
chmod +x docker-deploy.sh

# Deploy!
./docker-deploy.sh
```

### 4. Create Test User

```bash
docker compose exec app php artisan tinker
```

Then in tinker:
```php
User::create([
    'name' => 'Gym User',
    'email' => 'gym@test.com',
    'password' => bcrypt('workout123')
]);
exit
```

## Future Deployments

Just push your changes and run:

```bash
ssh golf-scarlet.exe.xyz
cd FitAndFocused
./docker-deploy.sh
```

## Useful Commands

```bash
# View logs
docker compose logs -f

# Restart the app
docker compose restart

# Stop the app
docker compose down

# Enter the container
docker compose exec app bash

# Run artisan commands
docker compose exec app php artisan <command>
```

## Troubleshooting

**Port already in use:**
```bash
docker compose down
./docker-deploy.sh
```

**Permission issues:**
```bash
sudo chmod -R 775 storage bootstrap/cache database
sudo chown -R $USER:$USER storage bootstrap/cache database
```

**Database locked:**
Stop all containers and restart:
```bash
docker compose down
docker compose up -d
```

