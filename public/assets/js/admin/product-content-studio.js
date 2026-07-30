(function(){
  function insertHtml(html){ document.execCommand('insertHTML', false, html); }
  function esc(value){
    return String(value||'').replace(/[&<>"']/g,function(ch){
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch];
    });
  }
  function addonCurrencyOptions(){
    const firstCurrency=document.querySelector('select[name^="checkout_addons[currency]"]') || document.querySelector('select[name^="base_currency"]');
    return firstCurrency ? firstCurrency.innerHTML : '<option value="TRY">TRY</option><option value="USD">USD</option>';
  }
  function addonRowHtml(idx,data){
    data=data||{};
    const currencyOptions=addonCurrencyOptions();
    const selectedCurrency=String(data.currency||'TRY').toUpperCase();
    const options=currencyOptions.replace(new RegExp('value="'+selectedCurrency+'"','i'),'value="'+selectedCurrency+'" selected');
    return [
      '<td class="center"><input type="hidden" name="checkout_addons[active]['+idx+']" value="0"><input type="checkbox" name="checkout_addons[active]['+idx+']" value="1" checked></td>',
      '<td><input type="hidden" name="checkout_addons[catalog_id]['+idx+']" value="'+esc(data.catalogId||'')+'"><input type="hidden" name="checkout_addons[provision_key]['+idx+']" value="'+esc(data.provisionKey||'')+'"><input type="hidden" name="checkout_addons[provision_value]['+idx+']" value="'+esc(data.provisionValue||'')+'"><input name="checkout_addons[key]['+idx+']" value="'+esc(data.key||'')+'" placeholder="site-tasima-kurulum"></td>',
      '<td><input name="checkout_addons[name]['+idx+']" value="'+esc(data.name||'')+'" placeholder="Sitemi hostinge taşı ve kur"></td>',
      '<td><input name="checkout_addons[description]['+idx+']" value="'+esc(data.description||'')+'" placeholder="Mevcut sitenin taşıma ve temel kurulum hizmeti"></td>',
      '<td><input name="checkout_addons[price]['+idx+']" value="'+esc(data.price||'')+'" inputmode="decimal" placeholder="149.00"></td>',
      '<td><select name="checkout_addons[currency]['+idx+']">'+options+'</select></td>',
      '<td class="center"><button type="button" class="ao-mini-btn danger" data-v249-remove-row>Sil</button></td>'
    ].join('');
  }
  const blocks={
    button:'<p><a class="ao-product-button" href="#siparis">Hemen Sipariş Ver</a></p>',
    features:'<div class="ao-product-feature-grid"><div><strong>Yüksek Performans</strong><p>SSD altyapı ve optimize sistem.</p></div><div><strong>Güvenli Altyapı</strong><p>SSL ve güvenlik katmanı.</p></div><div><strong>Uzman Destek</strong><p>Profesyonel destek ekibi.</p></div></div>',
    faq:'<div class="ao-product-faq"><h3>Sık Sorulan Sorular</h3><p><strong>Kurulum ne kadar sürer?</strong><br>Ödeme sonrası otomatik veya manuel teslim edilir.</p></div>',
    table:'<table><thead><tr><th>Özellik</th><th>Değer</th></tr></thead><tbody><tr><td>Disk</td><td>10 GB SSD</td></tr><tr><td>SSL</td><td>Ücretsiz</td></tr></tbody></table>',
    notice:'<div class="ao-product-notice"><strong>Bilgi:</strong> Bu paket büyüyen projeler için önerilir.</div>',
    media:'<figure><img src="/public/assets/img/placeholder-product.svg" alt="Ürün görseli"><figcaption>Ürün görseli açıklaması</figcaption></figure>',
    pricing:'<section class="ao-product-pricing-block"><h3>Öne Çıkan Paket</h3><p>Yüksek performans, güvenli altyapı ve uzman destek.</p><p><a class="ao-product-button" href="#siparis">Hemen Sipariş Ver</a></p></section>',
    hero:'<section><h2>Profesyonel Ürün Sayfası</h2><p>Satış odaklı açıklama, özellikler ve güçlü çağrı butonu.</p><p><a class="ao-product-button" href="#siparis">Hemen Başla</a></p></section>',
    compare:'<table><thead><tr><th>Özellik</th><th>Başlangıç</th><th>Kurumsal</th></tr></thead><tbody><tr><td>Disk</td><td>5 GB</td><td>50 GB</td></tr><tr><td>Destek</td><td>Standart</td><td>Öncelikli</td></tr></tbody></table>',
    steps:'<ol><li>Sipariş oluşturulur.</li><li>Ödeme kontrol edilir.</li><li>Hizmet otomatik veya manuel teslim edilir.</li></ol>'
  };
  document.addEventListener('click',function(e){
    const addPrice=e.target.closest('[data-v249-add-price]');
    if(addPrice){
      const body=document.querySelector('[data-v249-pricing-body]');
      const picker=document.querySelector('[data-v249-price-cycle-picker]');
      if(!body||!picker) return;
      const cycle=picker.value;
      if(!cycle) return;
      const existing=body.querySelector('[data-price-cycle="'+cycle+'"]');
      if(existing){
        existing.scrollIntoView({block:'center',behavior:'smooth'});
        const input=existing.querySelector('input[name^="price_base"]');
        if(input) input.focus();
        return;
      }
      const empty=body.querySelector('[data-v249-empty-pricing]');
      if(empty) empty.remove();
      const label=picker.options[picker.selectedIndex]?.text || cycle;
      const firstCurrency=document.querySelector('select[name^="base_currency"]') || document.querySelector('select[name^="checkout_addons[currency]"]');
      const currencyOptions=firstCurrency ? firstCurrency.innerHTML : '<option value="TRY">TRY</option><option value="USD">USD</option>';
      const tr=document.createElement('tr');
      tr.dataset.priceCycle=cycle;
      tr.innerHTML=[
        '<th><select class="v249-price-cycle-select" data-v249-price-cycle-select>'+Array.from(picker.options).map(function(opt){return '<option value="'+opt.value+'" '+(opt.value===cycle?'selected':'')+'>'+opt.text+'</option>';}).join('')+'</select></th>',
        '<td><select name="base_currency['+cycle+']">'+currencyOptions+'</select></td>',
        '<td><input class="js-price-base" data-cycle="'+cycle+'" data-kind="setup" name="setup_base['+cycle+']" inputmode="decimal"></td>',
        '<td><input class="js-price-base" data-cycle="'+cycle+'" data-kind="price" name="price_base['+cycle+']" inputmode="decimal"></td>',
        '<td><input class="js-price-try" data-cycle="'+cycle+'" data-kind="setup" name="setup_try['+cycle+']" inputmode="decimal"></td>',
        '<td><input class="js-price-try" data-cycle="'+cycle+'" data-kind="price" name="price_try['+cycle+']" inputmode="decimal"></td>',
        '<td class="center"><input type="hidden" name="price_active[USD]['+cycle+']" value="0"><input type="checkbox" name="price_active[USD]['+cycle+']" value="1" checked></td>',
        '<td class="center"><button type="button" class="ao-mini-btn danger" data-v249-remove-price>Sil</button></td>'
      ].join('');
      body.appendChild(tr);
      const input=tr.querySelector('input[name^="price_base"]');
      if(input) input.focus();
      return;
    }
    const removePrice=e.target.closest('[data-v249-remove-price]');
    if(removePrice){
      const row=removePrice.closest('[data-price-cycle]');
      const body=row?.closest('[data-v249-pricing-body]');
      const form=removePrice.closest('form');
      const cycle=row?.dataset.priceCycle;
      if(form&&cycle){
        const hidden=document.createElement('input');
        hidden.type='hidden';
        hidden.name='price_removed['+cycle+']';
        hidden.value='1';
        form.appendChild(hidden);
      }
      if(row) row.remove();
      if(body && !body.querySelector('tr')){
        const tr=document.createElement('tr');
        tr.setAttribute('data-v249-empty-pricing','');
        tr.innerHTML='<td colspan="8" class="v249-empty-row">Henüz fiyatlandırma yok. Periyot seçip “Fiyatlandırma Ekle” düğmesini kullanın.</td>';
        body.appendChild(tr);
      }
      return;
    }
    const addAddon=e.target.closest('[data-v249-add-addon]');
    if(addAddon){
      const body=document.querySelector('[data-v249-addons-body]');
      if(!body) return;
      const empty=body.querySelector('[data-v249-empty-addons]');
      if(empty) empty.remove();
      const idx=parseInt(body.dataset.nextIndex||'0',10);
      body.dataset.nextIndex=String(idx+1);
      const tr=document.createElement('tr');
      tr.innerHTML=addonRowHtml(idx,{});
      body.appendChild(tr);
      const first=tr.querySelector('input[name^="checkout_addons[key]"]');
      if(first) first.focus();
      return;
    }
    const addCatalogAddon=e.target.closest('[data-v249-add-catalog-addon]');
    if(addCatalogAddon){
      const body=document.querySelector('[data-v249-addons-body]');
      const picker=document.querySelector('[data-v249-addon-catalog-picker]');
      if(!body||!picker) return;
      const opt=picker.options[picker.selectedIndex];
      if(!opt||!opt.value) return;
      const key=opt.dataset.key||'';
      const existing=key ? body.querySelector('input[name^="checkout_addons[key]"][value="'+key.replace(/"/g,'\\"')+'"]') : null;
      if(existing){
        existing.scrollIntoView({block:'center',behavior:'smooth'});
        existing.focus();
        return;
      }
      const empty=body.querySelector('[data-v249-empty-addons]');
      if(empty) empty.remove();
      const idx=parseInt(body.dataset.nextIndex||'0',10);
      body.dataset.nextIndex=String(idx+1);
      const tr=document.createElement('tr');
      tr.innerHTML=addonRowHtml(idx,{
        catalogId:opt.value,
        key:key,
        name:opt.dataset.name||opt.textContent.trim(),
        description:opt.dataset.description||'',
        price:opt.dataset.price||'',
        currency:opt.dataset.currency||'TRY',
        provisionKey:opt.dataset.provisionKey||'',
        provisionValue:opt.dataset.provisionValue||''
      });
      body.appendChild(tr);
      const price=tr.querySelector('input[name^="checkout_addons[price]"]');
      if(price) price.focus();
      return;
    }
    const addCustomField=e.target.closest('[data-v249-add-custom-field]');
    if(addCustomField){
      const body=document.querySelector('[data-v249-custom-fields-body]');
      if(!body) return;
      const empty=body.querySelector('[data-v249-empty-custom-fields]');
      if(empty) empty.remove();
      const idx=parseInt(body.dataset.nextIndex||'0',10);
      body.dataset.nextIndex=String(idx+1);
      const tr=document.createElement('tr');
      tr.innerHTML=[
        '<td class="center"><input type="hidden" name="custom_fields[active]['+idx+']" value="0"><input type="checkbox" name="custom_fields[active]['+idx+']" value="1" checked></td>',
        '<td class="center"><input type="hidden" name="custom_fields[required]['+idx+']" value="0"><input type="checkbox" name="custom_fields[required]['+idx+']" value="1"></td>',
        '<td><input name="custom_fields[key]['+idx+']" placeholder="radio_ip"></td>',
        '<td><input name="custom_fields[label]['+idx+']" placeholder="Radyo IP Adresi"></td>',
        '<td><select name="custom_fields[type]['+idx+']"><option value="text">Metin</option><option value="textarea">Uzun Metin</option><option value="url">URL</option><option value="tel">Telefon</option><option value="email">E-posta</option><option value="number">Sayı</option><option value="select">Seçim</option><option value="file">Dosya / Logo</option></select></td>',
        '<td><input name="custom_fields[options]['+idx+']" placeholder="Seçim tipinde satır satır veya virgülle"></td>',
        '<td class="center"><button type="button" class="ao-mini-btn danger" data-v249-remove-row>Sil</button></td>'
      ].join('');
      body.appendChild(tr);
      const first=tr.querySelector('input[name^="custom_fields[key]"]');
      if(first) first.focus();
      return;
    }
    const removeRow=e.target.closest('[data-v249-remove-row]');
    if(removeRow){
      const row=removeRow.closest('tr');
      const body=row?.closest('[data-v249-custom-fields-body],[data-v249-addons-body]');
      if(row) row.remove();
      if(body && !body.querySelector('tr')){
        const tr=document.createElement('tr');
        const isAddon=body.matches('[data-v249-addons-body]');
        tr.setAttribute(isAddon ? 'data-v249-empty-addons' : 'data-v249-empty-custom-fields','');
        tr.innerHTML=isAddon
          ? '<td colspan="7" class="v249-empty-row">Henüz ek paket yok. Yeni ek paket oluşturmak için “Ek Paket Ekle” düğmesini kullanın.</td>'
          : '<td colspan="7" class="v249-empty-row">Henüz özel alan yok. Yeni alan eklemek için “Özel Alan Ekle” düğmesini kullanın.</td>';
        body.appendChild(tr);
      }
      return;
    }
    const studio=e.target.closest('[data-content-studio]'); if(!studio) return;
    const visual=studio.querySelector('[data-editor-visual]'); const html=studio.querySelector('[data-editor-html]');
    const btn=e.target.closest('button'); if(!btn) return;
    if(btn.dataset.mode){
      if(btn.dataset.mode==='html'){ html.value=visual.innerHTML.trim(); studio.classList.add('is-html'); }
      else { visual.innerHTML=html.value; studio.classList.remove('is-html'); }
      studio.querySelectorAll('[data-mode]').forEach(b=>b.classList.toggle('active',b===btn)); return;
    }
    if(btn.dataset.cmd){ visual.focus(); document.execCommand(btn.dataset.cmd,false,btn.dataset.value||null); html.value=visual.innerHTML.trim(); return; }
    if(btn.dataset.action==='link'){ const u=prompt('Bağlantı URL'); if(u){ visual.focus(); document.execCommand('createLink',false,u); html.value=visual.innerHTML.trim(); } return; }
    if(btn.dataset.action==='image'){ const u=prompt('Görsel URL'); if(u){ visual.focus(); insertHtml('<figure><img src=\"'+u.replace(/\"/g,'')+'\" alt=\"Ürün görseli\"><figcaption>Görsel açıklaması</figcaption></figure>'); html.value=visual.innerHTML.trim(); } return; }
    if(btn.dataset.block){ visual.focus(); insertHtml(blocks[btn.dataset.block]||''); html.value=visual.innerHTML.trim(); return; }
  });
  document.addEventListener('change',function(e){
    const select=e.target.closest('[data-v249-price-cycle-select]');
    if(!select) return;
    const row=select.closest('[data-price-cycle]');
    const body=row?.closest('[data-v249-pricing-body]');
    if(!row||!body) return;
    const oldCycle=row.dataset.priceCycle;
    const newCycle=select.value;
    if(!newCycle||newCycle===oldCycle) return;
    const duplicate=body.querySelector('[data-price-cycle="'+newCycle+'"]');
    if(duplicate&&duplicate!==row){
      select.value=oldCycle;
      alert('Bu periyot zaten ekli.');
      return;
    }
    row.dataset.priceCycle=newCycle;
    row.querySelectorAll('[name]').forEach(function(input){
      input.name=input.name.replace('['+oldCycle+']','['+newCycle+']');
    });
    row.querySelectorAll('[data-cycle]').forEach(function(input){
      input.dataset.cycle=newCycle;
    });
    const removed=document.querySelector('input[name="price_removed['+oldCycle+']"]');
    if(removed) removed.remove();
  });
  document.addEventListener('input',function(e){
    const studio=e.target.closest('[data-content-studio]'); if(!studio) return;
    const visual=studio.querySelector('[data-editor-visual]'); const html=studio.querySelector('[data-editor-html]');
    if(e.target.matches('[data-editor-visual]')) html.value=visual.innerHTML.trim();
  });
  document.addEventListener('submit',function(e){
    e.target.querySelectorAll('[data-content-studio]').forEach(function(studio){
      const visual=studio.querySelector('[data-editor-visual]'); const html=studio.querySelector('[data-editor-html]');
      if(!studio.classList.contains('is-html')) html.value=visual.innerHTML.trim();
    });
  },true);
})();
