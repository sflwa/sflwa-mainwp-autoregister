<?php
/**
 * Plugin Name:       SFLWA MainWP Auto-Register Bridge
 * Plugin URI:        https://github.com/sflwa/sflwa-mainwp-autoregister
 * Description:       v1.8.8: Restored Pre-Register Audit and standardized Tag/Client endpoints.
 * Version:           1.8.8
 * Author:            South Florida Web Advisors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class SFLWA_MainWP_Bridge {

	private string $api_key       = 'YOUR API KEY HERE';
	private string $dashboard_url = 'YOUR DASHBOARD HERE';

	public function __construct() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'sflwa-mainwp', [ $this, 'cli_handler' ] );
		}
	}

	private function ensure_local_settings( $user_id ): void {
		if ( ! is_plugin_active( 'mainwp-child/mainwp-child.php' ) ) return;
		delete_option( 'mainwp_child_auth' );
		delete_option( 'mainwp_child_pubkey' );
		update_option( 'mainwp_child_uniqueId', md5( uniqid( (string) wp_rand(), true ) ) );
		update_option( 'mainwp_child_secure_connection', 'no' );
		update_user_option( $user_id, 'mainwp_child_user_enable_passwd_auth_connect', 0 );
		
		$settings = get_option( 'mainwp_child_settings', [] );
		if ( ! is_array( $settings ) ) { $settings = []; }
		$settings['mainwp_child_user_enable_pwd_auth_connect'] = 0;
		$settings['require_password'] = 'no';
		update_option( 'mainwp_child_settings', $settings );
	}

	private function normalize_data( $data ): array {
		$items = isset( $data['data'] ) ? $data['data'] : $data;
		if ( ! is_array( $items ) || empty( $items ) ) return [];
		$items = array_values( $items );
		usort( $items, function( $a, $b ) {
			return (int) ( $a['id'] ?? 0 ) <=> (int) ( $b['id'] ?? 0 );
		});
		return $items;
	}

	public function cli_handler( $args, $assoc_args ): void {
		$subcommand = $args[0] ?? 'register';

		// User Selection Priority
		$user_id = isset( $assoc_args['user'] ) ? intval( $assoc_args['user'] ) : 0;
		if ( ! $user_id ) {
			$admins = get_users( [ 'role' => 'administrator', 'number' => 1, 'orderby' => 'ID' ] );
			$user_id = ! empty( $admins ) ? $admins[0]->ID : 0;
		}

		// 1. LOOKUP COMMANDS
		if ( $subcommand === 'groups' || $subcommand === 'clients' ) {
			$api_endpoint = ( $subcommand === 'groups' ) ? 'tags' : 'clients';
			$res = wp_remote_get( rtrim( $this->dashboard_url, '/' ) . '/wp-json/mainwp/v2/' . $api_endpoint, [
				'headers' => [ 'Authorization' => 'Bearer ' . $this->api_key ],
				'timeout' => 30
			]);
			$body = json_decode( wp_remote_retrieve_body( $res ), true );
			$items = $this->normalize_data( $body );

			if ( ! empty( $items ) ) {
				$fields = ( $subcommand === 'groups' ) ? [ 'id', 'name', 'count_sites' ] : [ 'id', 'name', 'client_email' ];
				WP_CLI\Utils\format_items( 'table', $items, $fields );
			} else {
				WP_CLI::error( "No " . $subcommand . " found." );
			}
			return;
		}

		// 2. PRE-REGISTER AUDIT
		if ( $subcommand === 'pre-register' ) {
			$user = get_userdata( $user_id );
			$user_pwd_auth = get_user_option( 'mainwp_child_user_enable_passwd_auth_connect', $user_id );
			$auth_log = get_option( 'mainwp_child_auth' );

			WP_CLI::log( WP_CLI::colorize( "%Y--- PRE-REGISTER HEALTH CHECK (v1.8.8) ---%n" ) );
			WP_CLI::log( "Target User  : " . ( $user ? $user->user_login : 'admin' ) . " (ID: $user_id)" );
			WP_CLI::log( "User Meta Pwd: " . ( $user_pwd_auth == 1 ? WP_CLI::colorize("%REnabled%n") : WP_CLI::colorize("%GDisabled%n") ) );
			WP_CLI::log( "Stale Auth   : " . ( ! empty($auth_log) ? WP_CLI::colorize("%RFound (Will wipe)%n") : WP_CLI::colorize("%GEmpty%n") ) );
			WP_CLI::log( "Unique ID    : " . ( get_option('mainwp_child_uniqueId') ?: 'Not Set' ) );
			return;
		}

		// 3. REGISTRATION
		if ( $subcommand === 'register' ) {
			$group  = $assoc_args['group'] ?? '3';
			$client = $assoc_args['client'] ?? '0';
			$user   = get_userdata( $user_id );

			WP_CLI::log( WP_CLI::colorize( "%Y--- SFLWA Registration Init ---%n" ) );
			WP_CLI::log( sprintf( "User: %s | Groups: %s | Client: %s", $user->user_login, $group, $client ) );
			
			$this->ensure_local_settings( $user_id );
			
			$params = [
				'url'       => get_site_url(),
				'name'      => get_bloginfo( 'name' ),
				'admin'     => $user->user_login,
				'uniqueid'  => get_option( 'mainwp_child_uniqueId' ),
				'groupids'  => $group,
				'client_id' => $client,
			];

			$endpoint = add_query_arg( array_filter( $params ), rtrim( $this->dashboard_url, '/' ) . '/wp-json/mainwp/v2/sites/add/' );
			$response = wp_remote_post( $endpoint, [
				'headers' => [ 'Authorization' => 'Bearer ' . $this->api_key ],
				'timeout' => 180, 
			]);

			$res_body = json_decode( wp_remote_retrieve_body( $response ), true );
			WP_CLI::log( json_encode( $res_body, JSON_PRETTY_PRINT ) );
			
			if ( isset($res_body['success']) && $res_body['success'] == 1 ) {
				WP_CLI::success( "Site successfully added!" );
			} else {
				WP_CLI::error( "Registration failed." );
			}
		}
	}
}

add_action( 'plugins_loaded', function() {
	if ( ! function_exists( 'is_plugin_active' ) ) require_once ABSPATH . 'wp-admin/includes/plugin.php';
	new SFLWA_MainWP_Bridge();
});
