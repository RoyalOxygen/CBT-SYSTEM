# CBT Pro - Installation Guide

## System Requirements

### Minimum Requirements
- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Apache 2.4+ with mod_rewrite
- 512MB RAM minimum (1GB recommended)
- 100MB disk space

### PHP Extensions Required
- pdo
- pdo_mysql
- json
- session
- fileinfo
- mbstring
- zip (required only for signed project updates)

## Step-by-Step Installation

Use the browser installer. You do not need to run PHP commands or import SQL by hand.

### 1. Download & Extract
Extract the CBT System files to your web server directory:
```bash
# For Apache on Linux
sudo cp -r cbt-system /var/www/html/

# For XAMPP on Windows
Copy cbt-system folder to C:\xampp\htdocs\
```

### 2. Make folders writable
The installer writes `config/node.local.php` and sync files:
```bash
# Linux/Mac
chmod -R 755 /var/www/html/cbt-system
chmod -R 777 /var/www/html/cbt-system/config
chmod -R 777 /var/www/html/cbt-system/storage
chmod -R 777 /var/www/html/cbt-system/assets/uploads
chmod -R 777 /var/www/html/cbt-system/logs
```

### 3. Open the installer
Visit `http://localhost/cbt-system/` (or `http://localhost/cbt-system/install/`).

1. Choose **Online server** or **Offline exam hall**.
2. Confirm the checklist is green.
3. Enter the site name, database login, and the first super-admin account.
4. For an offline hall, paste the pairing code from the online server (Admin > Offline Nodes > Add exam hall).
5. Click **Install now**.

The wizard creates the database (if the MySQL user is allowed), imports tables, writes local settings, and prepares background sync.

### 4. First login
Use the admin username and password you typed in the wizard.

For a pair of online + offline servers, see `docs/SYNC.md`.

### 5. Configure Apache
Ensure mod_rewrite is enabled:
```bash
# Linux
sudo a2enmod rewrite
sudo systemctl restart apache2
```

Update Apache virtual host to allow .htaccess:
```apache
<Directory /var/www/html/cbt-system>
    AllowOverride All
    Require all granted
</Directory>
```

### 6. Access Application
- Admin Panel: `http://localhost/cbt-system/admin/`
- Student Portal: `http://localhost/cbt-system/student/login.php`

## Configuration

### App URL
Set `app_url` in `config/node.local.php` (the installer writes this file).

### Timezone
```php
date_default_timezone_set('Africa/Lagos'); // Change to your timezone
```

### Grading Scale
Modify the `$GRADING_SCALE` array in `config/config.php`:
```php
$GRADING_SCALE = [
    ['min' => 70, 'max' => 100, 'grade' => 'A', 'remark' => 'Excellent'],
    ['min' => 60, 'max' => 69, 'grade' => 'B', 'remark' => 'Very Good'],
    // ... add your custom scale
];
```

## Production Deployment Checklist

- [ ] Change default admin password
- [ ] Enable HTTPS
- [ ] Set `display_errors` to Off
- [ ] Update `session.cookie_secure` to 1
- [ ] Configure proper database credentials
- [ ] Set up backup schedule
- [ ] Configure email (for notifications)
- [ ] Test with multiple concurrent users
- [ ] Set up log rotation
- [ ] Enable firewall rules

## Troubleshooting

### 404 Errors
- Check .htaccess is present
- Enable mod_rewrite
- Ensure AllowOverride All is set

### Database Connection Failed
- Re-open `/install/` if setup did not finish
- Verify DB credentials in `config/node.local.php`
- Check MySQL service is running
- Verify the database user can create or use the named database

### Upload Errors
- Check upload folder permissions (777)
- Increase upload_max_filesize in php.ini
- Check post_max_size in php.ini

### White Screen
- Check logs/logs/ directory for errors
- Enable display_errors temporarily
- Check PHP version (must be 8.0+)
