(function () {
  function ensureModal() {
    var modal = document.getElementById('aoDomainModal');
    if (!modal) {
      modal = document.createElement('div');
      modal.id = 'aoDomainModal';
      modal.className = 'ao-domain-modal';
      modal.innerHTML = '<div class="ao-domain-backdrop" data-close="1"></div><div class="ao-domain-dialog"><button class="ao-domain-close" data-close="1" aria-label="Kapat">×</button><h2 id="aoDomainModalTitle">Domain Sorgu</h2><div id="aoDomainModalBody"><div class="ao-loading">Yükleniyor...</div></div></div>';
      modal.addEventListener('click', function (event) {
        if (event.target.dataset.close) closeModal(modal);
      });
      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeModal(modal);
      });
    }
    if (modal.parentNode !== document.body) document.body.appendChild(modal);
    return modal;
  }

  function openModal(modal) {
    modal.classList.add('open');
    modal.classList.add('is-open');
    modal.removeAttribute('hidden');
    document.documentElement.classList.add('ao-domain-modal-active');
  }

  function closeModal(modal) {
    modal.classList.remove('open');
    modal.classList.remove('is-open');
    document.documentElement.classList.remove('ao-domain-modal-active');
  }

  function apiBase() {
    var meta = document.querySelector('meta[name="ahost-base-url"]');
    return (((meta && meta.getAttribute('content')) || '') || (window.location.origin + (window.location.pathname.split('/index.php')[0] || ''))).replace(/\/$/, '');
  }

  async function readJson(response) {
    var text = (await response.text()).replace(/^[\uFEFF\u200B\s]+/, '');
    try {
      return JSON.parse(text);
    } catch (error) {
      throw new Error('JSON beklenirken farklı cevap alındı: ' + text.slice(0, 180));
    }
  }

  function domainFrom(button) {
    if (button && button.dataset && button.dataset.domainValue) return button.dataset.domainValue.trim();
    var wrap = button.closest('[data-domain-widget]') || document;
    var input = wrap.querySelector('[data-domain-input]') || document.querySelector('[data-domain-input]');
    return input ? input.value.trim() : '';
  }

  async function openTool(tool, domain, limited) {
    var modal = ensureModal();
    var title = document.getElementById('aoDomainModalTitle');
    var body = document.getElementById('aoDomainModalBody');
    title.textContent = 'Domain Sorgu';
    body.innerHTML = '<div class="ao-loading">Sorgulanıyor...</div>';
    openModal(modal);
    try {
      var limitedParam = limited ? '&limited=1' : '';
      var response = await fetch(apiBase() + '/api/domain-tool?tool=' + encodeURIComponent(tool) + '&domain=' + encodeURIComponent(domain) + limitedParam, {
        headers: { Accept: 'application/json' }
      });
      var data = await readJson(response);
      title.textContent = data.title || 'Domain Sorgu';
      body.innerHTML = data.html || '<div class="ao-modal-error">Sonuç alınamadı.</div>';
      body.querySelectorAll('[data-dns-filter]').forEach(function (button) {
        button.addEventListener('click', function () {
          var filter = button.dataset.dnsFilter;
          body.querySelectorAll('[data-dns-filter]').forEach(function (item) { item.classList.remove('active'); });
          button.classList.add('active');
          body.querySelectorAll('[data-record-type]').forEach(function (row) {
            row.style.display = (filter === 'ALL' || row.dataset.recordType === filter) ? '' : 'none';
          });
        });
      });
    } catch (error) {
      body.innerHTML = '<div class="ao-modal-error">Sorgu tamamlanamadı. Lütfen birkaç saniye sonra tekrar deneyin.</div>';
      if (window.console) console.warn('[domain-tool]', error);
    }
  }

  async function searchDomain(domain, wrap) {
    var target = (wrap && wrap.querySelector('[data-domain-search-result]')) || document.querySelector('[data-domain-search-result]');
    if (target) target.innerHTML = '<div class="ao-loading">Domain sorgulanıyor...</div>';
    try {
      var compact = wrap && (wrap.hasAttribute('data-domain-compact') || wrap.classList.contains('e-domain-card'));
      var response = await fetch(apiBase() + '/api/domain-search?domain=' + encodeURIComponent(domain) + (compact ? '&compact=1' : ''), {
        headers: { Accept: 'application/json' }
      });
      var data = await readJson(response);
      if (target) target.innerHTML = data.html || ('<div class="ao-search-result"><b>' + domain + '</b><span>' + (data.message || 'Sonuç alınamadı.') + '</span></div>');
      return data;
    } catch (error) {
      if (target) target.innerHTML = '<div class="ao-modal-error">Domain sorgusu tamamlanamadı.</div>';
      if (window.console) console.warn('[domain-search]', error);
      return null;
    }
  }

  function showRequiredDomain() {
    var modal = ensureModal();
    openModal(modal);
    document.getElementById('aoDomainModalTitle').textContent = 'Domain gerekli';
    document.getElementById('aoDomainModalBody').innerHTML = '<div class="ao-modal-error">Lütfen önce domain adını yazın.</div>';
  }

  function openBackorder(domain) {
    domain = String(domain || '').trim();
    if (!domain) return;
    var root = document.querySelector('[data-domain-tabs]');
    var backorderInput = root ? root.querySelector('[data-backorder-domain-input]') : null;
    var backorderTab = root ? root.querySelector('[data-domain-tab="backorder"]') : null;
    if (backorderInput && backorderTab) {
      backorderTab.click();
      backorderInput.value = domain;
      window.setTimeout(function () {
        backorderInput.focus();
        backorderInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }, 80);
      return;
    }
    window.location.href = apiBase() + '/domain?domain=' + encodeURIComponent(domain) + '#backorder';
  }

  document.addEventListener('click', function (event) {
    var searchButton = event.target.closest('[data-domain-search]');
    if (searchButton) {
      event.preventDefault();
      var wrap = searchButton.closest('[data-domain-widget]') || document;
      var searchDomainValue = domainFrom(searchButton);
      if (!searchDomainValue) {
        showRequiredDomain();
        return;
      }
      searchDomain(searchDomainValue, wrap);
      return;
    }

    var moreButton = event.target.closest('[data-domain-more]');
    if (moreButton) {
      event.preventDefault();
      var moreWrap = moreButton.closest('[data-domain-widget]') || moreButton.closest('[data-domain-search-result]') || document;
      var moreDomain = moreButton.dataset.domainMore || domainFrom(moreButton);
      var modal = ensureModal();
      var title = document.getElementById('aoDomainModalTitle');
      var body = document.getElementById('aoDomainModalBody');
      title.textContent = 'Daha Fazla Domain Önerisi';
      body.innerHTML = '<div class="ao-loading">Daha fazla öneri hazırlanıyor...</div>';
      openModal(modal);
      try {
        var compact = moreWrap && (moreWrap.hasAttribute('data-domain-compact') || moreWrap.classList.contains('e-domain-card'));
        fetch(apiBase() + '/api/domain-search?domain=' + encodeURIComponent(moreDomain) + '&more=1' + (compact ? '&compact=1' : ''), {
          headers: { Accept: 'application/json' }
        }).then(readJson).then(function (data) {
          title.textContent = (data.keyword || moreDomain) + ' için Domain Önerileri';
          body.innerHTML = data.html || '<div class="ao-modal-error">Sonuç alınamadı.</div>';
        }).catch(function (error) {
          body.innerHTML = '<div class="ao-modal-error">Öneriler alınamadı.</div>';
          if (window.console) console.warn('[domain-more]', error);
        });
      } catch (error) {
        body.innerHTML = '<div class="ao-modal-error">Öneriler alınamadı.</div>';
      }
      return;
    }

    var backorderButton = event.target.closest('[data-domain-backorder-link]');
    if (backorderButton) {
      event.preventDefault();
      openBackorder(backorderButton.dataset.domainBackorder || domainFrom(backorderButton));
      return;
    }

    var button = event.target.closest('[data-domain-tool]');
    if (!button) return;
    event.preventDefault();
    var domain = domainFrom(button);
    if (!domain) {
      showRequiredDomain();
      return;
    }
    openTool(button.dataset.domainTool, domain, !!button.closest('[data-site-tools-limited]'));
  });

  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!form || !form.querySelector) return;
    var input = form.querySelector('[data-domain-input]');
    var searchButton = form.querySelector('[data-domain-search]');
    if (!input || !searchButton) return;
    event.preventDefault();
    event.stopPropagation();
    var wrap = form.closest('[data-domain-widget]') || form.closest('.e-domain-card') || form.parentElement || document;
    var domain = input.value.trim();
    if (!domain) {
      showRequiredDomain();
      return;
    }
    searchDomain(domain, wrap);
  }, true);

  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Enter') return;
    var input = event.target && event.target.closest ? event.target.closest('[data-domain-input]') : null;
    if (!input) return;
    var form = input.closest('form');
    var widget = input.closest('[data-domain-widget]') || input.closest('.e-domain-card') || document;
    var searchButton = form ? form.querySelector('[data-domain-search]') : widget.querySelector('[data-domain-search]');
    if (!searchButton) return;
    event.preventDefault();
    event.stopPropagation();
    var wrap = (form && (form.closest('[data-domain-widget]') || form.closest('.e-domain-card') || form.parentElement)) || widget || document;
    var domain = input.value.trim();
    if (!domain) {
      showRequiredDomain();
      return;
    }
    searchDomain(domain, wrap);
  }, true);
})();


