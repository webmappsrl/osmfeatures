# Immagine PHP di osmfeatures: base, pool FPM e rilascio

## Come funziona oggi

- **Base dell'immagine:** `php:8.2.35-fpm-trixie`, scritta con il suffisso della Debian. La base
  decide la versione di `osm2pgsql` installata da apt (2.1.1 su trixie, 1.8.0 su bookworm), cioè
  lo strumento dell'import notturno, che la suite di test non esercita.
- **Pool FPM** (`docker/configs/phpfpm/www.conf`): `upload_max_filesize = 32M`,
  `post_max_size = 128M`, `session.upload_progress.enabled = off`, `memory_limit = 1024M`. I
  valori sono quelli di camminiditalia (oc:8836): osmfeatures non riceve upload di file, il body
  più grande è il POST GeoJSON di `/api/v1/features/admin-areas/geojson`.
- **Il `docker-compose.yml` di produzione è fuori da git** (`.gitignore`): il tag dell'immagine nei
  servizi `phpfpm` e `horizon` si cambia a mano sul server, il repo tiene solo
  `docker-compose.yml.example`.
- **Il rilascio in produzione è manuale.** L'Action `Laravel deploy PROD` fa dipendere il deploy
  dai test, e 11 test falliscono già su PHP 8.2.15: da marzo 2026 il job `Deploy` viene sempre
  saltato. Nessuna delle Action ricostruisce comunque l'immagine.
- Procedura completa: [docs/howto/aggiornare-immagine-php.md](../howto/aggiornare-immagine-php.md).

## Perché così

- **Trixie invece di bookworm** (oc:8846): allineata agli altri shard passati a 8.2.35
  (carg, portapporta). La scelta è stata validata con un confronto dell'import:
  `all_imports.lua` con il comando di produzione sullo stesso PBF dà conteggi e contenuti identici
  con osm2pgsql 1.8.0 e 2.1.1. Le tabelle in più create da 2.x (`osm2pgsql_properties`,
  `planet_osm_users`) non arrivano nel DB Laravel, perché `Osm2pgsqlService` copia solo le tabelle
  elencate.
- **Ricreazione con `--no-deps`** (oc:8846): `phpfpm` e `horizon` dipendono da `db` e `redis`
  (`depends_on`); su oc:8844 un `up -d phpfpm` ha ricreato anche Redis e Postgres. Qui il DB pesa
  87 GB e Redis contiene la coda di Horizon.
- **Pool ristretto** (oc:8846): `session.upload_progress` è stato il vettore di una delle due
  CVE PHP2Shell (CVE-2026-109618/109619) e osmfeatures non lo usa.

## Come ci siamo arrivati

- **`php:8.2.15-fpm` senza suffisso** (fino a oc:8846, superata): base implicita, vulnerabile a
  PHP2Shell. Il tag senza suffisso nel frattempo era passato da bookworm a trixie senza che nulla
  nel repo lo dicesse.
- **Bookworm per non cambiare osm2pgsql** (oc:8846, scartata): era il ripiego nel caso il
  confronto dell'import desse differenze; non ne ha date.
- **PHP 8.4.27 come la maggior parte degli shard** (oc:8846, rimandata): Laravel 10 supporta
  PHP fino alla 8.3, quindi richiede prima l'aggiornamento del framework. Il ramo 8.2 va in EOL il
  31/12/2026.
