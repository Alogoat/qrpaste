# QRPaste - Dockerfile pro Render.com
# PHP 8.2 s Apache a SQLite podporou

FROM php:8.2-apache

# Metadata
LABEL maintainer="QRPaste"
LABEL description="QRPaste - Rychlé sdílení pro školy"

# Instalace systémových závislostí a PHP rozšíření
RUN apt-get update && apt-get install -y \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Povolení Apache mod_rewrite pro .htaccess
RUN a2enmod rewrite

# Nastavení working directory
WORKDIR /var/www/html

# Kopírování souborů aplikace
COPY index.html .
COPY backend.php .
COPY qrcode.min.js .
COPY styles.css .
COPY .htaccess .

# Kopírování assets složky (pokud existuje)
COPY assests/ ./assests/

# Vytvoření data složky pro SQLite databázi s správnými oprávněními
RUN mkdir -p data && \
    chown -R www-data:www-data data && \
    chmod 755 data

# Nastavení oprávnění pro ostatní soubory
RUN chown -R www-data:www-data /var/www/html && \
    chmod 644 index.html backend.php qrcode.min.js styles.css .htaccess

# Konfigurace Apache pro AllowOverride (podpora .htaccess)
RUN echo '<Directory /var/www/html/>' >> /etc/apache2/apache2.conf && \
    echo '    AllowOverride All' >> /etc/apache2/apache2.conf && \
    echo '    Require all granted' >> /etc/apache2/apache2.conf && \
    echo '</Directory>' >> /etc/apache2/apache2.conf

# Konfigurace PHP pro upload větších souborů
RUN echo 'upload_max_filesize = 10M' >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo 'post_max_size = 11M' >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo 'memory_limit = 128M' >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo 'max_execution_time = 30' >> /usr/local/etc/php/conf.d/uploads.ini

# Zabezpečení - skrytí PHP verze
RUN echo 'expose_php = Off' >> /usr/local/etc/php/conf.d/security.ini && \
    echo 'display_errors = Off' >> /usr/local/etc/php/conf.d/security.ini && \
    echo 'log_errors = On' >> /usr/local/etc/php/conf.d/security.ini

# Environment proměnné (přepiš při deployi)
ENV QRPASTE_SECRET="change_me_in_production_2025"

# Port pro Render.com (defaultně 80, Render proxy na svůj port)
EXPOSE 80

# Health check pro Render.com
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Spuštění Apache serveru
CMD ["apache2-foreground"]
