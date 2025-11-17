# E2E Test Documentation

## Test Ortamının Hazırlanması

E2E testlerini çalıştırmadan önce uygulamanın çalışıyor olması gerekiyor.

### 1. Docker Containers'ı Başlat

```bash
docker-compose up -d
```

### 2. Veritabanını Hazırla

```bash
# Migration'ları çalıştır
docker-compose exec app php artisan migrate:fresh --force

# Test verilerini yükle (admin@test.com kullanıcısı dahil)
docker-compose exec app php artisan db:seed --force
```

### 3. Laravel Server'ı Başlat

Laravel server'ın çalıştığından emin olun:

```bash
docker-compose exec app php artisan serve --host=0.0.0.0 --port=8000
```

Ya da otomatik setup script'i kullanın:

```bash
./scripts/setup-e2e-tests.sh
```

### 4. Uygulamanın Erişilebilir Olduğunu Doğrula

```bash
curl http://localhost:8000
```

## Test Kullanıcısı

E2E testleri `admin@test.com` kullanıcısı ile çalışır:
- **Email:** admin@test.com
- **Password:** password

Bu kullanıcı `DatabaseSeeder` tarafından otomatik olarak oluşturulur.

## Test Çalıştırma

### Tüm Testleri Çalıştır

```bash
npm run test:e2e
```

### Belirli Bir Test Dosyasını Çalıştır

```bash
npm run test:e2e tests/e2e/auth.spec.js
```

### UI Mode'da Çalıştır

```bash
npm run test:e2e:ui
```

### Debug Mode

```bash
npm run test:e2e:debug
```

## Test Yapısı

### Test Dosyaları

- `auth.spec.js` - Temel authentication testleri
- `auth-comprehensive.spec.js` - Kapsamlı authentication testleri
- `dashboard.spec.js` - Dashboard testleri
- `sites.spec.js` - Sites management testleri
- `alerts.spec.js` - Alerts testleri
- `clients.spec.js` - Clients testleri
- `tasks.spec.js` - Tasks testleri
- `settings.spec.js` - Settings testleri
- `comprehensive.spec.js` - Tüm özelliklerin kapsamlı testleri

### Helper Functions

- `helpers/auth.js` - Login/logout helper'ları
- `helpers/navigation.js` - Navigation helper'ları
- `helpers/selectors.js` - Selector helper'ları
- `helpers/wait.js` - Wait helper'ları

## Sık Karşılaşılan Sorunlar

### 1. Connection Refused Hatası

**Sorun:** `ERR_CONNECTION_REFUSED` hatası alıyorsunuz.

**Çözüm:**
- Docker container'ların çalıştığından emin olun: `docker-compose ps`
- Laravel server'ın çalıştığından emin olun
- Port 8000'in başka bir uygulama tarafından kullanılmadığından emin olun

### 2. Test Kullanıcısı Bulunamıyor

**Sorun:** Login testleri başarısız oluyor.

**Çözüm:**
- Veritabanını seed edin: `docker-compose exec app php artisan db:seed --force`
- `admin@test.com` kullanıcısının var olduğunu kontrol edin

### 3. Selector Bulunamıyor

**Sorun:** Testler element bulamıyor.

**Çözüm:**
- Sayfanın tamamen yüklendiğinden emin olun (networkidle state)
- Vue component'lerin hydrate olduğundan emin olun
- Test helper'ları kullanın (waitForPageReady, waitForFormReady)

### 4. Timeout Hatası

**Sorun:** Testler timeout'a düşüyor.

**Çözüm:**
- Timeout sürelerini artırın (playwright.config.js)
- Uygulamanın yavaş çalıştığını kontrol edin
- Daha fazla wait ekleyin

## Test Best Practices

1. **Her test bağımsız olmalı** - Testler birbirine bağımlı olmamalı
2. **Helper functions kullan** - Tekrar eden kodları helper'lara taşı
3. **Explicit waits kullan** - Hard-coded timeouts yerine explicit waits kullan
4. **Test data kullan** - Gerçek veriler yerine test verileri kullan
5. **Cleanup yap** - Test sonrası veritabanını temizle

## CI/CD İçin

CI/CD pipeline'ında testler çalıştırılırken:

1. Docker container'ları başlat
2. Migration ve seeding yap
3. Laravel server'ı arka planda başlat
4. Testleri çalıştır
5. Sonuçları raporla

