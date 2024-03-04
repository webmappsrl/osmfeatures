#!/bin/bash
set -e

echo "Deployment started ..."

# Enter maintenance mode or return true
# if already is in maintenance mode
(php artisan down) || true

#Install composer dependencies
composer install  --no-interaction --prefer-dist --optimize-autoloader

# Regenerate the db
# php artisan migrate:fresh

#Seed the db to create admin user
php artisan db:seed 

# #osmium and osm2pgsql sync for pois
# php artisan osmfeatures:sync isole_italia_pois pois --skip-download

# #osmium and osm2pgsql sync for admin areas
# php artisan osmfeatures:sync isole_italia_admin_areas admin_areas --skip-download

# #osmium and osm2pgsql sync for poles
# php artisan osmfeatures:sync isole_italia_poles poles --skip-download

php artisan optimize:clear
php artisan config:clear

# Exit maintenance mode
php artisan up

echo "Deployment finished!"
