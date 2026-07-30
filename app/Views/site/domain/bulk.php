<section class="ao-public-page ao-domain-page ao-domain-bulk-page" data-domain-bulk-widget>
  <div class="ao-public-shell">
    <section class="ao-domain-hero-card">
      <div>
        <span class="ao-kicker">Toplu Domain Sorgulama</span>
        <h1>Birden fazla domaini aynı anda sorgulayın.</h1>
        <p>Her satıra bir domain yazın. Uygun olanları sepet sürecine yönlendirebilirsiniz.</p>
      </div>
      <form class="ao-domain-bulk-form">
        <textarea data-domain-bulk-input rows="8" placeholder="ornek.com&#10;markam.net&#10;firmam.com.tr"></textarea>
        <button type="button" data-domain-bulk-search>Toplu Sorgula</button>
      </form>
    </section>
    <section class="ao-card ao-domain-bulk-results" data-domain-bulk-results>
      <p class="muted">Sorgulama sonuçları burada görünecek.</p>
    </section>
  </div>
</section>

<script>
(function(){
  const root = document.querySelector('[data-domain-bulk-widget]');
  if (!root) return;

  const input = root.querySelector('[data-domain-bulk-input]');
  const btn = root.querySelector('[data-domain-bulk-search]');
  const box = root.querySelector('[data-domain-bulk-results]');
  const base = document.querySelector('meta[name="app-url"]')?.content || '';
  const baseUrl = base.replace(/\/$/, '');

  async function check(domain) {
    const res = await fetch(baseUrl + '/api/domain-search?domain=' + encodeURIComponent(domain), {
      headers: { 'Accept': 'application/json' }
    });
    return await res.json();
  }

  btn.addEventListener('click', async function(){
    const domains = (input.value || '').split(/\s+/).map(v => v.trim()).filter(Boolean).slice(0, 30);
    if (!domains.length) {
      box.innerHTML = '<p class="ao-modal-error">En az bir domain yazın.</p>';
      return;
    }

    box.innerHTML = '<p class="ao-loading">Sorgulanıyor...</p>';
    let html = '<div class="ao-domain-bulk-list">';

    for (const domain of domains) {
      try {
        const result = await check(domain);
        const action = result.available
          ? '<a href="' + baseUrl + '/cart/add?domain=' + encodeURIComponent(domain) + '">Sepete Ekle</a>'
          : '<a href="' + baseUrl + '/domain?domain=' + encodeURIComponent(domain) + '&tool=whois">WHOIS</a>';
        html += '<div class="ao-domain-bulk-row ' + (result.available ? 'available' : 'taken') + '"><b>' + domain + '</b><span>' + (result.message || 'Sonuç alındı') + '</span>' + action + '</div>';
      } catch (error) {
        html += '<div class="ao-domain-bulk-row error"><b>' + domain + '</b><span>Sorgulanamadı</span></div>';
      }
    }

    html += '</div>';
    box.innerHTML = html;
  });
})();
</script>
