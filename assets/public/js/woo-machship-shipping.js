(function( $ ) {
  'use strict';
  var components = {};
  var cart_update = true;
  window.cart_updated = false;
  window.update_checkout = true;
  window.is_processing = false;

  /**
   * All of the code for your public-facing JavaScript source
   * should reside in this file.
   *
   * Note: It has been assumed you will write jQuery code here, so the
   * $ function reference has been prepared for usage within the scope
   * of this function.
   *
   * This enables you to define handlers, for when the DOM is ready:
   *
   * $(function() {
   *
   * });
   *
   * When the window is loaded:
   *
   * $( window ).load(function() {
   *
   * });
   *
   * ...and/or other possibilities.
   *
   * Ideally, it is not considered best practise to attach more than a
   * single DOM-ready or window-load handler for a particular page.
   * Although scripts in the WordPress core, Plugins and Themes may be
   * practising this, we should strive to set a better example in our own work.
   */
    if( jQuery('.woo-machship-shipping-quote-form').length > 0 ) {
      if(woo_machship.page_is == 'product'){
        if (jQuery('input.variation_id').length > 0) {
          if(jQuery('input.variation_id').val() == '' || jQuery('input.variation_id').val() < 1){
            jQuery('.no_variation td, .no_variation span, div.no_variation').html('Please select from the available product options to generate a delivery quote.');
            jQuery('#qq-popup-button').attr('rel','').addClass('qq-disabled');
            jQuery('#sqf-shipping-cost').hide();
            jQuery('.sqf-suburb-box').hide();
          }
        } else  if (jQuery('.component_options_select').length > 0) {
          jQuery('.component_options_select').each(function() {
            var selected = '';
            var attr_id = $(this).attr('id');

            jQuery.each(jQuery("#"+attr_id).data('options_data'), function(index, value) {
              selected = value.is_selected ? value.option_id : '';
            })

            var id = attr_id.replace('component_options_', '');

            if(!components.hasOwnProperty('key_' + id) && selected != '') {
              // add to array
              components['key_' + id] = selected;
            }
          });

          woo_machship_find_shipping_costs(false);
        } else {
          woo_machship_find_shipping_costs(false);
          jQuery('#qq-popup-button').attr('rel','modal:open').removeClass('qq-disabled');
        }
      } else {
        woo_machship_find_shipping_costs(true);
      }
    }

  // for product variation
  jQuery('input.variation_id').on('change', function(){
    if( '' != jQuery('input.variation_id').val() ) {
      jQuery('#sqf-shipping-cost').hide();
      jQuery('.no_variation').html('');
      jQuery('.sqf-suburb-box').show();
      jQuery('#qq-popup-button').attr('rel','modal:open').removeClass('qq-disabled');
      if (jQuery('#toLocationID').val() != '') {
        woo_machship_find_shipping_costs(false);
      }
    }else{
      jQuery('#qq-popup-button').attr('rel','').addClass('qq-disabled');
      jQuery('.no_variation').html('Please select from the available product options to generate a delivery quote.');
      jQuery('.sqf-suburb-box').hide();
      jQuery('#sqf-shipping-cost').hide();
    }
  });

  window.dcss = jQuery('#disable_checkout_suburb_search').val();

  jQuery('#billing_country').on('change', function(){
    check_country();
  });

  jQuery('#shipping_country').on('change', function(){
    check_country();
  });

  // for composite product
  jQuery('.component_options_select').on('change', function () {

    var value = jQuery(this).val();
    var id = jQuery(this).attr('id').replace('component_options_', '');

    if(!components.hasOwnProperty('key_' + id) && value != "") {
      // add to array
      components['key_' + id] = value;
    } else {
      // remove
      delete components['key_' + id];
    }
    woo_machship_find_shipping_costs(false);

  });

  jQuery(document).on('click','button[name=calc_shipping]',function(e) {
    e.preventDefault();
    woo_machship_find_shipping_costs(true);
    jQuery(this).prop("disabled", true);
  });

  jQuery(document).on('click', '.shipping-calculator-button', function() {
    if(typeof window.dcss === 'undefined'){
      window.dcss = jQuery('#disable_checkout_suburb_search').val();
    }
    if(window.dcss != "yes"){
      if ($('.additional-elements').length <= 0) {
        $("#calc_shipping_postcode_field").append(
          '\
            <div class="additional-elements">\
              <p class="small-text">Enter your postcode and/or suburb to display a list of suburbs.</p>\
              <div id="sqf-suburb-list"></div>\
              <input type="hidden" id="toSuburb" name="ToSuburb" value="">\
              <input type="hidden" id="toState" name="ToState" value="">\
              <input type="hidden" id="toPostcode" name="ToPostcode" value="">\
              <input type="hidden" id="toLocationID" name="ToLocationID" value="">\
            </div>\
          '
        );
      }
    }
  });

  jQuery( document ).on( 'click', '#sqf-suburb-list .woo-machship-suburb-item', function() {
    var suburb      = $( this ).attr( 'data-suburb' );
    var state       = $( this ).attr( 'data-state' );
    var postcode    = $( this ).attr( 'data-postcode' );
    var locationID  = $( this ).attr( 'data-locationid' );

    $('#hidden-ToLocationID').val(locationID);

    if( woo_machship.page_is == 'checkout' ) {
      if( $('#ship-to-different-address-checkbox').is(':checked') ) {

        $( '#shipping_city' ).val( suburb );
        $( '#shipping_state' ).val( state ).trigger( "change" );
        $( '#shipping_state' ).select2();
        $( '#shipping_postcode' ).val( postcode );

        // add delay to give time for select2 function to reflect
        setTimeout(() => {
          $( '#shipping_state' ).closest('span').find('.select2-container').attr('style', 'width: 100%;');
          $( '#shipping_state' ).closest('span').find('.select2-container').removeClass('select2-container--disabled'); // fix to make the select looks not disable
        }, 200);

        // try to update inner form of wordpress
        $( 'input[name="shipping_city"]' ).val( suburb );
        $( 'input[name="shipping_state"]' ).val( state );
        $( 'input[name="shipping_postcode"]' ).val( postcode );

      } else {

        $( '#billing_city' ).val( suburb );
        $( '#billing_state' ).val( state ).trigger( "change" );
        $( '#billing_state' ).select2();
        $( '#billing_postcode' ).val( postcode );

        // add delay to give time for select2 function to reflect
        setTimeout(() => {
          $( '#billing_state' ).closest('span').find('.select2-container').attr('style', 'width: 100%;'); // fix to make the select state full width
          $( '#billing_state' ).closest('span').find('.select2-container').removeClass('select2-container--disabled'); // fix to make the select looks not disable
        }, 200);

        // try to update inner form of wordpress
        $( 'input[name="billing_city"]' ).val( suburb );
        $( 'input[name="billing_state"]' ).val( state );
        $( 'input[name="billing_postcode"]' ).val( postcode );

      }

      $('#sqf-suburb-list').hide();
    }

    if (woo_machship.page_is === 'cart') {
      $('#calc_shipping_state').removeAttr('disabled');
      $('#calc_shipping_state').val( state ).trigger( "change" );
      // $('#calc_shipping_state').select2();
      $('#calc_shipping_city').val( suburb );
    }

    if (woo_machship.page_is === 'product') {
      $('#sqf-suburb-list').hide();
    }

    var data = {
      'action': 'woo_machship_set_shipping_suburb',
      'tosuburb':  suburb,
      'tostate':  state,
      'topostcode':  postcode,
      'tolocationid':  locationID,
      'nonce': woo_machship.nonce
    };

    if(jQuery('input[name=is_residential]').length > 0){
      var is_residential = jQuery('input[name=is_residential]').prop('checked');
    }else{
      var is_residential = false;
    }

    data['is_residential'] = is_residential;

    $( '#woo-machship-ajax-loader' ).fadeIn( 'slow' );
    var btnText = $('button[name="calc_shipping"]').text();
    if (woo_machship.page_is == 'cart') {
        $('#sqf-suburb-list').find('.woo-machship-suburb-item').prop('disabled', true);
        $('button[name="calc_shipping"]').prop('disabled', true);
        $('button[name="calc_shipping"]').text(btnText + '...');
    } else {
        $('button[name="calc_shipping"]').prop('disabled', false);
    }

    var interval = setInterval(function() {
        //delay this until ?wc-ajax=update_order_review is finished
        if (jQuery.active == 0) {
            clearInterval(interval);
            $.post(woo_machship.ajaxurl, data, function(response) {
                // Set the values
                var suburb_complete = suburb + ' ' + state + ' - ' + postcode;
                $('#sqf-to-suburb1').val(suburb_complete);
                $('#sqf-to-suburb1').data('old', suburb_complete);
                $('#toSuburb').val(suburb);
                $('#toState').val(state);
                $('#toPostcode').val(postcode);
                $('#toLocationID').val(locationID);
                $('#woo-machship-ajax-loader').fadeOut('slow');

                // find the shipping
                if (woo_machship.page_is == 'cart') {
                    jQuery('button[name=calc_shipping]').prop('disabled', false);
                    $('#sqf-suburb-list').find('.woo-machship-suburb-item').prop('disabled', false);
                    $('button[name="calc_shipping"]').text(btnText);
                } else {
                    woo_machship_find_shipping_costs(cart_update);
                }
            });
        }
    }, 1000);
  });

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

  if(woo_machship.page_is == 'product'){

    $.ajaxSetup({
      headers: {
          'X-WP-Nonce': woo_machship.nonce ?? '',
          // Add your custom headers here
      }
    });

    jQuery('.qty').on('change', function(){
      woo_machship_find_shipping_costs(false);
    });

    jQuery( document ).on('keyup', '#sqf-to-suburb1', debounce(function() {
      var keyword = jQuery(this).val();
      var old_keyword = jQuery(this).data('old');

      // control spam
      if (!keyword || keyword == '' || old_keyword == keyword) {
        return;
      }

    if ($('#disable_checkout_suburb_search').val() == "yes") {
      return;
    }
      // update the old data for this input
      jQuery(this).data('old', keyword);

      if( keyword.length > 3 ) {
        jQuery.ajax({
          type : "post",
          dataType : "json",
          url : woo_machship.ajaxurl,
          data : { action: "get_location", keyword : keyword, nonce: woo_machship.nonce},
          beforeSend: function () {
            $( '#woo-machship-ajax-loader' ).fadeIn( 'slow' );
          },
          success: function(response) {
            jQuery( '#woo-machship-ajax-loader' ).fadeOut( 'slow' );
            if(response.type == "success") {
              jQuery("#sqf-suburb-list").html(response.result);
              jQuery("#sqf-suburb-list").fadeIn();
            }
            else {
              alert("Error: failed to load response. Please try again.");
            }
          }
        });
      }
    }, 700));
  }

  if( woo_machship.page_is == 'checkout' ) {
    jQuery(document).on('change','.wcmca_address_select_menu', function(event){
      var random = Math.floor((Math.random() * 1000000) + 999);
      jQuery.ajax({
        url: woo_machship.ajaxurl+"?nocache="+random,
        type: 'post',
        data: {
          action: 'machship_wcmca_get_address_by_id',
          address_id: event.currentTarget.value
        }
      });
    });

    woo_macship_manage_address();
    jQuery( document ).on( 'click', '#ship-to-different-address-checkbox', function() {
      woo_macship_manage_address();
    });

    jQuery( document.body ).on('click', '#place_order', function() {
      $('#billing_state').removeAttr('disabled');
      $('#billing_state option:not(:selected)').prop('disabled', true);
      $('#shipping_state').removeAttr('disabled');
    });

    jQuery(document.body).on('updated_checkout', function() {

      var suburb = $('#ship-to-different-address-checkbox').prop('checked') ? $('#shipping_postcode').val() : $('#billing_postcode').val();
      var toLocationId      = $('input[name="ToLocationID"]').val();
      var hasShippingMethod = $('#shipping_method');
      var showSuburbList = true;

      // Fix for old user that doesn't have any ToLocationID
      if (suburb && toLocationId == '' && !hasShippingMethod.length) {
        // force cehckout to false
        window.update_checkout = false;
        showSuburbList = false;
      }

      if(window.update_checkout === false){
        if ($('#disable_checkout_suburb_search').val() == "yes") {
          return;
        }
        var existingSubUrb = $('#ship-to-different-address-checkbox').prop('checked') ? $('#shipping_city').val() : $('#billing_city').val();
        var data = { action: "get_location", keyword : suburb, nonce: woo_machship.nonce};

        if (showSuburbList) {
          $('#sqf-suburb-list').html('Loading suburbs...');
        }

        if ($('#toSuburb').val() === '') {
          $('.woo-machship-shipping').hide();
        } else {
        }

        $.post(woo_machship.ajaxurl, data, function(response) {
          if(response.type == "success") {
            var suburb_list = $('#sqf-suburb-list');
            suburb_list.html(response.result);

            // Fix for old user that doesn't have any ToLocationID
            if (showSuburbList) {
              suburb_list.fadeIn();
            }
            // this is set back to original bool status
            window.update_checkout = true;

            $('#sqf-suburb-list .woo-machship-suburb-item[data-suburb="' + existingSubUrb + '"]').prop('checked', true);
            if (suburb && toLocationId == '' && !hasShippingMethod.length) {
              $('#sqf-suburb-list .woo-machship-suburb-item[data-suburb="' + existingSubUrb + '"]').trigger('click');
            }
          }
          else {
            alert("Error: failed to load response. Please try again.");
          }
        }, 'json');
      }
    });

    jQuery( document ).on( 'keyup', '#billing_postcode, #shipping_postcode', debounce(function() {
      if ($('#ship-to-different-address-checkbox').prop('checked') && $(this).prop('id') === 'billing_postcode' ) {
        return false;
      }

      if ($('#disable_checkout_suburb_search').val() == "yes") {
      return false;
    }

      var suburb = jQuery(this).val();
      var existingSubUrb = $('#calc_shipping_city').val();

      if( suburb.length > 3 ) {
        var data = { action: "get_location", keyword : suburb, nonce: woo_machship.nonce};

        $('#sqf-suburb-list').html('Loading suburbs...');
        $('#shipping_city').val('');
        $('#shipping_state').val('').trigger('change');
        $.post( woo_machship.ajaxurl, data, function(response) {
          if(response.type == "success") {
            jQuery("#sqf-suburb-list").html(response.result);
            jQuery("#sqf-suburb-list").fadeIn();
            $('#sqf-suburb-list .woo-machship-suburb-item[data-suburb="' + existingSubUrb + '"]').prop('checked', true);
          }
          else {
            alert("Error: failed to load response. Please try again.");
          }
        }, 'json' );
      }

    }, 1000));

    jQuery('#hidden-billing_state').val(jQuery('select[name=billing_state]').val());

    if(jQuery('#ship-to-different-address-checkbox').prop('checked')){
      jQuery('#hidden-shipping_state').removeAttr('disabled');
      jQuery('#hidden-shipping_state').val(jQuery('select[name=shipping_state]').val());
    }else{
      jQuery('#hidden-shipping_state').attr('disabled', 'disabled');
    }


    jQuery('#billing_state').on('change', function(){
      jQuery('#hidden-billing_state').val(jQuery('select[name=billing_state]').val());
    });


    jQuery('#shipping_state').on('change', function(){
      jQuery('#hidden-shipping_state').val(jQuery('select[name=shipping_state]').val());
    });

    jQuery('#ship-to-different-address-checkbox').on('change', function(){
      if(jQuery(this).prop('checked')){
        jQuery('#hidden-shipping_state').removeAttr('disabled');
      }else{
        jQuery('#hidden-shipping_state').attr('disabled', 'disabled');
      }
    });
  }


  if (woo_machship.page_is == 'cart') {

    $(document.body).on('update_wc_div', function() {
      if ($('#toSuburb').val() === '') {
        $('.cart-machship-shipping').hide();
      }else {
        $('.cart-machship-shipping').show();
      }
    })

    if ($('#toSuburb').val() === '') {
      $('.cart-machship-shipping').hide();
    }else {
      $('.cart-machship-shipping').show();
    }

    $('.shipping-calculator-button').fadeIn();
    // remove this - the logged out user are stuck and cannot update shipping calculator
    // $('button[name="calc_shipping"]').prop('disabled', true);
    $('.additional-elements').remove();

    checkOnload();

    if(window.dcss != "yes"){
      // disable postcode autofill
      $('#calc_shipping_postcode').attr('autocomplete', 'one-time-code');
      $('#calc_shipping_postcode_field').append('\
          <div class="additional-elements">\
            <p class="small-text">Enter your postcode and/or suburb to display a list of suburbs.</p>\
            <div id="sqf-suburb-list"></div>\
            <input type="hidden" id="toSuburb" name="ToSuburb" value="">\
            <input type="hidden" id="toState" name="ToState" value="">\
            <input type="hidden" id="toPostcode" name="ToPostcode" value="">\
            <input type="hidden" id="toLocationID" name="ToLocationID" value="">\
          </div>\
        ')

      if ($('#calc_shipping_postcode').val()) {
        if ($('#calc_shipping_postcode').val().length > 3) {
          $('#calc_shipping_postcode').trigger('keyup');
        }else {
          $('#sqf-suburb-list').html('No suburb founds...');
        }
      }
    }

    $( document ).on( 'keyup', '#calc_shipping_postcode', debounce(function() {
    if ($('#disable_checkout_suburb_search').val() == "yes" && typeof $('#disable_checkout_suburb_search').val() != "undefined") {
      return;
    }
      var suburb = jQuery(this).val();
      var existingSubUrb = $('#calc_shipping_city').val();

      var data = { action: "get_location", keyword : suburb, nonce: woo_machship.nonce}

      $('#sqf-suburb-list').html('Loading suburbs...');
      $('button[name="calc_shipping"]').prop('disabled', true);

      $.post( woo_machship.ajaxurl, data, function(response) {
        if(response.type == "success") {
          jQuery("#sqf-suburb-list").html(response.result);
          jQuery("#sqf-suburb-list").fadeIn();
        }
        else {
          alert("Error: failed to load response. Please try again.");
        }
        $('#sqf-suburb-list .woo-machship-suburb-item[data-suburb="' + existingSubUrb + '"]').prop('checked', true);
      }, 'json' );

    }, 1000));


    //when update cart button is clicked
    $(document).on("click", "[name='update_cart']", function(){
      window.cart_updated = false;
    });
  }

  window.ajax_loading = false;
  $.checkAjaxRunning = function(){
    console.log($.active);
  }


  var reCalculateShippingCost = function() {
    if(woo_machship.page_is == "product"){
      woo_machship_find_shipping_costs(false);
    } else {
      var data = {
        'action': 'woo_machship_set_fields_on_session',
        'is_residential': jQuery('input[name="is_residential"]:checked').val(),
        'nonce': woo_machship.nonce
      };

      $.post(woo_machship.ajaxurl, data, function(response) {
        jQuery(document.body).trigger('wc_update_cart');
      });
    }
  }

  // This is for the initial recalculate
  jQuery('input[name=is_residential]').on('click', function(){
    reCalculateShippingCost();
  });

  // For after content dom gets repopulate
  jQuery(document).on('click', 'input[name=is_residential]', function() {
	  reCalculateShippingCost();
  });

  jQuery('#calc_shipping_country').on('change',function(){
    setTimeout(function(){ check_country(); }, 500);
  });
  $( document ).ajaxComplete(function( event, xhr, settings ) {
    console.log(settings.url);

    if (woo_machship.page_is == "cart" && !jQuery('.shipping-calculator-button')[0] && settings.url == "/?wc-ajax=get_refreshed_fragments") {

      jQuery('#disable_checkout_suburb_search').val('no');

      jQuery('input[name=is_residential]').on('click', function(){
        var data = {
          'action': 'woo_machship_set_fields_on_session',
          'is_residential' : jQuery('input[name=is_residential]:checked').val()
        };

        $.post( woo_machship.ajaxurl, data, function(response) {
          jQuery( document.body ).trigger( 'wc_update_cart');
        });
      });

      if(typeof window.dcss === 'undefined'){
        window.dcss = jQuery('#disable_checkout_suburb_search').val();
      }

      check_country(1);

      jQuery('.additional-elements').remove();
        jQuery("#calc_shipping_postcode_field").append(
          '\
            <div class="additional-elements">\
              <p class="small-text">Enter your postcode and/or suburb to display a list of suburbs.</p>\
              <div id="sqf-suburb-list"></div>\
              <input type="hidden" id="toSuburb" name="ToSuburb" value="">\
              <input type="hidden" id="toState" name="ToState" value="">\
              <input type="hidden" id="toPostcode" name="ToPostcode" value="">\
              <input type="hidden" id="toLocationID" name="ToLocationID" value="">\
            </div>\
          '
        );


    } else if(woo_machship.page_is == "cart" && settings.url == "/marketplace/?wc-ajax=get_refreshed_fragments"){
      jQuery('input[name=is_residential]').on('click', function(){
          var data = {
            'action': 'woo_machship_set_fields_on_session',
            'is_residential' : jQuery('input[name=is_residential]:checked').val()
          };

          $.post( woo_machship.ajaxurl, data, function(response) {
            jQuery( document.body ).trigger( 'wc_update_cart');
          });
        });

      jQuery('#calc_shipping_country').on('change',function(){
        setTimeout(function(){ check_country(); }, 500);
      });

      if(window.calc_shipping_country != null){
        jQuery('#calc_shipping_country').val(window.calc_shipping_country).change();
      }
      if(typeof window.dcss === 'undefined'){
        window.dcss = jQuery('#disable_checkout_suburb_search').val();
      }

      if(typeof window.dcss === 'undefined'){
        jQuery( document.body ).trigger( 'wc_update_cart');
      }
      check_country(1);
    }

    if(woo_machship.page_is == "cart" && settings.url == "/?wc-ajax=update_shipping_method"){
        $('.additional-elements').remove();
        if(window.dcss != "yes"){
          $('button[name="calc_shipping"]').prop('disabled', true);
          $('#calc_shipping_postcode_field').append('\
              <div class="additional-elements">\
                <p class="small-text">Enter your postcode and/or suburb to display a list of suburbs.</p>\
                <div id="sqf-suburb-list"></div>\
                <input type="hidden" id="toSuburb" name="ToSuburb" value="">\
                <input type="hidden" id="toState" name="ToState" value="">\
                <input type="hidden" id="toPostcode" name="ToPostcode" value="">\
                <input type="hidden" id="toLocationID" name="ToLocationID" value="">\
              </div>\
            ')

          if ($('#calc_shipping_postcode').val().length > 3) {
          }else {
            $('#sqf-suburb-list').html('No suburb founds...');
          }
        }
    }
  });


  /**
   * Find shipping cost
   */
  function woo_machship_find_shipping_costs(cart_update) {

    if (window.is_processing) {
      console.log("stopping, shipping cost is still in process!");
      return;
    }

    var suburb    = jQuery('#toSuburb').val();
    var postcode  = jQuery('#toPostcode').val();
    var locationId = jQuery('#toLocationID').val();
    var state = jQuery('#toState').val();

    $('.woo-machship-error-message').hide();

    if(jQuery('#calc_shipping_country').length > 0 && jQuery('#calc_shipping_country').val() != "AU"){
      postcode = jQuery('#calc_shipping_postcode').val();
      suburb = jQuery('#calc_shipping_city').val();
      state = jQuery('#calc_shipping_state').val();
    }


    if(woo_machship.page_is == "product"){
      if(jQuery('input.variation_id').length > 0 && jQuery('input.variation_id').val() != ''){
        var productId   = jQuery('input.variation_id').val();
      } else {
        var productId   = jQuery('#productID').val();
      }
    }

    // Check if suburb is not entered then return

      if( woo_machship.page_is == "cart" || woo_machship.page_is == "checkout"){

      if(jQuery('#disable_checkout_suburb_search').val() != "yes"){
       if(typeof suburb === 'undefined' || suburb == '' ){
       jQuery( document.body ).trigger( 'update_checkout', {
        update_shipping_method: true
       });

       return false;
       }
      }else{
      if(woo_machship.page_is == "cart"){
        postcode = jQuery('#calc_shipping_postcode').val();
        suburb = jQuery('#calc_shipping_city').val();
        state = jQuery('#calc_shipping_state').val();
        locationId = "";
      }else{
        postcode = jQuery('#shipping_postcode').val();
        suburb = jQuery('#shipping_city').val();
        state = jQuery('#shipping_state').val();
        locationId = "";
      }

      }
    }else{
      if(typeof suburb === 'undefined' || suburb == '' ) return false;
    }

    if (woo_machship.page_is === 'product') {
    if(typeof suburb === 'undefined' || suburb == '' ) return false;
      if( jQuery('#sqf-to-suburb1').length > 0 && jQuery('#sqf-to-suburb1').val() == '' ) {
        return false;
      }
    }

    var doAjax = 1;
    var product_quantity = 1; //default

    if( woo_machship.page_is == "product"){
      product_quantity = jQuery('.qty').val();
    }

    window.is_processing = true;

    var data = {
      'action': 'woo_machship_find_shipping_costs',
      'tosuburb': suburb,
      'tostate': state,
      'topostcode': postcode,
      'tolocationid': locationId,
      'productId': productId,
      'page_is': woo_machship.page_is,
      'product_quantity': product_quantity,
      'is_residential' : jQuery('input[name=is_residential]:checked').val(),
      'nonce': woo_machship.nonce
    };

    if( woo_machship.page_is == "cart" || woo_machship.page_is == "checkout"){
      if( doAjax == 1 ) {
        var ajax_timer;
        var timer = 0;
        jQuery( '#woo-machship-ajax-loader').html("<span class='loading_text'>Calculating Live Rates...</span>").fadeIn( 'slow' );
        data['action'] = 'woo_machship_set_fields_on_session';
        jQuery( '#woo-machship-ajax-loader' ).fadeIn( 'slow' );
        return $.post( woo_machship.ajaxurl, data, function(response) {

          jQuery( '#sqf-shipping-cost' ).html('');
          jQuery( '#woo-machship-ajax-loader' ).fadeOut( 'slow' );

          if( woo_machship.page_is == "checkout" && window.update_checkout === true){
              jQuery( document.body ).trigger( 'update_checkout', {
                update_shipping_method: true
              });
          }

          if( woo_machship.page_is == "cart" && cart_update == true) {
            jQuery( document.body ).trigger( 'updated_shipping_method' );
            jQuery( document.body ).trigger( 'wc_update_cart');

            checkOnload();
          }

        }).done(function(){
          window.is_processing = false;
          window.cart_updated = true;
          window.update_checkout = true;
          clearInterval(ajax_timer);
        });

        ajax_timer = setInterval(function(){
          if(timer > 5 && bp_ajax.readyState == 1){
            jQuery( '#woo-machship-ajax-loader').html("<span class='loading_text'>Still calculating live rates - this is taking a while!</span>");
          }
          timer++;

        }, 1000, true);
      }

    } else {

      // Check if doAjax
      if( doAjax == 1 ) {
        var ajax_timer;
        var timer = 0;
        jQuery( '#woo-machship-ajax-loader').html("<span class='loading_text'>Calculating Live Rates...</span>").fadeIn( 'slow' );
        data['components'] = {components: components};
        var bp_ajax = $.post( woo_machship.ajaxurl, data, function(response) {

          jQuery( '#sqf-shipping-cost' ).html('');
          jQuery( '#sqf-shipping-cost').show();
          jQuery( '#sqf-shipping-cost' ).html( response );
          jQuery( '#woo-machship-ajax-loader' ).fadeOut( 'slow' );

          if( woo_machship.page_is == "checkout" && window.update_checkout === true){
            jQuery( document.body ).trigger( 'update_checkout', {
              update_shipping_method: true
            });
          }

          if( woo_machship.page_is == "cart" && cart_update == true) {
            jQuery( document.body ).trigger( 'updated_shipping_method' );
            jQuery( document.body ).trigger( 'wc_update_cart');
          }

        }).done(function(){
          window.is_processing = false;
          window.cart_updated = true;
          window.update_checkout = true;
          clearInterval(ajax_timer);
        }).catch(function(error){

          $('#woo-machship-ajax-loader').fadeOut( 'slow' );

          // stops handling of error below if its not available
          if ($('.woo-machship-error-message').length == 0) {
            console.log('Error getting the shipping cost: ', error.responseText);
            return;
          }


          if (error.responseJSON && error.responseJSON.data) {
            var errorMessage = typeof error.responseJSON.data === 'string' ? error.responseJSON.data : JSON.stringify(error.responseJSON.data);
            $('.woo-machship-error-message').html(errorMessage).show();
          } else if (error.responseText) {
            $('.woo-machship-error-message').html(error.responseText).show();
          } else {
            $('.woo-machship-error-message').html("An error has occured. Please try again").show();
          }
        });

        ajax_timer = setInterval(function(){
          if(timer > 5 && bp_ajax.readyState == 1){
            jQuery( '#woo-machship-ajax-loader').html("<span class='loading_text'>Still calculating live rates - this is taking a while!</span>");
          }
          timer++;

        }, 1000, true);

        return bp_ajax;
      }
    }

  }


  /**
   * Manage Addresses
   */
  function woo_macship_manage_address() {

    if( jQuery('#ship-to-different-address-checkbox').is(':checked') ) {

      jQuery( '#shipping_city' ).val( jQuery('input#toSuburb').val() );
      jQuery( '#shipping_state' ).val( jQuery('input#toState').val() ).trigger( "change" );
      jQuery( '#shipping_postcode' ).val( jQuery('input#toPostcode').val() );

      // add fallback script if shipping location is still empty
      if (
        !jQuery( 'input[name="shipping_city"]' ).val() ||
        !jQuery( '#shipping_state' ).val() ||
        !jQuery( 'input[name="shipping_postcode"]' ).val()
      ) {
        jQuery( 'input[name="shipping_city"]' ).val( jQuery('input#billing_city').val() );
        jQuery( '#shipping_state' ).val( jQuery('#billing_state').val() ).trigger( "change" );
        jQuery( 'input[name="shipping_postcode"]' ).val( jQuery('input#billing_postcode').val() );
      }

      jQuery('.additional-elements').remove();

      if(window.dcss != "yes"){
        jQuery('#shipping_postcode').attr('autocomplete', 'one-time-code');
        jQuery('#shipping_postcode_field').append('\
          <div class="additional-elements">\
            <p class="small-text">Enter your postcode and/or suburb to display a list of suburbs.</p>\
            <div id="sqf-suburb-list"></div>\
            <input type="hidden" id="toSuburb" name="ToSuburb" value="">\
            <input type="hidden" id="toState" name="ToState" value="">\
            <input type="hidden" id="toPostcode" name="ToPostcode" value="">\
            <input type="hidden" id="toLocationID" name="ToLocationID" value="">\
          </div>\
        ');

        jQuery( '#shipping_city' ).prop("readonly", true);
        jQuery( '#shipping_state' ).prop("disabled", true);
      }

      jQuery( '#billing_city' ).prop("readonly", false);
      jQuery( '#billing_state' ).prop("disabled", false);

    } else {

      jQuery( '#billing_city' ).val( jQuery('#shipping_city').val() );
      jQuery( '#billing_state' ).val( jQuery('#shipping_state').val() ).trigger( "change" );
      jQuery( '#billing_postcode' ).val( jQuery('#shipping_postcode').val() );


      if (
        !jQuery( 'input[name="billing_city"]' ).val() ||
        !jQuery( '#billing_state' ).val() ||
        !jQuery( 'input[name="billing_postcode"]' ).val()
      ) {
        // try to update inner form of wordpress
        jQuery( 'input[name="billing_city"]' ).val( jQuery('#shipping_city').val() );
        jQuery( '[name="billing_state"]' ).val( jQuery('#shipping_state').val() ).trigger( "change" );
        jQuery( 'input[name="billing_postcode"]' ).val( jQuery('#shipping_postcode').val() );
      }



      jQuery('.additional-elements').remove();

      if(jQuery('#disable_checkout_suburb_search').val() != "yes"){
        jQuery('#billing_postcode').attr('autocomplete', 'one-time-code');
        jQuery('#billing_postcode_field').append('\
          <div class="additional-elements">\
            <p class="small-text">Enter your postcode and/or suburb to display a list of suburbs.</p>\
            <div id="sqf-suburb-list"></div>\
          </div>\
        ');

        jQuery( '#billing_city' ).prop("readonly", true);
        jQuery( '#billing_state' ).prop("disabled", true);
      }
    }

  }

  check_country();

  function checkOnload() {
    var unloadOverlay = setInterval(function() {
      // this is to fix the buggy onload not showing list of states
      if ($('.shipping-calculator-forms').is(':visible')) {
        // we need to check the other script enqueued until their process is done
        if (!$('.cart_totals').find('div.blockOverlay').length) {
          document.querySelector('.shipping-calculator-button').click();
          clearInterval(unloadOverlay);
        }
      }
      else {
        clearInterval(unloadOverlay);
      }
    }, 500);
  }

})( jQuery );

window.calc_shipping_country = '';

function check_country(is_refresh=0){
  if(jQuery('#calc_shipping_country').length == 0){
   return;
  }
  if(woo_machship.page_is == "cart"){
    var country = jQuery('#calc_shipping_country').val();
    var city = jQuery('#calc_shipping_city');
    var state = jQuery('#calc_shipping_state');
    var postcode = jQuery('#calc_shipping_postcode');
  }
  else{
    if(jQuery('#ship-to-different-address-checkbox').prop('checked')){
        var country = jQuery('#shipping_country').val();
        var city = jQuery('#shipping_city');
        var state = jQuery('#shipping_state');
        var postcode = jQuery('#shipping_postcode');
      }
      else{
        var country = jQuery('#billing_country').val();
        var city = jQuery('#billing_city');
        var state = jQuery('#billing_state');
        var postcode = jQuery('#billing_postcode');
    }
  }

  window.calc_shipping_country = country;
  window.calc_shipping_postcode = postcode;
  window.calc_shipping_state = state;
  window.calc_shipping_city = city;

  if(is_refresh == 0){
  //city.val('');
    //postcode.val('');
  }


  if(window.dcss == "no"){
    if(country != "AU"){
      jQuery('#disable_checkout_suburb_search').val('yes');
      jQuery('.additional-elements').hide();
      city.removeAttr('readonly');
      state.prop("disabled", false);

      if(woo_machship.page_is == "cart"){
        jQuery('button[name="calc_shipping"]').prop('disabled', false);
      }
    }else{
      jQuery('#disable_checkout_suburb_search').val(dcss);
      if(window.dcss == "no"){
        jQuery('.additional-elements').show();
        city.attr('readonly','readonly');
        state.prop("disabled", true);

        if(woo_machship.page_is == "cart"){
          jQuery('button[name="calc_shipping"]').prop('disabled', true);
        }
      }
    }
  }
}
