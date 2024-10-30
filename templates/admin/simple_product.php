<?php  if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly  ?>
<tr>
  <th><label for="woo-machship-no-of-cartons">
      <?php esc_html_e( 'No. of Items', 'machship-shipping' ); ?>
    </label></th>
  <td>
    <input type="number" name="no_of_cartons" id="woo-machship-no-of-cartons" min="1" value="<?php  echo esc_attr($no_of_cartons); ?>" />
  </td>

</tr>
<tr>
  <td colspan="2">
    <table class="wp-list-table widefat fixed quote_item_list_table" id="quote_item_list" style="width:auto;padding:10px;">
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
      $cartons__ = json_decode($carton_items);

      if(	!empty($carton_items) && !empty($cartons__)) {

        $increment = 1;
        foreach($cartons__  as $item ) { ?>
          <tr>
            <td>Item <?php echo esc_html($increment); ?></td>
            <td><input type="number" name="carton_length[]" min="0.01" step="0.01" value="<?php echo esc_html($item->length ?? ''); ?>" class="large-text" /></td>
            <td><input type="number" name="carton_width[]" min="0.01" step="0.01" value="<?php echo esc_html($item->width ?? ''); ?>" class="large-text" /></td>
            <td><input type="number" name="carton_height[]" min="0.01" step="0.01" value="<?php echo esc_html($item->height ?? ''); ?>" class="large-text" /></td>
            <td><input type="number" name="carton_weight[]" min="0.01" step="0.01" value="<?php echo esc_html($item->weight ?? ''); ?>" class="large-text" /></td>

            <td>
              <select name="packaging_type[]">
                <?php
                foreach($packageTypes as $key_package => $value_package ) {
                  $selected = '';
                  if( $item->packaging_type == $key_package ) $selected = 'selected="selected"';

                  echo '<option value="'.esc_attr($key_package).'" '.esc_attr($selected).'>'.esc_html($value_package).'</option>';
                } ?>
              </select>
            </td>
          </tr>

          <?php
          $increment++;
        }

        // If empty cartons array add defaults
      } else { ?>

        <tr>
          <td>Item 1</td>
          <td><input type="number" name="carton_height[]" min="0.1" step="0.01" class="large-text" /></td>
          <td><input type="number" name="carton_width[]" min="0.1" step="0.01" class="large-text" /></td>
          <td><input type="number" name="carton_length[]" min="0.1" step="0.01" class="large-text" /></td>
          <td><input type="number" name="carton_weight[]" min="0.1" step="0.01" class="large-text" /></td>
          <td>
            <select name="packaging_type[]">
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