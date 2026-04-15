FROM php:8.2-apache

LABEL org.opencontainers.image.title="Ultimate Dashboard" \
      org.opencontainers.image.description="Self-hosted browser start-page dashboard" \
      org.opencontainers.image.url="https://github.com/Marcel-Balk/UltimateDashboard" \
      org.opencontainers.image.source="https://github.com/Marcel-Balk/UltimateDashboard" \
      org.opencontainers.image.version="0.0.3" \
      org.opencontainers.image.vendor="eXtreme Hosting" \
      org.opencontainers.image.authors="Marcel Balk <marcel@extremehosting.nl>" \
      org.opencontainers.image.licenses="MIT"

# Enable required extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
        libsqlite3-dev \
        libpng-dev \
        libjpeg-dev \
        libwebp-dev \
    && docker-php-ext-install pdo pdo_sqlite gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable mod_rewrite
RUN a2enmod rewrite

# Allow .htaccess overrides
RUN sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf

# PHP settings
RUN echo "upload_max_filesize=10M" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "post_max_size=12M"       >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "memory_limit=128M"       >> /usr/local/etc/php/conf.d/uploads.ini

# Document root
WORKDIR /var/www/html

# Copy application
COPY src/ /var/www/html/

# Create required directories & set permissions
RUN mkdir -p /data /var/www/html/uploads/logos \
 && chown -R www-data:www-data /data /var/www/html/uploads \
 && chmod 755 /data /var/www/html/uploads /var/www/html/uploads/logos

# Entrypoint: fix volume-mount permissions then start Apache
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
