<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
$variations = $product_data->get_available_variations();
if(!empty($variations)){
  foreach($variations as $variation){
    $variationID = $variation['variation_id'];
    $attributes = !empty($variation['attributes']) ? implode(' / ', $variation['attributes']) : '';
    $title = '#'.$variationID;
    $no_cartons = get_post_meta( $variationID, '_no_of_cartons', true ) != "" ? get_post_meta( $variationID, '_no_of_cartons', true ) : 1;
    $carton_items = get_post_meta( $variationID, '_carton_items', true );
    $free_shipping = get_post_meta( $variationID, '_free_shipping', true );
    ?>
    <tr class="var_<?php echo esc_attr($variationID);?>">
      <th colspan="2" style="border-bottom: 1px solid #ccc"><?php echo esc_html($title);?> - <?php echo esc_html(strtoupper($attributes)); ?></th>
    </tr>
    <tr class="var_<?php echo esc_attr($variationID);?>">
      <th><label for="woo-machship-no-of-cartons"><?php esc_html_e( 'No. of Items', 'machship-shipping' ); ?></label></th>
      <td>
        <input type="number" class="woo-machship-variation-no-of-cartons" style="" name="woo-machship-variation-no-of-cartons[<?php echo esc_attr($variationID);?>]" id="woo-machship-variation-no-of-cartons_<?php echo esc_attr($variationID);?>" value="<?php echo esc_attr($no_cartons); ?>" placeholder="" step="1" min="1">
      </td>
    </tr>
    <tr class="var_carton_row_settings var_<?php echo esc_attr($variationID);?>">
      <td colspan="2">
        <table class="wp-list-table widefat fixed variation_quote_item_list_table" id="_variation_quote_item_list" style="width:auto;padding:10px;">
          <thead>
          <tr>
            <th>Item #</th>
            <th>Length(cm)</th>
            <th>Width(cm)</th>
            <th>Height(cm)</th>
            <th>Weight(kg)</th>
            <th><strong>Package Type</strong></th>
          </tr>
          </thead>
          <tbody>
          <?php
          if(	!empty($carton_items) ) {

            $increment = 1;
          //sometimes the above was returning an array - we suspect from other themes/plugins - so we added this handling.
					//we need an object, that's why we re-encode it.
					if(is_array($carton_items)) {
						$carton_items = json_encode($carton_items);
					}
            foreach( json_decode($carton_items) as $item ) { ?>
              <tr>
                <td>Item <?php echo esc_attr($increment); ?></td>
                <td><input type="number" name="carton_length[<?php echo esc_attr($variationID); ?>][]" min="0.1" step="0.01" value="<?php echo esc_html($item->length); ?>" class="large-text" /></td>
                <td><input type="number" name="carton_width[<?php echo esc_attr($variationID); ?>][]" min="0.1" step="0.01" value="<?php echo esc_html($item->width); ?>" class="large-text" /></td>
                <td><input type="number" name="carton_height[<?php echo esc_attr($variationID); ?>][]" min="0.1" step="0.01" value="<?php echo esc_html($item->height); ?>" class="large-text" /></td>
                <td><input type="number" name="carton_weight[<?php echo esc_attr($variationID); ?>][]" min="0.1" step="0.01" value="<?php echo esc_html($item->weight); ?>" class="large-text" /></td>

                <td>
                  <select name="packaging_type[<?php echo esc_attr($variationID) ;?>][]">
                    <?php
                    foreach( $packageTypes as $key_package => $value_package ) {
                      $selected = '';
                      if( $item->packaging_type == $key_package ) $selected = 'selected="selected"';

                      echo '<option value="'.esc_attr($key_package).'" '.$selected.'>'. esc_html($value_package).'</option>';
                    } ?>
                  </select>
                </td>
              </tr>

              <?php
              $increment++;
            }
            // If empty cartons array add defaults
          } else { ?>

            <tr class="var_<?php echo esc_attr($variationID);?>">
              <td>Item 1</td>
              <td><input type="number" name="carton_length[<?php echo esc_attr($variationID) ;?>][]" min="0.01" step="0.01" class="large-text" /></td>
              <td><input type="number" name="carton_width[<?php echo esc_attr($variationID) ;?>][]" min="0.01" step="0.01" class="large-text" /></td>
              <td><input type="number" name="carton_height[<?php echo esc_attr($variationID) ;?>][]" min="0.01" step="0.01" class="large-text" /></td>
              <td><input type="number" name="carton_weight[<?php echo esc_attr($variationID) ;?>][]" min="0.01" step="0.01" class="large-text" /></td>

              <td>
                <select name="packaging_type[<?php echo esc_attr($variationID) ;?>][]">
                  <?php
                  foreach( $packageTypes as $key_package => $value_package ) {
                    echo '<option value="'.esc_attr($key_package).'">'.esc_html($value_package).'</option>';
                  } ?>
                </select>
              </td>
            </tr>
          <?php
          } ?>
          </tbody>
        </table>
      </td>
    </tr>
  <?php
  }
}
?>
