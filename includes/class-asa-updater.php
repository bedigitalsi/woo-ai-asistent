<?php
/**
 * Plugin updater class for GitHub integration
 *
 * @package AI_Store_Assistant
 * @subpackage Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles plugin updates from GitHub.
 */
class ASA_Updater {

	/**
	 * GitHub repository owner.
	 *
	 * @var string
	 */
	private $owner = 'bedigitalsi';

	/**
	 * GitHub repository name.
	 *
	 * @var string
	 */
	private $repo = 'woo-ai-asistent';

	/**
	 * Current plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Plugin file path.
	 *
	 * @var string
	 */
	private $plugin_file;

	/**
	 * Constructor.
	 *
	 * @param string $plugin_file Main plugin file path.
	 * @param string $version Current plugin version.
	 */
	public function __construct( $plugin_file, $version ) {
		$this->plugin_file = $plugin_file;
		$this->version     = $version;

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_updates' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );
		add_filter( 'upgrader_post_install', array( $this, 'post_install' ), 10, 3 );
		add_filter( 'upgrader_source_selection', array( $this, 'source_selection' ), 10, 4 );
	}

	/**
	 * Check for updates from GitHub.
	 *
	 * @param object $transient Update transient object.
	 * @return object Modified transient object.
	 */
	public function check_for_updates( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$remote_version = $this->get_remote_version();

		if ( $remote_version && version_compare( $this->version, $remote_version, '<' ) ) {
			$plugin_slug = plugin_basename( $this->plugin_file );
			$plugin_data = get_plugin_data( $this->plugin_file );

			$obj              = new stdClass();
			$obj->slug         = dirname( $plugin_slug );
			$obj->plugin       = $plugin_slug;
			$obj->new_version  = $remote_version;
			$obj->url          = $plugin_data['PluginURI'];
			$obj->package       = $this->get_download_url( $remote_version );
			$obj->tested        = get_bloginfo( 'version' );
			$obj->requires_php  = '8.1';
			$obj->icons         = array(
				'default' => 'https://s.w.org/plugins/geopattern-icon/ai-store-assistant.svg',
			);

			$transient->response[ $plugin_slug ] = $obj;
		}

		return $transient;
	}

	/**
	 * Get plugin information for the "View details" popup.
	 *
	 * @param false|object|array $result Result object or array.
	 * @param string              $action Action being performed.
	 * @param object              $args Arguments.
	 * @return object Plugin information object.
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		$plugin_slug = dirname( plugin_basename( $this->plugin_file ) );

		if ( $plugin_slug !== $args->slug ) {
			return $result;
		}

		$remote_version = $this->get_remote_version();
		if ( ! $remote_version ) {
			return $result;
		}

		$plugin_data = get_plugin_data( $this->plugin_file );

		$info                 = new stdClass();
		$info->name            = $plugin_data['Name'];
		$info->slug            = $plugin_slug;
		$info->version         = $remote_version;
		$info->author           = $plugin_data['Author'];
		$info->author_profile  = $plugin_data['AuthorURI'];
		$info->homepage         = $plugin_data['PluginURI'];
		$info->requires         = $plugin_data['RequiresWP'];
		$info->tested           = get_bloginfo( 'version' );
		$info->requires_php     = '8.1';
		$info->downloaded       = 0;
		$info->last_updated     = $this->get_release_date( $remote_version );
		$info->sections         = array(
			'description' => $plugin_data['Description'],
			'changelog'   => $this->get_changelog(),
		);
		$info->download_link     = $this->get_download_url( $remote_version );
		$info->banners           = array();

		return $info;
	}

	/**
	 * Get remote version from GitHub (public method for manual checks).
	 *
	 * @param bool $force_skip_cache Skip cache and fetch fresh.
	 * @return string|false Version string or false on failure.
	 */
	public function get_remote_version_public( $force_skip_cache = false ) {
		if ( $force_skip_cache ) {
			delete_transient( 'asa_remote_version' );
		}
		return $this->get_remote_version();
	}

	/**
	 * Get remote version from GitHub.
	 *
	 * @return string|false Version string or false on failure.
	 */
	private function get_remote_version() {
		$cache_key = 'asa_remote_version';
		$version   = get_transient( $cache_key );

		if ( false !== $version ) {
			return $version;
		}

		$api_url = sprintf(
			'https://api.github.com/repos/%s/%s/releases/latest',
			$this->owner,
			$this->repo
		);

		$response = wp_remote_get(
			$api_url,
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept' => 'application/vnd.github.v3+json',
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! isset( $body['tag_name'] ) ) {
			return false;
		}

		$version = ltrim( $body['tag_name'], 'v' );
		set_transient( $cache_key, $version, HOUR_IN_SECONDS );

		return $version;
	}

	/**
	 * Get download URL for a specific version.
	 *
	 * @param string $version Version tag.
	 * @return string Download URL.
	 */
	private function get_download_url( $version ) {
		// GitHub creates ZIP files with repo-name-version folder structure
		// The updater's post_install method handles extracting and moving files correctly
		return sprintf(
			'https://github.com/%s/%s/archive/refs/tags/v%s.zip',
			$this->owner,
			$this->repo,
			$version
		);
	}

	/**
	 * Get release date for a version.
	 *
	 * @param string $version Version tag.
	 * @return string Release date.
	 */
	private function get_release_date( $version ) {
		$cache_key = 'asa_release_date_' . $version;
		$date      = get_transient( $cache_key );

		if ( false !== $date ) {
			return $date;
		}

		$api_url = sprintf(
			'https://api.github.com/repos/%s/%s/releases/tags/v%s',
			$this->owner,
			$this->repo,
			$version
		);

		$response = wp_remote_get(
			$api_url,
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept' => 'application/vnd.github.v3+json',
				),
			)
		);

		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( isset( $body['published_at'] ) ) {
				$date = date_i18n( get_option( 'date_format' ), strtotime( $body['published_at'] ) );
				set_transient( $cache_key, $date, DAY_IN_SECONDS );
				return $date;
			}
		}

		return date_i18n( get_option( 'date_format' ) );
	}

	/**
	 * Get changelog from GitHub releases.
	 *
	 * @return string Changelog HTML.
	 */
	private function get_changelog() {
		$cache_key = 'asa_changelog';
		$changelog = get_transient( $cache_key );

		if ( false !== $changelog ) {
			return $changelog;
		}

		$api_url = sprintf(
			'https://api.github.com/repos/%s/%s/releases',
			$this->owner,
			$this->repo
		);

		$response = wp_remote_get(
			$api_url,
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept' => 'application/vnd.github.v3+json',
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return '<p>' . esc_html__( 'Unable to fetch changelog.', 'ai-store-assistant' ) . '</p>';
		}

		$releases = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $releases ) || empty( $releases ) ) {
			return '<p>' . esc_html__( 'No releases found.', 'ai-store-assistant' ) . '</p>';
		}

		$changelog = '<ul>';
		$count     = 0;
		foreach ( $releases as $release ) {
			if ( $count >= 5 ) {
				break; // Limit to 5 most recent releases
			}
			$version = ltrim( $release['tag_name'], 'v' );
			$date    = isset( $release['published_at'] ) ? date_i18n( get_option( 'date_format' ), strtotime( $release['published_at'] ) ) : '';
			$body    = isset( $release['body'] ) ? wp_kses_post( $release['body'] ) : '';

			$changelog .= sprintf(
				'<li><strong>Version %s</strong> (%s)<br>%s</li>',
				esc_html( $version ),
				esc_html( $date ),
				wpautop( $body )
			);
			$count++;
		}
		$changelog .= '</ul>';

		set_transient( $cache_key, $changelog, HOUR_IN_SECONDS );

		return $changelog;
	}

	/**
	 * Filter the source file location for the upgrade package.
	 * This ensures WordPress extracts to the correct plugin folder.
	 *
	 * @param string      $source        File source location.
	 * @param string      $remote_source Remote file source location.
	 * @param WP_Upgrader $upgrader      WP_Upgrader instance.
	 * @param array       $hook_extra    Extra arguments.
	 * @return string Modified source location.
	 */
	public function source_selection( $source, $remote_source, $upgrader, $hook_extra ) {
		// Only process our plugin
		if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== plugin_basename( $this->plugin_file ) ) {
			return $source;
		}

		global $wp_filesystem;

		$plugin_slug = dirname( plugin_basename( $this->plugin_file ) );
		$correct_source = trailingslashit( dirname( $source ) ) . $plugin_slug . '/';

		// If source is different from correct source, we need to rename
		if ( $source !== $correct_source && $wp_filesystem->exists( $source ) ) {
			// Check if there's a versioned folder inside
			$files = $wp_filesystem->dirlist( $source );
			if ( $files ) {
				foreach ( $files as $file ) {
					if ( $file['type'] === 'd' ) {
						$folder_name = $file['name'];
						// If this is a versioned folder, rename it to plugin slug
						if ( strpos( $folder_name, 'woo-ai-asistent' ) !== false || preg_match( '/v?\d+\.\d+\.\d+/', $folder_name ) ) {
							$versioned_path = trailingslashit( $source ) . $folder_name;
							if ( $wp_filesystem->exists( $versioned_path ) ) {
								$wp_filesystem->move( $versioned_path, $correct_source, true );
								// Clean up old source if empty
								if ( $wp_filesystem->exists( $source ) ) {
									$remaining = $wp_filesystem->dirlist( $source );
									if ( empty( $remaining ) ) {
										$wp_filesystem->rmdir( $source, true );
									}
								}
								return $correct_source;
							}
						}
					}
				}
			}
		}

		return $source;
	}

	/**
	 * Handle post-install actions.
	 *
	 * @param bool  $response Installation response.
	 * @param array $hook_extra Extra arguments.
	 * @param array $result Installation result.
	 * @return bool Response.
	 */
	public function post_install( $response, $hook_extra, $result ) {
		global $wp_filesystem;

		$plugin_slug = dirname( plugin_basename( $this->plugin_file ) );

		if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== plugin_basename( $this->plugin_file ) ) {
			return $response;
		}

		$install_directory = plugin_dir_path( $this->plugin_file );
		$extracted_folder = trailingslashit( $result['destination'] );
		
		// GitHub ZIP files extract to a folder named "woo-ai-asistent-v1.0.0" or similar
		// We need to find and move contents from that folder to the plugin directory
		$files = $wp_filesystem->dirlist( $extracted_folder );
		
		if ( ! $files ) {
			return $response;
		}

		// Look for a folder that starts with "woo-ai-asistent" or contains version number
		$versioned_folder = null;
		foreach ( $files as $file ) {
			if ( $file['type'] === 'd' ) {
				$folder_name = $file['name'];
				// Check if this looks like a versioned folder
				if ( strpos( $folder_name, 'woo-ai-asistent' ) !== false || preg_match( '/v?\d+\.\d+\.\d+/', $folder_name ) ) {
					$versioned_folder = trailingslashit( $extracted_folder . $folder_name );
					break;
				}
			}
		}

		// If we found a versioned folder, move its contents
		if ( $versioned_folder && $wp_filesystem->exists( $versioned_folder ) ) {
			$versioned_files = $wp_filesystem->dirlist( $versioned_folder );
			if ( $versioned_files ) {
				foreach ( $versioned_files as $versioned_file ) {
					$source = $versioned_folder . $versioned_file['name'];
					$destination = $install_directory . $versioned_file['name'];
					
					// Remove destination if it exists (for updates)
					if ( $wp_filesystem->exists( $destination ) ) {
						if ( $versioned_file['type'] === 'd' ) {
							$wp_filesystem->rmdir( $destination, true );
						} else {
							$wp_filesystem->delete( $destination );
						}
					}
					
					// Move file/directory
					$wp_filesystem->move( $source, $destination, true );
				}
			}
			// Remove the versioned folder
			$wp_filesystem->rmdir( $versioned_folder, true );
		} else {
			// No versioned folder found, files might be directly extracted
			// Move files directly to plugin directory
			foreach ( $files as $file ) {
				$source = $extracted_folder . $file['name'];
				$destination = $install_directory . $file['name'];
				
				// Skip if source and destination are the same
				if ( $source === $destination ) {
					continue;
				}
				
				// Remove destination if it exists (for updates)
				if ( $wp_filesystem->exists( $destination ) ) {
					if ( $file['type'] === 'd' ) {
						$wp_filesystem->rmdir( $destination, true );
					} else {
						$wp_filesystem->delete( $destination );
					}
				}
				
				// Move file/directory
				$wp_filesystem->move( $source, $destination, true );
			}
		}

		// Remove the temporary extraction directory if it's empty
		if ( $wp_filesystem->exists( $extracted_folder ) ) {
			$remaining_files = $wp_filesystem->dirlist( $extracted_folder );
			if ( empty( $remaining_files ) ) {
				$wp_filesystem->rmdir( $extracted_folder, true );
			}
		}

		// Update result destination
		$result['destination'] = $install_directory;

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Reactivate plugin if it was active before update
		$plugin_basename = plugin_basename( $this->plugin_file );
		if ( ! is_plugin_active( $plugin_basename ) ) {
			// Try to reactivate - WordPress might have deactivated it due to folder mismatch
			activate_plugin( $plugin_basename );
		}

		return $response;
	}
}

