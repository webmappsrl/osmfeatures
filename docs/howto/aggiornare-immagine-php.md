# Aggiornare l'immagine PHP (locale e produzione)

L'immagine è custom (`docker/configs/phpfpm/Dockerfile`) e la usano due container: `phpfpm`
(`php81_osmfeatures`) e `horizon` (`horizon_osmfeatures`). Aggiornare PHP significa cambiare il
pin e **ricostruire**. Il perché delle scelte è in
[docs/knowledge/8846-messa-in-sicurezza-php2shell-osmfeatures.md](../knowledge/8846-messa-in-sicurezza-php2shell-osmfeatures.md).

## Nel repo

1. Cambia il `FROM` del Dockerfile **con il suffisso della Debian** (`-trixie`, `-bookworm`) e il
   tag nelle due righe `image:` di `docker-compose.yml.example` (servizi `phpfpm` e `horizon`),
   nello stesso commit.
2. Se cambia la Debian, confronta l'import prima di tutto: costruisci un'immagine per base
   (`sed "s|^FROM .*|FROM <base>|" docker/configs/phpfpm/Dockerfile | docker build --pull -t <tag> -f - docker/configs/phpfpm`),
   esegui `osm2pgsql ... -O flex -x -S storage/osm/lua/all_imports.lua <pbf> --slim` su un PBF
   piccolo in due database separati e confronta conteggi e contenuti delle tabelle.
3. In locale: `docker compose build --pull phpfpm`, poi `php -v`, `php-fpm -tt` e `php -m` dentro
   `php81_osmfeatures`, poi `php artisan test`. I fallimenti vanno confrontati con quelli
   dell'immagine precedente prima di attribuirli all'aggiornamento.

## Sul server (passi da dev, deploy manuale)

Nell'ordine, dalla cartella del repo sul server, di giorno (i cron girano dalle 19:00 alle 01:00):

1. **Tagga l'immagine in esecuzione** sull'ID letto da
   `docker inspect php81_osmfeatures --format '{{.Image}}'`:
   `docker tag <ID> wm-phpfpm:pre-<ticket>`. Annota la data `Created` di `postgres_osmfeatures`
   e `redis_osmfeatures`.
2. Controlla lo spazio (`df -h /`): il build chiede alcuni GB.
3. `git pull`, poi `git log -1` per controllare il commit.
4. Copia `docker-compose.yml` fuori dal repo e cambia **solo** le righe `image:` di `phpfpm` e
   `horizon`; controlla con `diff`.
5. `docker compose build --pull phpfpm` — sempre con `--pull`.
6. `docker exec horizon_osmfeatures php artisan horizon:terminate`.
7. `docker compose up -d --no-deps phpfpm horizon`. Mai senza `--no-deps`, mai senza i nomi dei
   servizi: ricreerebbe anche `db` e `redis`.
8. Non eseguire `scripts/deploy_prod.sh` se il commit non cambia codice o dipendenze.

## Verifiche finali

```bash
docker exec php81_osmfeatures php -v            # la versione attesa, in entrambi i container
docker exec horizon_osmfeatures php -v
docker exec php81_osmfeatures php-fpm -tt 2>&1 | grep -E "upload_max_filesize|post_max_size|upload_progress"
docker exec horizon_osmfeatures php artisan horizon:status
docker inspect postgres_osmfeatures redis_osmfeatures --format '{{.Name}} {{.Created}}'   # date invariate
curl -s -o /dev/null -w '%{http_code}' https://osmfeatures.maphub.it/api/v1/features/hiking-routes/list
```

**Rollback:** `docker tag wm-phpfpm:pre-<ticket> <tag del compose> && docker compose up -d --no-deps phpfpm horizon`.
L'immagine `pre-<ticket>` si rimuove solo a verifica conclusa, dopo qualche giorno.
