> Ticket: oc:7947

# Data di aggiornamento OSMFEATURES — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fare in modo che la card Nova "Last DB Import" mostri la data reale dell'ultimo import OSM riuscito, leggendo da `osm2pgsql_crontab_updates` anziché dalla tabella fossile `planet_osm_replication_status`.

**Architecture:** `Osm2pgsqlService::import()` viene wrappato in try/catch che registra una riga in `osm2pgsql_crontab_updates` sia in caso di successo che di fallimento (hardened pattern). La card Nova legge l'ultimo record con `success=true` e segnala eventuali fallimenti successivi.

**Tech Stack:** Laravel 10, Eloquent ORM, Laravel Nova (HtmlCard), Carbon, PHP 8.2, PostgreSQL/PostGIS, Docker (`php81_osmfeatures`)

---

### Task 1: Tracking import in `Osm2pgsqlService`

**Files:**
- Modify: `app/Services/Osm2pgsqlService.php:38-43`

- [ ] **Step 1: Leggi il metodo `import()` attuale**

Apri `app/Services/Osm2pgsqlService.php`. Il metodo da modificare è alle righe 38-43:

```php
public function import($luaPath, $pbfPath)
{
    $this->createOsm2pgsqlDatabaseIfNoteExists();
    $this->osm2pgsql($luaPath, $pbfPath);
    $this->copyOsm2pgsqlTablesToTheLaravelDb();
}
```

- [ ] **Step 2: Aggiungi `use App\Models\Osm2pgsqlCrontabUpdate;` in cima al file**

Aggiungi dopo `use Illuminate\Support\Facades\Log;` (riga 7):

```php
use App\Models\Osm2pgsqlCrontabUpdate;
```

- [ ] **Step 3: Riscrivi il metodo `import()` con try/catch hardened**

Sostituisci il corpo del metodo con:

```php
public function import($luaPath, $pbfPath)
{
    try {
        $this->createOsm2pgsqlDatabaseIfNoteExists();
        $this->osm2pgsql($luaPath, $pbfPath);
        $this->copyOsm2pgsqlTablesToTheLaravelDb();

        try {
            Osm2pgsqlCrontabUpdate::create([
                'imported_at' => now(),
                'from_lua'    => basename($luaPath),
                'from_pbf'    => basename($pbfPath),
                'success'     => true,
            ]);
        } catch (\Throwable $loggingException) {
            Log::error('Failed to record import success: ' . $loggingException->getMessage());
        }
    } catch (\Throwable $e) {
        try {
            Osm2pgsqlCrontabUpdate::create([
                'imported_at' => now(),
                'from_lua'    => basename($luaPath),
                'from_pbf'    => basename($pbfPath),
                'success'     => false,
                'log'         => substr($e->getMessage(), 0, 255),
            ]);
        } catch (\Throwable $loggingException) {
            Log::error('Failed to record import failure: ' . $loggingException->getMessage());
        }

        throw $e;
    }
}
```

Note:
- `basename()` evita di sforare i 255 char della colonna se il path completo è lungo.
- `\Throwable` (non solo `\Exception`) cattura anche `Error` nativi (OOM, type error).
- `substr($e->getMessage(), 0, 255)` tronca al limite della colonna `log`.
- Il `throw $e` finale preserva la propagazione: `OsmFullPbfImport::handle()` continua a vedere i fallimenti.
- Il try/catch interno silenzioso garantisce che un fallimento del tracking non mascheri l'eccezione originale dell'import.

- [ ] **Step 4: Esegui lo style fixer**

```bash
docker exec -it php81_osmfeatures ./vendor/bin/pint app/Services/Osm2pgsqlService.php
```

Expected: nessun errore, file riformattato.

- [ ] **Step 5: Verifica manuale — path di successo**

```bash
docker exec -it php81_osmfeatures php artisan tinker
```

```php
$service = app(App\Services\Osm2pgsqlService::class);
// Simula scrittura diretta senza eseguire un import reale
App\Models\Osm2pgsqlCrontabUpdate::create([
    'imported_at' => now(),
    'from_lua'    => 'all_imports.lua',
    'from_pbf'    => 'italy-latest.osm.pbf',
    'success'     => true,
]);
App\Models\Osm2pgsqlCrontabUpdate::orderByDesc('imported_at')->first();
// Expected: oggetto con success=true e imported_at recente
```

- [ ] **Step 6: Verifica manuale — path di fallimento**

```php
App\Models\Osm2pgsqlCrontabUpdate::create([
    'imported_at' => now(),
    'from_lua'    => 'all_imports.lua',
    'from_pbf'    => 'italy-latest.osm.pbf',
    'success'     => false,
    'log'         => 'Simulated failure for testing',
]);
App\Models\Osm2pgsqlCrontabUpdate::where('success', false)->latest('imported_at')->first();
// Expected: oggetto con success=false e log='Simulated failure for testing'
```

- [ ] **Step 7: Pulizia seed di test**

```php
App\Models\Osm2pgsqlCrontabUpdate::truncate();
```

---

### Task 2: Card Nova `LastLuaUpdate` — nuova sorgente dati e avviso fallimenti

**Files:**
- Modify: `app/Nova/Cards/LastLuaUpdate.php:29-41`

- [ ] **Step 1: Rimuovi `use Illuminate\Support\Facades\DB;`**

Questo import non è più necessario dopo la riscrittura. Rimuovi la riga 7:

```php
use Illuminate\Support\Facades\DB;
```

Il file deve mantenere:

```php
use Abordage\HtmlCard\HtmlCard;
use Carbon\Carbon;
```

- [ ] **Step 2: Riscrivi il metodo `content()`**

Sostituisci l'intero metodo `content()` (righe 29-41) con:

```php
public function content(): string
{
    $lastSuccess = \App\Models\Osm2pgsqlCrontabUpdate::query()
        ->where('success', true)
        ->latest('imported_at')
        ->first();

    $title = '<h1 class="text-4xl">Last DB Import</h1>';

    if (! $lastSuccess) {
        return $title . '<p class="text-lg text-gray-400 text-center">No data found</p>';
    }

    $lastSuccessTime = Carbon::parse($lastSuccess->imported_at);
    $output = $title
        . '<p class="text-lg text-gray-400 text-center">'
        . $lastSuccessTime->toDateTimeString()
        . '</p>';

    $failedAfter = \App\Models\Osm2pgsqlCrontabUpdate::query()
        ->where('success', false)
        ->where('imported_at', '>', $lastSuccess->imported_at)
        ->exists();

    if ($failedAfter) {
        $output .= '<p class="text-sm text-red-500 text-center mt-2">'
            . '⚠️ Import falliti dopo l\'ultimo successo'
            . '</p>';
    }

    return $output;
}
```

Note:
- Usa `latest('imported_at')` invece di `MAX()` raw SQL — più leggibile e testabile.
- Il secondo query `exists()` è leggero: una sola riga booleana.
- Il messaggio di avviso è in rosso (`text-red-500`) per distinguersi dalla data grigia.

- [ ] **Step 3: Esegui lo style fixer**

```bash
docker exec -it php81_osmfeatures ./vendor/bin/pint app/Nova/Cards/LastLuaUpdate.php
```

Expected: nessun errore.

- [ ] **Step 4: Verifica visiva della card — caso "solo successi"**

```bash
docker exec -it php81_osmfeatures php artisan tinker
```

```php
App\Models\Osm2pgsqlCrontabUpdate::create([
    'imported_at' => now(),
    'from_lua'    => 'all_imports.lua',
    'from_pbf'    => 'italy-latest.osm.pbf',
    'success'     => true,
]);
```

Apri `http://localhost/dashboards/features` (o l'URL locale Nova). Expected: card mostra "Last DB Import" con data odierna, nessun avviso rosso.

- [ ] **Step 5: Verifica visiva della card — caso "fallimento dopo successo"**

```php
App\Models\Osm2pgsqlCrontabUpdate::create([
    'imported_at' => now()->addMinutes(1),
    'from_lua'    => 'all_imports.lua',
    'from_pbf'    => 'italy-latest.osm.pbf',
    'success'     => false,
    'log'         => 'osm2pgsql: file not found',
]);
```

Ricarica Nova. Expected: card mostra la data del successo precedente + avviso rosso "⚠️ Import falliti dopo l'ultimo successo".

- [ ] **Step 6: Verifica visiva della card — caso "tabella vuota"**

```php
App\Models\Osm2pgsqlCrontabUpdate::truncate();
```

Ricarica Nova. Expected: card mostra "Last DB Import" + "No data found".

- [ ] **Step 7: Verifica che `Features.php` non richieda modifiche**

Apri `app/Nova/Dashboards/Features.php` e conferma che la card sia registrata come `LastLuaUpdate::class` (riga 28). Nessuna modifica necessaria — la classe non è stata rinominata.

---

### Task 3: Commit finale

- [ ] **Step 1: Verifica diff completo**

```bash
git diff app/Services/Osm2pgsqlService.php app/Nova/Cards/LastLuaUpdate.php
```

Controlla che:
- `import()` contenga il try/catch hardened
- `LastLuaUpdate::content()` non usi più `DB::select()`
- `use Illuminate\Support\Facades\DB` sia rimosso da `LastLuaUpdate.php`

- [ ] **Step 2: Commit**

```bash
git add app/Services/Osm2pgsqlService.php app/Nova/Cards/LastLuaUpdate.php
git commit -m "feat(oc:7947): rebase Last DB Import card on osm2pgsql_crontab_updates

- Track every Osm2pgsqlService::import() execution (success + failure)
  in osm2pgsql_crontab_updates (table and model already exist).
- Hardened pattern: create() in catch wrapped in inner try/catch to
  avoid masking the original exception.
- LastLuaUpdate card now reads from Osm2pgsqlCrontabUpdate instead of
  planet_osm_replication_status (table not updated since 2025-01-21).
- Show red warning if failed imports exist after last success.
- Rename visible title to 'Last DB Import'.
- Remove unused DB facade import from LastLuaUpdate."
```

---

### Task 4: Seed manuale in produzione (dopo deploy)

> Eseguire **manualmente** in produzione dopo il merge della PR, per non lasciare "No data found" fino al prossimo import schedulato.

- [ ] **Step 1: Inserisci riga seed con data import del 26/05/2026**

```bash
docker exec -i postgres_osmfeatures psql -U osmfeatures -d osmfeatures <<'SQL'
INSERT INTO osm2pgsql_crontab_updates (imported_at, from_lua, from_pbf, success, log)
VALUES ('2026-05-26 13:24:23', 'all_imports.lua', 'italy-latest.osm.pbf', true, 'Seeded manually after deploy');
SQL
```

- [ ] **Step 2: Verifica**

```bash
docker exec -i postgres_osmfeatures psql -U osmfeatures -d osmfeatures \
  -c "SELECT id, imported_at, from_pbf, success FROM osm2pgsql_crontab_updates ORDER BY imported_at DESC LIMIT 3;"
```

Expected: riga con `imported_at = 2026-05-26 13:24:23` e `success = true`.

- [ ] **Step 3: Ricarica dashboard Nova in produzione**

Vai su `https://osmfeatures.maphub.it/dashboards/features`. Expected: "Last DB Import: 2026-05-26 13:24:23", nessun avviso rosso.
