# Szerviz és Karbantartás Nyilvántartó Rendszer

Reszponzív webalkalmazás szervizcégek számára. Ügyfeleket, kazánokat, klímákat és más berendezéseket tart nyilván, kezeli a karbantartási előzményeket, az időpontfoglalásokat, a munkalapokat és a közelgő éves karbantartási emlékeztetőket.

## Technológia

- Kliens: HTML, CSS, JavaScript
- Szerver: PHP REST API
- Adattárolás: MySQL
- Teszt: PHP alapú API smoke teszt

## Mappaszerkezet

- `api/`: RESTful szerveroldali komponens
- `public/`: reszponzív webes kliens
- `database/`: adatbázis séma és importálható dump
- `docs/`: műszaki dokumentáció és adatbázismodell-diagram
- `tests/`: tesztkód

## Telepítés

1. Indíts MySQL-t és PHP-t támogató webszervert, például XAMPP vagy WAMP környezetet.
2. Importáld a mintaadatokat:

```bash
mysql -u root -p < database/dump.sql
```

Nagyobb, bemutatóhoz használható mintaadatbázis is készült 200 ügyféllel és 200 berendezéssel. Ebben berendezésfajtánként 50 tulajdonos ügyfél szerepel: kazán, klíma, hőszivattyú és egyéb.

```bash
mysql -u root -p < database/sample_200_berendezes_dump.sql
```

3. Ha nem `root` felhasználót vagy üres jelszót használsz, módosítsd az adatbázis kapcsolatot az `api/config.php` fájlban.
4. Tedd a projektet a webszerver kiszolgált mappájába, vagy indíts fejlesztői szervert:

```bash
php -S localhost:nyilvantarto
```

5. Nyisd meg a böngészőben:

```text
http://localhost/nyilvantarto/public/index.html
```

Fontos: a `public/index.html` fájlt ne dupla kattintással, `file://` címről nyisd meg, mert akkor a böngésző nem tudja meghívni a PHP API-t. Ilyenkor a mentésnél `NetworkError when attempting to fetch resource` hiba jelenhet meg.

Windows alatt kipróbálhatod a mellékelt indítót is:

```text
start-server.bat
```

XAMPP használata esetén másold a teljes projektmappát a `C:\xampp\htdocs` alá, indítsd el az Apache és MySQL szolgáltatást, majd ilyen címen nyisd meg:

```text
http://localhost/projektmappa-neve/public/
```

## Fő funkciók

- Ügyfelek rögzítése és törlése
- Berendezések felvétele ügyfélhez rendelve
- Karbantartási előzmények nyilvántartása
- Időpontok kezelése státusszal
- Munkalap generálása időponthoz kapcsolva
- Nyomtatható munkalap nézet
- 45 napon belül esedékes éves karbantartások listázása

## REST végpontok

- `GET /api/index.php?resource=dashboard`
- `GET|POST|DELETE /api/index.php?resource=customers`
- `GET|POST|DELETE /api/index.php?resource=equipment`
- `GET|POST|DELETE /api/index.php?resource=maintenance`
- `GET|POST|PUT|DELETE /api/index.php?resource=appointments`
- `GET|POST|DELETE /api/index.php?resource=work-orders`
- `GET /api/index.php?resource=reminders`

Részletesebb leírás: `docs/documentation.md`.
