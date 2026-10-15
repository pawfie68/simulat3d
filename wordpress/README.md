# Simulat3D WordPress — chroniony podgląd

Motyw blokowy `themes/simulat3d` i wtyczka `plugins/simulat3d-contact` są źródłami projektu. Rdzeń WordPressa, baza danych, hasła i pliki wp-config nie należą do repozytorium.

Podgląd: https://simulat3d.eu/wp-preview-s3d/ (HTTP Basic Auth, noindex).
Nowa baza: simulattestchat. Istniejąca baza i strona produkcyjna nie są migrowane ani zastępowane.

## Instalacja

1. Zainstaluj aktualnego WordPressa w osobnym, chronionym katalogu i pustej bazie MySQL. Ustaw WP_HOME i WP_SITEURL na pełny adres podglądu. W czasie instalacji ustaw również WP_CONTENT_URL na WP_SITEURL + `/wp-content`, aby uniknąć względnych URL-i zasobów.
2. Skopiuj motyw do wp-content/themes, a wtyczkę do wp-content/plugins.
3. Wykonaj setup.php przez WP-CLI po zalogowaniu jako administrator. Skrypt inicjuje strony tylko raz. Ustaw bezpośrednie odnośniki na `/%postname%/`.
4. Formularz wysyła dopiero po ustawieniu `define('SIMULAT3D_CONTACT_LIVE', true);` w prywatnym wp-config.php. Domyślnie niczego nie wysyła.

## Formularz

WordPress przekazuje dane do istniejącego Cloudflare Worker, który korzysta z Resend. Sukces oznacza przyjęcie przez usługę wysyłki; potwierdzenie dostarczenia wymaga sprawdzenia skrzynki. Brak zapisu treści wiadomości do bazy WordPressa. Walidacja serwerowa, nonce, honeypot i limit 5 prób/10 minut. Licznik zawiera HMAC adresu IP, bez treści zapytania. Bezpośredni endpoint Workera wymaga osobnego przeglądu zabezpieczeń przed publikacją.

## GitHub Actions

Istniejący workflow deploy.yml nadal buduje Astro i publikuje /www. Nie wolno traktować go jako wdrożenia WordPressa ani uruchamiać w celu publikacji tej wersji. Źródła WordPressa przechowywane są osobno; zmiana systemu wdrożeń i przełączenie domeny wymagają osobnego kroku po akceptacji podglądu.

## Przed przełączeniem produkcji

- Potwierdzić odbiór wiadomości testowej i ustawienia dostawców poczty.
- Zatwierdzić politykę prywatności; aktualna zawiera oznaczenie wersji roboczej. Zapewnić miesięczne usuwanie zwykłej korespondencji w skrzynce, zgodnie z deklaracją administratora.
- Przygotować kopię plików i bazy oraz procedurę powrotu do Astro.
- Ustawić docelowe adresy, przekierowania i obsługę istniejących adresów MSFS beta. Obecny podgląd linkuje do istniejącej bety.
- Włączyć harmonogram WordPressa dla czyszczenia transientów, aktualizacji i zadań utrzymaniowych. W podglądzie chronionym Basic Auth wyłączono samowywołania WP-Cron i automatyczne aktualizacje; nie jest to konfiguracja produkcyjna.
- Usunąć staging-no-system-mail.php z mu-plugins na produkcji (blokuje tylko systemowy wp_mail; formularz korzysta z osobnej integracji).
- Zaplanować niezależne wdrażanie motywu i wtyczki bez nadpisywania wp-config, uploads, bazy i rdzenia.
