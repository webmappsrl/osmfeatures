#!/bin/bash
set -e

echo "Deployment started ..."

# Enter maintenance mode or return true
# if already is in maintenance mode
(php artisan down) || true

# Install composer dependencies
composer install  --no-interaction --prefer-dist --optimize-autoloader

# Regenerate the db
php artisan migrate:fresh

#Seed the db to create admin user
php artisan db:seed 

#osmium and osm2pgsql sync for montepisano
php artisan osmfeatures:sync montepisano 172.31.0.3 https://download.geofabrik.de/europe/italy/centro-latest.osm.pbf 10.3,43.6,10.7,43.9

php artisan optimize:clear
php artisan config:clear

# Exit maintenance mode
php artisan up

echo "Deployment finished!"
