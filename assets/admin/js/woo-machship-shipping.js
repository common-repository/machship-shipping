(function( $ ) {
  'use strict';

  // TODO we need to know if client is using a new version or not
  var mode = 'legacy';

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

   jQuery(document).on( 'click', '.add_location_repeater', function(e) {
     e.preventDefault();
     var elem = jQuery(this).closest('tr').siblings('tr.row_loc:first').clone();
     jQuery('<button class="button button-primary remove_location">&minus;</button>').insertAfter(elem.find('select.company-location-select'));
     elem.find("input").val('');
     var final = elem.find("select").val('');
     final.end().insertBefore(jQuery(this).closest('tr'));

     elem.find('.warehouse-idx').html('_');

   });

   jQuery(document).on('change', '.company-location-select', function () {
     // populate values to hidden fields
     var elem = jQuery(this);
     var compID = jQuery('option:selected', elem).data('companyid');
     var locID = jQuery('option:selected', elem).data('locationid');
     var postcode = jQuery('option:selected', elem).data('postcode');
     var suburb = jQuery('option:selected', elem).data('suburb');
     var addressline1 = jQuery('option:selected', elem).data('addressline1');
     elem.closest('td').siblings('input.company_id').val(compID);
     elem.closest('td').siblings('input.location_id').val(locID);
     elem.closest('td').siblings('input.postcode').val(postcode);
     elem.closest('td').siblings('input.suburb').val(suburb);
   });

  //when Remove location button is clicked
  jQuery(document).on('click','.remove_location', function(e){
    e.preventDefault();
    var result = confirm("Are you sure you want to remove the location?");
    if (result) {
      jQuery(this).closest('tr').remove();
    }

  });

  jQuery( document ).on( 'click', '.show_plugin', function() {
    if($(this).is(':checked')){
      jQuery(this).next('.show_plugin_hidden').val('1');
    } else {
      jQuery(this).next('.show_plugin_hidden').val('0');
    }
  });

  jQuery( document ).on( 'click', '.pack_individually', function() {
    if($(this).is(':checked')){
      jQuery(this).attr('checked', true);
      jQuery(this).val('1');
    } else {
      jQuery(this).attr('checked', false);
      jQuery(this).val('0');
    }
  });

  /* Add / Remove rows for Cartons on product meta */
  jQuery( document ).on( 'change', '#woo-machship-no-of-cartons', function() {

    var no_of_cartons 	= jQuery( this ).val();
    var rowCount 		= jQuery( '#quote_item_list tbody tr' ).length;

    if( no_of_cartons > 0 ) {
      if( no_of_cartons > rowCount ) {

        var row_diff = ( no_of_cartons - rowCount );

        for( var i = 1; i <= row_diff; i++ ) {
          var select = jQuery( '#quote_item_list tbody tr:first-child').find('select')
          var fieldHTMl = '<tr>';
          fieldHTMl += '<td>Item '+(rowCount+i)+'</td>';
          fieldHTMl += '<td><input type="number" name="carton_length[]" min="1" class="large-text" /></td>';
          fieldHTMl += '<td><input type="number" name="carton_width[]" min="1" class="large-text" /></td>';
          fieldHTMl += '<td><input type="number" name="carton_height[]" min="1" class="large-text" /></td>';
          fieldHTMl += '<td><input type="number" name="carton_weight[]" min="1" class="large-text" /></td>';
          fieldHTMl += '<td><select name="packaging_type[]">'+select.html()+'</select></td>';
          fieldHTMl += '</tr>';

          jQuery( '#quote_item_list tbody' ).append( fieldHTMl );
        }
      } else if( no_of_cartons < rowCount ) {

        var row_diff = ( rowCount - no_of_cartons );
        for( var i=0 ; i < row_diff ; i++ ) {
          jQuery( '#quote_item_list tbody tr:last' ).remove();
        }
      }
    }
  });

  //for product variation carton setting
  jQuery( document ).on( 'change', '.woo-machship-variation-no-of-cartons', function() {
    var no_of_cartons 	= jQuery( this ).val();
    var carton_row      = jQuery( this).closest('tr').next('tr.var_carton_row_settings');
    var rowCount 		= carton_row.find('tbody tr').length;
    var consolClass		= '';

    if( no_of_cartons > 0 ) {
      if( no_of_cartons > rowCount ) {
        var row_diff = ( no_of_cartons - rowCount );

        for( var i = 1; i <= row_diff; i++ ) {
          var row_clone = carton_row.find('tbody tr:first-child').clone();
          var carton_num = rowCount+i;
          row_clone.find('td:first-child').text('Item '+ carton_num);
          row_clone.find('td input').val('');
          var fieldHTML = row_clone;
          carton_row.find('tbody').append( fieldHTML );
        }

      } else if( no_of_cartons < rowCount ) {
        var row_diff = ( rowCount - no_of_cartons );
        for( var i=0 ; i < row_diff ; i++ ) {
          carton_row.find('tbody tr:last').remove();
        }
      }
    }
  });

  // filter by carrier
  jQuery(document).on('change', '#filter-by-carrier', function() {
    var carrierId = $(this).val();
    jQuery('tr.carrier').hide();
    if (carrierId != '') {
      jQuery('tr.carrier_'+carrierId).show();
    } else {
      jQuery('tr.carrier').show();
    }

    // customize for select all feature
    jQuery('.select_service').prop('checked', false);
    var selectedAll = jQuery('#select_all_carrier_services').is(':checked');
    if (selectedAll) {
      jQuery('tr.carrier_'+carrierId).find('.select_service').prop('checked', true);
    }
  });

  jQuery(document).on('click', '#woo_machship_reset_cache', function(e) {
    e.preventDefault();
    var result = confirm("Are you sure you want to reset cache?");
    if (result) {

      var thisObj = jQuery(this);

      jQuery.ajax({
        type: "post",
        url: ajaxurl,
        data: {
          action: "machship_reset_cache",
          nonce: woo_machship.nonce
        },
        beforeSend: function () {
          thisObj.addClass('active');
          thisObj.prop('disabled', true);
        },
      })
      .done(function(response) {
        console.log("the response is : ", response);
        if (response.success) {
          alert('Success');
          location.reload();
        } else {
          var errmsg = response.message ? response.message : 'There seems to be a problem. Please try again later';
          jQuery('.reset-cache-error').text(errmsg).show();

          thisObj.removeClass('active');
          thisObj.prop('disabled', false);
        }
      });

    }
  });

  jQuery(document).on('click', '.woo-machship-advanced', function(e) {
    e.preventDefault();
    var link = $(this).attr('href');
    tb_show('Pull Carrier Service with Admin Key', link);
  });

  // add carrier services btn
  jQuery(document).on('click', '.carrier_services_btn', function(e) {
    e.preventDefault();
    var link = $(this).attr('href');
    tb_show('Add Carrier Services', link);
    var groupNum = jQuery(this).data('groupnum');
    jQuery('#add_to_service_list').data('groupnum', groupNum);
    // reset selected services
    jQuery('.select_service').prop('checked', false);
    jQuery('#select_all_carrier_services').prop('checked', false);

    // set selected services if not empty
    var rowService = jQuery('#carrier_group_'+groupNum+ ' .row_service');
    if (rowService.length > 0) {
      rowService.each(function() {
        var serviceId = jQuery(this).find('.hidden_service_field').val();
         if (serviceId) {
           jQuery("input.select_service[value='"+serviceId+"']").attr('checked','checked')
         }
      });
    }
  });

  jQuery(document).on('change', '#select_all_carrier_services', function(e) {
    var selectedAll = $(this).is(":checked");
    jQuery('tr.carrier:visible').find('.select_service').prop('checked', selectedAll);
  });

  // select service
  jQuery(document).on('click', '#add_to_service_list', function () {
    // get all selected services
    var selected = jQuery('.select_service:checked');
    var fieldKey = jQuery(this).data('fieldname');
    var groupNum = jQuery('#add_to_service_list').data('groupnum');
    var index = groupNum;
    if (selected.length > 0) {
      selected.each(function(){
        var service_id = jQuery(this).val();
        var carrier_id = jQuery(this).data('carrierid');
        if (jQuery('#carrier_group_'+groupNum+ ' .row_service_'+service_id).length == 0) {
          var service_name = jQuery(this).data('name');
          var cname = jQuery(this).data('cname');
          var fieldHTMl = '<tr class="row_service row_service_'+service_id+'">';
          fieldHTMl += '<td>'+cname+'</td>';
          fieldHTMl += '<td>'+service_name+'</td>';
          fieldHTMl += '<td><button data-id="'+service_id+'" class="button button-primary remove_service">&minus;</button></td>';
          fieldHTMl += '<input type="hidden" name="'+fieldKey+'[carrier_services]['+index+'][service_id][]" class="hidden_service_field" value="'+service_id+'">';
          fieldHTMl += '<input type="hidden" name="'+fieldKey+'[carrier_services]['+index+'][carrier_id][]" class="hidden_carrier_field" value="'+carrier_id+'">';
          fieldHTMl += '</tr>';
          jQuery('#carrier_group_'+groupNum+ ' .table_carrier_services tbody').append(fieldHTMl);
        }
      });
    }

    $('#TB_closeWindowButton').trigger('click');
  });

  //when Remove service button is clicked
  jQuery(document).on('click','.remove_service', function(e){
    e.preventDefault();
    var result = confirm("Are you sure you want to remove the service?");
    if (result) {
      jQuery(this).closest('tr').remove();
    }

  });

  // Add Group btn
  jQuery(document).on('click','.add_group', function(e){
    e.preventDefault();
    var elem = jQuery(this).closest('tr').siblings('tr.group_loc:first').clone();
    //get length of groups
    var groupCount = jQuery('tr.group_loc').length;
    elem.find("input").val('');
    elem.attr('id', 'carrier_group_'+ groupCount);
    elem.find('.carrier_services_btn').data('groupnum', groupCount);
    elem.find(".row_service").remove();
    var final = elem.find("textarea").val('');
    final.end().insertAfter(jQuery(this).closest('tr').siblings('tr.group_loc:last'));
  });

  //for machship_shipping product box migration
  jQuery(document).on('click','#woo-machship-product-box_btn',function(){
    var thisObj = jQuery(this);
    thisObj.prop('disabled',true);
    var message = jQuery('#woo-machship-product-box_overwrite_settings').is(':checked') ? 'Please do not refresh page,  settings migration is running.': 'Use the below button to copy all of your woocommerce box settings across to the machship box settings all at once.';
    jQuery('.woo-machship-product-box-msg p').html(message);
    thisObj.find('span').addClass('spin');
    jQuery('#woo-machship-product-box_form').submit();

  });

  jQuery(document).on('submit', '#woo-machship-product-box_form', function(e) {

    var formData = jQuery(this).serializeArray().reduce(function(obj, item) {
        obj[item.name] = item.value;
        return obj;
    }, {});


    if (!formData['woo_macship_product_shipping_locations[]']) {
      // validate formData
      e.preventDefault();
      jQuery('#woo-machship-product-box_btn').prop('disabled',false);
      jQuery('#woo-machship-product-box_btn').find('span').removeClass('spin');

      alert("Should have selected atleast 1 warehouse. Please try again.");

      return;
    }



  });

  jQuery(document).on('click','#woo-machship-product-box_overwrite_settings',function(){
    if(jQuery(this).is(':checked')){
      jQuery(this).val('yes');
    } else {
      jQuery(this).val('no');
    }
  });

  //when Remove location button is clicked
  jQuery(document).on('click','.remove_group_btn', function(e){
    e.preventDefault();
    var result = confirm("Are you sure you want to remove the group?");
    if (result) {
      jQuery(this).closest('tr').remove();
    }

  });

  woo_machship_hide_show_admin_settings();

  jQuery( document ).on( 'change', '.woocommerce_woo_machship_shipping_product_page ', function() {
    woo_machship_hide_show_admin_settings();
  });

  jQuery( document ).on( 'change', '.woocommerce_woo_machship_shipping_product_page ', function() {
    woo_machship_hide_show_admin_settings();
  });

  function woo_machship_hide_show_admin_settings() {
    var product_page = jQuery('.woocommerce_woo_machship_shipping_product_page:checked').val();

    jQuery( '#woocommerce_woo_machship_shipping_popup_button_text' ).parents( 'tr' ).hide();
    jQuery( '#woocommerce_woo_machship_shipping_product_position' ).parents( 'tr' ).hide();

    if(product_page=="popup"){
      jQuery( '#woocommerce_woo_machship_shipping_popup_button_text' ).parents( 'tr' ).show();
      jQuery( '#woocommerce_woo_machship_shipping_product_position' ).parents( 'tr' ).show();
    }

    if(product_page=="on_page"){
      jQuery( '#woocommerce_woo_machship_shipping_product_position' ).parents( 'tr' ).show();
    }
  }

  jQuery('.min_weight').keypress(function(event) {
    if ((event.which != 46 || jQuery(this).val().indexOf('.') != -1) && (event.which < 48 || event.which > 57)) {
      event.preventDefault();
    }
  });

  jQuery('.max_weight').keypress(function(event) {
    if ((event.which != 46 || jQuery(this).val().indexOf('.') != -1) && (event.which < 48 || event.which > 57)) {
      event.preventDefault();
    }
  });

  jQuery( document ).on( 'change', '.woocommerce_woo_machship_shipping_mode', function() {
    mode = $(this).val();
    hideShowLegacyFields();

  });

  jQuery( document ).on ( 'change', '#woocommerce_woo_machship_shipping_product_position', function() {
    if ($(this).val() == 'other') {
      $('#woocommerce_woo_machship_shipping_product_position_other').show();
    } else {
      $('#woocommerce_woo_machship_shipping_product_position_other').hide();
    }
  });

  /**
   * Handle the main form to check validations
   */
  jQuery('form#mainform').submit(function(e) {

    if (mode === 'dynamic') {
      var isValid = true;
      var elRequired = [];

      // reset every required element's label black
      $('.fusedship-required').closest('.forminp')
        .siblings('.titledesc')
        .find('label')
        .css('color', 'inherit');

      $('.fusedship-required').each(function() {

        if ( $(this).val() === '' ) {
          isValid = false;
          elRequired.push($(this));
        }
      });

      if (!isValid) {
        e.preventDefault();
        alert("Unable to save settings. Please fill up the required fields");
        $('html, body').animate({
          scrollTop: elRequired[0].offset().top - 300
        }, 800);

        // make required element's label red
        elRequired.forEach(function(el) {
          el.closest('.forminp')
            .siblings('.titledesc')
            .find('label')
            .css('color', 'red');
        });

      }
    }

  });

  jQuery('#woocommerce_woo_machship_shipping_machship_site_url').closest('tr').hide();

  jQuery('.woocommerce-save-button').click(function(e) {

    jQuery.ajax({
      type: "post",
      url: ajaxurl,
      data: {
        action: "generate_client_token",
        nonce: woo_machship.nonce,
        urlLink: jQuery('#woocommerce_woo_machship_shipping_machship_site_url').val()
      }
    });
  });


  function hideShowLegacyFields() {

    // whether to show other fields or not
    // LEGACY MODE - SHOWS EVERYTHING
    // DYNAMIC MODE - HIDES EVERYTHING EXCEPT :
    // - MACHSHIP CREDS
    // - WIDGET DESC
    // - QUICKQUOTE POSITION
    // - ADVANCE SETTINGS ?
    // - FUSEDSHIP CREDS

    if (mode == 'legacy') {
      $('.legacy-fields').show();
      $('.legacy-fields').next('table').show();

      var fusedshipCreds = $(".fusedship-credentials");
      var fusedshipCredsFields = fusedshipCreds.next('table');

      fusedshipCreds.detach().insertAfter(".form-table:last");
      fusedshipCredsFields.detach().insertAfter(".fusedship-credentials");

    } else {
      $('.legacy-fields').hide();
      $('.legacy-fields').next('table').hide();

      var fusedshipCreds = $(".fusedship-credentials");
      var fusedshipCredsFields = fusedshipCreds.next('table');

      fusedshipCreds.detach().insertAfter(".form-table:first");
      fusedshipCredsFields.detach().insertAfter(".fusedship-credentials");

    }
  }

  // each script should have init function
  function init() {
    console.log("init");

    // check if whether we need to hide/show legacy fields
    mode = $('.woocommerce_woo_machship_shipping_mode:checked').val();
    // this is to avoid unnecessary show/move of fields if it's already in legacy mode
    if (mode != 'legacy') {
      hideShowLegacyFields();
    }


    // we need to identify the default mode
    // mode default to dynamic if its a new installed plugin
    // mode default to legacy if plugin version upgraded

    // check if mode is not set yet === null
    // if machship shipping its enabled
    //    if true then MODE = LEGACY
    //    if false then MODE = DYNAMIC

    // NOTE!
    // make the field MODE as required so next time it will bypass condition above
    if (!mode || mode === "") {
      var isEnabled = $('#woocommerce_woo_machship_shipping_enabled').is(':checked');

      if (isEnabled) {
        $('.woocommerce_woo_machship_shipping_mode[value="legacy"]').prop('checked', true);
      } else {
        $('.woocommerce_woo_machship_shipping_mode[value="dynamic"]').prop('checked', true);
      }
    }


    // position to show other or not
    if ($('#woocommerce_woo_machship_shipping_product_position').val() == 'other') {
      $('#woocommerce_woo_machship_shipping_product_position_other').show();
    } else {
      $('#woocommerce_woo_machship_shipping_product_position_other').hide();
    }
  }

  init();

})( jQuery );