# Szoftverdokumentáció

## Cél

A rendszer valódi szervizcéges adminisztrációs problémára ad megoldást: egy helyen kezeli az ügyfeleket, berendezéseket, karbantartási előzményeket, időpontokat és munkalapokat. A cél az, hogy a szerelők és irodai munkatársak ne külön táblázatokból dolgozzanak, hanem kereshető, kapcsolt adatokat használjanak.

## Komponensek

### Kliens

A `public/` mappa tartalmazza a webes felületet. Az `index.html` adja a struktúrát, a `styles.css` a reszponzív megjelenést, az `app.js` pedig a REST API-val kommunikál. A felület asztali és mobil méreten is használható.

### Szerver

Az `api/` mappa PHP alapú REST API-t tartalmaz. Az `index.php` útvonal alapján választja ki az erőforrást, a `Database.php` PDO-val kapcsolódik MySQL-hez, a `Request.php` és `Response.php` pedig a bejövő JSON és a válaszok kezelését egységesíti.

### Adatbázis

A MySQL adatbázis fő táblái:

- `customers`: ügyfelek
- `equipment`: kazánok, klímák és egyéb berendezések
- `appointments`: időpontfoglalások
- `maintenance_records`: karbantartási előzmények
- `work_orders`: munkalapok

Az adatbázismodell diagramja: `docs/database-model.md`. A séma: `database/schema.sql`. Teljes importálható dump mintaadatokkal: `database/dump.sql`.

## Műszaki feltételek

- PHP 8.1 vagy újabb
- MySQL 8 vagy MariaDB 10.5 vagy újabb
- PDO MySQL PHP bővítmény
- Modern böngésző mobilon vagy asztali gépen

## Használat

1. Importáld a `database/dump.sql` fájlt.
2. Állítsd be az adatbázis kapcsolatot az `api/config.php` fájlban.
3. Indíts webszervert a projekt gyökeréből.
4. Nyisd meg a `public/` felületet.
5. Rögzíts ügyfelet, majd rendelj hozzá berendezést.
6. Vegyél fel időpontot vagy karbantartási előzményt.
7. Generálj munkalapot a kiválasztott időponthoz.
8. Az emlékeztetők nézetben ellenőrizd a 45 napon belül esedékes karbantartásokat.

## Tiszta kód szempontok

- Az adatbázis műveletek előkészített SQL utasításokkal futnak.
- A szerveroldali segédosztályok elkülönítik az adatbázis, kérés és válasz felelősségeket.
- A kliens oldalon külön renderelő függvények kezelik az egyes modulokat.
- A CSS változókat használ a következetes színekhez és méretezéshez.

## Tesztelés

A `tests/api-tests.php` smoke teszt ellenőrzi, hogy a fő API végpontok elérhetők-e, és JSON választ adnak-e. A teszteredmények dokumentációja: `docs/test-results.md`.

