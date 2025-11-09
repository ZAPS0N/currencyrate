# Currency Rate - Moduł PrestaShop

Moduł do wyświetlania aktualnych i historycznych kursów walut z API Narodowego Banku Polskiego (NBP) wraz z automatycznym przeliczaniem cen produktów.

## Opis

Moduł integruje się z publicznym API NBP, pobiera kursy walut i wyświetla je na stronach produktów. Dodatkowo umożliwia przeglądanie historycznych kursów walutowych oraz automatyczne aktualizowanie danych za pomocą crona.

## Użyte technologie

### Backend
- **PHP 8** - język programowania z wykorzystaniem strict types
- **PrestaShop 8.0+** - platforma e-commerce
- **Composer** - zarządzanie zależnościami i autoloading (PSR-4)
- **MySQL/MariaDB** - baza danych do przechowywania historycznych kursów

### Wzorce i architektura
- **Dependency Injection** - wstrzykiwanie zależności do serwisów
- **Service Layer Pattern** - logika biznesowa w dedykowanych serwisach
- **Repository Pattern** - abstrakcja dostępu do danych
- **Factory Pattern** - tworzenie klientów HTTP (HttpClientFactory)
- **Strategy Pattern** - różne implementacje HTTP Client (Symfony/cURL)

### Zewnętrzne API
- **NBP Web API** - https://api.nbp.pl/api/exchangerates
  - Tabele kursów: A (podstawowe waluty), B (waluty egzotyczne), C (kursy kupna/sprzedaży)
  - Format: JSON
  - Timeout: 10s, retry: 3 próby z exponential backoff

### Frontend
- **Smarty** - silnik szablonów PrestaShop
- **JavaScript (Vanilla)** - interakcje po stronie klienta
- **CSS3** - stylowanie komponentów

## Instalacja

### 1. Instalacja przez panel administracyjny

1. Przejdź do: **Moduły -> Menedżer modułów**
2. Kliknij **Załaduj moduł**
3. Wybierz archiwum ZIP z modułem

### 2. Konfiguracja po instalacji

Po zainstalowaniu moduł automatycznie:
- Tworzy tabelę `ps_currency_rate` w bazie danych
- Rejestruje hooki: `displayHeader`, `displayBackOfficeHeader`, `displayProductAdditionalInfo`, `moduleRoutes`
- Ustawia domyślną konfigurację (tabela A, cache 3600s, 20 elementów/stronę)
- Generuje bezpieczny token dla crona

## Działanie modułu

### Kluczowe komponenty

#### 1. **NbpApiService** [src/Service/NbpApiService.php](src/Service/NbpApiService.php)
- Komunikacja z API NBP
- Pobieranie tabel kursów (A/B/C)
- Obsługa retry z exponential backoff (3 próby)
- Cache na poziomie serwisu

#### 2. **CacheService** [src/Service/CacheService.php](src/Service/CacheService.php)
- Cachowanie odpowiedzi API
- Konfigurowalne TTL (domyślnie 3600s)
- Klucze cache: `nbp_table_A_last_30`, `nbp_currency_USD_A_last_30`

#### 3. **RateProcessor** [src/Service/RateProcessor.php](src/Service/RateProcessor.php)
- Przetwarzanie danych z API
- Zapisywanie kursów do bazy
- Obsługa duplikatów (UNIQUE KEY na currency_code, table_type, effective_date)

#### 4. **ProductPriceConverter** [src/Service/ProductPriceConverter.php](src/Service/ProductPriceConverter.php)
- Przeliczanie cen produktów na różne waluty
- Wyświetlanie kursów na stronach produktów

#### 5. **CronService** [src/Service/CronService.php](src/Service/CronService.php)
- Automatyczne aktualizacje kursów
- Czyszczenie starych danych (DataCleanupService)
- Endpoint: `/currency-rates/cron?token=SECURE_TOKEN`

### Struktura bazy danych

Tabela `ps_currency_rate`:
```sql
- id_currency_rate (PK, AUTO_INCREMENT)
- currency_code (VARCHAR(3)) - kod waluty (np. EUR, USD)
- currency_name (VARCHAR(100)) - nazwa waluty
- table_type (CHAR(1)) - typ tabeli NBP (A/B/C)
- rate_mid (DECIMAL(10,4)) - kurs średni
- rate_bid (DECIMAL(10,4)) - kurs kupna (tabela C)
- rate_ask (DECIMAL(10,4)) - kurs sprzedaży (tabela C)
- effective_date (DATE) - data obowiązywania
- table_number (VARCHAR(20)) - numer tabeli NBP
- date_add, date_upd (DATETIME)

UNIQUE KEY: (currency_code, table_type, effective_date)
INDEX: idx_currency_date, idx_effective_date, idx_table_type
```

### Hooki PrestaShop

1. **displayProductAdditionalInfo** - wyświetla kursy walut na stronie produktu
2. **displayHeader** - dodaje CSS/JS do frontendu
3. **displayBackOfficeHeader** - dodaje CSS/JS do panelu admin
4. **moduleRoutes** - definiuje własne routy dla frontcontrollerów (history, cron)

## Konfiguracja

### Dostępne opcje

| Opcja | Opis | Domyślna wartość |
|-------|------|------------------|
| **Waluty** | Wybór walut do śledzenia | - |
| **Typ tabeli NBP** | A (podstawowe), B (egzotyczne), C (kupno/sprzedaż) | A |
| **Cache TTL** | Czas życia cache w sekundach | 3600 |
| **Elementy/stronę** | Liczba wpisów na controllerze history per strona | 20 |
| **Auto-czyszczenie** | Automatyczne usuwanie starych danych | Wyłączone |
| **Token Cron** | Bezpieczny token do automatyzacji | Auto-generowany |

### Cron (automatyczne aktualizacje)

```bash
# Dodaj do crontab (aktualizacja codziennie o 14:15)
15 14 * * * curl -s "https://twoja-domena.pl/currency-rates/cron?token=TWOJ_TOKEN"
```

Token znajdziesz w panelu konfiguracji modułu.

## Możliwe optymalizacje

### 1. **Cache i wydajność**

#### Problem: Zbyt częste zapytania do API NBP
**Rozwiązanie:**
- Zwiększenie TTL cache do 86400s (24h) - kursy NBP aktualizują się raz dziennie
- Implementacja Redis/Memcached zamiast cache'a na dysku

### 2. **Integracja z innymi źródłami kursów**

#### Alternatywne API:
- **ECB API** (Europejski Bank Centralny) - kursy EUR
- **Fixer.io / exchangerate-api.com** - kursy globalne (wymaga klucza API)
- **CurrencyLayer** - obsługa 168 walut

### 3. **Rozszerzona funkcjonalność**

#### a) Powiadomienia o zmianach kursów
- Email alerts gdy kurs przekroczy próg

#### b) Widget z wykresami
- Integracja z Chart.js lub ApexCharts
- Wykresy liniowe historii kursów (7/30/90 dni)
- Porównanie wielu walut

#### c) Integracja z wieloma API
- Dodanie możliwosći integracji modułu z wieloma API na raz,
- Możliwość wyboru API z którego chce się korzystać

### 4. **Performance i skalowanie**

#### Problem: Duża ilość historycznych danych
**Rozwiązania:**
- Archiwizacja starszych danych (np. >365 dni) do osobnej tabeli
- Agregacja danych (średnie tygodniowe/miesięczne)
- Implementacja DataCleanupService z polityką retencji

#### Problem: Wysokie obciążenie podczas aktualizacji
**Rozwiązania:**
- Asynchroniczne przetwarzanie (RabbitMQ, Symfony Messenger)
- Batch processing - aktualizacja w paczkach po 10 walut
- Rate limiting dla API NBP

## API i Endpointy

### Frontend
- `/currency-rates/history` - historia kursów walutowych
- `/currency-rates/cron?token=XXX` - endpoint crona (zabezpieczony)

## Struktura plików

```
currencyrate/
├── classes/
│   ├── CurrencyRateModel.php         # Model danych
│   └── CurrencyRateRepository.php    # Warstwa dostępu do danych
├── controllers/front/
│   ├── cron.php                      # Kontroler crona
│   └── history.php                   # Kontroler historii
├── src/
│   ├── Config/                       # Konfiguracja modułu
│   ├── Form/                         # Formularze konfiguracyjne
│   ├── Hook/                         # Handlery hooków
│   ├── Install/                      # Instalator modułu
│   └── Service/                      # Serwisy biznesowe
├── views/
│   ├── css/                          # Style CSS
│   ├── js/                           # JavaScript
│   └── templates/                    # Szablony Smarty
├── composer.json                     # Zależności Composer
├── currencyrate.php                  # Główny plik modułu
└── README.md                         # Dokumentacja
```

## Licencja

Academic Free License 3.0 (AFL-3.0)

## Autor

Bartosz Żabicki

## Debugowanie

W przypadku problemów sprawdź:
1. Logi PrestaShop: `/var/logs/`
2. Logi PHP: sprawdź `error_log` serwera
3. Dostępność API NBP: https://api.nbp.pl/

---

Wersja: 1.0.0 | PrestaShop 8.0+
