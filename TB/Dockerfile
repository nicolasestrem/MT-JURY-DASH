FROM wordpress:6.4-php8.2-apache

# Install additional PHP extensions and tools
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

# Configure Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && a2enmod rewrite headers

# Set proper DocumentRoot permissions and DirectoryIndex
RUN echo '<Directory /var/www/html/>\n\
    Options FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
    DirectoryIndex index.php index.html\n\
</Directory>' > /etc/apache2/conf-available/wordpress.conf \
    && a2enconf wordpress

# Ensure the entrypoint script runs to install WordPress
RUN set -eux; \
    chmod +x /usr/local/bin/docker-entrypoint.sh

# Create wp-content directories with proper permissions
RUN mkdir -p /var/www/html/wp-content/uploads \
    && mkdir -p /var/www/html/wp-content/plugins \
    && mkdir -p /var/www/html/wp-content/themes \
    && chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/html

EXPOSE 80

# Use the original WordPress entrypoint to ensure WordPress gets installed
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]