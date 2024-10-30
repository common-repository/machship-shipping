(function($) {
    'use strict';
    var debounce = function debounce(func, delay){
        var inDebounce;
        return function(){
          var context = this;
          var args = arguments;
          clearTimeout(inDebounce);
          inDebounce = setTimeout(function(){
            return func.apply(context, args)
          }, delay);
        }
      }

    // Adding new Address
    // adding new address
    $(document).on('click', '.wcmca_add_new_address_button', function(e) {
        var interval = setInterval(function() {
            if ($('select[name=wcmca_billing_state]').html() != '' && typeof($('select[name=wcmca_billing_state]').html()) !== 'undefined') {
                clearInterval(interval);
                setTimeout(function() {
                    // reset old data to empty
                    $('input[name="wcmca_shipping_city"]').data('old', '');
                    $('input[name="wcmca_shipping_postcode"]').data('old', '');
                    $('input[name="wcmca_billing_city"]').data('old', '');
                    $('input[name="wcmca_billing_postcode"]').data('old', '');
                    $('p.wcmca_save_address_button_container button').prop('disabled', true);
                }, 500);
            }
        }, 500);
    });

    $(document).on('click', '.wcmca_edit_address_button', function() {
        var btn = $(this);
        var type = btn.data('type');

        // add delay to get the value of input
        setTimeout(function() {

            // a flag to know what modal is loaded
            if (type == 'shipping') {
                var modal = $('#wcmca_address_form_container_shipping'),
                    city = modal.find('input[name="wcmca_shipping_city"]'),
                    cityVal = city.val(),
                    postcode = modal.find('input[name="wcmca_shipping_postcode"]'),
                    postcodeVal = postcode.val();

            } else {
                var modal = $('#wcmca_address_form_container_billing'),
                    city = modal.find('input[name="wcmca_billing_city"]'),
                    cityVal = city.val(),
                    postcode = modal.find('input[name="wcmca_billing_postcode"]'),
                    postcodeVal = postcode.val();
            }

            modal.find('p.wcmca_save_address_button_container').find('button:eq(0)').prop({ 'disabled': true, 'style': '' });

            if (cityVal && postcodeVal) {

                var imgLoader = modal.find('img').clone();
                imgLoader.attr('style', 'margin:inherit; display:block;');
                postcode.closest('span').append(imgLoader)

                $.ajax({
                    type: "post",
                    dataType: "json",
                    url: woo_machship.ajaxurl,
                    data: { action: "get_location", keyword: postcodeVal, nonce: woo_machship.nonce, address_postcode: true, address_suburb: cityVal },
                    success: function(response) {

                        if (response.result == "No location found") {
                            alert("Invalid Postcode or Suburb - Please check spelling and try again.");

                            city.closest('p').addClass('woocommerce-invalid animate__shakeX');
                            postcode.closest('p').addClass('woocommerce-invalid animate__shakeX');
                        } else {
                            if (response.result.suburbs) {}
                            if (response.result == "No location found") {
                                alert("Invalid Postcode or Suburb - Please check spelling and try again.");
                            } else {
                                modal.find('p.wcmca_save_address_button_container').find('button:eq(0)').prop({ 'disabled': false, 'style': '' });
                                city.closest('p').removeClass('woocommerce-invalid animate__shakeX');
                                postcode.closest('p').removeClass('woocommerce-invalid animate__shakeX');
                            }
                        }

                        postcode.closest('span').find('img').remove();
                    }
                });
            }
        }, 200);
    });

    // adding suburb/
    $(document).on('keyup', 'input[name="wcmca_shipping_city"], input[name="wcmca_billing_city"]', debounce(function() {
        var keyword = $(this).val();
        var old_keyword = $(this).data('old');
        var postcode = $('input[name="wcmca_shipping_postcode"]').val();
        var inputArea = $(this);
        var inptname = inputArea.attr('name');

        if (inptname.indexOf('billing') >= 0) {
            postcode = $('input[name="wcmca_billing_postcode"]').val();
        }

        inputArea.parents('div').find('p.wcmca_save_address_button_container').find('button:eq(0)').prop('disabled', true);

        // control spam
        if (!keyword || keyword == '' || old_keyword == keyword || !postcode) {
            return;
        }

        var imgLoader = inputArea.parents('div').find('img').clone();

        // update the old data for this input
        $(this).data('old', keyword);

        if (keyword.length > 3) {
            imgLoader.attr('style', 'margin:inherit; display:block;');
            inputArea.closest('span').append(imgLoader)
            $.ajax({
                type: "post",
                dataType: "json",
                url: woo_machship.ajaxurl,
                data: { action: "get_location", keyword: keyword, nonce: woo_machship.nonce, address_suburb: true },
                success: function(response) {

                    if (response.result == "No location found") {
                        alert("Invalid Postcode or Suburb - Please check spelling and try again.");

                        inputArea.closest('p').addClass('woocommerce-invalid animate__shakeX');
                    } else {
                        if (!response.result.includes(postcode)) {
                            alert("Invalid Postcode or Suburb - Please check spelling and try again.");
                        } else {
                            inputArea.parents('div').find('p.wcmca_save_address_button_container').find('button:eq(0)').prop('disabled', false);
                            inputArea.closest('p').removeClass('woocommerce-invalid animate__shakeX');
                        }

                    }

                    inputArea.closest('span').find('img').remove();
                }
            });
        }
    }, 700));

    $(document).on('keyup', 'input[name="wcmca_shipping_postcode"], input[name="wcmca_billing_postcode"]', debounce(function() {
        var keyword = $(this).val();
        var old_keyword = $(this).data('old');
        var suburb = $('input[name="wcmca_shipping_city"]').val();
        var inputArea = $(this);
        var inptname = inputArea.attr('name');

        if (inptname.indexOf('billing') >= 0) {
            suburb = $('input[name="wcmca_billing_city"]').val();
        }

        inputArea.parents('div').find('p.wcmca_save_address_button_container').find('button:eq(0)').prop('disabled', true);

        // control spam
        if (!keyword || keyword == '' || old_keyword == keyword || !suburb) {
            return;
        }

        var imgLoader = inputArea.parents('div').find('img').clone();

        // update the old data for this input
        $(this).data('old', keyword);

        if (keyword.length >= 3) {
            imgLoader.attr('style', 'margin:inherit; display:block;');
            inputArea.closest('span').append(imgLoader)
            $.ajax({
                type: "post",
                dataType: "json",
                url: woo_machship.ajaxurl,
                data: { action: "get_location", keyword: keyword, nonce: woo_machship.nonce, address_postcode: true, address_suburb: suburb },
                success: function(response) {

                    if (response.result == "No location found") {
                        alert("Invalid Postcode or Suburb - Please check spelling and try again.");

                        inputArea.closest('p').addClass('woocommerce-invalid animate__shakeX');
                    } else {
                        if (response.result.suburbs) {}
                        if (response.result == "No location found") {
                            alert("Invalid Postcode or Suburb - Please check spelling and try again.");
                        } else {

                            inputArea.parents('div').find('p.wcmca_save_address_button_container').find('button:eq(0)').prop('disabled', false);
                            inputArea.closest('p').removeClass('woocommerce-invalid animate__shakeX');
                        }
                    }

                    inputArea.closest('span').find('img').remove();
                }
            });
        }
    }, 700));

})(jQuery);