# Price Monitoring System

Sistema di monitoraggio prezzi per competitor con supporto proxy configurabile.

## 🚀 Features

- **Web Scraping Multi-Competitor**: Supporto per Marionnaud, Makeup.it, Sephora, e altri
- **Sistema Proxy Configurabile**: GeoNode, Proxifly, FreeProxy24, o proxy manuali
- **Validazione Automatica**: Validazione e rotazione automatica dei proxy
- **Cache Intelligente**: Cache dei proxy con refresh automatico ogni ora
- **Retry Logic**: Fino a 8 tentativi con proxy diversi per massima affidabilità
- **Configurazione Flessibile**: Abilita/disabilita proxy via `.env`

## 📋 Requisiti

- PHP 8.1+
- Laravel 10.x
- Composer
- MySQL/MariaDB

## 🔧 Installazione

```bash
# Clone repository
git clone <repository-url>
cd price-monitoring-system

# Install dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# (Optional) Configure proxy system
cp .env.proxy.example .env
```

## ⚙️ Configurazione Proxy

### Quick Start

#### Disabilita Proxy (Richieste Dirette)
```env
PROXY_ENABLED=false
```

#### Abilita Proxy Gratuiti
```env
PROXY_ENABLED=true
PROXY_PROVIDER_GEONODE_ENABLED=true
PROXY_PROVIDER_PROXIFLY_ENABLED=true
```

#### Usa Solo Proxy Premium
```env
PROXY_ENABLED=true
PROXY_PROVIDER_GEONODE_ENABLED=false
PROXY_PROVIDER_PROXIFLY_ENABLED=false
PROXY_PROVIDER_MANUAL_ENABLED=true
PROXY_LIST="premium1.example.com:8080,premium2.example.com:8080"
```

### Configurazione Avanzata

Tutte le opzioni disponibili in `config/proxy.php`:

```env
# Enable/Disable
PROXY_ENABLED=true

# Providers
PROXY_PROVIDER_GEONODE_ENABLED=true      # ~50 proxy HTTP
PROXY_PROVIDER_PROXIFLY_ENABLED=true     # ~1600 proxy HTTP
PROXY_PROVIDER_FREEPROXY24_ENABLED=false # ~7960 proxy (lento)
PROXY_PROVIDER_MANUAL_ENABLED=false      # Proxy premium

# Settings
PROXY_TIMEOUT=30              # Timeout richiesta (secondi)
PROXY_MAX_RETRIES=8           # Tentativi con proxy diversi
PROXY_VALIDATION_LIMIT=100    # Max proxy da validare
PROXY_CACHE_TTL=3600          # Cache TTL (1 ora)
```

## 📚 Comandi Disponibili

### Aggiorna Proxy
```bash
php artisan cyper:proxies:update
```

### Test Crawler
```bash
php artisan cyper:test-crawler <competitor-id> <product-url>
```

### Pulisci Cache
```bash
php artisan cache:clear
```

## 🏗️ Architettura Proxy

### Provider Disponibili

| Provider | Proxy | Protocollo | Update | Validazione |
|----------|-------|------------|--------|-------------|
| **GeoNode** | ~50 | HTTP/HTTPS | API | ✅ curl_multi |
| **Proxifly** | ~1600 | HTTP | 5 min | ✅ curl_multi |
| **FreeProxy24** | ~7960 | Mixed | Real-time | ✅ curl_multi |
| **Manual** | Custom | HTTP | - | ❌ Trusted |

### Interfaccia Generica

```php
interface ProxyProviderInterface
{
    public function getProxies(): array;
    public function updateProxies(): array;
    public function removeProxy(string $proxyUrl): void;
}
```

### Estendere con Nuovo Provider

```php
class MyProxyService implements ProxyProviderInterface
{
    public function getProxies(): array
    {
        // Implementa logica di recupero
    }
    
    // ...
}
```

Aggiungi in `config/proxy.php`:
```php
'providers' => [
    'myprovider' => [
        'enabled' => env('PROXY_PROVIDER_MYPROVIDER_ENABLED', false),
        'url' => 'https://api.myprovider.com/proxies',
    ],
],
```

## ⚠️ Limitazioni Proxy Gratuiti

**Testato con 4 fonti diverse**:
- GeoNode: 48 proxy → 0% success HTTPS tunneling
- Proxifly: 89 proxy → 0% success HTTPS tunneling
- Proxifly HTTPS: 651 proxy (100% transparent)
- FreeProxy24: 365 proxy → 0% success HTTPS tunneling

**Conclusione**: I proxy HTTP gratuiti **non supportano HTTPS tunneling** (metodo CONNECT) in modo affidabile.

**Raccomandazioni**:
- **Sviluppo**: `PROXY_ENABLED=false` (richieste dirette, più veloce)
- **Produzione**: Proxy premium ($50-75/mese) per siti che bloccano IP

### Servizi Proxy Premium Raccomandati

| Servizio | Costo/mese | Success Rate | HTTPS Support |
|----------|-----------|--------------|---------------|
| **Webshare** | $50 | 95% | ✅ |
| **SmartProxy** | $75 | 98% | ✅ |
| **Oxylabs** | $300 | 99% | ✅ |
| **BrightData** | $500 | 99.9% | ✅ |

## 📊 Monitoraggio

### Logs
```bash
tail -f storage/logs/laravel.log | grep -i proxy
```

Output esempio:
```
[2026-02-12] Proxy system is enabled
[2026-02-12] Loaded 48 proxies from GeoNode
[2026-02-12] Loaded 89 proxies from Proxifly
[2026-02-12] ProxyPool loaded 137 proxies
```

### Statistiche
```bash
php artisan tinker
>>> app(\App\Services\ProxyPool::class)->getTotalCount()
=> 137
```

## 🧪 Testing

```bash
# Test proxy system
php artisan test --filter ProxyTest

# Test crawler
php artisan cyper:test-crawler 1 https://example.com/product/123
```

## 🔒 Sicurezza

- ✅ SSL verification configurabile
- ✅ User-Agent personalizzabile
- ✅ Timeout configurabili
- ✅ Validazione proxy automatica
- ✅ Rotazione automatica proxy falliti

## 📖 Documentazione

- [Proxy Configuration Guide](docs/proxy-configuration.md)
- [Crawler Implementation](docs/crawler.md)
- [API Reference](docs/api.md)

## 🤝 Contributing

1. Fork il repository
2. Crea feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add AmazingFeature'`)
4. Push al branch (`git push origin feature/AmazingFeature`)
5. Apri Pull Request

## 📝 License

Questo progetto è proprietario.

## 👥 Authors

- **Cyper Team**

## 🙏 Acknowledgments

- GeoNode per API proxy gratuita
- Proxifly per lista proxy aggiornata
- Laravel community
