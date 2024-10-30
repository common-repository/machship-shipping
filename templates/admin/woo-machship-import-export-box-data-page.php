<spa?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;
?>

<div class="wrap">
    <h2><?php echo esc_html__( 'Machship Import/Export Box Data', 'machship-shipping' ); ?></h2>
    <div id="poststuff">
        <div id="post-body-content" class="woo-machship-import-export-box-data">
            <div class="stuffbox">
                <div class="woo-machship-export-section">
                    <div class="woo-machship-info" style="display: none;">
                        <span class="success">You have exported 120 products box settings</span>
                        <span class="error">Error! an unknown error occurred</span>
                    </div>
                    <h2>Export Data</h2>
                    <p>This will be used to export all machship products box settings</p>

                    <div class="woo-machship-groupform">
                        <!-- Default will be yes -->
                        <p>Include publish product</p>
                        <label><input type="radio" name="export_publish_include" value="yes" checked> Yes</label>
                        <label><input type="radio" name="export_publish_include" value="no"> No</label>
                    </div>

                    <div class="woo-machship-groupform">
                        <!-- Default will be no -->
                        <p>Include machship enabled</p>
                        <label><input type="radio" name="export_ms_enabled_include" value="yes" checked> Yes</label>
                        <label><input type="radio" name="export_ms_enabled_include" value="no"> No</label>
                    </div>

                    <button class="button button-primary" id="ms-btn-export-data">Export Now</button>
                </div>

                <div class="woo-machship-export-section section-import">
                    <div class="woo-machship-info" style="display: none;">
                        <span class="success">You have imported 120 products box settings</span>
                        <span class="error">Error! Invalid format</span>
                    </div>
                    <h2>Import Data</h2>
                    <p>This will be used to import box settings. Note: Make sure that you have the proper csv file from our export feature.</p>
                    <div class="woo-machship-processing">
                        <span class="spinner is-active"></span>
                        Importing data please wait...
                    </div>
                    <input type="file" id="woo-machship-input-import" style="display:none" accept="text/csv" />
                    <button class="button button-primary" id="ms-btn-import-data">Import Now</button>
                </div>

            </div>
        </div>
    </div>
</div>