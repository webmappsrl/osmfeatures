# CLAUDE.md

Questo file fornisce indicazioni a Claude Code (claude.ai/code) quando lavora con il codice di questo repository.

## Panoramica del Progetto

**osmfeatures** è un'applicazione Laravel 10 che importa, elabora ed espone feature geografiche di OpenStreetMap (OSM) tramite una JSON API. Usa PostgreSQL/PostGIS per i dati spaziali, Redis per le code e Laravel Horizon per la gestione dei job. Lo stack gira all'interno di Docker (PHP 8.2 FPM, PostGIS 14-3.3, Redis).

## Comandi

Tutti i comandi PHP/artisan vanno eseguiti **dentro il container Docker** (`php81_osmfeatures`):

```bash
docker exec -it php81_osmfeatures bash
# Poi dentro il container:
php artisan <comando>
```

### Sviluppo

```bash
# Avvia il server locale (fuori Docker, tramite alias geobox)
geobox_serve osmfeatures

# Installa le dipendenze
composer install

# Esegui le migrazioni
php artisan migrate

# Esegui tutti i test
php artisan test

# Esegui un singolo file di test
php artisan test tests/Api/HikingRoutesApiTest.php

# Esegui un singolo metodo di test
php artisan test --filter nomeDelMetodo

# Correzione dello stile del codice (Laravel Pint)
./vendor/bin/pint

# Genera la documentazione Swagger
php artisan l5-swagger:generate
```

### Gestione Dati OSM

```bash
# Sync OSM interattivo (scarica il PBF e importa via osm2pgsql)
php artisan osmfeatures:sync

# Aggiorna i dati OSM (schedulato ogni giorno alle 00:00)
php artisan osmfeatures:pbf-update

# Correggi i timestamp delle hiking routes (schedulato ogni giorno alle 03:00)
php artisan osmfeatures:correct-hr-timestamps

# Arricchimento DEM
php artisan osmfeatures:dem-enrichment

# Calcola le intersezioni con le aree amministrative
php artisan osmfeatures:calculate-admin-areas-intersecting

# Carica il backup del DB su AWS
php artisan osmfeatures:upload-db-to-aws
```

I file Lua per le importazioni osm2pgsql si trovano in `storage/app/osm/lua/`. I file PBF sono salvati in `storage/app/osm/pbf/`.

## Architettura

### Flusso dei Dati

1. **Importazione PBF OSM**: `osmfeatures:sync` scarica un file PBF da Geofabrik, poi esegue `osm2pgsql` con uno script Lua per popolare le tabelle raw (es. `hiking_routes`, `poles`, `places`, `pois`, `admin_areas`).
2. **Pipeline di Arricchimento**: I Job (tramite la coda Horizon/Redis) arricchiscono le feature con descrizioni generate da OpenAI (a partire da contenuti Wikipedia/Wikidata) e immagini da Wikimedia Commons. I dati vengono salvati nella tabella `enrichments` tramite relazione polimorfica.
3. **Layer API**: I Controller servono le feature GeoJSON dalle tabelle PostGIS.

### Modelli Principali

Tutti i modelli geografici estendono `OsmfeaturesModel` (`app/Models/OsmfeaturesModel.php`), che fornisce:
- `getGeojsonFeature()` — restituisce il GeoJSON con proprietà, link OSM e dati di arricchimento
- `getOsmFeaturesId()` — restituisce l'ID composto come `R12345` (osm_type + osm_id)
- Trait: `OsmTagsProcessor` (helper per URL wiki), `OsmFeaturesIdProcessor`, `Enrichable`

Modelli: `HikingRoute`, `HikingWay`, `Pole`, `Place`, `Poi`, `AdminArea`, `Enrichment`, `DemEnrichment`, `AdminAreasEnrichment`

**Formato ID osmfeatures**: `{osm_type}{osm_id}` — es. `R1234567` (Relation), `W9876543` (Way), `N111222` (Node).

### Rotte API (`routes/api.php`)

Tutte sotto `/api/v1/features/`, con throttling. Gli endpoint seguono lo schema:
- `GET /features/{type}/list` — lista paginata (1000/pagina), filtrabile per `updated_at`, `bbox`, `score`
- `GET /features/{type}/{id}` — singola feature GeoJSON
- `GET /features/search` — ricerca spaziale/testuale cross-model (`SearchController` → `FeatureSearchService`)
- `GET /features/places/{lon}/{lat}/{distance}` — ricerca per prossimità

Tipi di feature: `pois`, `admin-areas`, `poles`, `hiking-routes`, `places`

### Servizi (`app/Services/`)

- `EnrichmentService` — orchestra l'arricchimento testuale (OpenAI) e multimediale (Wikimedia) per ogni modello
- `FeatureSearchService` — ricerca spaziale cross-model (modalità radius, bbox, point-in-polygon)
- `Osm2pgsqlService` — wrapper per i comandi CLI osm2pgsql e osmium
- `WikimediaService` — recupera immagini da Wikimedia Commons
- `DataFetchers/WikipediaFetcher`, `WikiDataFetcher` — recuperano contenuti wiki come input per OpenAI
- `Generators/OpenAiGenerator` — genera descrizioni e abstract tramite OpenAI

### Job (`app/Jobs/`)

- `EnrichmentJob` — arricchimento completo (testo + media) per un singolo modello
- `DemEnrichmentJob` — arricchimento DEM (Digital Elevation Model)
- `ProcessHikingRoutesJob` / `ProcessHikingRoutesWayJob` — correzione dei timestamp per le hiking routes
- `CalculateAdminAreasIntersectingJob` — calcolo delle intersezioni spaziali

### Admin Nova (`app/Nova/`)

Laravel Nova è usato per l'interfaccia di amministrazione. Le Resource rispecchiano i modelli. Filtri personalizzati in `app/Nova/Filters/`, azioni personalizzate in `app/Nova/Actions/`.

### Struttura dei Test

- `tests/Unit/` — test unitari (trait, utility)
- `tests/Feature/` — test di feature
- `tests/Api/` — test degli endpoint API (un file per tipo di feature: `HikingRoutesApiTest`, `PolesApiTest`, ecc.)

I test usano un database PostgreSQL/PostGIS reale (non SQLite in-memory — vedi `phpunit.xml`).

## Pacchetti Locali

Sono inclusi due pacchetti locali come path-repository:
- `wm-package/` — utility condivise Webmapp
- `wm-internal/` — strumenti interni Webmapp

Dopo aver aggiornato questi pacchetti, eseguire `composer update wm/wm-package wm/wm-internal`.
