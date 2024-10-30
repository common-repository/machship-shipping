<?php  if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly  ?>
<tr class="cart-machship-shipping" style="display: none">
  <th><?php echo esc_html($title); ?></th>
  <td data-title="<?php echo esc_attr($title); ?>">
    <div id="<?php echo esc_attr($idWrapper); ?>">
      <div id="<?php echo esc_attr($uniqueID); ?>" class="<?php echo esc_attr($class); ?> cart">
        <div id="woo-machship-ajax-loader" style="background-image: url( <?php echo esc_attr(esc_url(admin_url('images/spinner.gif'))); ?> );"></div>
        <div class="woo-machshipt-sqf-body">
          <div class="sqf-suburb-box">
            <?php
            $textfieldValue = ( $toSuburb != '' && $toPostcode != '' && $toState != '' ) ? $toSuburb .' '.$toState. ' - ' . $toPostcode:'';
            if( is_checkout() ) {
              ?>
            <?php } ?>
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
  </td>
</tr>