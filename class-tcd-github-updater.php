<?php
/**
 * TCD GitHub Updater
 * 
 * Checks a private GitHub repo for new releases and hooks into
 * the WordPress plugin update system. Zero external dependencies.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TCD_GitHub_Updater {

    private $slug;
    private $plugin_file;
    private $repo;
    private $token;
    private $plugin_data;
    private $github_response;

    public function __construct( $plugin_file, $repo, $token = '' ) {
        $this->plugin_file = $plugin_file;
        $this->slug        = plugin_basename( $plugin_file );
        $this->repo        = $repo;
        $this->token       = $token;

        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
        add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
        add_filter( 'upgrader_post_install', array( $this, 'after_install' ), 10, 3 );

        // Inject auth header into GitHub download requests
        if ( ! empty( $this->token ) ) {
            add_filter( 'http_request_args', array( $this, 'inject_auth_header' ), 10, 2 );
        }
    }


    /**
     * Get plugin header data
     */
    private function get_plugin_data() {
        if ( empty( $this->plugin_data ) ) {
            $this->plugin_data = get_plugin_data( $this->plugin_file );
        }
        return $this->plugin_data;
    }


    /**
     * Fetch latest release from GitHub
     */
    private function get_github_release() {
        if ( ! empty( $this->github_response ) ) {
            return $this->github_response;
        }

        $url  = 'https://api.github.com/repos/' . $this->repo . '/releases/latest';
        $args = array(
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
            ),
            'timeout' => 10,
        );

        if ( ! empty( $this->token ) ) {
            $args['headers']['Authorization'] = 'Bearer ' . $this->token;
        }

        $response = wp_remote_get( $url, $args );

        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body ) || ! isset( $body['tag_name'] ) ) {
            return false;
        }

        $this->github_response = $body;
        return $this->github_response;
    }


    /**
     * Inject Authorization header for GitHub API and download requests
     */
    public function inject_auth_header( $args, $url ) {
        if ( strpos( $url, 'api.github.com/repos/' . $this->repo ) !== false ||
             strpos( $url, 'github.com/' . $this->repo ) !== false ||
             strpos( $url, 'codeload.github.com/' . $this->repo ) !== false ) {

            $args['headers']['Authorization'] = 'Bearer ' . $this->token;
        }

        return $args;
    }


    /**
     * Check for updates
     */
    public function check_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $release = $this->get_github_release();
        if ( ! $release ) {
            return $transient;
        }

        $plugin_data     = $this->get_plugin_data();
        $current_version = $plugin_data['Version'];
        $remote_version  = ltrim( $release['tag_name'], 'vV' );

        if ( version_compare( $remote_version, $current_version, '>' ) ) {
            $transient->response[ $this->slug ] = (object) array(
                'slug'        => dirname( $this->slug ),
                'plugin'      => $this->slug,
                'new_version' => $remote_version,
                'url'         => $release['html_url'],
                'package'     => $release['zipball_url'],
            );
        }

        return $transient;
    }


    /**
     * Plugin info popup
     */
    public function plugin_info( $result, $action, $args ) {
        if ( $action !== 'plugin_information' ) {
            return $result;
        }

        if ( ! isset( $args->slug ) || $args->slug !== dirname( $this->slug ) ) {
            return $result;
        }

        $release     = $this->get_github_release();
        $plugin_data = $this->get_plugin_data();

        if ( ! $release ) {
            return $result;
        }

        return (object) array(
            'name'          => $plugin_data['Name'],
            'slug'          => dirname( $this->slug ),
            'version'       => ltrim( $release['tag_name'], 'vV' ),
            'author'        => $plugin_data['AuthorName'],
            'homepage'      => $plugin_data['PluginURI'],
            'requires'      => '6.0',
            'tested'        => get_bloginfo( 'version' ),
            'requires_php'  => '7.4',
            'downloaded'    => 0,
            'last_updated'  => $release['published_at'],
            'sections'      => array(
                'description' => $plugin_data['Description'],
                'changelog'   => nl2br( esc_html( $release['body'] ) ),
            ),
            'download_link' => $release['zipball_url'],
        );
    }


    /**
     * Rename folder after install (GitHub zips use owner-repo-hash format)
     */
    public function after_install( $response, $hook_extra, $result ) {
        if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->slug ) {
            return $result;
        }

        global $wp_filesystem;

        $install_dir = $result['destination'];
        $proper_dir  = WP_PLUGIN_DIR . '/' . dirname( $this->slug );

        $wp_filesystem->move( $install_dir, $proper_dir );
        $result['destination'] = $proper_dir;

        // Re-activate if it was active
        if ( is_plugin_active( $this->slug ) ) {
            activate_plugin( $this->slug );
        }

        return $result;
    }
}
