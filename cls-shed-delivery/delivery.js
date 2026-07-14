(function($) {
    "use strict";
    function init() {
        var input = document.getElementById("cls_delivery_address");
        if (!input || $(input).hasClass('ready')) return;

        if (typeof google === 'object' && google.maps && google.maps.places) {
            var ac = new google.maps.places.Autocomplete(input, {
                componentRestrictions: { country: "us" },
                fields: ["address_components", "geometry", "formatted_address"]
            });
            $(input).addClass('ready');

            ac.addListener("place_changed", function() {
                var p = ac.getPlace();
                if (!p.geometry) return;
                var z = "";
                for (var i = 0; i < p.address_components.length; i++) {
                    if (p.address_components[i].types.includes("postal_code")) { z = p.address_components[i].short_name; break; }
                }

                $("#cls_res").html("Calculating...").show();
                var ds = new google.maps.DistanceMatrixService();
                ds.getDistanceMatrix({
                    origins: [clsData.origin],
                    destinations: [p.formatted_address],
                    travelMode: "DRIVING"
                }, function(r, s) {
                    if (s === "OK" && r.rows[0].elements[0].status === "OK") {
                        var miles = parseFloat((r.rows[0].elements[0].distance.value * 0.000621371).toFixed(1));
                        $("#cls_res").html("<strong>Distance:</strong> " + miles + " miles.").show();
                        $.post(clsData.ajax, { action: "cls_save_distance", distance: miles, zip: z, address: p.formatted_address }, function() {
                            $(document.body).trigger('wc_update_cart');
                            $(document.body).trigger('update_checkout');
                        });
                    }
                });
            });

            // Wipe session if input is cleared manually by the user
            $(input).on('input', function() {
                if ($(this).val() === "") {
                    $.post(clsData.ajax, { action: "cls_save_distance", distance: "", zip: "", address: "" }, function() {
                        $(document.body).trigger('wc_update_cart');
                    });
                }
            });
        }
    }
    $(document).ready(init);
    $(document.body).on('updated_cart_totals', init);
})(jQuery);