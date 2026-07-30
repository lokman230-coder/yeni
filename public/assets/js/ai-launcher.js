(function(){
  if (window.__aoAiLauncherV1) return;
  window.__aoAiLauncherV1 = true;

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"]/g, function(char){
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[char];
    });
  }

  function setOpen(root, open) {
    var panel = root.querySelector('[data-ai-launcher-panel]');
    var button = root.querySelector('[data-ai-launcher-open]');
    if (!panel || !button) return;
    panel.hidden = !open;
    button.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (open) {
      var field = root.querySelector('textarea[name="prompt"]');
      if (field) setTimeout(function(){ field.focus(); }, 40);
    }
  }

  function renderResult(target, data) {
    var ok = data && data.ok !== false;
    var html = '<div class="ao-ai-launcher__answer"><strong>' + (ok ? 'Yanıt hazır' : 'İşlem yapılamadı') + '</strong><p>' + escapeHtml((data && data.message) || 'Yanıt alınamadı.') + '</p></div>';
    var actions = data && Array.isArray(data.actions) ? data.actions : [];
    if (actions.length) {
      html += '<div class="ao-ai-launcher__actions">';
      actions.forEach(function(action){
        var href = action.url || action.route || '';
        if (!href) return;
        html += '<a href="' + escapeHtml(href) + '">' + escapeHtml(action.label || action.title || 'İşleme Git') + '</a>';
      });
      html += '</div>';
    }
    target.innerHTML = html;
  }

  document.addEventListener('click', function(event){
    var opener = event.target.closest('[data-ai-launcher-open]');
    if (opener) {
      event.preventDefault();
      var root = opener.closest('[data-ai-launcher]');
      var panel = root && root.querySelector('[data-ai-launcher-panel]');
      if (root && panel) setOpen(root, panel.hidden);
      return;
    }

    var closer = event.target.closest('[data-ai-launcher-close]');
    if (closer) {
      event.preventDefault();
      var closeRoot = closer.closest('[data-ai-launcher]');
      if (closeRoot) setOpen(closeRoot, false);
      return;
    }

    var quick = event.target.closest('[data-ai-prompt]');
    if (quick) {
      event.preventDefault();
      var quickRoot = quick.closest('[data-ai-launcher]');
      var textarea = quickRoot && quickRoot.querySelector('textarea[name="prompt"]');
      if (textarea) {
        textarea.value = quick.getAttribute('data-ai-prompt') || '';
        textarea.focus();
      }
      return;
    }

    document.querySelectorAll('[data-ai-launcher]').forEach(function(root){
      if (!root.contains(event.target)) setOpen(root, false);
    });
  });

  document.addEventListener('keydown', function(event){
    if (event.key !== 'Escape') return;
    document.querySelectorAll('[data-ai-launcher]').forEach(function(root){ setOpen(root, false); });
  });

  document.addEventListener('keydown', function(event){
    if (event.key !== 'Enter' || event.shiftKey || event.ctrlKey || event.altKey || event.metaKey) return;
    var field = event.target && event.target.closest ? event.target.closest('[data-ai-launcher-form] textarea[name="prompt"]') : null;
    if (!field) return;
    var form = field.closest('[data-ai-launcher-form]');
    if (!form) return;
    event.preventDefault();
    if (form.requestSubmit) form.requestSubmit();
    else form.dispatchEvent(new Event('submit', {cancelable:true, bubbles:true}));
  });

  document.addEventListener('submit', function(event){
    var form = event.target.closest('[data-ai-launcher-form]');
    if (!form) return;
    event.preventDefault();
    var root = form.closest('[data-ai-launcher]');
    var result = root && root.querySelector('[data-ai-launcher-result]');
    var endpoint = root && root.getAttribute('data-endpoint');
    var csrf = root && root.getAttribute('data-csrf');
    var prompt = (form.querySelector('textarea[name="prompt"]') || {}).value || '';
    if (!root || !result || !endpoint) return;
    if (!prompt.trim()) {
      result.innerHTML = '<p>Lütfen kısa bir istek yazın.</p>';
      return;
    }
    var body = new FormData();
    body.append('prompt', prompt);
    body.append('q', prompt);
    if (csrf) body.append('csrf_token', csrf);
    result.innerHTML = '<p>Yanıt hazırlanıyor...</p>';
    fetch(endpoint, {method:'POST', body:body, headers:{'Accept':'application/json'}})
      .then(function(response){ return response.json(); })
      .then(function(data){ renderResult(result, data); })
      .catch(function(){ result.innerHTML = '<p>AI yardımcıya ulaşılamadı. Birkaç saniye sonra tekrar deneyin.</p>'; });
  }, true);
})();
