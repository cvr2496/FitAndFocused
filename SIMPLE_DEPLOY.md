# Simple exe.dev Deployment (No Docker)

This is a straightforward approach to deploy FitAndFocused on exe.dev VMs without Docker complexity.

## Quick Start

### 1. Create a new VM
```bash
ssh exe.dev new --name=fitandfocused
ssh exe.dev share set-public fitandfocused
```

### 2. SSH into the VM and run setup
```bash
ssh fitandfocused.exe.xyz
export ANTHROPIC_API_KEY="your-api-key-here"
curl -fsSL https://raw.githubusercontent.com/cvr2496/FitAndFocused/main/setup-vm.sh | bash
```

Or in one line:
```bash
ssh fitandfocused.exe.xyz 'export ANTHROPIC_API_KEY="your-api-key-here" && curl -fsSL https://raw.githubusercontent.com/cvr2496/FitAndFocused/main/setup-vm.sh | bash'
```

### 3. Start the server
```bash
cd ~/FitAndFocused
php artisan serve --host=0.0.0.0 --port=8000
```

Or run in background:
```bash
cd ~/FitAndFocused
nohup php artisan serve --host=0.0.0.0 --port=8000 > server.log 2>&1 &
```

### 4. Access your app
Your app will be available at: `https://fitandfocused.exe.xyz`

## Updating the App

When you push changes to GitHub:

```bash
ssh fitandfocused.exe.xyz
cd ~/FitAndFocused
git pull origin main
composer install --no-interaction
npm ci && npm run build
php artisan migrate --force
php artisan config:clear

# Restart the server (find and kill the old process, then start new)
pkill -f "php artisan serve"
nohup php artisan serve --host=0.0.0.0 --port=8000 > server.log 2>&1 &
```

## Viewing Logs

```bash
ssh fitandfocused.exe.xyz
cd ~/FitAndFocused

# Laravel logs
tail -f storage/logs/laravel.log

# Server output (if running in background)
tail -f server.log
```

## Multiple VMs

To create additional VMs (dev, staging, etc.):

```bash
# Dev environment
ssh exe.dev new --name=fitandfocused-dev
ssh fitandfocused-dev.exe.xyz 'curl -fsSL https://raw.githubusercontent.com/cvr2496/FitAndFocused/main/setup-vm.sh | bash'

# Staging environment  
ssh exe.dev new --name=fitandfocused-staging
ssh fitandfocused-staging.exe.xyz 'curl -fsSL https://raw.githubusercontent.com/cvr2496/FitAndFocused/main/setup-vm.sh | bash'
```

## Troubleshooting

### Check if server is running
```bash
ps aux | grep "php artisan serve"
```

### Check what's listening on port 8000
```bash
sudo lsof -i :8000
```

### Restart the server
```bash
pkill -f "php artisan serve"
cd ~/FitAndFocused && php artisan serve --host=0.0.0.0 --port=8000
```

### Clear all caches
```bash
cd ~/FitAndFocused
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

## Advantages of This Approach

✅ **Simple**: No Docker complexity  
✅ **Fast**: No image building delays  
✅ **Debuggable**: Direct access to logs and files  
✅ **Flexible**: Easy to make quick changes and test  
✅ **Appropriate**: Perfect for personal projects

## Notes

- This runs Laravel in development mode (`php artisan serve`)
- For 5 VMs, setup takes ~2 minutes each
- Changes are instant - just `git pull` and restart
- Logs are easily accessible
- No caching issues to debug

