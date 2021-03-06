<?php
defined( 'ABSPATH' ) || die( 'Cheatin&#8217; uh?' );

/**
 * This warning is displayed when the API KEY isn't already set or not valid
 *
 * @since 1.0
 */
function rocket_need_api_key() {
	?>
	<div class="notice notice-warning">
		<p><strong><?php echo WP_ROCKET_PLUGIN_NAME; ?></strong>: <?php

		printf(
			wp_kses(
				__( 'Get ready to speed up your website! But at first, please <a href="%s">enter your API key</a>.', 'rocket' ),
				array( 'a' => array( 'href' => array() ) )
			),
			esc_url( admin_url( 'admin.php?page=' . WP_ROCKET_PLUGIN_SLUG ) )
		);

		?></p>
	</div>
<?php
}

/**
 * Add Rocket information into API request body
 *
 * @since 1.1.0
 *
 * @return string WP Rocket request body
 */
function rocket_api_request_body() {
	global $wpdb;

	$consumer_key = '';
	if ( isset( $_POST[ WP_ROCKET_SETTINGS_SLUG ]['consumer_key'] ) ) {
		$consumer_key = sanitize_key( $_POST[ WP_ROCKET_SETTINGS_SLUG ]['consumer_key'] );
	} elseif ( '' !== (string) get_rocket_option( 'consumer_key' ) ) {
		$consumer_key = (string) get_rocket_option( 'consumer_key' );
	}

	$server_software = 'unknown';
	$server_types = array( 'apache', 'nginx', 'IIS', 'iis7' );

	foreach ( $server_types as $server_type ) {
		if ( $GLOBALS['is_' . $server_type] ) {
			$server_software = strtolower( $server_type );
		}
	}

	$request_body = array(
		'plugin_name' => WP_ROCKET_PLUGIN_NAME,
		'plugin_version' => WP_ROCKET_VERSION,
		'api_key' => $consumer_key,
		'site_url' => esc_url( home_url() ),
		'site_name' => get_bloginfo( 'name' ),
		'site_language' => get_locale(),
		'site_timezone' => get_option( 'timezone_string' ),
		'multisite' => is_multisite(),
		'wp_version' => get_bloginfo( 'version' ),
		'php_version' => phpversion(),
		'db_version' => $wpdb->db_version(),
		'server_software' => $server_software,
	);

	$request_body = json_encode( $request_body );

	return $request_body;
}

/**
 * Renew all boxes for everyone if $uid is missing
 *
 * @since 1.1.10
 * @modified 2.1 :
 *	- Better usage of delete_user_meta into delete_metadata
 *
 * @param (int|null)     $uid : a User id, can be null, null = all users.
 * @param (string|array) $keep_this : which box have to be kept.
 * @return void
 */
function rocket_renew_all_boxes( $uid = null, $keep_this = array() ) {
	// Delete a user meta for 1 user or all at a time.
	delete_metadata( 'user', $uid, 'rocket_boxes', null === $uid );

	// $keep_this works only for the current user.
	if ( ! empty( $keep_this ) && null !== $uid ) {
		if ( is_array( $keep_this ) ) {
			foreach ( $keep_this as $kt ) {
				rocket_dismiss_box( $kt );
			}
		} else {
			rocket_dismiss_box( $keep_this );
		}
	}
}

/**
 * Renew a dismissed error box admin side
 *
 * @since 1.1.10
 *
 * @param string $function function name.
 * @param int    $uid User ID.
 * @return void
 */
function rocket_renew_box( $function, $uid = 0 ) {
	global $current_user;
	$uid    = 0 === $uid ? $current_user->ID : $uid;
	$actual = get_user_meta( $uid, 'rocket_boxes', true );

	if ( $actual && false !== array_search( $function, $actual, true ) ) {
		unset( $actual[ array_search( $function, $actual, true ) ] );
		update_user_meta( $uid, 'rocket_boxes', $actual );
	}
}

/**
 * Dismissed 1 box, wrapper of rocket_dismiss_boxes()
 *
 * @since 1.3.0
 *
 * @param string $function function name.
 * @return void
 */
function rocket_dismiss_box( $function ) {
	rocket_dismiss_boxes(
		array(
			'box'      => $function,
			'_wpnonce' => wp_create_nonce( 'rocket_ignore_' . $function ),
			'action'   => 'rocket_ignore',
		)
	);
}

/**
 * Create a unique id for some Rocket options and functions
 *
 * @since 2.1
 */
function create_rocket_uniqid() {
	return str_replace( '.', '', uniqid( '', true ) );
}

/**
 * Force our API request body when we hit our urls
 *
 * @since 2.4
 *
 * @param array  $r An array of request arguments.
 * @param string $url Requested URL.
 * @return array An array of requested arguments
 */
function rocket_add_api_request_body( $r, $url ) {
	if ( strpos( $url, rocket_parse_url( WP_ROCKET_URL_MAIN, PHP_URL_HOST ) ) !== false ) {
		$r['body'] = rocket_api_request_body();
	}
	return $r;
}
add_filter( 'http_request_args', 'rocket_add_api_request_body', 10, 2 );

/**
 * Function used to print all hidden fields from rocket to avoid the loss of these.
 *
 * @since 2.1
 *
 * @param array $fields An array of fields to add to WP Rocket settings.
 */
function rocket_hidden_fields( $fields ) {
	if ( ! is_array( $fields ) ) {
		return;
	}

	foreach ( $fields as $field ) {
		echo '<input type="hidden" name="' . WP_ROCKET_SETTINGS_SLUG . '[' . $field . ']" value="' . esc_attr( get_rocket_option( $field ) ) . '" />';
	}
}

/**
 * Sanitizes a string key like the sanitize_key() WordPress function without forcing lowercase.
 *
 * @since 2.7
 *
 * @param string $key A string to sanitize.
 * @return string Sanitized string
 */
function rocket_sanitize_key( $key ) {
	$key = preg_replace( '/[^a-z0-9_\-]/i', '', $key );
	return $key;
}

/**
 * Used to sanitize values of the "Never send cache pages for these user agents" option.
 *
 * @since 2.6.4
 *
 * @param string $ua User Agent string to sanitize.
 * @return string Sanitized user agent string
 */
function rocket_sanitize_ua( $ua ) {
	$ua = preg_replace( '/[^a-z0-9._\(\)\*\-\/\s\x5c]/i', '', $ua );
	return $ua;
}

/**
 * Check if the whole website is on the SSL protocol
 *
 * @since 2.7
 */
function rocket_is_ssl_website() {
	return 'https' === rocket_parse_url( home_url(), PHP_URL_SCHEME );
}

/**
 * Get the WP Rocket documentation URL
 *
 * @since 2.7
 */
function get_rocket_documentation_url() {
	$langs  = array(
		'fr_FR' => 'fr.',
		'it_IT' => 'it.',
		'de_DE' => 'de.',
	);
	$lang   = get_locale();
	$prefix = isset( $langs[ $lang ] ) ? $langs[ $lang ] : '';
	$url    = "http://{$prefix}docs.wp-rocket.me/?utm_source=wp-rocket&utm_medium=wp-admin&utm_term=doc-support&utm_campaign=plugin";

	return $url;
}

/**
 * Get WP Rocket FAQ URL
 *
 * @since 2.10
 * @author Remy Perona
 *
 * @return string URL in the correct language
 */
function get_rocket_faq_url() {
	$langs  = array(
		'fr_FR' => 'fr.docs.wp-rocket.me/category/146-faq',
		'it_IT' => 'it.docs.wp-rocket.me/category/321-domande-frequenti',
		'de_DE' => 'de.docs.wp-rocket.me/category/285-haufig-gestellte-fragen-faq',
	);
	$lang   = get_locale();
	$faq 	= isset( $langs[ $lang ] ) ? $langs[ $lang ] : 'docs.wp-rocket.me/category/65-faq';
	$url    = "http://{$faq}/?utm_source=wp-rocket&utm_medium=wp-admin&utm_term=doc-faq&utm_campaign=plugin";

	return $url;
}

/**
 * Performs the database optimization
 *
 * @since 2.8
 * @author Remy Perona
 */
function do_rocket_database_optimization() {
	$options = array(
	 'revisions',
	 'auto_drafts',
	 'trashed_posts',
	 'spam_comments',
	 'trashed_comments',
	 'expired_transients',
	 'all_transients',
	 'optimize_tables',
	);

	foreach ( $options as $option ) {
		if ( get_rocket_option( 'database_' . $option, false ) ) {
			rocket_database_optimize( $option );
		}
	}
}

/**
 * Optimizes the database depending on the option
 *
 * @since 2.8
 * @author Remy Perona
 *
 * @param string $type Type of optimization to perform.
 */
function rocket_database_optimize( $type ) {
	global $wpdb;

	switch ( $type ) {
		case 'revisions':
			$query = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = %s", 'revision' ) );
			if ( $query ) {
				foreach ( $query as $id ) {
					wp_delete_post_revision( intval( $id ) );
				}
			}
			break;
		case 'auto_drafts':
			$query = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_status = %s", 'auto-draft' ) );
			if ( $query ) {
				foreach ( $query as $id ) {
					wp_delete_post( intval( $id ), true );
				}
			}
			break;
		case 'trashed_posts':
			$query = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_status = %s", 'trash' ) );
			if ( $query ) {
				foreach ( $query as $id ) {
					wp_delete_post( $id, true );
				}
			}
			break;
		case 'spam_comments':
			$query = $wpdb->get_col( $wpdb->prepare( "SELECT comment_ID FROM $wpdb->comments WHERE comment_approved = %s", 'spam' ) );
			if ( $query ) {
				foreach ( $query as $id ) {
					wp_delete_comment( intval( $id ), true );
				}
			}
			break;
		case 'trashed_comments':
			$query = $wpdb->get_col( $wpdb->prepare( "SELECT comment_ID FROM $wpdb->comments WHERE (comment_approved = %s OR comment_approved = %s)", 'trash', 'post-trashed' ) );
			if ( $query ) {
				foreach ( $query as $id ) {
					wp_delete_comment( intval( $id ), true );
				}
			}
			break;
		case 'expired_transients':
			$time = isset( $_SERVER['REQUEST_TIME'] ) ? (int) $_SERVER['REQUEST_TIME'] : time();
			$query = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s AND option_value < %s", '_transient_timeout%', $time ) );

			if ( $query ) {
				foreach ( $query as $transient ) {
					$key = str_replace( '_transient_timeout_', '', $transient );
					delete_transient( $key );
				}
			}
			break;
		case 'all_transients':
			$query = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s", '%_transient_%' ) );
			if ( $query ) {
				foreach ( $query as $transient ) {
					if ( strpos( $transient, '_site_transient_' ) !== false ) {
						delete_site_transient( str_replace( '_site_transient_', '', $transient ) );
					} else {
						delete_transient( str_replace( '_transient_', '', $transient ) );
					}
				}
			}
			break;
		case 'optimize_tables':
			$query = $wpdb->get_results( $wpdb->prepare( "SELECT table_name, data_free FROM information_schema.tables WHERE table_schema = %s and Engine <> 'InnoDB' and data_free > 0", DB_NAME ) );
			if ( $query ) {
				foreach ( $query as $table ) {
					$wpdb->query( 'OPTIMIZE TABLE ' . $table->table_name );
				}
			}
			break;
	}
}

/**
 * Run an async job to preload sitemaps in background
 *
 * @since 2.8
 *
 * @param array $body Contains the usual $_POST.
 **/
function rocket_do_async_job( $body ) {
	$args = array(
		'timeout'   => 0.01,
		'blocking'  => false,
		'body'      => $body,
		'cookies'   => $_COOKIE,
		'sslverify' => apply_filters( 'https_local_ssl_verify', true ),
	);

	wp_remote_post( esc_url_raw( admin_url( 'admin-ajax.php' ) ), $args );
}

/**
 * Check if a mobile plugin is active
 *
 * @since 2.10
 * @author Remy Perona
 *
 * @return true if a mobile plugin in the list is active, false otherwise.
 **/
function rocket_is_mobile_plugin_active() {
	$mobile_plugins = array(
		'wptouch/wptouch.php',
		'wiziapp-create-your-own-native-iphone-app/wiziapp.php',
		'wordpress-mobile-pack/wordpress-mobile-pack.php',
		'wp-mobilizer/wp-mobilizer.php',
		'wp-mobile-edition/wp-mobile-edition.php',
		'device-theme-switcher/dts_controller.php',
		'wp-mobile-detect/wp-mobile-detect.php',
		'easy-social-share-buttons3/easy-social-share-buttons3.php',
	);

	foreach ( $mobile_plugins as $mobile_plugin ) {
		if ( is_plugin_active(  $mobile_plugin ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Allow upload of JSON file.
 *
 * @since 2.10.7
 * @author Remy Perona
 *
 * @param array $wp_get_mime_types Array of allowed mime types.
 * @return array Updated array of allowed mime types
 */
function rocket_allow_json_mime_type( $wp_get_mime_types ) {
	$wp_get_mime_types['json'] = 'application/json';

	return $wp_get_mime_types;
}
