(function(){
  const modal = document.querySelector('[data-support-modal]');

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"]/g, char => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;'
    }[char]));
  }

  function setTab(name) {
    if (!modal) return;
    modal.querySelectorAll('[data-support-tab]').forEach(button => {
      button.classList.toggle('active', button.dataset.supportTab === name);
    });
    modal.querySelectorAll('[data-support-pane]').forEach(pane => {
      pane.classList.toggle('active', pane.dataset.supportPane === name);
    });
  }

  function openSupport(tab) {
    if (!modal) return;
    modal.hidden = false;
    modal.classList.add('is-open');
    if (tab) setTab(tab);
  }

  function closeSupport() {
    if (!modal) return;
    modal.classList.remove('is-open');
    modal.hidden = true;
  }

  function appBaseUrl() {
    const meta = document.querySelector('meta[name="ahost-base-url"]');
    return ((meta && meta.getAttribute('content')) || '').replace(/\/+$/, '');
  }

  function syncScrollTop() {
    if (document.body) document.body.classList.toggle('ao-show-scroll-top', window.scrollY > 220);
  }

  function closeMobileCategoryPanel() {
    document.querySelectorAll('[data-mobile-category-panel]').forEach(panel => {
      panel.hidden = true;
    });
    document.querySelectorAll('[data-mobile-category-toggle]').forEach(button => {
      button.setAttribute('aria-expanded', 'false');
    });
  }

  window.addEventListener('scroll', syncScrollTop, {passive: true});
  window.addEventListener('load', syncScrollTop);
  document.addEventListener('DOMContentLoaded', syncScrollTop);
  syncScrollTop();

  document.addEventListener('click', event => {
    const topButton = event.target.closest('[data-support-scroll-top]');
    if (topButton) {
      event.preventDefault();
      window.scrollTo({top: 0, behavior: 'smooth'});
      return;
    }

    const mobileCategoryToggle = event.target.closest('[data-mobile-category-toggle]');
    if (mobileCategoryToggle) {
      event.preventDefault();
      const panel = document.querySelector('[data-mobile-category-panel]');
      if (panel) {
        const willOpen = panel.hidden;
        panel.hidden = !willOpen;
        mobileCategoryToggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
      }
      return;
    }

    if (event.target.closest('[data-mobile-category-close]')) {
      closeMobileCategoryPanel();
      return;
    }

    const opener = event.target.closest('[data-support-open]');
    if (opener) {
      event.preventDefault();
      openSupport(opener.dataset.mobileTab || opener.dataset.supportOpen || null);
      return;
    }

    const tab = event.target.closest('[data-support-tab]');
    if (tab) {
      event.preventDefault();
      setTab(tab.dataset.supportTab);
      return;
    }

    if (event.target.closest('[data-support-close]')) {
      event.preventDefault();
      closeSupport();
      return;
    }

    if (modal && !modal.hidden && event.target === modal) closeSupport();

    if (!event.target.closest('[data-mobile-category-panel]') && !event.target.closest('[data-mobile-category-toggle]')) {
      closeMobileCategoryPanel();
    }
  }, true);

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
      closeSupport();
      closeMobileCategoryPanel();
    }
  });

  const mobileButton = document.querySelector('[data-mobile-support]');
  const mobilePanel = document.querySelector('[data-mobile-support-panel]');
  if (mobileButton && mobilePanel) {
    mobileButton.addEventListener('click', event => {
      event.preventDefault();
      mobilePanel.hidden = !mobilePanel.hidden;
      mobileButton.classList.toggle('support-open', !mobilePanel.hidden);
    });
    document.addEventListener('click', event => {
      if (!mobilePanel.hidden && !mobilePanel.contains(event.target) && !mobileButton.contains(event.target)) {
        mobilePanel.hidden = true;
        mobileButton.classList.remove('support-open');
      }
    });
  }

  if (!modal) return;

  const searchInput = modal.querySelector('[data-support-search-input]');
  const searchResults = modal.querySelector('[data-support-search-results]');
  let searchTimer = null;
  if (searchInput && searchResults) {
    searchInput.addEventListener('input', function(){
      clearTimeout(searchTimer);
      const query = this.value.trim();
      if (query.length < 2) {
        searchResults.innerHTML = '<p>En az 2 karakter yazın.</p>';
        return;
      }

      searchTimer = setTimeout(() => {
        fetch(appBaseUrl() + '/support/widget/search?q=' + encodeURIComponent(query))
          .then(response => response.json())
          .then(data => {
            const items = data.items || [];
            searchResults.innerHTML = items.length
              ? items.map(item => '<div class="ao-support-result"><a href="' + (item.url || '#') + '">' + escapeHtml(item.title || 'Makale') + '</a><p>' + escapeHtml(item.excerpt || '') + '</p></div>').join('')
              : '<p>Sonuç bulunamadı. AI destek veya canlı sohbeti deneyin.</p>';
          })
          .catch(() => {
            searchResults.innerHTML = '<p>Arama yapılamadı.</p>';
          });
      }, 350);
    });
  }

  const aiForm = modal.querySelector('[data-support-ai-form]');
  const aiResult = modal.querySelector('[data-support-ai-result]');
  if (aiForm && aiResult) {
    aiForm.addEventListener('submit', event => {
      event.preventDefault();
      const formData = new FormData(aiForm);
      aiResult.innerHTML = '<p>Cevap aranıyor...</p>';

      fetch(appBaseUrl() + '/support/widget/ai', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
          let html = '<div class="ao-support-result"><p>' + escapeHtml(data.answer || 'Cevap bulunamadı.') + '</p></div>';
          if (Array.isArray(data.actions) && data.actions.length) {
            html += '<div class="ao-support-result-actions">';
            data.actions.forEach(action => {
              const href = action.route || action.url || '';
              if (!href) return;
              html += '<a href="' + escapeHtml(href) + '">' + escapeHtml(action.label || action.title || action.type || 'İşleme Git') + '</a>';
            });
            html += '</div>';
          }
          if (data.handoff) html += '<button type="button" class="ao-support-submit" data-support-tab="live">Canlı Temsilciye Aktar</button>';
          aiResult.innerHTML = html;
        })
        .catch(() => {
          aiResult.innerHTML = '<p>AI cevap alınamadı.</p>';
        });
    });
  }
})();
