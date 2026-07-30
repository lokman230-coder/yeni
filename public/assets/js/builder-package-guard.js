(function(){
  function ensureModal(){
    var modal=document.getElementById('aoBuilderPackageModal');
    if(modal) return modal;
    modal=document.createElement('div');
    modal.id='aoBuilderPackageModal';
    modal.className='ao-builder-package-modal';
    modal.innerHTML='<div class="ao-builder-package-backdrop" data-package-close="1"></div><div class="ao-builder-package-dialog" role="dialog" aria-modal="true" aria-labelledby="aoBuilderPackageTitle"><button type="button" class="ao-builder-package-close" data-package-close="1">×</button><span class="ao-builder-package-kicker">Paket gerekli</span><h2 id="aoBuilderPackageTitle">Çıktı oluşturmak için paket almalısınız</h2><p id="aoBuilderPackageText">ZIP, APK, AAB veya kaynak kod çıktısı almak için uygun Site Builder / Mobile Builder paketini satın almanız gerekir.</p><div class="ao-builder-package-actions"><a class="ao-builder-package-buy" href="#">Satın Al</a><button type="button" class="ao-builder-package-cancel" data-package-close="1">Vazgeç</button></div></div>';
    document.body.appendChild(modal);
    modal.addEventListener('click',function(e){
      if(e.target && e.target.dataset && e.target.dataset.packageClose) modal.classList.remove('open');
    });
    document.addEventListener('keydown',function(e){
      if(e.key==='Escape') modal.classList.remove('open');
    });
    return modal;
  }
  document.addEventListener('click',function(e){
    var link=e.target && e.target.closest ? e.target.closest('[data-builder-package-alert]') : null;
    if(!link) return;
    e.preventDefault();
    var modal=ensureModal();
    var buy=modal.querySelector('.ao-builder-package-buy');
    var title=modal.querySelector('#aoBuilderPackageTitle');
    var text=modal.querySelector('#aoBuilderPackageText');
    var kind=link.getAttribute('data-builder-package-kind') || '';
    var href=link.getAttribute('href') || link.getAttribute('data-package-url') || '#';
    if(title) title.textContent=link.getAttribute('data-package-title') || (kind==='mobile' ? 'APK/AAB ve kaynak kod için paket almalısınız' : 'ZIP ve kaynak kod için paket almalısınız');
    if(text) text.textContent=link.getAttribute('data-package-message') || (kind==='mobile' ? 'Mobil uygulama çıktısı, APK, AAB, PWA veya kaynak kod almak için uygun Mobile Builder paketini satın almanız gerekir.' : 'Site ZIP export, kaynak kod ve kalıcı yayın çıktısı almak için uygun Site Builder paketini satın almanız gerekir.');
    if(buy) buy.setAttribute('href', href);
    modal.classList.add('open');
  }, true);
})();

