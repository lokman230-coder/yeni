# cPanel public_html kurulum rehberi

## 1. Sunucu gereksinimleri

- PHP 8.2 veya üzeri
- MySQL 8 / MariaDB 10.6+
- PHP extensions: pdo_mysql, mbstring, curl, openssl, fileinfo, json, zip, dom, xml
- SSL aktif
- Composer yoksa repository içindeki `vendor/` klasörü korunmalı

## 2. Dosya yükleme

Repository içindeki tüm dosyaları doğrudan `public_html` içine yükleyin. Sonuç şu şekilde olmalı:

```text
public_html/index.php
public_html/install.php
public_html/.htaccess
public_html/app/
public_html/assets/
public_html/config/
public_html/database/
public_html/routes/
public_html/storage/
public_html/vendor/
```

`public_html/ahost-one/` gibi ekstra bir alt klasör oluşmamalıdır.

## 3. Veritabanı

cPanel > MySQL Databases bölümünden:

1. Veritabanı oluşturun.
2. Veritabanı kullanıcısı oluşturun.
3. Kullanıcıyı veritabanına tüm yetkilerle ekleyin.
4. DB adını, kullanıcı adını ve şifreyi not edin.

## 4. Kurulum

Tarayıcıdan açın:

```text
https://alanadiniz.com/install.php
```

Kurulum sihirbazı `.env` dosyası istemez; veritabanı bilgilerini doğrudan korumalı `config/installation.php` dosyasına kaydeder. Kurulum sihirbazında yalnızca şu bilgileri girin:

- Veritabanı hostu: `localhost`
- Veritabanı portu: `3306`
- Veritabanı adı
- Veritabanı kullanıcı adı
- Veritabanı şifresi
- İlk admin adı
- İlk admin e-posta adresi
- İlk admin şifresi

Site adı ve diğer ayarlar varsayılan değerlerle otomatik oluşturulur; kurulumdan sonra admin panelindeki Ayarlar bölümünden değiştirilebilir.

Kurulumdan sonra:

```text
https://alanadiniz.com/admin
```

adresinden giriş yapın ve varsayılan bilgileri değiştirin.

## 5. Cron

cPanel > Cron Jobs bölümüne ekleyin:

```bash
* * * * * /usr/local/bin/php -q /home/CPANEL_USER/public_html/console cron:run >/dev/null 2>&1
```

`CPANEL_USER` bölümünü gerçek cPanel kullanıcı adınızla değiştirin.

## 6. Kurulum sonrası güvenlik

- `install.php` erişimini kapatın veya dosyayı yeniden adlandırın.
- `.env` dosyasının webden okunamadığını test edin.
- `/config`, `/database`, `/storage`, `/vendor` adreslerini tarayıcıda açmayı deneyin; 403 dönmelidir.
- Admin 2FA’yı etkinleştirin.
- Güçlü admin şifresi belirleyin.
- PayTR ve registrar anahtarlarını GitHub’a koymayın.
- Gerçek API bilgilerini yalnızca sunucu ortamındaki `.env`/ayar sistemine girin.
