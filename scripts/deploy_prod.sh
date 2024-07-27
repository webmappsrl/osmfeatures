#!/bin/bash
set -e

source ./.env.example

# echo "Production deployment started ..."

# php artisan down

# composer install
# composer dump-autoload

# # Clear and cache config
# php artisan config:cache
# php artisan config:clear

# # Clear the old cache
# php artisan clear-compiled

# # TODO: Uncomment when api.favorite issue will be resolved
# # php artisan optimize

# php artisan migrate --force

# gracefully terminate laravel horizon in supervisor container
docker exec -it supervisor_$APP_NAME bash -c "cd /var/www/html/$APP_NAME && php artisan horizon:terminate"

#  php artisan up

echo "Deployment finished!"
