# Upload Limits Configuration

## Application-Level Limits

The application enforces the following maximum compressed archive sizes:

| Upload Type | Max Size | Constant Location |
|-------------|----------|-------------------|
| Vehicle ZIP (Admin → Vehicles) | **100 MB** (102400 KB) | `VehicleVinstackZipUploadService::MAX_ZIP_KILOBYTES` |
| Container ZIP (Admin → Containers) | **150 MB** (153600 KB) | `ContainerZipUploadService::MAX_ZIP_KILOBYTES` |

These are **application-level validation limits** enforced by Laravel FormRequest validation (`StoreVehicleZipImagesRequest`, `UploadContainerZipImagesRequest`).

---

## Required Server Configuration

For uploads to succeed, the **server/hosting environment** must be configured to accept files of these sizes. The following PHP and web server settings must be adjusted on the **production server**:

### 1. PHP Configuration (`php.ini` or equivalent)

The hosting environment requires **at least** these PHP settings to handle 100 MB vehicle ZIP uploads:

```ini
upload_max_filesize = 110M
post_max_size = 115M
memory_limit = 256M
max_execution_time = 300
max_input_time = 300
```

**Why these values:**

- `upload_max_filesize` — must be ≥ 100 MB (we use 110 MB for overhead)
- `post_max_size` — must be > `upload_max_filesize` to accommodate multipart form data overhead (metadata, boundaries, etc.)
- `memory_limit` — extraction and processing require additional memory beyond the compressed file size
- `max_execution_time` — large ZIP extraction and upload can take time, especially on slow hosting
- `max_input_time` — time allowed to receive the upload body over the network

**For 150 MB container uploads**, increase further:

```ini
upload_max_filesize = 160M
post_max_size = 165M
```

### 2. Web Server Configuration

#### Apache (`.htaccess` or VirtualHost)

If the hosting uses Apache with `mod_php` or PHP-FPM, you may also need to set:

```apache
php_value upload_max_filesize 110M
php_value post_max_size 115M
php_value memory_limit 256M
php_value max_execution_time 300
php_value max_input_time 300
```

For CGI/FastCGI (common on shared hosting), these directives may not work in `.htaccess`. In that case, the hosting provider must adjust the global `php.ini` or per-account PHP configuration (often via cPanel MultiPHP INI Editor).

#### Nginx + PHP-FPM

For Nginx with PHP-FPM, adjust **both**:

**Nginx** (`/etc/nginx/nginx.conf` or site config):

```nginx
client_max_body_size 115M;
```

**PHP-FPM** pool config (`/etc/php/8.2/fpm/pool.d/www.conf` or equivalent):

```ini
php_admin_value[upload_max_filesize] = 110M
php_admin_value[post_max_size] = 115M
php_admin_value[memory_limit] = 256M
php_admin_value[max_execution_time] = 300
php_admin_value[max_input_time] = 300
```

Then restart PHP-FPM and Nginx:

```bash
sudo systemctl restart php8.2-fpm
sudo systemctl reload nginx
```

#### LiteSpeed / OpenLiteSpeed

For LiteSpeed Web Server (common on shared hosting with LSCache):

- Adjust the PHP settings via **LiteSpeed Web Admin** or the hosting control panel (cPanel → MultiPHP INI Editor)
- Ensure the LiteSpeed server-level **Max Request Body Size** is also ≥ 115 MB (check WHM → LiteSpeed Web Server → Server-level settings or consult the hosting provider)

### 3. Shared Hosting / cPanel

Most shared hosting control panels (cPanel, DirectAdmin, Plesk) provide a **PHP Settings** or **MultiPHP INI Editor** interface:

1. Log into the hosting control panel (cPanel, Plesk, etc.)
2. Navigate to **MultiPHP INI Editor** (cPanel) or **PHP Settings** (DirectAdmin/Plesk)
3. Adjust the following values **for the domain/account** running the application:
   - `upload_max_filesize` → `110M`
   - `post_max_size` → `115M`
   - `memory_limit` → `256M`
   - `max_execution_time` → `300`
   - `max_input_time` → `300`
4. Save and verify by checking `phpinfo()` or attempting a 100 MB upload

**Important**: On some shared hosting, the control panel settings may be overridden by a global limit. If uploads still fail after adjusting these values, contact the hosting provider's support to request an increase.

---

## Verifying Configuration

### Via `phpinfo()`

Create a temporary file (e.g., `phpinfo.php`) in the document root:

```php
<?php phpinfo();
```

Access it via browser (`https://kaml-kamal.intellij-app.com/phpinfo.php`) and verify:

- `upload_max_filesize` ≥ 110M
- `post_max_size` ≥ 115M
- `memory_limit` ≥ 256M
- `max_execution_time` ≥ 300
- `max_input_time` ≥ 300

**Delete the file immediately after verification** to avoid exposing server configuration.

### Via Laravel Artisan (SSH)

If SSH access is available, run:

```bash
php artisan tinker
```

Then:

```php
ini_get('upload_max_filesize');
ini_get('post_max_size');
ini_get('memory_limit');
ini_get('max_execution_time');
```

### Test Upload

After configuration, test a 100 MB vehicle ZIP upload via **Admin → Vehicles → [select vehicle] → Photos → Upload Compressed File** to confirm the change is effective.

---

## Deployment Procedure (No-SSH Hosting)

Per `docs/DEPLOY_NO_SSH.md`, the target hosting has **no SSH access**. Follow this procedure:

1. **Local machine** (before deploying):
   - ✅ Code changes already committed to this branch
   - No frontend changes, so `npm run build` is not required
   - No new dependencies, so `composer install` is not required

2. **Production server**:
   - Pull/upload the code via Git or FTP
   - **Adjust PHP configuration** (see section 3 above — cPanel MultiPHP INI Editor or contact hosting support)
   - **Test** by attempting a 100 MB vehicle ZIP upload via the admin panel

3. **Verify**:
   - Check `phpinfo()` (temporary file) or use the Laravel logs (`storage/logs/laravel.log`) for any upload size errors
   - If uploads still fail with "file too large" or "request entity too large", the hosting provider may have additional limits (e.g., ModSecurity rules, LiteSpeed server-level limits) — contact their support

---

## Rollback

If the server cannot be configured to accept 100 MB uploads (e.g., hosting provider refuses to increase limits), revert the application-level constant to 50 MB:

```php
// app/Services/VehicleVinstackZipUploadService.php
public const MAX_ZIP_KILOBYTES = 51200; // back to 50 MB
```

And update the validation message:

```php
// app/Http/Requests/Admin/StoreVehicleZipImagesRequest.php
'zip.max' => 'حجم ملف ZIP كبير جداً — الحد الأقصى 50 ميغابايت.',
```

Then redeploy.

---

## Summary

- **Application limit raised**: Vehicle ZIP uploads now accept up to **100 MB** (was 50 MB)
- **Server configuration required**: PHP `upload_max_filesize`, `post_max_size`, `memory_limit`, and web server `client_max_body_size` (Nginx) or equivalent must be adjusted on the production server
- **No production credentials provided**: This change is **prepared but not deployed**. The hosting provider or server administrator must apply the PHP/web server configuration changes before the new limit becomes effective.
- **Testing**: Two new tests verify the 100 MB boundary (rejection of 102401 KB, acceptance of valid archives)
