FROM debian:12

# Apache + PHP + herramientas
RUN apt update && apt install -y \
    apache2 \
    php \
    libapache2-mod-php \
    php-cli \
    php-mbstring \
    php-xml \
    php-curl \
    php-zip \
    unzip \
    curl \
    nano \
    && apt clean \
    && rm -rf /var/lib/apt/lists/*

# Composer
RUN curl -sS https://getcomposer.org/installer -o composer-setup.php \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && rm composer-setup.php

# DocumentRoot apuntando a public/
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf \
    && a2enmod rewrite headers expires

WORKDIR /var/www/html

EXPOSE 80

CMD ["apache2ctl", "-D", "FOREGROUND"]
