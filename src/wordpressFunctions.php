<?php

use Ssntpl\Newsomatic\Manager;

$post_data = [];

define('ABSPATH', __DIR__);
define('WP_PLUGIN_DIR', __DIR__);
define('WPMU_PLUGIN_DIR', __DIR__);
if (!isset($_SERVER['SERVER_ADDR'])) {
    $_SERVER['SERVER_ADDR'] = '127.0.0.1';
}
if (!isset($_SERVER['SERVER_NAME'])) {
    $_SERVER['SERVER_NAME'] = '127.0.0.1';
}
define( 'WP_ADMIN', false );
define( 'MULTISITE', false );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 60 * MINUTE_IN_SECONDS );
define( 'DAY_IN_SECONDS', 24 * HOUR_IN_SECONDS );
define( 'WEEK_IN_SECONDS', 7 * DAY_IN_SECONDS );
define( 'MONTH_IN_SECONDS', 30 * DAY_IN_SECONDS );
define( 'YEAR_IN_SECONDS', 365 * DAY_IN_SECONDS );
require_once 'wordpressEnvironment/cache.php';

function wp_suspend_cache_addition( $suspend = null ) {
	static $_suspend = false;

	if ( is_bool( $suspend ) ) {
		$_suspend = $suspend;
	}

	return $_suspend;
}

function wp_installing( $is_installing = null ) {
	static $installing = null;

	// Support for the `WP_INSTALLING` constant, defined before WP is loaded.
	if ( is_null( $installing ) ) {
		// $installing = defined( 'WP_INSTALLING' ) && WP_INSTALLING;
	}

	if ( ! is_null( $is_installing ) ) {
		$old_installing = $installing;
		$installing     = $is_installing;

		return (bool) $old_installing;
	}

	return (bool) $installing;
}

function request_filesystem_credentials( $url ){
	return null;
}

function site_url(){
	return "";
}

function wp_filesystem($creds){
	return "";
}

function get_option( $option, $default_value = false ) {
	global $wpdb;


	if ( is_scalar( $option ) ) {
		$option = trim( $option );
	}

	if ( empty( $option ) ) {
		return false;
	}

	/*
	 * Until a proper _deprecated_option() function can be introduced,
	 * redirect requests to deprecated keys to the new, correct ones.
	 */
	$deprecated_keys = array(
		'blacklist_keys'    => 'disallowed_keys',
		'comment_whitelist' => 'comment_previously_approved',
	);

	if ( isset( $deprecated_keys[ $option ] ) && ! wp_installing() ) {
		_deprecated_argument(
			__FUNCTION__,
			'5.5.0',
			sprintf(
				/* translators: 1: Deprecated option key, 2: New option key. */
				__( 'The "%1$s" option key has been renamed to "%2$s".' ),
				$option,
				$deprecated_keys[ $option ]
			)
		);
		return get_option( $deprecated_keys[ $option ], $default_value );
	}

	/**
	 * Filters the value of an existing option before it is retrieved.
	 *
	 * The dynamic portion of the hook name, `$option`, refers to the option name.
	 *
	 * Returning a value other than false from the filter will short-circuit retrieval
	 * and return that value instead.
	 *
	 * @since 1.5.0
	 * @since 4.4.0 The `$option` parameter was added.
	 * @since 4.9.0 The `$default_value` parameter was added.
	 *
	 * @param mixed  $pre_option    The value to return instead of the option value. This differs from
	 *                              `$default_value`, which is used as the fallback value in the event
	 *                              the option doesn't exist elsewhere in get_option().
	 *                              Default false (to skip past the short-circuit).
	 * @param string $option        Option name.
	 * @param mixed  $default_value The fallback value to return if the option does not exist.
	 *                              Default false.
	 */
	$pre = apply_filters( "pre_option_{$option}", false, $option, $default_value );

	/**
	 * Filters the value of all existing options before it is retrieved.
	 *
	 * Returning a truthy value from the filter will effectively short-circuit retrieval
	 * and return the passed value instead.
	 *
	 * @since 6.1.0
	 *
	 * @param mixed  $pre_option    The value to return instead of the option value. This differs from
	 *                              `$default_value`, which is used as the fallback value in the event
	 *                              the option doesn't exist elsewhere in get_option().
	 *                              Default false (to skip past the short-circuit).
	 * @param string $option        Name of the option.
	 * @param mixed  $default_value The fallback value to return if the option does not exist.
	 *                              Default false.
	 */
	$pre = apply_filters( 'pre_option', $pre, $option, $default_value );

	if ( false !== $pre ) {
		return $pre;
	}

	if ( defined( 'WP_SETUP_CONFIG' ) ) {
		return false;
	}

	// Distinguish between `false` as a default, and not passing one.
	$passed_default = func_num_args() > 1;

	if ( ! wp_installing() ) {
		// $alloptions = wp_load_alloptions();
		$alloptions = false;
		/*
		 * When getting an option value, we check in the following order for performance:
		 *
		 * 1. Check the 'alloptions' cache first to prioritize existing loaded options.
		 * 2. Check the 'notoptions' cache before a cache lookup or DB hit.
		 * 3. Check the 'options' cache prior to a DB hit.
		 * 4. Check the DB for the option and cache it in either the 'options' or 'notoptions' cache.
		 */
		if ( isset( $alloptions[ $option ] ) ) {
			$value = $alloptions[ $option ];
		} else {
			// Check for non-existent options first to avoid unnecessary object cache lookups and DB hits.
			$notoptions = wp_cache_get( 'notoptions', 'options' );

			if ( ! is_array( $notoptions ) ) {
				$notoptions = array();
				wp_cache_set( 'notoptions', $notoptions, 'options' );
			}

			if ( isset( $notoptions[ $option ] ) ) {
				/**
				 * Filters the default value for an option.
				 *
				 * The dynamic portion of the hook name, `$option`, refers to the option name.
				 *
				 * @since 3.4.0
				 * @since 4.4.0 The `$option` parameter was added.
				 * @since 4.7.0 The `$passed_default` parameter was added to distinguish between a `false` value and the default parameter value.
				 *
				 * @param mixed  $default_value  The default value to return if the option does not exist
				 *                               in the database.
				 * @param string $option         Option name.
				 * @param bool   $passed_default Was `get_option()` passed a default value?
				 */
				return apply_filters( "default_option_{$option}", $default_value, $option, $passed_default );
			}

			$value = wp_cache_get( $option, 'options' );

			if ( false === $value ) {
                // $row = fetch_option($option);

				$optionKey = $option;
				if($option == 'newsomatic_Main_Settings'){
					$optionKey = 'newsomatic_Main_Settings1';
				}
				$row = config('newsomatic.'.$optionKey) ?? null;
				if($row != null){
					$obj = new stdClass();
					$obj->option_value = $row;
					$row = $obj;
				}

				// Has to be get_row() instead of get_var() because of funkiness with 0, false, null values.
				if ( is_object( $row ) ) {
					$value = $row->option_value;
					wp_cache_add( $option, $value, 'options' );
				} else { // Option does not exist, so we must cache its non-existence.
					$notoptions[ $option ] = true;
					wp_cache_set( 'notoptions', $notoptions, 'options' );

					/** This filter is documented in wp-includes/option.php */
					return apply_filters( "default_option_{$option}", $default_value, $option, $passed_default );
				}
			}
		}
	} else {
		$suppress = $wpdb->suppress_errors();
		$row      = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", $option ) );
		$wpdb->suppress_errors( $suppress );

		if ( is_object( $row ) ) {
			$value = $row->option_value;
		} else {
			/** This filter is documented in wp-includes/option.php */
			return apply_filters( "default_option_{$option}", $default_value, $option, $passed_default );
		}
	}

	// If home is not set, use siteurl.
	if ( 'home' === $option && '' === $value ) {
		return get_option( 'siteurl' );
	}

	if ( in_array( $option, array( 'siteurl', 'home', 'category_base', 'tag_base' ), true ) ) {
		$value = untrailingslashit( $value );
	}

	/**
	 * Filters the value of an existing option.
	 *
	 * The dynamic portion of the hook name, `$option`, refers to the option name.
	 *
	 * @since 1.5.0 As 'option_' . $setting
	 * @since 3.0.0
	 * @since 4.4.0 The `$option` parameter was added.
	 *
	 * @param mixed  $value  Value of the option. If stored serialized, it will be
	 *                       unserialized prior to being returned.
	 * @param string $option Option name.
	 */

	 if($option == 'newsomatic_Main_Settings'){
		return config('newsomatic.'.$option);
	 }
	return apply_filters( "option_{$option}", maybe_unserialize( $value ), $option );
}

function wp_load_alloptions( $force_cache = false ) {
	global $wpdb;

	/**
	 * Filters the array of alloptions before it is populated.
	 *
	 * Returning an array from the filter will effectively short circuit
	 * wp_load_alloptions(), returning that value instead.
	 *
	 * @since 6.2.0
	 *
	 * @param array|null $alloptions  An array of alloptions. Default null.
	 * @param bool       $force_cache Whether to force an update of the local cache from the persistent cache. Default false.
	 */
	$alloptions = apply_filters( 'pre_wp_load_alloptions', null, $force_cache );
	if ( is_array( $alloptions ) ) {
		return $alloptions;
	}

	if ( ! wp_installing() || ! is_multisite() ) {
		$alloptions = wp_cache_get( 'alloptions', 'options', $force_cache );
	} else {
		$alloptions = false;
	}

	if ( ! $alloptions ) {
		$suppress      = $wpdb->suppress_errors();
		$alloptions_db = $wpdb->get_results( "SELECT option_name, option_value FROM $wpdb->options WHERE autoload IN ( '" . implode( "', '", esc_sql( wp_autoload_values_to_autoload() ) ) . "' )" );

		if ( ! $alloptions_db ) {
			$alloptions_db = $wpdb->get_results( "SELECT option_name, option_value FROM $wpdb->options" );
		}
		$wpdb->suppress_errors( $suppress );

		$alloptions = array();
		foreach ( (array) $alloptions_db as $o ) {
			$alloptions[ $o->option_name ] = $o->option_value;
		}

		if ( ! wp_installing() || ! is_multisite() ) {
			/**
			 * Filters all options before caching them.
			 *
			 * @since 4.9.0
			 *
			 * @param array $alloptions Array with all options.
			 */
			$alloptions = apply_filters( 'pre_cache_alloptions', $alloptions );

			wp_cache_add( 'alloptions', $alloptions, 'options' );
		}
	}

	/**
	 * Filters all options after retrieving them.
	 *
	 * @since 4.9.0
	 *
	 * @param array $alloptions Array with all options.
	 */
	return apply_filters( 'alloptions', $alloptions );
}

function esc_html__( $text, $domain = 'default' ) {
	return htmlspecialchars( $text, ENT_QUOTES, get_bloginfo( 'charset' ) );
}

function wp_normalize_path( $path ) {
    $path = str_replace( '\\', '/', $path ); // Replace backslashes with forward slashes
    $path = preg_replace( '|/+|', '/', $path ); // Remove duplicate slashes

    // Handle Windows drive letters (e.g., C:/ becomes lowercase c:/)
    if ( ':' === substr( $path, 1, 1 ) ) {
        $path = strtolower( substr( $path, 0, 1 ) ) . substr( $path, 1 );
    }

    return $path;
}

function is_admin() {
	if ( isset( $GLOBALS['current_screen'] ) ) {
		return $GLOBALS['current_screen']->in_admin();
	} elseif ( defined( 'WP_ADMIN' ) ) {
		return WP_ADMIN;
	}

	return false;
}

function add_shortcode( $tag, $callback ) {
	global $shortcode_tags;

	if ( '' === trim( $tag ) ) {
		_doing_it_wrong(
			__FUNCTION__,
			__( 'Invalid shortcode name: Empty name given.' ),
			'4.4.0'
		);
		return;
	}

	if ( 0 !== preg_match( '@[<>&/\[\]\x00-\x20=]@', $tag ) ) {
		_doing_it_wrong(
			__FUNCTION__,
			sprintf(
				/* translators: 1: Shortcode name, 2: Space-separated list of reserved characters. */
				__( 'Invalid shortcode name: %1$s. Do not use spaces or reserved characters: %2$s' ),
				$tag,
				'& / < > [ ] ='
			),
			'4.4.0'
		);
		return;
	}

	$shortcode_tags[ $tag ] = $callback;
}

function is_multisite() {
	if ( defined( 'MULTISITE' ) ) {
		return MULTISITE;
	}

	if ( defined( 'SUBDOMAIN_INSTALL' ) || defined( 'VHOST' ) || defined( 'SUNRISE' ) ) {
		return true;
	}

	return false;
}

function maybe_unserialize($original) {
    if (is_serialized($original)) {
        return @unserialize($original);
    }
    return $original;
}

function is_serialized($data) {
    return $data === 'b:0;' || @unserialize($data) !== false;
}

function update_option( $option, $value, $autoload = null ) {
    return true;
}

function current_time( $type, $gmt = 0 ) {
	// Don't use non-GMT timestamp, unless you know the difference and really need to.
	if ( 'timestamp' === $type || 'U' === $type ) {
		return $gmt ? time() : time() + (int) ( (float) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
	}

	if ( 'mysql' === $type ) {
		$type = 'Y-m-d H:i:s';
	}

	$timezone = $gmt ? new DateTimeZone( 'UTC' ) : wp_timezone();
	$datetime = new DateTime( 'now', $timezone );

	return $datetime->format( $type );
}

function get_temp_dir() {
	static $temp = '';
	if ( defined( 'WP_TEMP_DIR' ) ) {
		return trailingslashit( WP_TEMP_DIR );
	}

	if ( $temp ) {
		return trailingslashit( $temp );
	}

	if ( function_exists( 'sys_get_temp_dir' ) ) {
		$temp = sys_get_temp_dir();
		if ( @is_dir( $temp ) && wp_is_writable( $temp ) ) {
			return trailingslashit( $temp );
		}
	}

	$temp = ini_get( 'upload_tmp_dir' );
	if ( @is_dir( $temp ) && wp_is_writable( $temp ) ) {
		return trailingslashit( $temp );
	}

	$temp = WP_CONTENT_DIR . '/';
	if ( is_dir( $temp ) && wp_is_writable( $temp ) ) {
		return $temp;
	}

	return __DIR__.'/tmp/';
}

function wp_is_writable( $path ) {
	if ( 'Windows' === PHP_OS_FAMILY ) {
		return win_is_writable( $path );
	}

	return @is_writable( $path );
}

function trailingslashit( $value ) {
	return untrailingslashit( $value ) . '/';
}

function untrailingslashit( $value ) {
	return rtrim( $value, '/\\' );
}

function home_url(){
	return "https://theindiansystem.com";
}

function wp_remote_get($url, $args = [])
{
	$response = custom_http_get($url,$args);
	return $response;
}

function custom_http_get($url, $args = []) {
	$ch = curl_init();

	// Set default cURL options
	$default_options = [
		CURLOPT_URL            => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HEADER         => true, // Needed to separate headers
		CURLOPT_TIMEOUT        => $args['timeout'] ?? 30,
		CURLOPT_USERAGENT      => $args['user-agent'] ?? 'CustomUserAgent/1.0',
		CURLOPT_SSL_VERIFYPEER => $args['sslverify'] ?? false,
	];

	curl_setopt_array($ch, $default_options);

	$raw_response = curl_exec($ch);
	$header_size  = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$status_code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$error        = curl_error($ch);

	curl_close($ch);

	if ($error) {
		return [
			'headers'  => [],
			'body'     => '',
			'response' => [
				'code'    => 0,
				'message' => $error,
			],
		];
	}

	$headers_raw = substr($raw_response, 0, $header_size);
	$body        = substr($raw_response, $header_size);

	// Parse headers into an associative array
	$headers = [];
	foreach (explode("\r\n", trim($headers_raw)) as $line) {
		if (strpos($line, ':') !== false) {
			[$key, $value] = explode(': ', $line, 2);
			$headers[$key] = $value;
		}
	}

	return [
		'headers'  => $headers,
		'body'     => $body,
		'response' => [
			'code'    => $status_code,
			'message' => '', // You can map codes to reason phrases if needed
		],
	];
}

function wp_remote_retrieve_response_code( $response ) {
    if ( is_wp_error( $response ) || ! isset( $response['response']['code'] ) ) {
        return false;
    }

    return (int) $response['response']['code'];
}

function wp_remote_retrieve_response_message( $response ) {
    if ( is_wp_error( $response ) || ! isset( $response['response']['message'] ) ) {
        return false;
    }

    return (int) $response['response']['message'];
}

class WP_Error {
    private $errors = [];

    public function __construct($code = '', $message = '', $data = '') {
        if (!empty($code)) {
            $this->add($code, $message, $data);
        }
    }

    public function add($code, $message, $data = '') {
        $this->errors[$code][] = ['message' => $message, 'data' => $data];
    }

    public function get_error_message($code = '') {
        if ($code && isset($this->errors[$code])) {
            return $this->errors[$code][0]['message'];
        }

        // Return the first error message if no code specified
        foreach ($this->errors as $error) {
            return $error[0]['message'];
        }

        return '';
    }

    public function get_error_data($code = '') {
        if ($code && isset($this->errors[$code])) {
            return $this->errors[$code][0]['data'];
        }

        return null;
    }
}

// Define is_wp_error() like WordPress does
function is_wp_error($thing) {
    return $thing instanceof WP_Error;
}

function wp_remote_retrieve_body( $response ) {
    if ( is_wp_error( $response ) || ! isset( $response['body'] ) ) {
        return '';
    }

    return $response['body'];
}

function esc_html( $text ) {
    return htmlspecialchars( $text, ENT_QUOTES, get_bloginfo( 'charset' ) );
}

function esc_url($url) {
    // Strip all tags and encode dangerous characters
    $url = trim($url);
    $url = filter_var($url, FILTER_SANITIZE_URL);

    // Optionally block dangerous protocols (basic version)
    if (preg_match('#^(javascript|data|vbscript):#i', $url)) {
        return '';
    }

    return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
}

function esc_attr($text, $charset = 'UTF-8') {
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, $charset);
}

function get_bloginfo( $show = '', $filter = 'raw' ) {
	switch ( $show ) {
		case 'home':    // Deprecated.
		case 'siteurl': // Deprecated.
			_deprecated_argument(
				__FUNCTION__,
				'2.2.0',
				sprintf(
					/* translators: 1: 'siteurl'/'home' argument, 2: bloginfo() function name, 3: 'url' argument. */
					__( 'The %1$s option is deprecated for the family of %2$s functions. Use the %3$s option instead.' ),
					'<code>' . $show . '</code>',
					'<code>bloginfo()</code>',
					'<code>url</code>'
				)
			);
			// Intentional fall-through to be handled by the 'url' case.
		case 'url':
			$output = home_url();
			break;
		case 'wpurl':
			$output = site_url();
			break;
		case 'description':
			$output = get_option( 'blogdescription' );
			break;
		case 'rdf_url':
			$output = get_feed_link( 'rdf' );
			break;
		case 'rss_url':
			$output = get_feed_link( 'rss' );
			break;
		case 'rss2_url':
			$output = get_feed_link( 'rss2' );
			break;
		case 'atom_url':
			$output = get_feed_link( 'atom' );
			break;
		case 'comments_atom_url':
			$output = get_feed_link( 'comments_atom' );
			break;
		case 'comments_rss2_url':
			$output = get_feed_link( 'comments_rss2' );
			break;
		case 'pingback_url':
			$output = site_url( 'xmlrpc.php' );
			break;
		case 'stylesheet_url':
			$output = get_stylesheet_uri();
			break;
		case 'stylesheet_directory':
			$output = get_stylesheet_directory_uri();
			break;
		case 'template_directory':
		case 'template_url':
			$output = get_template_directory_uri();
			break;
		case 'admin_email':
			$output = get_option( 'admin_email' );
			break;
		case 'charset':
			$output = get_option( 'blog_charset' );
			if ( '' === $output ) {
				$output = 'UTF-8';
			}
			break;
		case 'html_type':
			$output = get_option( 'html_type' );
			break;
		case 'version':
			global $wp_version;
			$output = $wp_version;
			break;
		case 'language':
			/*
			 * translators: Translate this to the correct language tag for your locale,
			 * see https://www.w3.org/International/articles/language-tags/ for reference.
			 * Do not translate into your own language.
			 */
			$output = __( 'html_lang_attribute' );
			if ( 'html_lang_attribute' === $output || preg_match( '/[^a-zA-Z0-9-]/', $output ) ) {
				$output = determine_locale();
				$output = str_replace( '_', '-', $output );
			}
			break;
		case 'text_direction':
			_deprecated_argument(
				__FUNCTION__,
				'2.2.0',
				sprintf(
					/* translators: 1: 'text_direction' argument, 2: bloginfo() function name, 3: is_rtl() function name. */
					__( 'The %1$s option is deprecated for the family of %2$s functions. Use the %3$s function instead.' ),
					'<code>' . $show . '</code>',
					'<code>bloginfo()</code>',
					'<code>is_rtl()</code>'
				)
			);
			if ( function_exists( 'is_rtl' ) ) {
				$output = is_rtl() ? 'rtl' : 'ltr';
			} else {
				$output = 'ltr';
			}
			break;
		case 'name':
		default:
			$output = get_option( 'blogname' );
			break;
	}

	if ( 'display' === $filter ) {
		if (
			str_contains( $show, 'url' )
			|| str_contains( $show, 'directory' )
			|| str_contains( $show, 'home' )
		) {
			/**
			 * Filters the URL returned by get_bloginfo().
			 *
			 * @since 2.0.5
			 *
			 * @param string $output The URL returned by bloginfo().
			 * @param string $show   Type of information requested.
			 */
			$output = apply_filters( 'bloginfo_url', $output, $show );
		} else {
			/**
			 * Filters the site information returned by get_bloginfo().
			 *
			 * @since 0.71
			 *
			 * @param mixed  $output The requested non-URL site information.
			 * @param string $show   Type of information requested.
			 */
			$output = apply_filters( 'bloginfo', $output, $show );
		}
	}

	return $output;
}

function esc_url_raw($url) {
    // Strip all tags and encode dangerous characters
    $url = trim($url);
    $url = filter_var($url, FILTER_SANITIZE_URL);

    // Optionally block dangerous protocols (basic version)
    if (preg_match('#^(javascript|data|vbscript):#i', $url)) {
        return '';
    }

    return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
}

function wp_trim_words($text, $num_words = 55, $more = '...') {
    $words_array = preg_split("/[\n\r\t ]+/", $text, -1, PREG_SPLIT_NO_EMPTY);
    
    if (count($words_array) <= $num_words) {
        return $text;
    }

    $trimmed = array_slice($words_array, 0, $num_words);
    return implode(' ', $trimmed) . $more;
}

function wp_insert_post( $postarr, $wp_error = false, $fire_after_hooks = true ){
	$defaults = array(
		'post_author'           => 11,
		'post_content'          => '',
		'post_content_filtered' => '',
		'post_title'            => '',
		'post_excerpt'          => '',
		'post_status'           => 'draft',
		'post_type'             => 'post',
		'comment_status'        => '',
		'ping_status'           => '',
		'post_password'         => '',
		'to_ping'               => '',
		'pinged'                => '',
		'post_parent'           => 0,
		'menu_order'            => 0,
		'guid'                  => '',
		'import_id'             => 0,
		'context'               => '',
		'post_date'             => '',
		'post_date_gmt'         => '',
	);
	
	$postarr = wp_parse_args( $postarr, $defaults );
	Manager::$postData[] = $postarr; 
	$posts  = config('newsomatic.models.post');
    $post = new $posts;
    $post->insertPost($postarr);
	return (int) 111;
}

function wp_set_object_terms( $object_id, $terms, $taxonomy, $append = false ) {
	return [];
}

function add_post_meta( $post_id, $meta_key, $meta_value, $unique = false ) {
	return true;	
}

function get_posts( $args = null ){
	$defaults = array(
		'numberposts'      => 5,
		'category'         => 0,
		'orderby'          => 'date',
		'order'            => 'DESC',
		'include'          => array(),
		'exclude'          => array(),
		'meta_key'         => '',
		'meta_value'       => '',
		'post_type'        => 'post',
		'suppress_filters' => true,
	);
	$parsed_args = wp_parse_args( $args, $defaults );
	$post_id = $parsed_args['meta_query'][0]['value'];
	$posts  = config('newsomatic.models.post');
    $post = new $posts;
    $res = $post->checkPost($post_id) ?? [];
	return $res;
}

function wp_parse_args( $args, $defaults = array() ) {
	if ( is_object( $args ) ) {
		$parsed_args = get_object_vars( $args );
	} elseif ( is_array( $args ) ) {
		$parsed_args =& $args;
	} else {
		parse_str( $args, $parsed_args );
	}

	if ( is_array( $defaults ) && $defaults ) {
		return array_merge( $defaults, $parsed_args );
	}
	return $parsed_args;
}

function createPost(){
	newsomatic_cron();
	return "Done";
}
