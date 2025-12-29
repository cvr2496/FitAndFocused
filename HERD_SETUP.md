# Herd Configuration for FitAndFocused

This project requires increased PHP upload limits to support workout photo uploads (up to 10MB).

## Required Herd Configuration

### 1. PHP Upload Limits

Edit: `~/Library/Application Support/Herd/config/php/84/php.ini`

```ini
memory_limit=256M
upload_max_filesize=50M
post_max_size=50M
```

### 2. Nginx Upload Limits

Edit: `~/Library/Application Support/Herd/config/nginx/herd.conf`

Find line 6 and change:
```nginx
client_max_body_size 50M;  # Change from 2M
```

### 3. Restart Herd

```bash
herd restart
```

## Why These Changes?

- Workout photos can be 4-10MB in size
- Default Herd limits are 2MB
- These settings allow photo uploads up to 50MB

## Project-Specific Settings

The project includes `public/.user.ini` with PHP settings, but Herd's PHP-FPM doesn't always respect it. The above global changes are required for reliable operation.

## Production Deployment

When deploying to production (exe.dev or other):
- Ensure nginx `client_max_body_size` is set to 50M
- Ensure PHP `upload_max_filesize` and `post_max_size` are 50M
- The `.user.ini` file will be deployed with the project

