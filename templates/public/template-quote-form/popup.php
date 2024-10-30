<?php  if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly  ?>
<div style="margin-top: 15px;">
  <a href="#quick-quote-modal" id="qq-popup-button" class="button"><?php echo esc_html($settings['popup_button_text']); ?></a>
  <p class="no_variation" style="margin-top: 10px;"><span></span></p>
</div>
<div id="machship-modal">
  <div id="quick-quote-modal">
    <a href="#closemodal" id="qq-popup-close">x</a>
    <?php require_once(  $path. 'templates/public/template-quote-form/default.php' ); ?>
  </div>
</div>

<script>

function loadJQuery(){

var waitForLoad = function () {

    if (typeof jQuery != "undefined") {
        jQuery(document).ready(function(){

          // we have to know if jquery.modal exist
          // so we can utilized the native events of jquery.modal

          var isJQueryModalExist = typeof jQuery.modal !== 'undefined';


          // Before anything else, we have to verify if the Current "ADD TO CART" is disabled.
          if (jQuery('.single_add_to_cart_button').hasClass('disabled')) {
            // so we have to disable our popup button aswell until certain condition met to enable again

            jQuery('#qq-popup-button').addClass('qq-disabled');

            // But we also need to enable this by user's update
            // It will enable once the user select product's variations

            jQuery('.single_add_to_cart_button').on('classChange', function() {
              var isDisabled = jQuery(this).hasClass('disabled');
              if (isDisabled) {
                jQuery('#qq-popup-button').addClass('qq-disabled');
              } else {
                jQuery('#qq-popup-button').removeClass('qq-disabled');
              }
            });


          }

          jQuery('#machship-modal').hide().prependTo(document.body);


          if (isJQueryModalExist) {

            // hide the custom button
            jQuery('#qq-popup-close').hide();

          } else {

            jQuery('#qq-popup-button').on('click touchstart',function(){
              if(jQuery(this).hasClass('qq-disabled')){

              }else{
                jQuery('#machship-modal').show();
                jQuery('body').addClass('modalOn');
              }
            });

            jQuery(document).on('click', '#quick-quote-modal .close-modal', function() {
              jQuery('#machship-modal').hide();
              jQuery('body').removeClass('modalOn');
            });

            jQuery('#qq-popup-close').on('click touchstart',function(e){
              jQuery('#machship-modal').hide();
              jQuery('body').removeClass('modalOn');
            })

          }



      });
    } else {
        console.log("jquery not loaded..");
        window.setTimeout(waitForLoad, 500);
    }
 };
 window.setTimeout(waitForLoad, 500);
}

loadJQuery();

</script>
