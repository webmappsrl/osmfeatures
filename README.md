# Laravel Postgis Boilerplate

Webmapp's Starting point

## Laravel 10 Project based on Nova 4

Boilerplate per Laravel 10 basato su php 8.2 e posgres + postgis. Supporto locale per web server php ed xdebug.

## INSTALL

First of all install the [GEOBOX](https://github.com/webmappsrl/geobox) repo and configure the [ALIASES command](https://github.com/webmappsrl/geobox#aliases-and-global-shell-variable).
Replace `${instance name}` with the instance name (APP_NAME in .env file)

```sh
git clone git@github.com:webmappsrl/osmfeatures.git osmfeatures
git flow init
```

Important NOTE: remember to checkout the develop branch.

```sh
cd osmfeatures
bash docker/init-docker.sh
docker exec -u 0 -it php81_osmfeatures bash
chown -R 33 storage
```

_Important NOTE_: if you have installed XDEBUG you need to create the xdebug.log file on the docker:

```bash
docker exec -u 0 -it php81_osmfeatures bash
touch /var/log/xdebug.log
chown -R 33 /var/log/
```

At the end run install command to for this instance

```bash
geobox_install osmfeatures
```

_Important NOTE_:

-   Update your local repository of Geobox following its [Aliases instructions](https://github.com/webmappsrl/geobox#aliases-and-global-shell-variable). Make sure that you have set the environment variable GEOBOX_PATH correctly.
-   Make sure that the version of wm-package of your instance is at leaset 1.1. Use command:

```bash
composer update wm/wp-package
```

Finally to import a fresh copy of database use Geobox restore command:

```bash
geobox_dump_restore osmfeatures
```

## Run web server from shell outside docker

In order to start a web server in local environment use the following command:
Replace `${instance name}` with the instance name (APP_NAME in .env file)

```sh
geobox_serve osmfeatures
```

### Differenze ambiente produzione locale

Questo sistema di container docker è utilizzabile sia per lo sviluppo locale sia per un sistema in produzione. In locale abbiamo queste caratteristiche:

-   la possibilità di lanciare il processo processo `php artisan serve` all'interno del container phpfpm, quindi la configurazione della porta `DOCKER_SERVE_PORT` (default: `8000`) necessaria al progetto. Se servono più istanze laravel con processo artisan serve contemporaneamente in locale, valutare di dedicare una porta tcp dedicata ad ognuno di essi. Per fare questo basta solo aggiornare `DOCKER_SERVE_PORT`.
-   la presenza di xdebug, definito in fase di build dell'immagine durante l'esecuzione del comando
-   `APP_ENV=local`, `APP_DEBUG=true` e `LOG_LEVEL=debug` che istruiscono laravel su una serie di comportamenti per il debug e l'esecuzione locale dell'applicativo
-   Una password del db con complessità minore. **In produzione usare [password complesse](https://www.avast.com/random-password-generator#pc)**

### Inizializzazione tramite boilerplate

-   Download del codice del boilerplate in una nuova cartella `nuovoprogetto` e disattivare il collegamento tra locale/remote:
    ```sh
    git clone https://github.com/webmappsrl/laravel-postgis-boilerplate.git nuovoprogetto
    cd nuovoprogetto
    git remote remove origin
    ```
-   Effettuare il link tra la repository locale e quella remota (repository vuota github)

    ```sh
    git remote add origin git@github.com:username/repo.git
    ```

-   Copy file `.env-example` to `.env`

    Questi valori nel file .env sono necessari per avviare l'ambiente docker. Hanno un valore di default e delle convenzioni associate, valutare la modifica:

    -   `APP_NAME` (it's php container name and - postgrest container name, no space)
    -   `DOCKER_PHP_PORT` (Incrementing starting from 9100 to 9199 range for MAC check with command "lsof -iTCP -sTCP:LISTEN")
    -   `DOCKER_SERVE_PORT` (always 8000, only on local environment)
    -   `DOCKER_PROJECT_DIR_NAME` (it's the folder name of the project)
    -   `DB_DATABASE`
    -   `DB_USERNAME`
    -   `DB_PASSWORD`

    Se siamo in produzione, rimuovere (o commentare) la riga:

    ```yml
    - ${DOCKER_SERVE_PORT}:8000
    ```

    dal file `docker-compose.yml`

-   Creare l'ambiente docker
    ```sh
    bash docker/init-docker.sh
    ```
-   Digitare `y` durante l'esecuzione dello script per l'installazione di xdebug

-   Verificare che i container si siano avviati

    ```sh
    docker ps
    ```

-   Avvio di una bash all'interno del container php per installare tutte le dipendenze e lanciare il comando php artisan serve (utilizzare `APP_NAME` al posto di `$nomeApp`):

    ```sh
    docker exec -it php81_$nomeApp bash
    composer install
    php artisan key:generate
    php artisan optimize
    php artisan migrate
    php artisan serve --host 0.0.0.0
    ```

-   A questo punto l'applicativo è in ascolto su <http://127.0.0.1:8000> (la porta è quella definita in `DOCKER_SERVE_PORT`)

### Configurazione xdebug vscode (solo in locale)

Assicurarsi di aver installato l'estensione [PHP Debug](https://marketplace.visualstudio.com/items?itemName=xdebug.php-debug).

Una volta avviato il container con xdebug configurare il file `.vscode/launch.json`, in particolare il `pathMappings` tenendo presente che **sulla sinistra abbiamo la path dove risiede il progetto all'interno del container**, `${workspaceRoot}` invece rappresenta la pah sul sistema host. Eg:

```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9200,
            "pathMappings": {
                "/var/www/html/geomixer2": "${workspaceRoot}"
            }
        }
    ]
}
```

Aggiornare `/var/www/html/geomixer2` con la path della cartella del progetto nel container phpfpm.

Per utilizzare xdebug **su browser** utilizzare uno di questi 2 metodi:

-   Installare estensione xdebug per browser [Xdebug helper](https://chrome.google.com/webstore/detail/xdebug-helper/eadndfjplgieldjbigjakmdgkmoaaaoc)
-   Utilizzare il query param `XDEBUG_SESSION_START=1` nella url che si vuole debuggare
-   Altro, [vedi documentazione xdebug](https://xdebug.org/docs/step_debug#web-application)

Invece **su cli** digitare questo prima di invocare il comando php da debuggare:

```bash
export XDEBUG_SESSION=1
```

### Scripts

Ci sono vari scripts per il deploy nella cartella `scripts`. Per lanciarli basta lanciare una bash con la path dello script dentro il container php, eg (utilizzare `APP_NAME` al posto di `$nomeApp`):

```bash
docker exec -it php81_$nomeApp bash scripts/deploy_dev.sh
```

### Artisan commands

-   `db:dump_db`
    Create a new sql file exporting all the current database in the local disk under the `database` directory
-   `db:download`
    download a dump.sql from server
-   `db:restore`
    Restore a last-dump.sql file (must be in root dir)

### Problemi noti

Durante l'esecuzione degli script potrebbero verificarsi problemi di scrittura su certe cartelle, questo perchè di default l'utente dentro il container è `www-data (id:33)` quando invece nel sistema host l'utente ha id `1000`:

-   Chown/chmod della cartella dove si intende scrivere, eg:

    NOTA: per eseguire il comando chown potrebbe essere necessario avere i privilegi di root. In questo caso si deve effettuare l'accesso al cointainer del docker utilizzando lo specifico utente root (-u 0). Questo è valido anche sbloccare la possibilità di scrivere nella cartella /var/log per il funzionamento di Xdedug

    Utilizzare il parametro `-u` per il comando `docker exec` così da specificare l'id utente, eg come utente root (utilizzare `APP_NAME` al posto di `$nomeApp`):

    ```bash
    docker exec -u 0 -it php81_$nomeApp bash
    chown -R 33 storage
    ```

Xdebug potrebbe non trovare il file di log configurato nel .ini, quindi generare vari warnings

-   creare un file in `/var/log/xdebug.log` all'interno del container phpfpm. Eseguire un `chown www-data /var/log/xdebug.log`. Creare questo file solo se si ha esigenze di debug errori xdebug (impossibile analizzare il codice tramite breakpoint) visto che potrebbe crescere esponenzialmente nel tempo
-

## Risorse

-   [osm data](https://webmappsrl.gitbook.io/osmdata-2.0/)

---

# Documentazione del Comando `osmfeatures:sync`

Il comando `osmfeatures:sync` è una parte integrante del progetto `osmfeatures`, che utilizza osm2pgsql e osmium per la sincronizzazione e l'elaborazione dei dati OSM (OpenStreetMap). Questo documento fornisce una guida su come utilizzare questo comando, inclusi i parametri, le opzioni e le loro funzioni.

## Panoramica del Comando

Il comando `artisan osmfeatures:sync` é progettato per essere flessibile e configurabile, consentendo agli sviluppatori di specificare diverse opzioni per il processo di importazione.

### Struttura del Comando

```bash
osmfeatures:sync {defaultName?} {defaultHost?} {defaultLua?} {--skip-download} {defaultPbf?}
```

### Parametri e Opzioni

Il comando supporta diversi parametri e opzioni:

1. **defaultName**: Il nome del file finale che verrá salvato. Obbligatorio.

2. **defaultHost**: PostgreSQL database host. Per trovare il valore lanciare il comando `docker inspect -f '{{range.NetworkSettings.Networks}}{{.IPAddress}}{{end}}' <container_name>`. Obbligatorio.

3. **defaultLua**: Il nome del file .lua (da digitare senza l'estensione \*.lua) da utilizzare per l'importazione con osm2pgsql. Questo file deve essere presente nella cartella `storage/app/osm/lua`. Obbligatorio.

4. **Opzione --skip-download**: Se non si vuole scaricare nuovamente il file PBF, utilizzare questa opzione. NOTA: é necessario che il file PBF sia già presente nella cartella `storage/app/osm/pbf`.

5. **defaultPbf**: Accetta un URL da cui scaricare il pbf. Non obbligatorio se si utilizza l'opzione `--skip-download`.

6. **bbox**: Bounding box per filtrare i dati OSM con osmium. Accetta una stringa di coordinate separate da virgola. Ad esempio, `10.0,10.0,11.0,11.0`.

## Esempio di Utilizzo

Utilizzando il package Laravel Prompts, il comando `osmfeatures:sync` guida l'utente attraverso le opzioni disponibili. Non sará necessario, quindi, digitare tutta la lista di parametri necessari. Per esempio, se si lancia:

```bash
osmfeatures:sync
```

---

Nel terminale verrà visualizzato un elenco di opzioni:

## ![Esempio di Utilizzo](storage/app/public/readme.png)

Questo esempio scarica un file da geofabrik, lo nomina Montepisano e lo salva in `storage/app/osm/pbf`. Il file viene quindi elaborato con osm2pgsql utilizzando il file lua `pois.lua`, che defiinisce la tabella e le colonne nel database, oltre ai dati che importeremo dal file .pbf scaricato in precedenza . NOTA: il file lua deve essere presente nella cartella `storage/app/osm/lua`.

In questo esempio specifico non viene utilizzata l'opzione `--skip-download`, quindi il file viene scaricato da geofabrik. Se si vuole utilizzare un file già presente nella cartella `storage/app/osm/pbf`, utilizzare l'opzione `--skip-download` ed il comando prenderà il file dalla cartella basandosi sul nome specificato nel primo parametro.

Il campo `bbox` è opzionale. Se non viene specificato, il comando importerà tutti i dati OSM dal file .pbf. Se si vuole importare solo una parte dei dati, utilizzare il parametro `bbox` per filtrare i dati OSM prima dell'importazione utilizzando osmium.

## Documentazione di osm2pgsql e osmium

-   **osm2pgsql**: Utilizzato per convertire i dati OSM in un formato utilizzabile dal database PostgreSQL. Documentazione dettagliata disponibile su [osm2pgsql.org](https://osm2pgsql.org/doc/manual.html).

-   **osmium**: Uno strumento per lavorare con i dati OSM, utilizzato per filtrare e manipolare i dati prima dell'importazione. Documentazione disponibile su [Osmium Tool](https://osmcode.org/osmium-tool/manual.html).

## Laravel Prompts

L'interfaccia di prompt di Laravel migliora l'esperienza dello sviluppatore fornendo una guida interattiva attraverso le opzioni del comando. Per maggiori informazioni, fare riferimento alla [documentazione di Laravel](https://laravel.com/docs/10.x/prompts#main-content).

---
