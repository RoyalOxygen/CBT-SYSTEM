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

## Step-by-Step Installation

### 1. Download & Extract
Extract the CBT System files to your web server directory:
```bash
# For Apache on Linux
sudo cp -r cbt-system /var/www/html/

# For XAMPP on Windows
Copy cbt-system folder to C:\xampp\htdocs\
```

### 2. Create Database
```sql
CREATE DATABASE cbt_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Import Schema
```bash
# Using MySQL command line
mysql -u root -p cbt_system < sql/schema.sql

# Using phpMyAdmin
- Select the cbt_system database
- Go to Import tab
- Choose sql/schema.sql file
- Click Go
```

### 4. Configure Database
Edit `config/database.php`:
```php
private const DB_HOST = 'localhost';
private const DB_NAME = 'cbt_system';
private const DB_USER = 'root';        // Change to your DB user
private const DB_PASS = '';            // Change to your DB password
```

### 5. Set Permissions
```bash
# Linux/Mac
chmod -R 755 /var/www/html/cbt-system
chmod -R 777 /var/www/html/cbt-system/assets/uploads
chmod -R 777 /var/www/html/cbt-system/logs

# Windows
# Right-click folder → Properties → Security → Edit Permissions
```

### 6. Configure Apache
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

### 7. Access Application
- Admin Panel: `http://localhost/cbt-system/admin/`
- Student Portal: `http://localhost/cbt-system/student/login.php`

### 8. First Login
- **Admin:** username `admin`, password `Admin@123`
- **Student:** Use matric number (password = matric number on first login)

## Configuration

### App URL
Edit `config/config.php`:
```php
const APP_URL = 'http://localhost/cbt-system';
```

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
- Verify DB credentials in config/database.php
- Check MySQL service is running
- Verify database exists

### Upload Errors
- Check upload folder permissions (777)
- Increase upload_max_filesize in php.ini
- Check post_max_size in php.ini

### White Screen
- Check logs/logs/ directory for errors
- Enable display_errors temporarily
- Check PHP version (must be 8.0+)
