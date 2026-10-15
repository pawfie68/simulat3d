# Simulat3D WordPress — produkcja

Strona: https://simulat3d.eu/ . Panel: https://simulat3d.eu/wordpress/wp-admin/ .
Przełączenie z Astro: 2026-10-15. WordPress działa w `/home/simulat/www/wordpress`, a główny index.php uruchamia motyw pod głównym adresem. Baza: simulattestchat. Dane dostępowe nie należą do repozytorium.

## Wdrażanie

GitHub Actions `.github/workflows/deploy.yml` sprawdza PHP/JSON i wysyła przez SFTP wyłącznie `wordpress/themes/simulat3d` oraz `wordpress/plugins/simulat3d-contact`. Uruchamia się po zmianach tych plików na main lub ręcznie na main. Używa sekretu FTP_PASSWORD i przypiętego klucza serwera SSH. Nie buduje już Astro, nie nadpisuje treści w bazie, uploads, rdzenia, wp-config ani adresów sklepu i bety. Zmiana treści polityki w pliku wymaga osobnej aktualizacji strony w panelu. Usunięcie pliku ze źródeł nie usuwa go automatycznie z hostingu.

`hosting/` zawiera referencyjne pliki routingu; nie są przesyłane automatycznie. WordPress core aktualizuje się niezależnie (automatyczne aktualizacje drobne). WP-Cron jest włączony. Kopie i większe aktualizacje wymagają normalnej obsługi administratora.

## Formularz

Wtyczka przekazuje dane do istniejącego Cloudflare Worker i Resend, na office@simulat3d.eu. Test wysyłki został odebrany i potwierdzony przez właściciela. Wtyczka nie zapisuje imion, e-maili ani treści w bazie WordPressa. Walidacja, nonce, honeypot, licznik prób HMAC IP: 5 prób/10 minut. Aktywna wysyłka wymaga SIMULAT3D_CONTACT_LIVE=true w prywatnym wp-config. Komunikat sukcesu oznacza przyjęcie do wysyłki, nie potwierdzenie doręczenia.

Miesięczna retencja zwykłej korespondencji dotyczy obsługi skrzynki i wymaga realizacji przez administratora. Nie wdrożono usuwania wiadomości z poczty. Ograniczenie formularza w WordPressie nie blokuje bezpośrednich wywołań istniejącego Workera.

## Kopia i wycofanie przełączenia

Prywatna kopia sprzed przełączenia znajduje się w `/home/simulat/simulat3d-private/wp-cutover-20261015/`: wordpress-before.zip, wordpress-before.sql i index.html. Kopia bazy zawiera 12 tabel, archiwum plików około 38 MB. Zawiera sekrety — nie publikować.

Aby przywrócić Astro: zabezpieczyć aktualną bazę WordPressa; przenieść nowy `/www/index.php` i `/www/.htaccess` do prywatnego katalogu, przywrócić zapisany index.html (wcześniej główny .htaccess nie istniał). Zachowane katalogi assets, _astro, images, sklep i msfs-beta nadal istnieją. Najpierw sprawdzić działanie starej witryny, dopiero potem zmieniać workflow. Samo wycofanie strony głównej nie wymaga odtwarzania bazy. Nie uruchamiać starego workflow Astro na nowej produkcji.

`setup.php` służy wyłącznie do pierwszej instalacji pustego WordPressa; nie uruchamiać ponownie na produkcji. Źródłowy układ strony jest wzorcem, a jej bieżąca treść znajduje się w bazie i może być edytowana w panelu.
