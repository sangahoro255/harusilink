# Majaribio ya mkono – HarusiLink

Tumia akaunti tatu: **Super Admin**, **Mwenye harusi A**, **Mwenye harusi B** (kila mmoja kwenye kivinjari/Incognito tofauti).
Kabla ya kuanza: `php database/migrate.php`, `php tests/lint.php`, `php tests/run.php` (yote lazima yapite).

## A. Safari ya mwenye harusi (A)
- [ ] Jisajili, ingia, toka, ingia tena
- [ ] Tengeneza harusi (tarehe ya baadaye) na uhariri maelezo
- [ ] Pakia picha ya jalada (JPG). Faili la `.txt` lililopewa jina `.jpg` linakataliwa
- [ ] Washa harusi (Rasimu hadi Inaendelea)
- [ ] Ongeza wageni 3 kwa mkono (simu `0712...`), na wengine kwa CSV (faili la mfano)
- [ ] Mwaliko: badilisha kiolezo, rangi na ujumbe; kadi ya mfano inabadilika
- [ ] Michango: weka lengo na tarehe ya mwisho; ongeza malipo, ahadi, na "Imelipwa"
- [ ] Ripoti: namba zinalingana na kurasa za Wageni, RSVP na Michango
- [ ] Pakua CSV tatu na ufungue kwenye Excel (simu hazibadiliki, majina yenye `=` hayatekelezwi)

## B. Safari ya mgeni (Incognito, bila kuingia)
- [ ] Fungua kiungo cha mgeni: kadi ina jina lake, tarehe, saa na ukumbi
- [ ] Kiungo kibovu (herufi moja imebadilishwa): "Mwaliko haupatikani"
- [ ] Bonyeza "Ndiyo, nitahudhuria": hali inabadilika, QR inaonekana, RSVP ya A inaonyesha jibu
- [ ] Badilisha mawazo (Hapana): QR inatoweka
- [ ] Mwenye harusi akiondoa kiungo: kadi inakufa. Akirudisha: inafanya kazi
- [ ] "Kiungo kipya": kiungo cha zamani kinakufa
- [ ] Harusi ikiwa Rasimu: kadi ya mgeni haifunguki
- [ ] Badilisha lugha SW/EN: kila kitu kinabadilika, ikiwemo tarehe

## C. Mlangoni
- [ ] Tengeneza PIN; PIN mbaya inakataliwa; PIN sahihi inafungua skana
- [ ] Majaribio 8 ya PIN mbaya: yanazuiwa kwa dakika 15
- [ ] Skani QR ya mgeni aliyekubali: kijani, "Thibitisha kuingia"
- [ ] Skani tena: nyekundu "Ameshaingia"
- [ ] Mgeni asiyejibu: njano, "Ruhusu kuingia"; aliyekataa: nyekundu
- [ ] Tafuta kwa jina: mgeni anapatikana, onyo la "umemtafuta kwa jina" linaonekana
- [ ] Mbio: fungua matokeo ya mgeni mmoja kwenye madirisha mawili, thibitisha kwenye yote; ni moja tu inayoingiza
- [ ] "Tengua" kwa mwenye harusi: mgeni anaweza kuskaniwa tena
- [ ] PIN mpya: wafanyakazi walioingia wanatolewa
- [ ] Bila kuingia kwenye skana, `/verify/TOKEN` haionyeshi taarifa za mgeni

## D. Ujumbe na vikumbusho (hali ya majaribio)
- [ ] Skrini inaonyesha "MAJARIBIO"; ujumbe unarekodiwa kwenye `storage/logs/messages.log`
- [ ] Kutuma mara mbili (nyuma na tuma tena): hakuna ujumbe wa pili
- [ ] Kikumbusho cha ahadi: mtu hakumbushwi tena ndani ya siku 3
- [ ] Mialiko: "ambao hawajapokea" inashuka hadi 0 baada ya kutuma
- [ ] `MESSAGING_MODE=live` bila akaunti: njia zote "haipatikani"
- [ ] Webhook: `php tests/simulate_whatsapp.php TOKEN accepted` inabadilisha RSVP; ombi lisilosainiwa linapata 403

## E. Usalama wa harusi nyingi (A dhidi ya B)
Kwa kila kiungo cha A, jaribu kama B (ID za A zinaonekana kwenye URL):
- [ ] `/weddings/ID`, `/details`, `/edit`, `/guests`, `/invitation`, `/rsvp`
- [ ] `/contributions`, `/reminders`, `/messages`, `/reports`, `/checkin`
- [ ] `/reports/export/guests` na `/contributions/ID/edit`
- [ ] Zote zinatoa 404, na hakuna data ya A inayoonekana
- [ ] Ukitumia ID ya mgeni/mchango wa harusi ya B ndani ya URL ya harusi ya A: 404
- [ ] Mwenye harusi hawezi kufungua `/admin` (403); mtu asiyeingia anapelekwa `/login`

## F. Super Admin
- [ ] Orodha ya watumiaji: tafuta, simamisha (mtumiaji anatolewa mara moja), ruhusu
- [ ] Mtumiaji aliyesimamishwa hawezi kuingia
- [ ] Harusi zote zinaonekana; shughuli (audit) zina matukio yote muhimu
- [ ] Super Admin hawezi kujisimamisha wala kusimamisha admin mwingine

## G. Ulinzi wa jumla
- [ ] Nenosiri baya mara 5: akaunti inazuiwa kwa dakika 15
- [ ] Futa `_csrf` kwenye fomu yoyote: "Ombi si halali"
- [ ] Jina la mgeni `<script>alert(1)</script>` linaonekana kama maandishi kila mahali
- [ ] `' OR 1=1 --` kwenye utafutaji: hakuna kosa la SQL
- [ ] `APP_DEBUG=false`: kosa la makusudi linaonyesha ukurasa wa kirafiki, si maelezo ya kiufundi
- [ ] `/.env`, `/database/`, `/storage/logs/app.log`, `/vendor/` zinatoa 403 kwenye browser
- [ ] Kwenye simu: kurasa zote zinasomeka, vitufe vinabonyezeka, jedwali ni kadi