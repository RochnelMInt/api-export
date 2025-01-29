#!/bin/bash

if [ ! -f "vendor/autoload.php" ]; then
    composer install --no-progress --no-interaction
fi

if [ ! -f ".env" ]; then
    echo "Creating env file for env $APP_ENV"
    cp .env.example .env
else
    echo "env file exists."
fi

role=${CONTAINER_ROLE:-app}

initialized_file="initialized.txt"

if [ $role = "app" ]; then
    if [ ! -f $initialized_file ]; then
        echo "existe pas"
        sleep 15  # Temporisation de 10 secondes
        php artisan migrate
        php artisan key:generate
        php artisan cache:clear
        php artisan config:clear
        php artisan route:clear
        touch $initialized_file
    fi
    php artisan serve --port=$PORT --host=0.0.0.0 --env=.env
    exec docker-php-entrypoint "$@"
fi
