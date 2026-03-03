<?php
$sBaseDirName = dirname(__FILE__).DIRECTORY_SEPARATOR; ?>

<div class="wrap">
	<div class="bootstrap-wpadmin <?php echo esc_attr($sFeatureSlug) ?? ''; ?> icwp-options-page">
		<div class="page-header">
			<h2>
				<span class="feature-headline">
					<a id="pluginlogo_32" class="header-icon32" href="http://icwp.io/2k" target="_blank"></a><?php echo esc_html($sPageTitle); ?>
				<?php if ( !empty( $sTagline ) ) : ?>
					<small class="feature-tagline">- <?php echo esc_html($sTagline); ?></small>
				<?php endif; ?>
			</h2>
		</div>