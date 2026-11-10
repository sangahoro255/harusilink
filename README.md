# HarusiLink

Mfumo wa kusimamia mialiko ya harusi: wageni (pamoja na CSV), kadi za mwaliko, RSVP, QR ya kuingia mlangoni,
michango, ujumbe/vikumbusho (WhatsApp), ripoti, na usimamizi wa watumiaji. Lugha: Kiswahili na Kiingereza.

## Mahitaji

- PHP 8.0 au zaidi, na extensions: `pdo_mysql`, `mbstring`, `gd`, `fileinfo` (hiari: `exif`, `curl`)
- MySQL 8 / MariaDB
- XAMPP (Windows) ndiyo njia rahisi zaidi

## Njia A - XAMPP (Apache)

1. Nakili folda hii ndani ya `C:\xampp\htdocs\` na uipe jina `harusilink` (herufi ndogo).
2. Fungua **XAMPP Control Panel** na uwashe **Apache** na **MySQL**.
3. Fungua faili `.env` na uhakikishe `DB_USER` na `DB_PASS` ni za MySQL yako.
   XAMPP kwa kawaida ina `DB_USER=root` na `DB_PASS=` (tupu). Mfano uko kwenye `.env.example`.
4. Fungua Command Prompt ndani ya folda ya mradi, kisha tengeneza database na meza zote:
   ```
   C:\xampp\php\php.exe database\migrate.php
   ```
5. Tengeneza akaunti ya Super Admin:
   ```
   C:\xampp\php\php.exe database\create_super_admin.php
   ```
6. Fungua **http://localhost/harusilink/public/** kwenye kivinjari.

`APP_URL` kwenye `.env` lazima ilingane na anwani unayotumia (`http://localhost/harusilink/public`),
kwa sababu viungo vya QR vinatengenezwa kutokana nayo.

## Njia B - bila Apache (`php -S`)

Bonyeza mara mbili `serve.bat` (Windows) au endesha `./serve.sh`, kisha fungua **http://localhost:8000**.
Badilisha `.env` iwe `APP_URL=http://localhost:8000`. Hatua za 3 hadi 5 hapo juu bado zinahitajika.

## QR na kamera ya skana

- QR zinatengenezwa bila hatua yoyote ya ziada (kisimbaji cha ndani `app/Services/SimpleQr.php`).
  Ukipenda maktaba ya nje: `composer require bacon/bacon-qr-code` - itatumika kiotomatiki ikiwepo `vendor/`.
- Skana ya mlangoni inatumia kisomaji cha QR cha kivinjari (Chrome / Edge / Android). Kivinjari kisichokuwa nacho
  kinahitaji `jsQR.js` iwekwe kwenye `public/assets/js/jsQR.js` (hiari).
- Kamera inahitaji `localhost` au HTTPS. Simu kwenye mtandao wa nyumbani kupitia `http://192.168...` haitaruhusu kamera:
  tumia kamera ya kawaida ya simu, au "Tafuta mgeni kwa jina" kwenye skana.

## Ujumbe (WhatsApp)

`MESSAGING_MODE=mock` (chaguo-msingi): hakuna ujumbe unaotumwa kwa kweli; unarekodiwa kwenye `storage/logs/messages.log`.
Kujaribu majibu ya RSVP ya WhatsApp: `php tests/simulate_whatsapp.php TOKEN accepted`.

## Majaribio

```
php tests/lint.php     (ukaguzi wa sintaksia ya faili zote za PHP)
php tests/run.php      (majaribio ya mantiki)
```
Orodha ya majaribio ya mkono: `docs/TESTING.md`.

## Muundo wa folda

```
app/            Controllers, Models, Services, Helpers, Core, bootstrap.php
config/         database.php (muunganisho wa PDO)
database/       migrations 001-011, migrate.php, create_super_admin.php
docs/           TESTING.md
lang/           sw.php, en.php na sw/, en/ (tafsiri kwa moduli)
public/         index.php, assets/ (css, js, logos), uploads/covers/  <- hii pekee ndiyo inafunguliwa na wavuti
routes/         web.php
storage/logs/   app.log, messages.log
tests/          lint.php, run.php, simulate_whatsapp.php
views/          kurasa zote (HTML/PHP)
```

## Matatizo ya kawaida

| Dalili | Suluhisho |
| --- | --- |
| Ukurasa wa 500 | Angalia `storage/logs/app.log` (na `APP_DEBUG=true` kwenye `.env`) |
| `Access denied for user` / `Imeshindwa kuunganisha MySQL` | `DB_USER` / `DB_PASS` kwenye `.env` si sahihi, au MySQL haijawashwa |
| Kila ukurasa unatoa 404 isipokuwa mwanzo | Washa `mod_rewrite` na `AllowOverride All` kwenye Apache (XAMPP huwa imewashwa) |
| Picha ya jalada haipakiwi | Washa `extension=gd` na `extension=fileinfo` kwenye `php.ini`, kisha anzisha Apache upya |
| CSS/picha hazionekani | `APP_URL` na jina la folda (`harusilink`) havilingani |
