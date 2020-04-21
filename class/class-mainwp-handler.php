<?php
namespace MainWP\Dashboard;

/**
 * MainWP Post Handler
 */
abstract class MainWP_Handler {


	protected $security_nonces;

	// Force Extending class to define this method.
	abstract protected function init();


	/**
	 * Method secure_request()
	 *
	 * Add security check to request parameter
	 *
	 * @param string $action
	 * @param string $query_arg
	 */
	public function secure_request( $action = '', $query_arg = 'security' ) {
		if ( ! MainWP_Utility::is_admin() ) {
			die( 0 );
		}
		if ( '' === $action ) {
			return;
		}

		if ( ! $this->check_security( $action, $query_arg ) ) {
			die( wp_json_encode( array( 'error' => __( 'Invalid request!', 'mainwp' ) ) ) );
		}

		if ( isset( $_POST['dts'] ) ) {
			$ajaxPosts = get_option( 'mainwp_ajaxposts' );
			if ( ! is_array( $ajaxPosts ) ) {
				$ajaxPosts = array();
			}

			// If already processed, just quit!
			if ( isset( $ajaxPosts[ $action ] ) && ( $ajaxPosts[ $action ] == $_POST['dts'] ) ) {
				die( wp_json_encode( array( 'error' => __( 'Double request!', 'mainwp' ) ) ) );
			}

			$ajaxPosts[ $action ] = $_POST['dts'];
			MainWP_Utility::update_option( 'mainwp_ajaxposts', $ajaxPosts );
		}
	}

	/**
	 * Method check_security()
	 *
	 * Check security request
	 *
	 * @param string $action
	 * @param string $query_arg
	 *
	 * @return bool true or false
	 */
	public function check_security( $action = - 1, $query_arg = 'security' ) {
		if ( - 1 === $action ) {
			return false;
		}

		$adminurl = strtolower( admin_url() );
		$referer  = strtolower( wp_get_referer() );
		$result   = isset( $_REQUEST[ $query_arg ] ) ? wp_verify_nonce( $_REQUEST[ $query_arg ], $action ) : false;
		if ( ! $result && ! ( - 1 === $action && 0 === strpos( $referer, $adminurl ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Method add_action()
	 *
	 * Add ajax action
	 *
	 * @param string $action
	 * @param string $callback
	 */
	public function add_action( $action, $callback ) {
		add_action( 'wp_ajax_' . $action, $callback );
		$this->add_security_nonce( $action );
	}

	/**
	 * Method add_security_nonce()
	 *
	 * Add security nonce
	 *
	 * @param string $action
	 */
	public function add_security_nonce( $action ) {
		if ( ! is_array( $this->security_nonces ) ) {
			$this->security_nonces = array();
		}

		if ( ! function_exists( 'wp_create_nonce' ) ) {
			include_once ABSPATH . WPINC . '/pluggable.php';
		}
		$this->security_nonces[ $action ] = wp_create_nonce( $action );
	}

	public function get_security_nonces() {
		return $this->security_nonces;
	}

}
