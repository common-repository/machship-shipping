<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;
?>

<div class="wrap">
  <h2><?php echo esc_html__( 'Machship Product Box Migration', 'machship-shipping' ); ?></h2>
  <div class="woo-machship-product-box-wrap">
    <div class="woo-machship-product-box-start">
      <div class="woo-machship-product-box-msg">
        <p>
          <?php
          if(isset($_GET['product_box_migration']) && sanitize_text_field($_GET['product_box_migration']) == 'true'){
            echo esc_html__("Woocommerce box settings shipping dimensions has been copied to Machship box settings.", 'machship-shipping');
          } else {
            echo esc_html__( 'Use the function below to copy all of your woocommerce box settings across to the Machship box settings all at once.', 'machship-shipping' );
          }
          ?>
        </p>
      </div>
    </div>
  </div>
  <div id="poststuff">
    <div id="post-body-content" class="woo-machship-product-box-start">
      <form id="woo-machship-product-box_form" action="<?php echo esc_url(admin_url( 'admin.php' )); ?>" method="post">
        <?php wp_nonce_field( basename( __FILE__ ), 'woo-machship-product-box_nonce' ); ?>
        <input type="hidden" name="action" value="woo_machship_product_box_update">
        <div id="namediv" class="stuffbox">
          <h2><label>Overwrite my existing Machship Product Package settings</label></h2>
          <div class="inside">
            <input type="checkbox" name="woo-machship-product-box_overwrite_settings" id="woo-machship-product-box_overwrite_settings" value="<?php echo esc_attr($overwrite); ?>" <?php if($overwrite == 'yes'){?>checked="checked" <?php } ?> style="width: 1%"/>
          </div>
          <h2><label>Package Type</label></h2>
          <div class="inside">
            <select id="woo-machship-product-box_package_type" name="woo-machship-product-box_package_type">
              <?php
              foreach($packaging_types as $key=>$type){
                ?>
                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($type);?></option>
              <?php
              }
              ?>
            </select>
          </div>
          <?php
          if (!empty($settings['warehouse_locations'])) {
          ?>
          <h2><label>Available At Warehouse</label></h2>
          <div class="inside">
            <?php
            foreach($settings['warehouse_locations'] as $key=>$location) {?>
              <input class="shipping_location" type="checkbox" value="<?php echo esc_attr($key); ?>" name="woo_macship_product_shipping_locations[]" <?php if(!empty($product_location)){ checked( in_array($key, $product_location) );} ?> style="width: 1%"/>
              <?php echo esc_html($location['warehouse_name']); ?><br />
            <?php } ?>
          </div>
          <?php
          }
          else {
          ?>
          <div class="inside">
            <p>No warehouse locations available.</p>
          </div>
          <?php
          }
          ?>

          <div class="inside woo-machship-product-box_btn">
            <button id="woo-machship-product-box_btn" class="button button-primary woocommerce-save-button" type="button">
              <span class="dashicons dashicons-update"></span>
              <?php echo esc_html__( 'GO', 'machship-shipping' ) ?>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>