# 08 · UI Tasarım Sistemi

> Amaç: 2026 seviyesinde premium, tutarlı, hızlı bir görsel dil.
> Kural: Her komponent tek yerde tanımlıdır, her yerde aynı görünür.

---

## 1. Marka Rengi (Placeholder — sonra rebrand mümkün)

```css
:root {
  /* Primary — Deep Ocean */
  --aho-color-primary-50:  #f0f9ff;
  --aho-color-primary-100: #e0f2fe;
  --aho-color-primary-500: #0ea5e9;
  --aho-color-primary-600: #0284c7;
  --aho-color-primary-700: #0369a1;
  --aho-color-primary-900: #0c4a6e;

  /* Accent — Cyan (aksiyon, vurgu) */
  --aho-color-accent-400:  #22d3ee;
  --aho-color-accent-500:  #06b6d4;
  --aho-color-accent-600:  #0891b2;

  /* Ink (metin) */
  --aho-color-ink-900: #0f172a;
  --aho-color-ink-700: #334155;
  --aho-color-ink-500: #64748b;
  --aho-color-ink-300: #cbd5e1;

  /* Surface */
  --aho-color-bg:        #ffffff;
  --aho-color-bg-soft:   #f8fafc;
  --aho-color-bg-muted:  #f1f5f9;
  --aho-color-border:    #e2e8f0;

  /* Semantic */
  --aho-color-success: #10b981;
  --aho-color-warning: #f59e0b;
  --aho-color-danger:  #ef4444;
  --aho-color-info:    #3b82f6;
}

[data-theme="dark"] {
  --aho-color-bg:        #0b1220;
  --aho-color-bg-soft:   #111a2e;
  --aho-color-bg-muted:  #1a2540;
  --aho-color-border:    #253150;
  --aho-color-ink-900:   #f8fafc;
  --aho-color-ink-700:   #cbd5e1;
  --aho-color-ink-500:   #94a3b8;
  --aho-color-ink-300:   #475569;
}
```

**Logo placeholder:** "A1" monogram, geometrik, koyu lacivert zeminde turkuaz aksan (Faz 1 SVG üretilecek).

---

## 2. Tipografi

```css
:root {
  --aho-font-sans: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
  --aho-font-mono: 'JetBrains Mono', 'Fira Code', Consolas, monospace;

  /* Type scale — modular 1.25 */
  --aho-text-xs:   0.75rem;    /* 12px */
  --aho-text-sm:   0.875rem;   /* 14px */
  --aho-text-base: 1rem;       /* 16px */
  --aho-text-lg:   1.125rem;   /* 18px */
  --aho-text-xl:   1.25rem;    /* 20px */
  --aho-text-2xl:  1.5rem;     /* 24px */
  --aho-text-3xl:  1.875rem;   /* 30px */
  --aho-text-4xl:  2.25rem;    /* 36px */
  --aho-text-5xl:  3rem;       /* 48px */
  --aho-text-6xl:  3.75rem;    /* 60px */

  --aho-leading-tight:  1.2;
  --aho-leading-normal: 1.5;
  --aho-leading-loose:  1.75;

  --aho-weight-regular:  400;
  --aho-weight-medium:   500;
  --aho-weight-semibold: 600;
  --aho-weight-bold:     700;
}
```

- Inter font `preload` ile yüklenir, `font-display: swap`.
- Base font-size: 16px.
- Body line-height: 1.5.
- Başlıklar: `Inter Semibold` / `Bold`.

---

## 3. Spacing (4px scale)

```css
--aho-space-0:  0;
--aho-space-1:  0.25rem;   /* 4px */
--aho-space-2:  0.5rem;    /* 8px */
--aho-space-3:  0.75rem;   /* 12px */
--aho-space-4:  1rem;      /* 16px */
--aho-space-5:  1.25rem;   /* 20px */
--aho-space-6:  1.5rem;    /* 24px */
--aho-space-8:  2rem;      /* 32px */
--aho-space-10: 2.5rem;    /* 40px */
--aho-space-12: 3rem;      /* 48px */
--aho-space-16: 4rem;      /* 64px */
--aho-space-20: 5rem;      /* 80px */
--aho-space-24: 6rem;      /* 96px */
```

---

## 4. Radius, Shadow, Motion

```css
--aho-radius-sm:  6px;
--aho-radius-md:  10px;
--aho-radius-lg:  14px;
--aho-radius-xl:  20px;
--aho-radius-2xl: 28px;
--aho-radius-full: 999px;

--aho-shadow-xs: 0 1px 2px rgb(15 23 42 / .04);
--aho-shadow-sm: 0 1px 3px rgb(15 23 42 / .06), 0 1px 2px rgb(15 23 42 / .04);
--aho-shadow-md: 0 4px 12px rgb(15 23 42 / .08);
--aho-shadow-lg: 0 12px 32px rgb(15 23 42 / .10);
--aho-shadow-xl: 0 24px 60px rgb(15 23 42 / .14);
--aho-shadow-glow: 0 0 0 4px rgb(6 182 212 / .18);   /* focus ring */

--aho-motion-fast:   150ms cubic-bezier(.4, 0, .2, 1);
--aho-motion-base:   220ms cubic-bezier(.4, 0, .2, 1);
--aho-motion-slow:   320ms cubic-bezier(.4, 0, .2, 1);
```

`prefers-reduced-motion` içinde tüm transition `<= 0`.

---

## 5. Breakpoint'ler

```css
/* Mobile-first */
/* base       :   < 640px  */
/* @media (min-width: 640px)   → sm */
/* @media (min-width: 768px)   → md */
/* @media (min-width: 992px)   → lg */
/* @media (min-width: 1280px)  → xl */
/* @media (min-width: 1536px)  → 2xl */
```

Container:
```css
.aho-container {
  width: 100%;
  padding-inline: var(--aho-space-4);
  margin-inline: auto;
}
@media (min-width: 768px) { .aho-container { max-width: 720px; } }
@media (min-width: 992px) { .aho-container { max-width: 960px; padding-inline: var(--aho-space-6); } }
@media (min-width: 1280px){ .aho-container { max-width: 1200px; } }
@media (min-width: 1536px){ .aho-container { max-width: 1400px; } }
```

---

## 6. Komponentler (Faz 1'de üretilecek)

### 6.1 Buton (`.aho-btn`)
- Varyantlar: `primary`, `secondary`, `outline`, `ghost`, `danger`, `success`.
- Boyutlar: `xs`, `sm`, `md` (default), `lg`.
- İkonlu: `.aho-btn--icon-left`, `.aho-btn--icon-right`, `.aho-btn--icon-only`.
- Yüklenme: `[aria-busy="true"]` → spinner göster, disable.
- Pasifse `title` ile neden pasif açıklaması (şartname madde 2).

### 6.2 Form (`.aho-form-*`)
- Input, textarea, select, checkbox, radio, switch.
- Etiket üstte, hata mesajı altta kırmızı.
- Focus: `--aho-shadow-glow` ring.
- Validation state: `.is-invalid`, `.is-valid`.

### 6.3 Kart (`.aho-card`)
- Padding var (default md), shadow `sm`, radius `lg`.
- Hover: shadow `md`, transform `translateY(-2px)`.
- Header/body/footer alt komponentler.

### 6.4 Tablo (`.aho-table`)
- Header sticky opsiyonu.
- Zebra opsiyonu.
- Responsive: mobilde kart görünümü.
- Sort, filter, pagination.

### 6.5 Modal (`.aho-modal`)
- Overlay + panel.
- ESC ile kapanır.
- Focus trap.
- Boyutlar: `sm`, `md`, `lg`, `xl`, `fullscreen`.

### 6.6 Toast (`.aho-toast`)
- Sağ üst köşe.
- Tipler: success, error, warning, info.
- Auto-dismiss 5s (hover'da durur).
- `AhostOne.toast.success('Kayıt eklendi')`.

### 6.7 Dropdown / Menu
- Click veya hover trigger.
- Klavye erişilebilir (arrow keys).
- Portal (body sonu) — z-index sorunları yok.

### 6.8 Tabs / Accordion
- ARIA uyumlu.
- URL sync opsiyonlu.

### 6.9 Badge / Chip / Tag
- Renk semantik.
- Kapatılabilir.

### 6.10 Skeleton Loader
- Kart, tablo satırı, metin bloğu için.
- Shimmer animasyonu (reduced-motion'da statik).

### 6.11 Empty State
- İkon + başlık + açıklama + aksiyon butonu.
- Her liste sayfasında zorunlu.

### 6.12 Stat Card (dashboard)
- Metric + değişim yüzdesi + mini spark chart.

---

## 7. İkon Sistemi

- **Lucide** (SVG sprite, tek dosya, ~10KB gzip).
- `<svg class="aho-icon" aria-hidden="true"><use href="#icon-shopping-cart"/></svg>`.
- Boyutlar utility: `.aho-icon--sm`, `.aho-icon--md`, `.aho-icon--lg`.

---

## 8. Layout Tipleri

### 8.1 Public Layout
```
┌─────────────────────────────────────┐
│  Topbar (kur, dil, para, sepet)     │  şartname 6
├─────────────────────────────────────┤
│  Header (logo, menu, cta)           │
├─────────────────────────────────────┤
│                                     │
│  [Main content — modül view]        │
│                                     │
├─────────────────────────────────────┤
│  Footer                             │  şartname 7
└─────────────────────────────────────┘
[Çerez banner] [AI floating widget]
```

### 8.2 Admin Layout
```
┌─────┬───────────────────────────────┐
│     │  Topbar (arama, bildirim, hesap)│
│ S   ├───────────────────────────────┤
│ i   │                               │
│ d   │  [Main content]               │
│ e   │                               │
│ b   │                               │
│ a   │                               │
│ r   │                               │
└─────┴───────────────────────────────┘
```
Sidebar collapse edilebilir, mobilde drawer.

### 8.3 Customer Layout
Admin ile aynı iskelet, daha yumuşak renkler, sidebar daha kısa menü.

---

## 9. Dark Mode

- Her sayfada üst köşede switch.
- Sistem tercihini ilk yüklemede al.
- LocalStorage'da hatırla.
- FOUC yok — `<script>` inline `<head>` içinde tema class'ını `<html>`e ekler ilk boyamada.

---

## 10. Erişilebilirlik (a11y)

- WCAG 2.1 AA hedef.
- Kontrast oranı ≥ 4.5:1 (normal metin), ≥ 3:1 (büyük metin).
- Klavye ile tüm etkileşim mümkün.
- Focus outline daima görünür (`--aho-shadow-glow`).
- ARIA doğru kullanılır (yanlış aria = hiç aria).
- Skip link (`Ana içeriğe atla`) header'ın hemen üstünde.
- `prefers-reduced-motion`, `prefers-color-scheme` respect.

---

## 11. Micro-interactions (2026 hissi)

- Buton hover: hafif scale (1.02) + shadow yükselir.
- Kart hover: `translateY(-2px)` + shadow.
- Modal açılış: fade + slide-up 220ms.
- Toast: slide-in sağdan.
- Sayfa geçişi (SPA benzeri admin): `view-transition-api` (destekli tarayıcılarda).
- Skeleton shimmer 1.4s.
- Success feedback: kısa yeşil "check" animasyonu.

---

## 12. Örnek Sayfa Wire (Public Home)

```
┌──────────────────────────────────────────────────┐
│ Topbar: 📞 0850… | ₺ TRY | 🇹🇷 TR | 🛒 | Giriş │
├──────────────────────────────────────────────────┤
│ [Logo]   Hosting  Domain  Sunucu  Builder  Blog │
├──────────────────────────────────────────────────┤
│                                                  │
│   [Hero başlık — büyük, akıcı]                   │
│   [alt metin]                                    │
│   [Domain sorgu input + buton]                   │
│                                                  │
├──────────────────────────────────────────────────┤
│ Kampanya slider (opsiyonel)                      │
├──────────────────────────────────────────────────┤
│  [Hosting Kartı] [Domain Kartı] [Builder Kartı]  │
│  [VPS Kartı]     [Mobile Kartı] [Marketplace]    │
├──────────────────────────────────────────────────┤
│  Site araçları tanıtımı (3-4 kart)               │
├──────────────────────────────────────────────────┤
│  Referanslar (marka logo şeridi)                 │
├──────────────────────────────────────────────────┤
│  Son blog yazıları (3 kart)                      │
├──────────────────────────────────────────────────┤
│  CTA — "Hemen başla"                             │
├──────────────────────────────────────────────────┤
│  Footer                                          │
└──────────────────────────────────────────────────┘
   [🍪 Çerez banner alt]      [🤖 AI sol alt]
```

---

## 13. Kabul Kriteri (Design System)

- [ ] Tüm renkler CSS custom property üzerinden gelir; hard-coded hex yok (utility hariç).
- [ ] Tüm spacing token'lardan; magic number yok.
- [ ] Dark mode her sayfada çalışır.
- [ ] Lighthouse Accessibility ≥ 95.
- [ ] Tüm formlar klavye ile doldurulabilir.
- [ ] Odak halkası (focus ring) daima görünür.
