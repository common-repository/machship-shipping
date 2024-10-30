(function( $ ) {
    'use strict';

    // for machship_shipping export/import box data
    $(document).on('click', '.woo-machship-export-section button#ms-btn-export-data', function() {

        // start import here
        $('button#ms-btn-export-data').prop('disabled',true);
        $(this).siblings('.woo-machship-processing').addClass('active');

        var msInfo = $(this).siblings('.woo-machship-info');
        msInfo.find('span').hide();
        msInfo.hide();

        jQuery.ajax({
            type: "post",
            url: ajaxurl,
            data: {
                action: "machship_shipping_exporting_box_data",
                nonce: woo_machship.nonce
            }
        })
        .done(function(response) {
            $('button#ms-btn-export-data').prop('disabled', false);

            if (response.success) {
                var includeEnabledMS = $('input[name="export_ms_enabled_include"]:checked').val();
                var includePublished = $('input[name="export_published_include"]:checked').val();

                window.open(response.url + '&include_published=' + includePublished + '&include_enabled_ms=' + includeEnabledMS, '_blank');
                showMsInfoSuccess(msInfo, "Successfully export product box settings");
            } else {
                // error might occurred here
                showMsInfoError(msInfo, response.message);
            }
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
            showMsInfoError(msInfo, exImportErrorHandler(jqXHR, textStatus, errorThrown));
        });



    });

    $(document).on('click','.woo-machship-export-section button#ms-btn-import-data',function(){

        // this is to trigger input change to open select a file
        $('#woo-machship-input-import').trigger('click');

    });

    $(document).on('change', '#woo-machship-input-import', function(e) {

        const file = e.target.files[0];

        if (!file) {
            return; // Exit if no file selected
        }

        // start import here
        var targetEl = jQuery(this);
        jQuery('button#ms-btn-import-data').prop('disabled',true);

        // show processing
        targetEl.siblings('.woo-machship-processing').addClass('active');
        var msInfo = targetEl.siblings('.woo-machship-info');
        msInfo.find('span').hide();
        msInfo.hide();

        var fd = new FormData();
        fd.append('action', 'machship_shipping_import_box_data');
        fd.append('nonce', woo_machship.nonce);
        fd.append('import_data', file);

        jQuery.ajax({
            type: "post",
            url: ajaxurl,
            processData: false, // important
            contentType: false, // important
            data: fd
        })
        .done(function(response) {
            console.log("the response is : ", response);
            if (response.success) {
                showMsInfoSuccess(msInfo, response.message);
            } else {
                showMsInfoError(msInfo, response.message);
            }
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
            showMsInfoError(msInfo, exImportErrorHandler(jqXHR, textStatus, errorThrown));
        })
        .always(function() {
            targetEl.siblings('.woo-machship-processing').removeClass('active');
            jQuery('button#ms-btn-import-data').prop('disabled', false);
        });

    });

    const exImportErrorHandler = function(jqXHR, textStatus, errorThrown) {
        let errorMessage = 'An error occurred';
        // Check if the server returned a JSON object with an error message
        if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
            errorMessage = jqXHR.responseJSON.message;
        } else if (errorThrown) {
            errorMessage = errorThrown;
        }

        return exImportErrorHandler;
    }

    const showMsInfoError = function(msInfo, message) {
        // show error here
        msInfo.find('span.error').html(message);
        msInfo.find('span.error').show();
        msInfo.show();
    }

    const showMsInfoSuccess = function(msInfo, message) {
        // show success here
        msInfo.find('span.success').html(message);
        msInfo.find('span.success').show();
        msInfo.show();
    }

})( jQuery );