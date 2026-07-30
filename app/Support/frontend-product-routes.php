<?php
// v23.3.5 public product routes
if ($route === 'urunler' || $route === 'products') {
    ao_v2332_ensure_schema();
    ao_v2510_seed_default_config_options();
    site_view('products/index', [
        'pageTitle'=>'Ürünler',
        'metaTitle'=>'Ürünler ve Dijital Hizmetler - Ahost One',
        'metaDescription'=>'Hosting, domain, web tasarım, mobil uygulama, SSL, sunucu ve dijital hizmet paketlerini Ahost One üzerinden karşılaştırın.',
        'canonicalUrl'=>url('urunler'),
        'groups'=>ao_v2335_product_groups(),
        'products'=>ao_v2335_products(),
        'selectedGroup'=>null
    ]); exit;
}
if (preg_match('#^urun-grubu/([a-z0-9\-_]+)$#', $route, $m) || preg_match('#^product-group/([a-z0-9\-_]+)$#', $route, $m)) {
    ao_v2332_ensure_schema();
    ao_v2510_seed_default_config_options();
    $group=ao_v2335_group_by_slug($m[1]);
    if(!$group){ http_response_code(404); site_view('errors/404', ['pageTitle'=>'Ürün Grubu Bulunamadı']); exit; }
    $groupDesc=trim((string)($group['description'] ?? ''));
    if($groupDesc==='') $groupDesc=$group['name'].' ürünlerini, paketlerini ve fiyatlarını Ahost One üzerinden inceleyin.';
    site_view('products/index', [
        'pageTitle'=>$group['name'],
        'metaTitle'=>$group['name'].' - Ahost One',
        'metaDescription'=>mb_substr(strip_tags($groupDesc),0,160,'UTF-8'),
        'canonicalUrl'=>url('urun-grubu/'.$m[1]),
        'groups'=>ao_v2335_product_groups(),
        'products'=>ao_v2335_products($m[1]),
        'selectedGroup'=>$group
    ]); exit;
}
if (preg_match('#^urun/([a-z0-9\-_]+)$#', $route, $m) || preg_match('#^product/([a-z0-9\-_]+)$#', $route, $m)) {
    ao_v2332_ensure_schema();
    ao_v2510_seed_default_config_options();
    $product=ao_v2335_product_by_slug($m[1]);
    if(!$product){ http_response_code(404); site_view('errors/404', ['pageTitle'=>'Ürün Bulunamadı']); exit; }
    $plainDesc=function_exists('ao_v2400_plain_from_html') ? ao_v2400_plain_from_html($product['description'] ?? '', 500) : strip_tags((string)($product['description'] ?? ''));
    $productMetaDesc=trim((string)($product['meta_description'] ?? '')) ?: trim((string)($product['short_description'] ?? '')) ?: trim((string)$plainDesc);
    $productMetaDesc=mb_substr(strip_tags($productMetaDesc),0,160,'UTF-8');
    $productSchema=[
        '@context'=>'https://schema.org',
        '@type'=>'Product',
        'name'=>(string)($product['name'] ?? ''),
        'description'=>$productMetaDesc,
        'url'=>url('urun/'.($product['slug'] ?? $m[1])),
        'brand'=>['@type'=>'Brand','name'=>(string)admin_setting('site_name','Ahost One')]
    ];
    $primaryPrice=function_exists('ao_v2335_primary_price') ? ao_v2335_primary_price($product) : null;
    if(is_array($primaryPrice) && (float)($primaryPrice['amount'] ?? 0) > 0){
        $productSchema['offers']=[
            '@type'=>'Offer',
            'priceCurrency'=>'TRY',
            'price'=>(string)number_format((float)$primaryPrice['amount'],2,'.',''),
            'availability'=>'https://schema.org/InStock',
            'url'=>url('urun/'.($product['slug'] ?? $m[1]))
        ];
    }
    site_view('products/detail', [
        'pageTitle'=>$product['name'],
        'metaTitle'=>trim((string)($product['seo_title'] ?? '')) ?: (($product['name'] ?? 'Ürün').' - Ahost One'),
        'metaDescription'=>$productMetaDesc,
        'canonicalUrl'=>url('urun/'.($product['slug'] ?? $m[1])),
        'schemaJsonLd'=>json_encode($productSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'product'=>$product,
        'pricing'=>ao_v2335_product_pricing((int)$product['id']),
        'configOptions'=>ao_v2510_product_config_options((int)$product['id']),
        'customFields'=>function_exists('ao_v249_product_custom_fields') ? ao_v249_product_custom_fields((int)$product['id'], (int)($product['group_id'] ?? 0)) : []
    ]); exit;
}
