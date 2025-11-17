# E2E Test Sorunları ve Çözümler

## Ana Sorun: Node.js Versiyonu

**Problem:** Playwright ESM modülleri için Node.js 18.19+ gerekiyor, ancak sistemde Node.js 18.18.2 yüklü.

**Hata:**
```
Error: playwright.config.js: Playwright requires Node.js 18.19 or higher to load esm modules.
```

## Çözüm Adımları

### 1. Node.js Versiyonunu Güncelle

**Yöntem A: NVM Kullanarak (Önerilen)**

```bash
# NVM kurulu değilse önce kurun
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash

# Terminal'i yeniden başlatın veya:
source ~/.zshrc  # veya ~/.bash_profile

# Node.js 20'yi kurun
nvm install 20

# Bu projede Node.js 20 kullanın
nvm use 20

# Kalıcı olarak ayarlayın (opsiyonel)
nvm alias default 20
```

**Yöntem B: Homebrew ile Güncelle**

```bash
# Homebrew ile Node.js 20 kurun
brew install node@20

# PATH'i güncelleyin
echo 'export PATH="/opt/homebrew/opt/node@20/bin:$PATH"' >> ~/.zshrc
source ~/.zshrc
```

**Yöntem C: Node.js Resmi Site'den**

1. https://nodejs.org adresine gidin
2. LTS versiyonunu (Node.js 20.x) indirin
3. Installer'ı çalıştırın

### 2. Versiyonu Kontrol Edin

```bash
node --version  # v20.x.x görmeli
```

### 3. Testleri Çalıştırın

```bash
npm run test:e2e
```

## Alternatif Geçici Çözüm (Önerilmez)

Eğer Node.js güncelleyemiyorsanız, `playwright.config.js`'yi CommonJS formatına çevirebilirsiniz, ancak bu proje ESM kullandığı için önerilmez.

## Notlar

- `.nvmrc` dosyası eklendi - proje klasöründe `nvm use` komutu otomatik olarak doğru versiyonu kullanır
- `package.json`'a `engines` field eklendi - Node.js 18.19+ gereksinimini belirtir
- CI/CD zaten Node.js 20 kullanıyor (`.github/workflows/ci.yml`)

## Test Sonrası

Node.js güncelledikten sonra:

1. `npm install` çalıştırın (gerekirse)
2. `docker-compose up -d` ile container'ları başlatın
3. `docker-compose exec app php artisan migrate:fresh --seed` ile veritabanını hazırlayın
4. `npm run test:e2e` ile testleri çalıştırın

