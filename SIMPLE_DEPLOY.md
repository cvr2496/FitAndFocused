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
```

Then copy/paste these commands (replace with your actual API key):

```bash
export ANTHROPIC_API_KEY="your-anthropic-api-key"
curl -fsSL https://raw.githubusercontent.com/cvr2496/FitAndFocused/main/setup-vm.sh | bash
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

## Development vs Production Workflow

### ⚠️ IMPORTANT: Hot Reload vs Public Domain

**You CANNOT use `npm run dev` with the public domain!**

When accessing via the public domain (e.g., `https://fitandfocused.exe.xyz`), you MUST use built assets:

```bash
# Build the frontend assets
npm run build

# Then run only Laravel
php artisan serve --host=0.0.0.0 --port=8000
```

**Why?** The Vite dev server runs on `127.0.0.1:5173` (local only), which causes CORS errors when accessed through the public domain. The browser cannot reach your local Vite server from the public URL.

### Local Development with Hot Reload

If you want hot reload, access `http://localhost:8000` directly on the VM:

```bash
# Terminal 1: Vite dev server
npm run dev

# Terminal 2: Laravel server
php artisan serve --host=0.0.0.0 --port=8000
```

Then open `http://localhost:8000` in your browser (not the public domain).

### Production Workflow (Public Domain)

When deploying or testing via public domain:

```bash
# Stop Vite dev server if running
pkill -f "npm run dev"

# Build frontend assets
npm run build

# Restart Laravel
pkill -f "php artisan serve"
php artisan serve --host=0.0.0.0 --port=8000

# Or in background:
nohup php artisan serve --host=0.0.0.0 --port=8000 > server.log 2>&1 &
```

**Remember:** After frontend changes, always run `npm run build` before accessing the public domain!

## Advantages of This Approach

✅ **Simple**: No Docker complexity  
✅ **Fast**: No image building delays  
✅ **Debuggable**: Direct access to logs and files  
✅ **Flexible**: Easy to make quick changes and test  
✅ **Appropriate**: Perfect for personal projects

## Notes

- This runs Laravel in development mode (`php artisan serve`)
- For 5 VMs, setup takes ~2 minutes each
- Changes require `npm run build` + restart for public domain
- Logs are easily accessible
- Use `localhost:8000` for hot reload, public domain for built assets

