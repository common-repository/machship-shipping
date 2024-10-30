<?php  if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly  ?>
<div id="<?php echo esc_attr($idWrapper) ?>">
  <div id="<?php echo esc_attr($uniqueID); ?>" class="<?php echo esc_attr($class); ?>">
    <div id="woo-machship-ajax-loader" style="background-image: url( <?php echo esc_url(admin_url()); ?>images/spinner.gif );"></div>
    <div class="woo-machship-sqf-title">
      <h2><?php echo esc_html($title); ?></h2>
    </div>
    <div class="woo-machship-sqf-body">
      <div class="no_variation"></div>
      <div class="woo-machship-error-message"></div>
      <div class="sqf-suburb-box">
        <div class="widget-description">
          <p><?php echo esc_html($desc); ?></p>
        </div>
        <?php
        $textfieldValue = ( $toSuburb != '' && $toPostcode != '' && $toState != '' ) ? $toSuburb .' '.$toState. ' - ' . $toPostcode:'';
		  if(WC()->session->get('is_residential')){
			  $default_res = WC()->session->get('is_residential');
		  }else{
			  $default_res = 0;
		  }
        ?>

        <?php if($has_residential=="yes"): ?>
          <div class="woo-machship-quickquote-isresidential-section">
            <div style="width: 50%;">
              <input name="is_residential" type="radio" value="1" <?php if($default_res == 1){ echo "checked"; } ?> class="woo-machship-quickquote-isresidential-options" />&nbsp;
              <label for="Residential" class="woo-machship-quickquote-isresidential-options">Residential</label>
            </div>
            <div style="width: 50%;">
              <input name="is_residential" type="radio" value="0" <?php if($default_res == 0){ echo "checked"; } ?> class="woo-machship-quickquote-isresidential-options" />&nbsp;
              <label for="Residential" class="woo-machship-quickquote-isresidential-options">Business</label>
            </div>
          </div>
        <?php endif; ?>
        <input class="text-input" type="text" name="to_suburb1" id="sqf-to-suburb1" autocomplete="one-time-code" placeholder="<?php esc_html_e("Type suburb name or postcode",'machship-shipping'); ?>" value="<?php echo esc_attr($textfieldValue); ?>" />
        <div id="sqf-suburb-list" style="display: none;"></div>
        <input type="hidden" id="toSuburb" name="ToSuburb" value="<?php echo esc_attr($toSuburb); ?>" />
        <input type="hidden" id="toState" name="ToState" value="<?php echo esc_attr($toState); ?>" />
        <input type="hidden" id="toPostcode" name="ToPostcode" value="<?php echo esc_attr($toPostcode); ?>" />
        <input type="hidden" id="toLocationID" name="ToLocationID" value="<?php echo esc_attr($toLocationId); ?>" />
        <input type="hidden" id="productID" name="ProductID" value="<?php echo esc_attr($productid); ?>" />
      </div>

      <div id="sqf-shipping-cost"><?php echo wp_kses_post($shippingPrices_Display); ?></div>
      <?php
      if( is_checkout() ) { ?>
        <div class="shipping-type-result-div" id="shipping-type-result-div-checkout"></div>
      <?php } ?>
    </div>
  </div>
</div>