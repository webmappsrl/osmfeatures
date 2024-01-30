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

#osmium and osm2pgsql sync for pois
export DEFAULT_NAME="centro_italia_pois"
export DEFAULT_LUA="pois"
export DEFAULT_HOST="172.30.0.3"
export DEFAULT_PBF="https://download.geofabrik.de/europe/italy/centro-latest.osm.pbf"
php artisan osmfeatures:sync

#osmium and osm2pgsql sync for admin areas
export DEFAULT_NAME="centro_italia_admin_areas"
export DEFAULT_LUA="admin_areas"
export DEFAULT_HOST="172.30.0.3"
export DEFAULT_PBF="https://download.geofabrik.de/europe/italy/centro-latest.osm.pbf"
php artisan osmfeatures:sync

#osmium and osm2pgsql sync for poles
export DEFAULT_NAME="centro_italia_poles"
export DEFAULT_LUA="poles"
export DEFAULT_HOST="172.30.0.3"
export DEFAULT_PBF="https://download.geofabrik.de/europe/italy/centro-latest.osm.pbf"
php artisan osmfeatures:sync

php artisan optimize:clear
php artisan config:clear

# Exit maintenance mode
php artisan up

echo "Deployment finished!"
