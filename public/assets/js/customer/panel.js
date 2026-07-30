(function(){
  document.addEventListener('click',function(e){
    var toggle=e.target.closest('[data-customer-menu-toggle]');
    if(toggle) document.body.classList.toggle('customer-sidebar-open');
  });
})();
