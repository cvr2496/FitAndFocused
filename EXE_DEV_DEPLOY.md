# Deploy to exe.dev with Docker

Based on [exe.dev's Docker image support](https://exe.dev/docs/use-case-marimo), we can deploy in seconds!

## How it Works

1. **GitHub Action** automatically builds and pushes a Docker image to GitHub Container Registry (ghcr.io) whenever you push to `main`
2. **exe.dev** pulls and runs that image with a single SSH command
3. Your app is live at `https://yourvm.exe.xyz` with automatic HTTPS

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

**Can't pull image:**
- Make sure GitHub Container Registry package is set to public
- Wait for GitHub Action to finish building

**Port issues:**
By default exe.dev proxies to port 8080. Our Dockerfile uses port 8000, which should work. If issues arise, we can:
- Change Dockerfile to use port 8080, OR
- Configure exe.dev port forwarding

**Database persistence:**
Currently, the database is stored in the container. For production, you'd want to mount a persistent volume or use an external database.

## Why This is Better

✅ **No VM setup** - no installing PHP, Composer, Node, etc.
✅ **Consistent environment** - works the same everywhere
✅ **Easy redeployment** - just recreate the VM with the latest image
✅ **Portable** - same image works on any Docker host
✅ **Fast** - exe.dev handles all the infrastructure

