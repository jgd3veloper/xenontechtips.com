<?php

if ( ! defined( 'ABSPATH' ) ) exit;

use PremiumAddons\Includes\Helper_Functions;

$prefix = Helper_Functions::get_prefix();

//Get elements settings
$enabled_elements = self::get_enabled_elements();

?>

<div class="pa-section-content">
	<div class="row">
		<div class="col-full">
			<form action="" method="POST" id="pa-features" name="pa-features" class="pa-settings-form">
			<div id="pa-features-settings" class="pa-settings-tab">

            	<div class="pa-section-info-wrap">
                    <div class="pa-section-info">
                        <h4><?php echo sprintf( '%1$s %2$s', $prefix, __('Templates', 'premium-addons-for-elementor') ); ?></h4>
                        <p><?php echo __('Build Professional Website in Minutes Using Our Pre-Made Premium Elementor Templates.', 'premium-addons-for-elementor'); ?></p>
					</div>

					<div class="pa-section-info-cta">
                        <label class="switch">
                            <input type="checkbox" id="premium-templates" name="premium-templates" <?php echo checked( 1, $enabled_elements[ 'premium-templates' ], false ); ?>>
                                <span class="slider round"></span>
                            </label>
						</p>
					</div>
				</div>

				<div class="pa-section-info-wrap">
                    <div class="pa-section-info">
                        <h4><?php echo __('Cross-Domain Copy N’ Paste', 'premium-addons-for-elementor'); ?></h4>
                        <p><?php echo __('Copy any Elementor content from site to another in just ONE click.', 'premium-addons-for-elementor'); ?></p>
					</div>

					<div class="pa-section-info-cta">
                        <label class="switch">
                            <input type="checkbox" id="premium-cross-domain" name="premium-cross-domain" <?php echo checked( 1, $enabled_elements[ 'premium-cross-domain' ], false ); ?>>
                                <span class="slider round"></span>
                            </label>
						</p>
					</div>
                </div>
                
                <div class="pa-section-info-wrap">
                    <div class="pa-section-info">
                        <h4><?php echo __('Duplicator', 'premium-addons-for-elementor'); ?></h4>
                        <p><?php echo __('Duplicate any post, page or template on your website.', 'premium-addons-for-elementor'); ?></p>
					</div>

					<div class="pa-section-info-cta">
                        <label class="switch">
                            <input type="checkbox" id="premium-duplicator" name="premium-duplicator" <?php echo checked( 1, $enabled_elements[ 'premium-duplicator' ], false ); ?>>
                                <span class="slider round"></span>
                            </label>
						</p>
					</div>
				</div>

                <input type="submit" value="<?php echo __('Save Settings', 'premium-addons-for-elementor'); ?>" class="button pa-btn pa-save-button">
			</div>
			</form> <!-- End Form -->
		</div>
	</div>
</div> <!-- End Section Content -->