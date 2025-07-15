FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
     git \
     unzip \
     libzip-dev \
     libpng-dev \
     libonig-dev \
     libxml2-dev \
     zip \
     curl

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mbstring zip exif pcntl bcmath gd

# Install Composer
COPY --from=composer:2.5 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy existing application directory contents
COPY . /var/www/html

# Install dependencies
RUN composer install

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
