FROM wordpress:php8.2-apache

# Install Git and other development tools
RUN apt-get update && apt-get install -y \
    git \
    vim \
    nano \
    curl \
    wget \
    zip \
    unzip \
    mariadb-client \
    && rm -rf /var/lib/apt/lists/*

# Install additional PHP extensions that might be needed
RUN docker-php-ext-install opcache pdo_mysql mysqli

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Node.js and npm (for frontend development)
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Install WP-CLI
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x wp-cli.phar \
    && mv wp-cli.phar /usr/local/bin/wp

# Set working directory
WORKDIR /var/www/html

# Enable Apache modules for WordPress
RUN a2enmod rewrite headers expires

# Create uploads directories with proper permissions
RUN mkdir -p /var/www/html/wp-content/uploads/tmp \
    && chown -R www-data:www-data /var/www/html/wp-content \
    && chmod -R 755 /var/www/html/wp-content

# Add Git safe directory configuration to avoid ownership issues
RUN git config --global --add safe.directory /var/www/html/wp-content/plugins/mobility-trailblazers

# Set proper permissions for the web server
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \;

# Add a script to fix permissions after container starts
RUN echo '#!/bin/bash\n\
chown -R www-data:www-data /var/www/html/wp-content/uploads\n\
chmod -R 755 /var/www/html/wp-content/uploads\n\
chown -R www-data:www-data /var/www/html/wp-content/plugins/mobility-trailblazers\n\
chmod -R 755 /var/www/html/wp-content/plugins/mobility-trailblazers\n\
exec docker-entrypoint.sh apache2-foreground' > /usr/local/bin/custom-entrypoint.sh \
    && chmod +x /usr/local/bin/custom-entrypoint.sh

# Use custom entrypoint to ensure permissions are set
ENTRYPOINT ["/usr/local/bin/custom-entrypoint.sh"]