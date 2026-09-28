# Przygotowanie OVH — Simulat3D

Przygotowano prywatny katalog `/home/simulat/simulat3d-private`, poza publicznym `/home/simulat/www`.

- Katalogi prywatne: uprawnienia 700.
- Konfiguracja i paczka ZIP: uprawnienia 600.
- Hasło przechowywane na serwerze jako hash PHP, nie w postaci jawnej.
- Paczka: `SKALARKIe_MSFSInterface_v2.24.zip`.
- SHA-256 oryginału i ponownie pobranej prywatnej kopii: `32c86d285223749cc7c9a42c9f28516ff4904d48ee0cebe64408e17514878e6e`.
- Odczytana `.ovhconfig` wskazuje PHP 8.5. Nie wykonano jeszcze testu nowego endpointu przez HTTPS na OVH.
- Rekordy MX domeny wskazują OVH: mx1.mail.ovh.net, mx2.mail.ovh.net, mx3.mail.ovh.net. Dalsze przekierowania skrzynki nie były sprawdzane.

## Co pozostaje

1. W panelu OVH ustawić codzienne uruchamianie skryptu `simulat3d-private/cleanup.php` jako zadania PHP (8.2+). Plik jest już na serwerze. Nie udało się wykonać polecenia przez SSH; SFTP działa. Zadanie harmonogramu nie zostało utworzone.
2. Potwierdzić retencję logów i zasady transferów danych dla używanych kont OVH, Cloudflare i Resend; wdrożyć miesięczne usuwanie zakończonej korespondencji w skrzynce.
3. Przetestować nowy endpoint w środowisku testowym OVH: sesję, HTTPS, dostęp do prywatnego ZIP-a i blokadę starych bezpośrednich adresów.
4. Opublikować komplet przez GitHub Actions dopiero po spełnieniu kontroli gotowości i zgodzie na publikację. Obecny publiczny katalog downloads pozostaje niezmieniony — prywatna kopia sama nie zabezpiecza starego URL.

Nie zmieniono publicznej strony, istniejących pobrań ani sekretów GitHub. Nowe hasło przygotowano w oddzielnym pliku Dostep-beta.txt, poza repozytorium. Zacznie działać po wdrożeniu nowego mechanizmu.
