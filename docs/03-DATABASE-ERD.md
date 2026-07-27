# 03 · Veritabanı Şeması (ERD)

MySQL 8.0+ / MariaDB 10.6+. Motor: **InnoDB**, Charset: **utf8mb4_unicode_ci**.

Toplam **48 tablo** (şartname madde 36'daki 42 + rakip üstünlüğü için eklenenler).

---

## 1. Tablo Kategorileri

```
┌─── AUTH & USERS ────────┐  ┌─── PRODUCTS & PRICING ──┐  ┌─── ORDERS & INVOICES ────┐
│  admins                 │  │  product_groups         │  │  cart_items              │
│  admin_roles            │  │  products               │  │  orders                  │
│  admin_permissions      │  │  product_prices         │  │  order_items             │
│  admin_role_permissions │  │  product_addons         │  │  invoices                │
│  customers              │  │  product_custom_fields  │  │  invoice_items           │
│  customer_sessions      │  │  product_upsells        │  │  payments                │
│  customer_addresses     │  │                         │  │  coupons                 │
│  two_factor_secrets     │  │                         │  │  coupon_usages           │
└─────────────────────────┘  └─────────────────────────┘  └──────────────────────────┘

┌─── DOMAINS & REGISTRAR ┐  ┌─── HOSTING & SERVERS ───┐  ┌─── SUPPORT & KB ─────────┐
│  domains                │  │  hosting_servers        │  │  tickets                 │
│  domain_registrars      │  │  hosting_accounts       │  │  ticket_replies          │
│  registrar_configs      │  │  server_groups          │  │  ticket_departments      │
│  domain_dns_records     │  │                         │  │  knowledge_categories    │
│  domain_pricing         │  │                         │  │  knowledge_articles      │
└─────────────────────────┘  └─────────────────────────┘  └──────────────────────────┘

┌─── BUILDERS ────────────┐  ┌─── MARKETPLACE ─────────┐  ┌─── AI & ANALYTICS ───────┐
│  builder_projects       │  │  marketplace_listings   │  │  ai_settings             │
│  builder_pages          │  │  marketplace_offers     │  │  ai_logs                 │
│  builder_templates      │  │  marketplace_categories │  │  ai_actions              │
│  builder_assets         │  │  seller_profiles        │  │  cookie_analytics_events │
│  mobile_apps            │  │                         │  │                          │
│  mobile_app_pages       │  │                         │  │                          │
│  mobile_app_builds      │  │                         │  │                          │
└─────────────────────────┘  └─────────────────────────┘  └──────────────────────────┘

┌─── SETTINGS & META ─────┐  ┌─── LOGS & OPS ──────────┐
│  settings               │  │  audit_logs             │
│  currency_rates         │  │  api_logs               │
│  tax_rules              │  │  cron_logs              │
│  email_templates        │  │  btk_exports            │
│  notifications          │  │                         │
│  cms_pages              │  │                         │
│  blog_posts             │  │                         │
│  announcements          │  │                         │
└─────────────────────────┘  └─────────────────────────┘
```

---

## 2. Tablo Şemaları (özet — DDL Faz 1'de tam üretilecek)

### 2.1 AUTH & USERS

**admins**
```
id BIGINT PK
username VARCHAR(64) UNIQUE
email VARCHAR(191) UNIQUE
password_hash VARCHAR(255)
full_name VARCHAR(191)
avatar VARCHAR(255) NULL
role_id BIGINT FK admin_roles.id
is_active TINYINT(1) DEFAULT 1
last_login_at DATETIME NULL
last_login_ip VARCHAR(45) NULL
two_factor_enabled TINYINT(1) DEFAULT 0
created_at, updated_at
INDEX (email), INDEX (role_id)
```

**admin_roles**
```
id, name, slug UNIQUE, description, is_system TINYINT, created_at, updated_at
Seed: super_admin, admin, support, accounting
```

**admin_permissions**
```
id, key VARCHAR(120) UNIQUE ("customers.view", "products.edit"), label, group VARCHAR(64)
Seed: 80+ izin (her admin controller action için)
```

**admin_role_permissions** — many-to-many `role_id, permission_id`

**customers**
```
id BIGINT PK
email VARCHAR(191) UNIQUE
password_hash VARCHAR(255)
first_name, last_name, phone, company
tax_id VARCHAR(32), tax_office VARCHAR(120)
is_individual TINYINT(1) DEFAULT 1
country VARCHAR(2), city, address TEXT, postcode
preferred_language VARCHAR(5) DEFAULT 'tr'
preferred_currency CHAR(3) DEFAULT 'TRY'
balance DECIMAL(14,4) DEFAULT 0        -- müşteri kredisi (şartname 15)
status ENUM('active','suspended','pending','closed')
email_verified_at DATETIME NULL
two_factor_enabled TINYINT DEFAULT 0
last_login_at, last_login_ip
created_at, updated_at, deleted_at
INDEX (status), INDEX (country)
```

**customer_sessions** — device fingerprint, IP, ua, last_activity
**customer_addresses** — çoklu fatura/teslim adresi
**two_factor_secrets** — TOTP secret, backup codes (şifreli)

---

### 2.2 PRODUCTS & PRICING (şartname 9-13)

**product_groups**
```
id, name, slug UNIQUE, description, icon, sort_order, is_active, seo_title, seo_description
```

**products**
```
id BIGINT PK
group_id FK
type ENUM('hosting','vps','dedicated','domain','ssl','email_hosting',
         'radio_hosting','site_builder','mobile_builder','web_design',
         'mobile_app','digital_service','license','marketplace','custom')
name, slug UNIQUE, short_description, description LONGTEXT
image VARCHAR(255) NULL
status ENUM('active','hidden','disabled')
stock_type ENUM('unlimited','limited'), stock_qty INT NULL
payment_type ENUM('free','onetime','recurring')
setup_fee DECIMAL(14,4) DEFAULT 0
setup_fee_currency CHAR(3) DEFAULT 'TRY'
tax_rule_id FK tax_rules.id NULL
automation_module VARCHAR(64) NULL     -- cpanel/da/plesk/manual
server_group_id FK server_groups.id NULL
free_domain_rules JSON NULL            -- {periods:[12,24], tlds:['com','net']}
seo_title, seo_description
sort_order INT DEFAULT 0
created_at, updated_at, deleted_at
INDEX (type, status), INDEX (group_id)
```

**product_prices** (şartname 10 — birden fazla periyot)
```
id, product_id FK
period ENUM('onetime','monthly','quarterly','semiannually','annually','biennially','triennially')
source_currency CHAR(3)                -- kaynak fiyat para birimi
source_price DECIMAL(14,4)             -- kaynak fiyat
is_active TINYINT(1)                   -- boş/silinmiş → pasif
sort_order
UNIQUE (product_id, period, source_currency)
```

**product_addons** (şartname 12)
```
id, product_id FK NULL   -- NULL = genel, tekrar kullanılabilir
name, slug, description, price, currency, period, addon_type, automation_code, is_active
```

**product_custom_fields** (şartname 13)
```
id, product_id FK
label, field_key, field_type ENUM('text','textarea','number','ip','url','email','phone',
                                  'select','radio','checkbox','file','image','password')
options JSON NULL        -- select/radio için
is_required TINYINT, is_active TINYINT
show_on ENUM('order','profile','admin_only')
validation_rules JSON, sort_order
```

**product_upsells** — çapraz satış: `product_id, related_product_id, discount_percent`

---

### 2.3 ORDERS & INVOICES

**cart_items**
```
id, session_id VARCHAR(128) NULL, customer_id FK NULL
product_id FK, period, quantity DEFAULT 1
domain_action ENUM('register','transfer','use_own','update_dns') NULL
domain_name VARCHAR(255) NULL
addons JSON, custom_fields JSON
unit_price DECIMAL(14,4), currency CHAR(3)
created_at, updated_at
INDEX (session_id), INDEX (customer_id)
```

**orders**
```
id, order_number VARCHAR(32) UNIQUE
customer_id FK
status ENUM('pending','paid','processing','active','failed','cancelled','refunded')
subtotal, discount_total, tax_total, total DECIMAL(14,4)
currency CHAR(3)
coupon_id FK NULL
payment_method VARCHAR(32)
ip_address VARCHAR(45), user_agent VARCHAR(255)
notes TEXT
created_at, paid_at, activated_at, updated_at
INDEX (customer_id, status)
```

**order_items** — order_id, product_id, period, addons JSON, custom_fields JSON, prices...

**invoices**
```
id, invoice_number VARCHAR(32) UNIQUE
order_id FK NULL       -- manuel fatura için NULL olabilir
customer_id FK
status ENUM('draft','unpaid','paid','partially_paid','overdue','cancelled','refunded')
issue_date DATE, due_date DATE, paid_at DATETIME NULL
subtotal, discount_total, tax_total, total, paid_total, balance DECIMAL(14,4)
currency CHAR(3)
notes TEXT, terms TEXT
pdf_path VARCHAR(255) NULL
created_at, updated_at
INDEX (customer_id, status), INDEX (due_date)
```

**invoice_items** — invoice_id, description, quantity, unit_price, tax_rate, line_total

**payments**
```
id, invoice_id FK NULL, customer_id FK
method VARCHAR(32)
amount DECIMAL(14,4), currency CHAR(3)
gateway_transaction_id VARCHAR(191) NULL
status ENUM('pending','success','failed','refunded')
gateway_response JSON
processed_at DATETIME
created_at
INDEX (invoice_id), INDEX (customer_id)
```

**coupons**
```
id, code VARCHAR(64) UNIQUE, name
type ENUM('percent','fixed'), value DECIMAL(10,4)
currency CHAR(3) NULL   -- fixed için
starts_at, ends_at DATETIME NULL
usage_limit INT NULL, usage_limit_per_customer INT NULL
min_order_total DECIMAL(14,4) NULL
applicable_products JSON NULL, applicable_groups JSON NULL
applicable_customers JSON NULL
is_active TINYINT, created_at
```

**coupon_usages** — coupon_id, customer_id, order_id, used_at

---

### 2.4 DOMAINS & REGISTRAR (şartname 16-17)

**domains**
```
id, customer_id FK
domain_name VARCHAR(255) UNIQUE
registrar_id FK domain_registrars.id NULL
registration_date, expiry_date DATE
next_due_date DATE, next_invoice_date DATE
status ENUM('active','pending','pending_transfer','expired','cancelled','suspended')
auto_renew TINYINT(1) DEFAULT 1
transfer_lock TINYINT(1) DEFAULT 1
whois_privacy TINYINT(1) DEFAULT 0
nameservers JSON     -- [ns1, ns2, ns3, ns4]
epp_code VARCHAR(191) NULL
period_years INT DEFAULT 1
recurring_amount DECIMAL(14,4), currency CHAR(3)
created_at, updated_at
INDEX (customer_id), INDEX (expiry_date), INDEX (status)
```

**domain_registrars**
```
id, name, slug UNIQUE   -- domainnameapi, manual
class VARCHAR(191)      -- App\Modules\Registrar\Drivers\DomainNameApiDriver
is_active, is_default, sort_order
```

**registrar_configs** — registrar_id, key, value ENCRYPTED (API key vs.)

**domain_dns_records** — cache; canlı registrar'dan çekilir ama cache tutulur

**domain_pricing** — TLD, registrar_id, period_years, register/transfer/renew fiyatları

---

### 2.5 HOSTING & SERVERS (şartname 18)

**hosting_servers**
```
id, name, hostname, ip, panel ENUM('cpanel','da','plesk','manual')
username, password_encrypted, api_key_encrypted, port
use_ssl, is_active
max_accounts INT NULL, current_accounts INT DEFAULT 0
server_group_id FK NULL
created_at, updated_at
```

**hosting_accounts**
```
id, order_item_id FK, customer_id FK, product_id FK
server_id FK
domain VARCHAR(255), username, password_encrypted
package VARCHAR(120)
status ENUM('active','suspended','terminated','pending')
disk_usage_mb INT NULL, bandwidth_usage_mb INT NULL, usage_updated_at DATETIME NULL
next_due_date DATE
notes TEXT
created_at, suspended_at, terminated_at
INDEX (customer_id, status), INDEX (server_id)
```

**server_groups** — id, name, fill_type ENUM('fill_first','round_robin','least_used')

---

### 2.6 SUPPORT & KB (şartname 27-28)

**ticket_departments** — id, name, email, is_active, auto_assign_admin_id
**tickets** — id, ticket_number UNIQUE, department_id, customer_id, admin_id NULL, subject, priority ENUM('low','medium','high','urgent'), status ENUM('open','answered','customer_reply','closed','on_hold'), related_service_type, related_service_id, created_at, last_reply_at, closed_at
**ticket_replies** — ticket_id, author_type ENUM('customer','admin','system'), author_id, message, attachments JSON, is_internal TINYINT, created_at
**knowledge_categories** — id, parent_id NULL, name, slug, description, icon, sort_order, is_active
**knowledge_articles** — id, category_id, title, slug, content LONGTEXT, view_count, helpful_count, unhelpful_count, related_product_id NULL, seo, published_at

---

### 2.7 BUILDERS (şartname 23-25)

**builder_projects**
```
id, customer_id FK
name, slug
template_id FK builder_templates.id NULL
type ENUM('site','landing','ecommerce')
sector ENUM('hosting','agency','landing','radio','ecommerce','restaurant','clinic',
           'education','portfolio','saas','local')
package_id FK products.id NULL   -- hangi builder paketiyle alındı
settings JSON     -- {logo, colors, fonts, seo, ...}
status ENUM('draft','published','exported')
published_url VARCHAR(255) NULL
last_export_at DATETIME NULL
created_at, updated_at
```

**builder_pages**
```
id, project_id FK
name, slug, is_homepage TINYINT
tree LONGTEXT  -- JSON: {blocks:[...]}
seo_title, seo_description, seo_image
sort_order, is_published
created_at, updated_at
```

**builder_templates** — id, name, sector, preview_image, tree_json LONGTEXT, is_pro, is_active
**builder_assets** — project_id, kind ENUM('image','video','font','icon','file'), path, size, uploaded_at

**mobile_apps**
```
id, customer_id, name, package_id FK products.id
sector ENUM('radio','corporate','restaurant','ecommerce','news','education','gym','booking')
settings JSON  -- {app_name, package_name, logo, splash, primary_color, ...}
status ENUM('draft','built','published')
created_at, updated_at
```

**mobile_app_pages** — app_id, name, tree_json, sort_order
**mobile_app_builds** — app_id, type ENUM('apk','aab','source'), file_path, size, build_log, status, created_at

---

### 2.8 MARKETPLACE (şartname 26)

**marketplace_categories** — id, parent_id NULL, name, slug, icon, sort_order, is_active
**marketplace_listings** — id, seller_id FK customers.id, category_id, title, slug, description, price, currency, images JSON, attributes JSON, status ENUM('draft','pending','active','sold','rejected'), commission_rate DECIMAL(5,2), sold_at, created_at
**marketplace_offers** — listing_id, buyer_id, amount, message, status ENUM('pending','accepted','rejected','cancelled')
**seller_profiles** — customer_id UNIQUE, display_name, bio, avatar, rating, total_sales, verified

---

### 2.9 AI & ANALYTICS (şartname 8, 22)

**ai_settings** — key, value (provider, api_key ENCRYPTED, model, temperature vs.)
**ai_logs** — id, context ENUM('public','customer','admin'), user_type, user_id NULL, prompt, response, action_taken NULL, tokens_used, latency_ms, created_at
**ai_actions** — id, key UNIQUE, label, context, handler_class, required_permission, is_active
**cookie_analytics_events** — id, session_hash, event_type ENUM('pageview','click','cart_add','cart_abandon','builder_use','tool_use'), event_data JSON, url, referrer, user_agent_hash, ip_hash, created_at, INDEX (event_type, created_at)

---

### 2.10 SETTINGS & META

**settings** — key VARCHAR(120) UNIQUE, value TEXT, type ENUM('string','int','bool','json','encrypted'), group, is_public
**currency_rates** — currency CHAR(3), rate DECIMAL(14,6), margin_percent DECIMAL(6,3), source ENUM('manual','api'), updated_at
**tax_rules** — id, name, rate DECIMAL(6,3), country VARCHAR(2) NULL, apply_type ENUM('inclusive','exclusive'), is_active
**email_templates** — id, key UNIQUE, subject, body_html, body_text, variables JSON, is_active
**notifications** — id, user_type, user_id, channel, title, body, url, is_read, created_at
**cms_pages** — id, slug UNIQUE, title, content LONGTEXT, seo, is_published (şartname 5 yasal sayfalar)
**blog_posts** — id, title, slug, excerpt, content, cover_image, category, tags, author_id, published_at, seo, view_count
**announcements** — id, title, content, importance, starts_at, ends_at, is_active

---

### 2.11 LOGS & OPS

**audit_logs** — id, user_type, user_id, action VARCHAR(120), entity_type, entity_id, before JSON, after JSON, ip, user_agent, created_at, INDEX (entity_type, entity_id), INDEX (user_type, user_id)
**api_logs** — id, integration VARCHAR(64), endpoint, method, request_body, response_body, http_code, duration_ms, error, related_entity_type, related_entity_id, created_at, INDEX (integration, created_at)
**cron_logs** — id, command, status ENUM('running','success','failed'), started_at, finished_at, output TEXT, error TEXT
**btk_exports** — id, admin_id, type ENUM('hosting','domains','customers'), file_path, row_count, created_at

---

## 3. Kritik İlişkiler (ER Şeması metinsel)

```
customers 1──n orders 1──n order_items n──1 products
customers 1──n invoices 1──n invoice_items
invoices 1──n payments
customers 1──n domains n──1 domain_registrars
customers 1──n hosting_accounts n──1 hosting_servers
hosting_accounts n──1 products
customers 1──n tickets 1──n ticket_replies
tickets n──1 ticket_departments
customers 1──n builder_projects 1──n builder_pages
customers 1──n mobile_apps 1──n mobile_app_pages 1──n mobile_app_builds
customers 1──n marketplace_listings n──1 marketplace_categories
products 1──n product_prices
products 1──n product_addons
products 1──n product_custom_fields
admins n──1 admin_roles n──n admin_permissions
```

---

## 4. İndeks Stratejisi

- Her FK için otomatik INDEX.
- Sık filtre kolonları: `status`, `type`, `created_at`, `expiry_date`, `due_date`.
- Composite index'ler: `(customer_id, status)`, `(type, status)`, `(event_type, created_at)`.
- Full-text: `products(name, short_description)`, `knowledge_articles(title, content)`, `blog_posts(title, content)` — arama için.

---

## 5. Şifreleme

- Şifreler: **bcrypt** (`password_hash(PASSWORD_BCRYPT, cost=12)`).
- API key/token'lar: **AES-256-GCM**, anahtar `.env` `APP_KEY`.
- IP adresleri (analytics): hash'lenerek saklanır (KVKK).
- 2FA secret: AES-256-GCM.

---

## 6. Migration Sırası (Faz 1'de üretilecek)

```
0001_create_settings_table.php
0002_create_admin_roles_table.php
0003_create_admin_permissions_table.php
0004_create_admin_role_permissions_table.php
0005_create_admins_table.php
0006_create_customers_table.php
0007_create_customer_addresses_table.php
0008_create_customer_sessions_table.php
0009_create_two_factor_secrets_table.php
0010_create_currency_rates_table.php
0011_create_tax_rules_table.php
0012_create_email_templates_table.php
0013_create_notifications_table.php
0014_create_cms_pages_table.php
0015_create_blog_posts_table.php
0016_create_announcements_table.php
0017_create_product_groups_table.php
0018_create_products_table.php
0019_create_product_prices_table.php
0020_create_product_addons_table.php
0021_create_product_custom_fields_table.php
0022_create_product_upsells_table.php
0023_create_coupons_table.php
0024_create_coupon_usages_table.php
0025_create_cart_items_table.php
0026_create_orders_table.php
0027_create_order_items_table.php
0028_create_invoices_table.php
0029_create_invoice_items_table.php
0030_create_payments_table.php
0031_create_domain_registrars_table.php
0032_create_registrar_configs_table.php
0033_create_domain_pricing_table.php
0034_create_domains_table.php
0035_create_domain_dns_records_table.php
0036_create_server_groups_table.php
0037_create_hosting_servers_table.php
0038_create_hosting_accounts_table.php
0039_create_ticket_departments_table.php
0040_create_tickets_table.php
0041_create_ticket_replies_table.php
0042_create_knowledge_categories_table.php
0043_create_knowledge_articles_table.php
0044_create_builder_templates_table.php
0045_create_builder_projects_table.php
0046_create_builder_pages_table.php
0047_create_builder_assets_table.php
0048_create_mobile_apps_table.php
0049_create_mobile_app_pages_table.php
0050_create_mobile_app_builds_table.php
0051_create_marketplace_categories_table.php
0052_create_marketplace_listings_table.php
0053_create_marketplace_offers_table.php
0054_create_seller_profiles_table.php
0055_create_ai_settings_table.php
0056_create_ai_logs_table.php
0057_create_ai_actions_table.php
0058_create_cookie_analytics_events_table.php
0059_create_audit_logs_table.php
0060_create_api_logs_table.php
0061_create_cron_logs_table.php
0062_create_btk_exports_table.php
```

Toplam: **62 migration** (bazı ana tablo + destek tablolar).
