(function(){
  var popup = document.querySelector('[data-ao-campaign-popup]');
  if (!popup) return;

  var preview = popup.dataset.preview === '1';
  var key = 'ao_campaign_seen_' + popup.dataset.campaignId;
  var cooldown = Math.max(0, Number(popup.dataset.cooldown || 0)) * 3600000;
  var seen = 0;

  try { seen = Number(localStorage.getItem(key) || 0); } catch (e) {}
  if (!preview && cooldown && seen && Date.now() - seen < cooldown) return;

  function close() {
    popup.hidden = true;
    if (!preview) {
      try { localStorage.setItem(key, String(Date.now())); } catch (e) {}
    }
  }

  window.setTimeout(function(){
    popup.hidden = false;
    var closeButton = popup.querySelector('[data-ao-campaign-close]');
    if (closeButton) closeButton.focus();
  }, 220);

  var button = popup.querySelector('[data-ao-campaign-close]');
  if (button) button.addEventListener('click', close);

  popup.addEventListener('click', function(event){
    if (event.target === popup) close();
  });

  document.addEventListener('keydown', function(event){
    if (event.key === 'Escape' && !popup.hidden) close();
  });
})();
