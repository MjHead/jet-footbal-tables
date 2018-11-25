<?php
/**
 * Plugin Name: Jet Football Tables
 * Plugin URI:  https://jetengine.zemez.io/
 * Description: The ultimate solution for managing custom post types, taxonomies and meta boxes.
 * Version:     1.0.0
 * Author:      Zemez
 * Author URI:  https://zemez.io/wordpress/
 * Text Domain: jet-engine
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die();
}

// If class `Jet_Football_Tables` doesn't exists yet.
if ( ! class_exists( 'Jet_Football_Tables' ) ) {

	/**
	 * Sets up and initializes the plugin.
	 */
	class Jet_Football_Tables {

		/**
		 * A reference to an instance of this class.
		 *
		 * @since  1.0.0
		 * @access private
		 * @var    object
		 */
		private static $instance = null;

		/**
		 * Holder for base plugin URL
		 *
		 * @since  1.0.0
		 * @access private
		 * @var    string
		 */
		private $plugin_url = null;

		/**
		 * Holder for base plugin path
		 *
		 * @since  1.0.0
		 * @access private
		 * @var    string
		 */
		private $plugin_path = null;

		public $db;

		/**
		 * Sets up needed actions/filters for the plugin to initialize.
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */
		public function __construct() {

			$this->init();

			add_action( 'save_post', array( $this, 'store_match_data' ), 99, 2 );

			add_shortcode( 'jet_footbal_table', array( $this, 'table_shortcode' ) );

			// Register activation and deactivation hook.
			register_activation_hook( __FILE__, array( $this, 'activation' ) );

		}

		public function table_shortcode( $args ) {

			$tid = isset( $args['tid'] ) ? $args['tid'] : false;

			if ( ! $tid ) {
				return;
			}

			$matches = $this->db->query( 'matches', array( 'tournament' => $tid ) );
			$teams   = get_post_meta( $tid, '_teams', true );

			if ( ! $teams ) {
				return;
			}

			$standings = array();
			$fixture   = array();

			foreach ( $teams as $team ) {
				$standings[ $team ] = array(
					'm' => 0,
					'w' => 0,
					'd' => 0,
					'l' => 0,
					'gf' => 0,
					'ga' => 0,
					'p' => 0,
				);

				$fixture[ $team ] = array();
			}

			foreach ( $matches as $match ) {
				
				$home_team = absint( $match['home_team'] );
				$away_team = absint( $match['away_team'] );
				$home_score = absint( $match['home_score'] );
				$away_score = absint( $match['away_score'] );

				if ( $home_score === $away_score ) {
					
					$standings[ $home_team ]['m']++;
					$standings[ $home_team ]['d']++;
					$standings[ $home_team ]['gf'] = $standings[ $home_team ]['gf'] + $home_score;
					$standings[ $home_team ]['ga'] = $standings[ $home_team ]['ga'] + $away_score;
					$standings[ $home_team ]['p'] = $standings[ $home_team ]['p'] + 1;

					$standings[ $away_team ]['m']++;
					$standings[ $away_team ]['d']++;
					$standings[ $away_team ]['gf'] = $standings[ $away_team ]['gf'] + $away_score;
					$standings[ $away_team ]['ga'] = $standings[ $away_team ]['ga'] + $home_score;
					$standings[ $away_team ]['p'] = $standings[ $away_team ]['p'] + 1;

				} elseif ( $home_score > $away_score ) {

					$standings[ $home_team ]['m']++;
					$standings[ $home_team ]['w']++;
					$standings[ $home_team ]['gf'] = $standings[ $home_team ]['gf'] + $home_score;
					$standings[ $home_team ]['ga'] = $standings[ $home_team ]['ga'] + $away_score;
					$standings[ $home_team ]['p'] = $standings[ $home_team ]['p'] + 3;

					$standings[ $away_team ]['m']++;
					$standings[ $away_team ]['l']++;
					$standings[ $away_team ]['gf'] = $standings[ $away_team ]['gf'] + $away_score;
					$standings[ $away_team ]['ga'] = $standings[ $away_team ]['ga'] + $home_score;

				} else {

					$standings[ $home_team ]['m']++;
					$standings[ $home_team ]['l']++;
					$standings[ $home_team ]['gf'] = $standings[ $home_team ]['gf'] + $home_score;
					$standings[ $home_team ]['ga'] = $standings[ $home_team ]['ga'] + $away_score;

					$standings[ $away_team ]['m']++;
					$standings[ $away_team ]['w']++;
					$standings[ $away_team ]['gf'] = $standings[ $away_team ]['gf'] + $away_score;
					$standings[ $away_team ]['ga'] = $standings[ $away_team ]['ga'] + $home_score;
					$standings[ $away_team ]['p'] = $standings[ $away_team ]['p'] + 3;

				}

				$fixture[ $home_team ][ $away_team ] = $home_score . ':' . $away_score;

			}

			uasort( $standings, function( $a, $b ) {
				
				if ( $a['p'] == $b['p'] ) {

					$adiff = $a['gf'] - $a['ga'];
					$bdiff = $b['gf'] - $b['ga'];

					if ( $adiff === $bdiff ) {
						return 0;
					} else {
						return ( $adiff < $bdiff ) ? 1 : -1;
					}
				    
				}

				return ( $a['p'] < $b['p'] ) ? 1 : -1;
			} );

			ob_start();
			include $this->plugin_path( 'templates/standings.php' );
			$standings_html = ob_get_clean();

			$fixture_order = array_keys( $fixture );

			ob_start();
			include $this->plugin_path( 'templates/fixtures.php' );
			$fixtures_html = ob_get_clean();

			return $fixtures_html . $standings_html;

		}

		public function store_match_data( $post_id, $post ) {

			if ( 'matches' !== $post->post_type ) {
				return;
			}

			$home_team = get_post_meta( $post->ID, '_home_team', true );
			$away_team = get_post_meta( $post->ID, '_away_team', true );
			$home_score = get_post_meta( $post->ID, '_home_score', true );
			$away_score = get_post_meta( $post->ID, '_away_score', true );
			$tournament = get_post_meta( $post->ID, '_tournament', true );

			if ( ! $home_score ) {
				$home_score = 0;
			}

			if ( ! $away_score ) {
				$away_score = 0;
			}

			$match = $this->db->query( 'matches', array(
				'home_team' => $home_team,
				'away_team' => $away_team,
				'tournament' => $tournament,
			) );

			$data = array(
				'home_team' => $home_team,
				'away_team' => $away_team,
				'home_score' => $home_score,
				'away_score' => $away_score,
				'tournament' => $tournament,
				'tournament_group' => '',
			);

			if ( ! empty( $match ) ) {
				$data['id'] = $match[0]['id'];
			}

			$this->db->update( 'matches', $data );

			remove_action( 'save_post', array( $this, 'store_match_data' ), 99, 2 );
			wp_update_post( array(
				'ID'         => $post->ID,
      			'post_title' => get_the_title( $home_team ) . ' v ' . get_the_title( $away_team ),
			) );

		}

		/**
		 * Manually init required modules.
		 *
		 * @return void
		 */
		public function init() {
			require $this->plugin_path( 'includes/db.php' );
			$this->db = new Jet_FT_DB();
		}

		/**
		 * Returns path to file or dir inside plugin folder
		 *
		 * @param  string $path Path inside plugin dir.
		 * @return string
		 */
		public function plugin_path( $path = null ) {

			if ( ! $this->plugin_path ) {
				$this->plugin_path = trailingslashit( plugin_dir_path( __FILE__ ) );
			}

			return $this->plugin_path . $path;
		}

		/**
		 * Returns url to file or dir inside plugin folder
		 *
		 * @param  string $path Path inside plugin dir.
		 * @return string
		 */
		public function plugin_url( $path = null ) {

			if ( ! $this->plugin_url ) {
				$this->plugin_url = trailingslashit( plugin_dir_url( __FILE__ ) );
			}

			return $this->plugin_url . $path;
		}

		/**
		 * Do some stuff on plugin activation
		 *
		 * @since  1.0.0
		 * @return void
		 */
		public function activation() {
			require $this->plugin_path( 'includes/db.php' );
			Jet_FT_DB::create_all_tables();
		}

		/**
		 * Returns the instance.
		 *
		 * @since  1.0.0
		 * @access public
		 * @return object
		 */
		public static function get_instance() {
			// If the single instance hasn't been set, set it now.
			if ( null == self::$instance ) {
				self::$instance = new self;
			}
			return self::$instance;
		}

	}
}

if ( ! function_exists( 'jet_football_tables' ) ) {

	/**
	 * Returns instanse of the plugin class.
	 *
	 * @since  1.0.0
	 * @return object
	 */
	function jet_football_tables() {
		return Jet_Football_Tables::get_instance();
	}
}

jet_football_tables();
