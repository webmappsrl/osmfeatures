> Ticket: oc:7947

# Data di aggiornamento OSMFEATURES

## Cosa cambia
La card Nova "Last Lua Update" smette di leggere da `planet_osm_replication_status`
(tabella fossile, non aggiornata dal 21/01/2025) e legge invece da
`osm2pgsql_crontab_updates`, popolata a ogni esecuzione di `Osm2pgsqlService::import()`.
Il titolo visibile diventa "Last DB Import".

## Perché
Il vecchio sistema di sincronizzazione basato su osm2pgsql-replication è stato dismesso
il 21/01/2025. Il flusso attuale (full PBF import + dump/restore) non aggiorna mai
`planet_osm_replication_status`, quindi la card mostrava una data di 4 mesi fa.

## Requisiti
- [ ] `Osm2pgsqlService::import()` registra una riga in `osm2pgsql_crontab_updates` sia in caso di successo che di fallimento
- [ ] Il `create()` nel catch è avvolto in un try/catch interno (hardened) per non mascherare l'eccezione originale
- [ ] Il `throw $e` finale è preservato: il command `osmfeatures:osm-full-pbf-import` continua a riportare i fallimenti
- [ ] `LastLuaUpdate::content()` legge l'ultimo import con `success = true` da `osm2pgsql_crontab_updates`
- [ ] Se dopo l'ultimo import riuscito esistono righe con `success = false`, la card mostra un avviso visivo che segnala import falliti recenti
- [ ] Il titolo visualizzato diventa "Last DB Import"
- [ ] `use Illuminate\Support\Facades\DB` rimosso da `LastLuaUpdate.php` (non più usato)

## Rischi
- **Doppia responsabilità in `import()`** — accettabile dato il vincolo "no new files"; mitigato con hardened pattern
- **"No data found" se tutti gli import falliscono** — comportamento voluto: la card mostra solo l'ultimo import andato a buon fine; i fallimenti sono visibili nei log e segnalati sulla card stessa

## Out of scope
- Nessuna nuova migration
- Nessuna modifica a `.env`, crontab host o scheduler
- Nessuna rinomina della classe `LastLuaUpdate` (minimize diff)
- Nessun ImportTracker separato

## Moduli toccati
- `app/Services/Osm2pgsqlService.php` — aggiunta try/catch + tracking con hardened pattern
- `app/Nova/Cards/LastLuaUpdate.php` — riscrittura `content()`, cambio titolo, segnalazione fallimenti recenti, rimozione `use DB`
