# Docker Setup for Mobility Trailblazers WordPress Plugin

## Overview
This Docker configuration provides a complete WordPress development environment for the Mobility Trailblazers plugin with proper permissions and without Redis.

## Services
- **WordPress** (PHP 8.2 + Apache) - Port 8080
- **MariaDB 11** - Port 3306  
- **phpMyAdmin** - Port 8081
- **WP-CLI** - For WordPress management

## Directory Structure
```
MT-JURY-DASH/
├── TB/                          # Docker configuration files
│   ├── docker-compose.yml       # Main Docker Compose configuration
│   ├── Dockerfile              # Custom WordPress image with tools
│   ├── php.ini                 # PHP configuration
│   └── .env                    # Environment variables (DO NOT COMMIT)
├── Plugin/                     # Your plugin source code
│   ├── assets/
│   ├── includes/
│   ├── templates/
│   └── mobility-trailblazers.php
```

## Quick Start

### 1. Navigate to Docker directory
```bash
cd C:\Users\nicol\Desktop\MT-JURY-DASH\TB
```

### 2. Start the containers
```bash
docker-compose up -d
```

### 3. Wait for WordPress to initialize (first run only)
The first run will take a few minutes to download images and set up WordPress.

### 4. Access the services
- **WordPress**: http://localhost:8080
- **phpMyAdmin**: http://localhost:8081
- **Plugin Location**: `/wp-content/plugins/mobility-trailblazers/`

## Common Commands

### Start services
```bash
docker-compose up -d
```

### Stop services
```bash
docker-compose down
```

### View logs
```bash
# All services
docker-compose logs -f

# WordPress only
docker-compose logs -f wordpress

# Database only
docker-compose logs -f db
```

### Execute WP-CLI commands
```bash
# List plugins
docker-compose exec wpcli wp plugin list

# Activate the Mobility Trailblazers plugin
docker-compose exec wpcli wp plugin activate mobility-trailblazers

# Clear cache
docker-compose exec wpcli wp cache flush

# Create admin user (first time setup)
docker-compose exec wpcli wp user create admin admin@example.com --role=administrator --user_pass=admin123
```

### Access WordPress container shell
```bash
docker-compose exec wordpress bash
```

### Access database shell
```bash
docker-compose exec db mysql -u wp_user -p
# Password: Wp7kL9xP2qR7vN6wE3zY4uC1sA5f
```

## Database Access

### phpMyAdmin
- URL: http://localhost:8081
- Server: db
- Username: wp_user
- Password: Wp7kL9xP2qR7vN6wE3zY4uC1sA5f

### Direct MySQL Connection
- Host: localhost
- Port: 3306
- Database: wordpress_db
- Username: wp_user
- Password: Wp7kL9xP2qR7vN6wE3zY4uC1sA5f

## Development Features

### File Permissions
The setup automatically handles file permissions for:
- Plugin files (read/write access)
- Upload directories
- Temporary files

### Debug Mode
WordPress debug mode is enabled by default with:
- WP_DEBUG: true
- WP_DEBUG_LOG: true
- MT_DEBUG: true
- Error logs: `/wp-content/debug.log`

### PHP Configuration
Custom PHP settings include:
- Max upload: 100MB
- Memory limit: 256MB
- Execution time: 300s
- Timezone: Europe/Paris

## Troubleshooting

### Permission Issues
If you encounter permission issues:
```bash
# Fix plugin permissions
docker-compose exec wordpress chown -R www-data:www-data /var/www/html/wp-content/plugins/mobility-trailblazers
docker-compose exec wordpress chmod -R 755 /var/www/html/wp-content/plugins/mobility-trailblazers

# Fix uploads permissions  
docker-compose exec wordpress chown -R www-data:www-data /var/www/html/wp-content/uploads
docker-compose exec wordpress chmod -R 755 /var/www/html/wp-content/uploads
```

### Plugin Not Appearing
```bash
# Check if plugin files are mounted
docker-compose exec wordpress ls -la /var/www/html/wp-content/plugins/

# Activate plugin
docker-compose exec wpcli wp plugin activate mobility-trailblazers
```

### Database Connection Issues
```bash
# Check database is running
docker-compose ps

# Test database connection
docker-compose exec wordpress wp db check
```

### Clear Everything and Start Fresh
```bash
# Stop and remove all containers, volumes, and networks
docker-compose down -v

# Start fresh
docker-compose up -d
```

## Notes
- The plugin directory is mounted from `../Plugin` (relative to TB directory)
- All database credentials are stored in `.env` file (not committed to git)
- WordPress data persists in Docker volumes between restarts
- The setup uses proper www-data permissions for file uploads