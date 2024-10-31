jQuery(document.body).on('update_checkout', function(e){
    e.stopImmediatePropagation();
});
jQuery(document).ready(function($){
  wc_checkout_params.is_checkout = false;
});
