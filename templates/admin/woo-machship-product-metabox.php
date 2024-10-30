<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Product metabox fields
 */
?>
<style>
    .form-table tr td label { margin-right: 20px;}
</style>
<table class="form-table">
    <tr>
      <th><?php esc_html_e("Enable", 'machship-shipping'); ?></th>
      <td>
        <label>
          <input class="show_plugin" type="checkbox" <?php if($show_plugin_value == '1'){ echo esc_attr('checked="checked"'); } ?> value="<?php echo esc_attr($show_plugin_value);?>"/>
          <input class="show_plugin_hidden" type="hidden" name="woo_machship_show_plugin" value="<?php echo esc_attr($show_plugin_value);?>"/>
          Check to enable for this product
        </label>
      </td>
    </tr>
    <?php if ($is_dynamic) { ?>
    <tr>
      <th><?php esc_html_e("Pack Individually", 'machship-shipping'); ?></th>
      <td>
        <label>
          <input
            class="pack_individually"
            type="checkbox"
            name="pack_individually"
            <?php if($pack_individually == '1'){ echo esc_attr('checked="checked"'); } ?>
            value="<?php echo esc_attr($pack_individually);?>">
            Check this to ensure this product is never re-packaged, and sent by itself.
        </label>
      </td>
    </tr>
    <?php } ?>
    <tr>
      <th><h3><?php esc_html_e("International Fields", 'machship-shipping'); ?></h3></th>
    </tr>
    <tr>
      <th><?php esc_html_e("Harmonisation Code", 'machship-shipping'); ?></th>
      <td>
        <input
          type="number"
          oninput="javascript: if (this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"
          name="international_harmonisation_code"
          value="<?php echo esc_attr($internationalFields['harmonisation_code']); ?>"
          maxlength="6">
        <small>(6 digits)</small>
      </td>
    </tr>
    <tr>
      <th><?php esc_html_e("Country Of Manufacture", 'machship-shipping'); ?></th>
      <td>

        <?php
          echo '<select name="international_country_manufacture">';

          foreach ( $countries as $code => $country ) {
            $countrySelected = $code === $internationalFields['country_manufacture'] ? 'selected="selected"' : '';
            if (empty($internationalFields['country_manufacture']) && $code === 'AU') {
              $countrySelected = 'selected="selected"'; // default to AU
            }
            echo '<option value="' . esc_attr($code) . '" ' . esc_attr($countrySelected) . '>' . esc_html($country) . '</option>';
          }

          echo '</select>';
        ?>

      </td>
    </tr>
    <tr>
      <th><h3><?php esc_html_e("Box settings", 'machship-shipping'); ?></h3></th>
    </tr>
    <?php if(isset($settings['warehouse_locations']) && !empty($settings['warehouse_locations'])){ ?>
      <tr>
        <th><label for="warehouse_locations">
            <?php esc_html_e( 'Available At Warehouse', 'machship-shipping' ); ?>
          </label>
        </th>
        <td>
          <?php
          foreach($settings['warehouse_locations'] as $key=>$location) {
            $readonly = "";
            ?>
            <label>
              <input class="shipping_location" type="checkbox" value="<?php echo esc_attr($key); ?>" name="woo_machship_product_shipping_locations[]" <?php if(is_array($product_locations) && !empty($product_locations)){ checked( in_array($key, $product_locations) );} ?> <?php echo esc_attr($readonly); ?> />
              <?php echo esc_html($location['warehouse_name']); ?>
            </label>
          <?php } ?>
        </td>
      </tr>
    <?php }
    if($product_data->get_type() == 'variable'){
      require_once( $path. '/templates/admin/variable_product.php' );
    } else if($product_data->get_type() == 'simple' || $product_data->get_type() == 'composite'){
      require_once( $path. '/templates/admin/simple_product.php' );
    }
    ?>
  </table>