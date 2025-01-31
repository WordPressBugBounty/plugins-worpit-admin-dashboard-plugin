<?php

$sUrlServiceHomeHelp = 'http://icwp.io/help';
$sUrlServiceHomeFeatures = 'http://icwp.io/features';

$bWhitelabelled = ( $aPluginLabels[ 'Name' ] != 'iControlWP' );
?>

	<script type="text/javascript">
		jQuery( document ).ready(
		function () {
			jQuery( 'input.confirm-plugin-reset' ).on( 'click',
				function () {
					var $oThis = jQuery( this );
					if ( $oThis.is( ':checked' ) ) {
						jQuery( 'button[name=submit_reset]' ).removeAttr( 'disabled' );
					}
					else {
						jQuery( 'button[name=submit_reset]' ).attr( 'disabled', 'disabled' );
					}
				}
			);
		}
	);

	function icwp_formAddSiteSubmit() {
		var $elemSubmit = jQuery( "button[name=icwp_add_remotely_submit]" );
		$elemSubmit.html( "Please wait, attempting to add site - please do not reload this page." );
		$elemSubmit.attr( "disabled", "disabled" );

		var form = jQuery( "#icwpform-remote-add-site" ).submit();
	}
	</script>
	<style>
		#pluginlogo_32 {
		background: url("<?php echo esc_url($aPluginLabels['icon_url_32x32']); ?>") no-repeat 0 3px transparent;
	}
	</style>

	<div class="row">
		<div class="span12">
			<div class="well">
				<?php
		if ( empty( $aHiddenOptions[ 'key' ] ) ) {
			echo '<h3>You need to generate your Access Key - reset your key using the red button below.</h3>';
		}
		?>
				<div class="assigned-state">
					<?php if ( $bAssigned ): ?>
						<h3 id="isAssigned">
								<?php echo sprintf( 'Currently connected to %s.%s',
										'<u>'.esc_html( esc_html( $aPluginLabels[ 'Name' ] ) ).'</u>',
										$bWhitelabelled ? '' : esc_html(" ($sAssignedTo)") );
								?>
						</h3>

		  <?php else: ?>
						<h3>The unique <?php echo esc_html( $aPluginLabels[ 'Name' ] ); ?> Access Key for this site is:
							<div class="the-key"><?php echo esc_html( $sAuthKey ); ?></div>
						</h3>
						<h4 id="isNotAssigned">Currently waiting for connection from a <?php echo esc_html( $aPluginLabels[ 'Name' ] ); ?>
																	 account.
							<br />[ <a href="<?php echo esc_url( $aPluginLabels[ 'PluginURI' ] ); ?>" id="signupLinkIcwp"
												 target="_blank">Don't have a <?php echo esc_html( $aPluginLabels[ 'Name' ] ); ?> account? Get it today!</a> ]</h4>
						<p><strong>Important:</strong> if you don't plan to add this site now, disable this plugin to prevent this site from being added to another <?php echo esc_html( $aPluginLabels[ 'Name' ] ); ?>
																					 account.</p>
		  <?php endif; ?>
				</div>

			</div>
		</div>
	</div>
<?php if ( !$bIsLinked ) : ?>
	<div class="row">
		<div class="span12">
			<div class="well">
				<h3>Remotely add site to <?php echo esc_html( $aPluginLabels[ 'Name' ] ); ?> account</h3>
				<p>You may add your site to your <?php echo esc_html( $aPluginLabels[ 'Name' ] ); ?>
					 from here, or from within your <?php echo esc_html( $aPluginLabels[ 'Name' ] ); ?> Dashboard. Both methods are supported and secure.</p>
				<p>Note: If this doesn't work, your web host probably has restrictions on outgoing web connections. Please try adding this site from you <?php echo esc_html( $aPluginLabels[ 'Name' ] ); ?>
					 dashboard.</p>
				<form method="POST" name="icwpform-remote-add-site" id="form-remote-add-site" class="">
					<input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo esc_attr( $nonce_field ); ?>">
					<input type="hidden" name="<?php echo esc_html( $var_prefix ); ?>plugin_form_submit" value="Y" />
					<fieldset>
						<legend style="margin-bottom: 8px;">Remote Add Site</legend>
						<label for="_account_auth_key"><?php echo esc_html( $aPluginLabels[ 'Name' ] ); ?> Unique Account Authentication Key:
							<input name="account_auth_key" type="text" class="span6" id="_account_auth_key" />
						</label>
						<label for="_account_email_address"><?php echo esc_html( $aPluginLabels[ 'Name' ] ); ?> Account Email Address:
							<input name="account_email_address" type="text" class="span6" id="_account_email_address" />
						</label>
					</fieldset>
					<button class="btn" name="<?php echo esc_html( $var_prefix ); ?>remotely_add_site_submit" value="Y"
									type="submit" onclick="icwp_formAddSiteSubmit()">Add Site</button>
				</form>
			</div>
		</div>
	</div>
<?php endif; ?>

	<div class="row">
		<div class="span12">
			<div class="well">
				<div class="reset-authentication" name="">
					<h3>Reset <?php echo esc_html( $aPluginLabels[ 'Name' ] ); ?> Access Key</h3>
					<p>You can break the connection with <?php echo esc_html( $aPluginLabels[ 'Name' ] ); ?> and regenerate a new access key, using the button below</p>
					<p><strong>Warning:</strong> Clicking this button <em>will disconnect this site if it has been added to a <?php echo esc_html( $aPluginLabels[ 'Name' ] ); ?>
																																account</em>. <u>Not Recommended - please contact support if you're seeing issues</u>.</p>
					<div>
						<form action="<?php echo esc_url( $form_action ); ?>" method="POST" name="form-reset-auth"
									id="form-reset-auth">
							<input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo esc_attr( $nonce_field ); ?>">
							<input type="hidden" name="<?php echo esc_attr( $var_prefix ); ?>plugin_form_submit" value="Y" />
							<label>
								<input type="checkbox"
											 name="<?php echo esc_html( $var_prefix ); ?>reset_plugin"
											 class="confirm-plugin-reset"
											 value="Y"
											 style="margin-right:10px;"
								/>I'm sure I want to reset the <?php echo esc_html( $aPluginLabels[ 'Name' ] ); ?> plugin.
							</label>
							<button class="btn btn-danger" disabled="disabled" name="submit_reset" type="submit">Reset Plugin</button>
						</form>
					</div>
				</div>

			</div>
		</div>
	</div>

<?php if ( !$bWhitelabelled ) : ?>

	<div class="row">
		<div class="span12">
			<div class="well">
				<div class="row">
					<div class="span11">
						<h2>About <?php echo esc_html( $aPluginLabels[ 'Name' ] ); ?></h2>
						<div>
							<p><?php echo esc_html( $aPluginLabels[ 'Name' ] ); ?> is <strong>completely free</strong> to get started with an unlimited sites 15 day trial -
								<a href="<?php echo esc_html( $aPluginLabels[ 'PluginURI' ] ); ?>" id="signupLinkIcwp"
									 target="_blank">Sign Up for a <?php echo esc_html( $aPluginLabels[ 'Name' ] ); ?>
																	 account here</a>.</p>
						</div>
						<h3><?php echo esc_html( $aPluginLabels[ 'Name' ] ); ?> Features [<a
											href="<?php echo esc_html( $sUrlServiceHomeFeatures ); ?>"
											target="_blank">full details</a>]</h3>
					</div>
				</div>
				<div class="row">
					<div class="span5">
						<ul>
							<li>Free to get started with unlimited sites, 15 day trial</li>
							<li>Manage all your WordPress sites in 1 place</li>
							<li>One-click Updates for WordPress.org Plugins, Themes and WordPress Core</li>
							<li>One-Click login to admin each WordPress website</li>
							<li>True pay-as-you-go pricing.</li>
						</ul>
					</div>
					<div class="span6">
						<ul>
							<li>Fully Automated WordPress Installer Tool!</li>
							<li>Complete <?php echo esc_html( $aPluginLabels[ 'Name' ] ); ?>
									Dashboard Access - no standard/pro/business tiers</li>
							<li>Access to all future <?php echo esc_html( $aPluginLabels[ 'Name' ] ); ?> Dashboard updates!</li>
							<li>Smooth scaling based on your needs</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>

<?php else :
	esc_html( $sExtraContent );
endif;