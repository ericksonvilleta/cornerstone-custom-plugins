jQuery(document).scroll(function () {
    var sticky = jQuery('#sticky'); h2 = false; found_one = false;

    if (!sticky.length)
        sticky = jQuery('<div id="sticky">').appendTo('.woocommerce-layout__header');

    jQuery('.hide-from-categories h2').each(function () {
        if (jQuery(this).parents('#sticky').length) return true;

        if (jQuery(this).offset().top > (jQuery(window).scrollTop() + 60))
            return false; // Exit loop

        h2 = jQuery(this);
        found_one = true;
    });
    if(found_one){
      sticky.empty().append(h2.clone());
    }else{
      sticky.empty();
    }

});
