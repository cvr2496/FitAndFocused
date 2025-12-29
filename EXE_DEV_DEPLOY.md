# Deploy to exe.dev with Docker

Based on [exe.dev's Docker image support](https://exe.dev/docs/use-case-marimo), we can deploy in seconds!

## How it Works

1. **GitHub Action** automatically builds and pushes a Docker image to GitHub Container Registry (ghcr.io) whenever you push to `main`
2. **exe.dev** pulls and runs that image with a single SSH command
3. Your app is live at `https://yourvm.exe.xyz` with automatic HTTPS

## Test Locally First (Recommended)

Before deploying to exe.dev, test the Docker image locally to catch any issues:

### 1. Build the Image

```bash
cd /Users/cvr/Herd/FitAndFocused
docker build -t fitandfocused:local .
```

### 2. Run Locally

```bash
# Run the container
docker run -p 8000:8000 --name fitandfocused-test fitandfocused:local

# In another terminal, test it
curl http://localhost:8000/health

# Or open in browser
open http://localhost:8000
```

### 3. Troubleshoot Issues

If you see errors:

```bash
# View container logs
docker logs fitandfocused-test

# Enter the container to debug
docker exec -it fitandfocused-test bash

# Inside container, check:
php artisan --version
cat .env
ls -la public/build/
```

### 4. Clean Up

```bash
docker stop fitandfocused-test
docker rm fitandfocused-test
```

Once it works locally, proceed to deployment!

---

## One-Time Setup

### 1. Make GitHub Container Registry Public

After your first push (which will trigger the build):

1. Go to https://github.com/cvr2496/FitAndFocused/pkgs/container/fitandfocused
2. Click "Package settings"
3. Scroll to "Danger Zone" → Change visibility to **Public**

(This allows exe.dev to pull the image without authentication)

### 2. Register with exe.dev

```bash
ssh exe.dev
```

## Deploy

### First Deployment

```bash
# Create a new VM with your Docker image
# This will automatically proxy port 8000 to HTTPS
ssh exe.dev new --image=ghcr.io/cvr2496/fitandfocused:latest golf-scarlet

# You'll get output like:
# Creating golf-scarlet using image cvr2496/fitandfocused:latest...
# App (HTTPS proxy → :8000)
# https://golf-scarlet.exe.xyz
# SSH
# ssh golf-scarlet.exe.xyz
```

### Set Environment Variables

```bash
# SSH into your VM
ssh golf-scarlet.exe.xyz

# Create .env file with your actual API key
cat > .env << 'EOF'
APP_NAME="FitAndFocused"
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY_HERE
APP_DEBUG=false
APP_URL=https://golf-scarlet.exe.xyz
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/database.sqlite
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
ANTHROPIC_API_KEY=your-actual-api-key-here
EOF

# Generate an app key
php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
# Copy the output and update APP_KEY in .env

# Restart the container to pick up .env changes
# (exit SSH and recreate the VM, or restart the PHP server)
exit
```

### Create Test User

```bash
ssh golf-scarlet.exe.xyz
cd /var/www/html
php artisan tinker
```

In tinker:
```php
User::create([
    'name' => 'Gym User',
    'email' => 'gym@test.com',
    'password' => bcrypt('workout123')
]);
exit
```

## Redeployments

Just push your code:

```bash
git add .
git commit -m "your changes"
git push origin main
```

GitHub Action builds the new image automatically. Then recreate your VM:

```bash
# Remove old VM
ssh exe.dev rm golf-scarlet

# Create new one with updated image
ssh exe.dev new --image=ghcr.io/cvr2496/fitandfocused:latest golf-scarlet

# Set your .env again (or mount a persistent volume)
ssh golf-scarlet.exe.xyz
# ... recreate .env ...
```

## View Logs

```bash
ssh golf-scarlet.exe.xyz
tail -f storage/logs/laravel.log
```

## Useful Commands

```bash
# List your VMs
ssh exe.dev ls

# Remove a VM
ssh exe.dev rm golf-scarlet

# Get help
ssh exe.dev help
```

## Troubleshooting

### Deployment Issues

**Can't pull image:**
- Make sure GitHub Container Registry package is set to public
- Wait for GitHub Action to finish building
- Check build status: https://github.com/cvr2496/FitAndFocused/actions

**Port issues:**
By default exe.dev proxies to port 8080. Our Dockerfile uses port 8000. To change ports:
```bash
ssh exe.dev share port golf-scarlet 8080
```

**Service Unavailable (503):**
- Container is still starting up (wait 10-15 seconds)
- Run `ssh golf-scarlet.exe.xyz` and check if process is running

### Application Issues

**500 Errors on Inertia Pages:**

Current known issue: Inertia pages return 500 errors while Laravel routes work fine.

Possible causes:
1. **Missing Vite manifest** - Check if `public/build/manifest.json` exists in container
2. **Asset path issues** - Verify `APP_URL` in `.env` matches your domain
3. **SSR misconfiguration** - We disabled SSR but may need further config

To debug:
```bash
# SSH into container
ssh golf-scarlet.exe.xyz

# Check Vite build output
ls -la public/build/

# Check Laravel logs
tail -f storage/logs/laravel.log

# Test health endpoint (should work)
curl http://localhost:8000/health

# Enable debug mode temporarily
nano .env
# Set APP_DEBUG=true
```

**Missing built assets:**

If `public/build/` is empty or incomplete:
```bash
# Locally, ensure build works
npm run build
ls public/build/

# Check .dockerignore isn't excluding build files
cat .dockerignore

# Rebuild image with verbose output
docker build --no-cache -t fitandfocused:debug .
```

### Database Issues

**Database locked errors:**
Only one process can write to SQLite at a time. This is fine for testing.

**Database persistence:**
Currently, the database is stored in the container and will be lost when the VM is recreated.

For persistence, you'd need to:
1. Mount a volume for the database directory, OR
2. Use an external database (MySQL/PostgreSQL)

## Current Status

### ✅ What's Working:
- ✅ Docker image builds automatically via GitHub Actions
- ✅ One-command deployment to exe.dev
- ✅ Laravel is running (PHP 8.4.16)
- ✅ Database migrations execute successfully
- ✅ Routing works (proper 404s for missing routes)
- ✅ Public HTTPS access configured

### ⚠️ Known Issues:
- ❌ Inertia pages return 500 errors (likely Vite manifest/asset issue)
- ❌ Frontend not rendering (needs debugging)

### Next Steps to Fix:
1. Test the Docker image locally first (see "Test Locally First" section)
2. Debug Vite build output - ensure `public/build/manifest.json` exists
3. Verify asset paths are correct for production
4. Consider temporarily switching to Blade views to isolate the issue

---

## Why This Approach is Better

✅ **No VM setup** - no installing PHP, Composer, Node, etc.  
✅ **Consistent environment** - works the same everywhere  
✅ **Easy redeployment** - just recreate the VM with the latest image  
✅ **Portable** - same image works on any Docker host  
✅ **Fast** - exe.dev handles all the infrastructure  
✅ **Automated** - GitHub Actions build on every push


