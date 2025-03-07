<?php declare( strict_types=1 );

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Worpdrive\Database\Operators\Table;

class EnumTablePrimaryKeys {

	public function all() :array {
		return \array_merge(
			$this->wordpress(),
			$this->woocommerce(),
			$this->gravityForms(),
			$this->edd(),
		);
	}

	private function edd() :array {
		return [
			'edd_customer_addresses'       => 'id',
			'edd_customer_email_addresses' => 'id',
			'edd_customers'                => 'id',
			'edd_customermeta'             => 'meta_id',
			'edd_license_activations'      => 'site_id',
			'edd_licensemeta'              => 'meta_id',
			'edd_licenses'                 => 'id',
			'edd_logmeta'                  => 'meta_id',
			'edd_logs_file_downloads'      => 'id',
			'edd_logs_file_downloadmeta'   => 'meta_id',
			'edd_logs'                     => 'id',
			'edd_logs_api_requestmeta'     => 'meta_id',
			'edd_logs_api_requests'        => 'id',
			'edd_logs_emails'              => 'id',
			'edd_notes'                    => 'id',
			'edd_ordermeta'                => 'meta_id',
			'edd_orders'                   => 'id',
			'edd_order_addresses'          => 'id',
			'edd_order_itemmeta'           => 'meta_id',
			'edd_order_items'              => 'id',
			'edd_order_transactions'       => 'id',
			'edd_subscriptions'            => 'id',
		];
	}

	private function gravityForms() :array {
		return [
			'gf_draft_submissions' => 'id',
			'gf_entry'             => 'id',
			'gf_entry_meta'        => 'id',
			'gf_form'              => 'id',
			'gf_form_revisions'    => 'id',
			'gf_form_view'         => 'id',
		];
	}

	private function wordpress() :array {
		return [
			'blogs'         => 'blog_id',
			'blogmeta'      => 'meta_id',
			'comments'      => 'comment_ID',
			'commentsmeta'  => 'meta_id',
			'links'         => 'link_id',
			'options'       => 'option_id',
			'postmeta'      => 'meta_id',
			'posts'         => 'ID',
			//				   'term_relationships' => 'term_taxonomy_id', No auto_increment primary key
			'term_taxonomy' => 'term_taxonomy_id',
			'termmeta'      => 'meta_id',
			'terms'         => 'term_id',
			'usermeta'      => 'umeta_id',
			'users'         => 'ID',

			'registration_log' => 'ID',
			'site'             => 'id',
			'sitemeta'         => 'meta_id',
			'signups'          => 'signup_id',
		];
	}

	/**
	 * https://github.com/woocommerce/woocommerce/wiki/Database-Description
	 */
	private function woocommerce() :array {
		return [
			'actionscheduler_logs'                => 'log_id',
			'woocommerce_log'                     => 'log_id',
			'woocommerce_sessions'                => 'session_id',
			'woocommerce_order_items'             => 'order_item_id',
			'woocommerce_order_itemmeta'          => 'meta_id',
			'woocommerce_payment_tokens'          => 'token_id',
			'woocommerce_payment_tokenmeta'       => 'meta_id',
			'woocommerce_shipping_zones'          => 'zone_id',
			'woocommerce_shipping_zone_locations' => 'location_id',
			'woocommerce_shipping_zone_methods'   => 'instance_id',
			'woocommerce_tax_rate_locations'      => 'location_id',
			'woocommerce_tax_rates'               => 'tax_rate_id',
			'wc_download_log'                     => 'download_log_id',
			'wc_webhooks'                         => 'webhook_id',
		];
	}
}