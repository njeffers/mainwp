<?php

class MainWP_Post {

	public static function getClassName() {
		return __CLASS__;
	}

	public static $subPages;

	public static function init() {
		/**
		 * This hook allows you to render the Post page header via the 'mainwp-pageheader-post' action.
		 * @link http://codex.mainwp.com/#mainwp-pageheader-post
		 *
		 * This hook is normally used in the same context of 'mainwp-getsubpages-post'
		 * @link http://codex.mainwp.com/#mainwp-getsubpages-post
		 *
		 * @see \MainWP_Post::renderHeader
		 */
		add_action( 'mainwp-pageheader-post', array( MainWP_Post::getClassName(), 'renderHeader' ) );

		/**
		 * This hook allows you to render the Post page footer via the 'mainwp-pagefooter-post' action.
		 * @link http://codex.mainwp.com/#mainwp-pagefooter-post
		 *
		 * This hook is normally used in the same context of 'mainwp-getsubpages-post'
		 * @link http://codex.mainwp.com/#mainwp-getsubpages-post
		 *
		 * @see \MainWP_Post::renderFooter
		 */
		add_action( 'mainwp-pagefooter-post', array( MainWP_Post::getClassName(), 'renderFooter' ) );

//    	MainWP_Post_Handler::Instance()->addAction( 'mainwp_post_addmeta', array( MainWP_Post::getClassName(), 'ajax_add_meta', ) );

	    // to hook into featured image html content
	    add_filter( 'admin_post_thumbnail_html', array( MainWP_Post::getClassName(), 'admin_post_thumbnail_html' ), 10, 3 );

		add_action( 'mainwp_help_sidebar_content', array( MainWP_Post::getClassName(), 'mainwp_help_content' ) ); // Hook the Help Sidebar content
	}

	public static function initMenu() {
		$_page = add_submenu_page( 'mainwp_tab', __( 'Posts', 'mainwp' ), '<span id="mainwp-Posts">' . __( 'Posts', 'mainwp' ) . '</span>', 'read', 'PostBulkManage', array(
			MainWP_Post::getClassName(),
			'render',
		) );
		add_action( 'load-' . $_page, array( self::getClassName(), 'on_load_page' ) );
		add_filter( 'manage_' . $_page . '_columns', array( self::getClassName(), 'get_manage_columns' ) );

		if ( !MainWP_Menu::is_disable_menu_item( 3, 'PostBulkAdd' ) ) {
			$_page = add_submenu_page( 'mainwp_tab', __( 'Posts', 'mainwp' ), '<div class="mainwp-hidden">' . __( 'Add New', 'mainwp' ) . '</div>', 'read', 'PostBulkAdd', array(
				MainWP_Post::getClassName(),
				'renderBulkAdd',
			) );
            add_action( 'load-' . $_page, array( self::getClassName(), 'on_load_add_edit' ) );
		}

		if ( !MainWP_Menu::is_disable_menu_item( 3, 'PostBulkEdit' ) ) {
			$_page = add_submenu_page( 'mainwp_tab', __( 'Posts', 'mainwp' ), '<div class="mainwp-hidden">' . __( 'Edit Post', 'mainwp' ) . '</div>', 'read', 'PostBulkEdit', array(
				MainWP_Post::getClassName(),
				'renderBulkEdit',
			) );
            add_action( 'load-' . $_page, array( self::getClassName(), 'on_load_add_edit' ) );
		}

		add_submenu_page( 'mainwp_tab', 'Posting new bulkpost', '<div class="mainwp-hidden">' . __( 'Posts', 'mainwp' ) . '</div>', 'read', 'PostingBulkPost', array(
			MainWP_Post::getClassName(),
			'posting',
		) ); //removed from menu afterwards

		/**
		 * This hook allows you to add extra sub pages to the Post page via the 'mainwp-getsubpages-post' filter.
		 * @link http://codex.mainwp.com/#mainwp-getsubpages-post
		 */
		self::$subPages = apply_filters( 'mainwp-getsubpages-post', array() );
		if ( isset( self::$subPages ) && is_array( self::$subPages ) ) {
			foreach ( self::$subPages as $subPage ) {
				if ( MainWP_Menu::is_disable_menu_item( 3, 'Post' . $subPage[ 'slug' ] ) )
					continue;
				add_submenu_page( 'mainwp_tab', $subPage[ 'title' ], '<div class="mainwp-hidden">' . $subPage[ 'title' ] . '</div>', 'read', 'Post' . $subPage[ 'slug' ], $subPage[ 'callback' ] );
			}
		}
		MainWP_Post::init_left_menu( self::$subPages );
	}

	public static function on_load_page() {
		add_action( 'admin_head', array( MainWP_Post::getClassName(), 'admin_head' ) );
		add_filter( 'hidden_columns', array( MainWP_Post::getClassName(), 'get_hidden_columns' ), 10, 3 );
	}

    public static function on_load_add_edit() {
        if ( isset($_GET['page']) && $_GET['page'] == 'PostBulkAdd') {
            global $_mainwp_default_post_to_edit;
            $post_type = 'bulkpost';
            $_mainwp_default_post_to_edit = get_default_post_to_edit( $post_type, true );
            $post_id = $_mainwp_default_post_to_edit ? $_mainwp_default_post_to_edit->ID : 0;
        } else {
            $post_id = isset($_GET['post_id']) ?  intval ($_GET['post_id']) : 0;
        }

        if ( ! $post_id )
           wp_die( __( 'Invalid post.' ) );

        self::on_load_bulkpost( $post_id );
    }

    public static function on_load_bulkpost( $post_id ) {

        wp_enqueue_media( array( 'post' => $post_id ) );

        wp_enqueue_script( 'mainwp-post', MAINWP_PLUGIN_URL . 'assests/js/mainwp-post.js', array(
			'jquery',
            'postbox',
            'word-count',
            'media-views',
            'mainwp'
		), MAINWP_VERSION );

        $post = get_post( $post_id );
        $GLOBALS['post'] = $post; // to fix: WP Seo metabox loading scripts

	}

	public static function get_manage_columns() {
		$colums = array(
			'title'				 		=> __( 'Title', 'mainwp' ),
			'author'			 		=> __( 'Author', 'mainwp' ),
			'date'				 		=> __( 'Date', 'mainwp' ),
			'categories'		  => __( 'Categories', 'mainwp' ),
			'tags'				 		=> __( 'Tags', 'mainwp' ),
			'post-type'			  => __( 'Post type', 'mainwp' ),
			'comments'			  => __( 'Comments', 'mainwp' ),
			'status'			 		=> __( 'Status', 'mainwp' ),
			'seo-links'			  => __( 'Links', 'mainwp' ),
			'seo-linked'		  => __( 'Linked', 'mainwp' ),
			'seo-score'			  => __( 'SEO Score', 'mainwp' ),
			'seo-readability' => __( 'Readability score', 'mainwp' ),
			'website'			 		=> __( 'Website', 'mainwp' )
		);

		if ( !MainWP_Utility::enabled_wp_seo() ) {
			unset( $colums[ 'seo-links' ] );
			unset( $colums[ 'seo-linked' ] );
			unset( $colums[ 'seo-score' ] );
			unset( $colums[ 'seo-readability' ] );
		}

		return $colums;
	}

	public static function admin_head() {
		global $current_screen;
		// fake pagenow to compatible with wp_ajax_hidden_columns
		?>
		<script type="text/javascript"> pagenow = '<?php echo esc_html(strtolower( $current_screen->id )); ?>';</script>
		<?php
	}

	// to fix compatible with fake pagenow
	public static function get_hidden_columns( $hidden, $screen ) {
		if ( $screen && $screen->id == 'mainwp_page_PostBulkManage' ) {
			$hidden = get_user_option( 'manage' . strtolower( $screen->id ) . 'columnshidden' );
		}
		return $hidden;
	}

    public static function initMenuSubPages() {
		?>
		<div id="menu-mainwp-Posts" class="mainwp-submenu-wrapper">
			<div class="wp-submenu sub-open" style="">
				<div class="mainwp_boxout">
					<div class="mainwp_boxoutin"></div>
					<?php if ( mainwp_current_user_can( 'dashboard', 'manage_posts' ) ) { ?>
						<a href="<?php echo admin_url( 'admin.php?page=PostBulkManage' ); ?>" class="mainwp-submenu"><?php _e( 'Manage Posts', 'mainwp' ); ?></a>
						<?php if ( !MainWP_Menu::is_disable_menu_item( 3, 'PostBulkAdd' ) ) { ?>
							<a href="<?php echo admin_url( 'admin.php?page=PostBulkAdd' ); ?>" class="mainwp-submenu"><?php _e( 'Add New', 'mainwp' ); ?></a>
						<?php } ?>
					<?php } ?>
					<?php
					if ( isset( self::$subPages ) && is_array( self::$subPages ) ) {
						foreach ( self::$subPages as $subPage ) {
							if ( !isset( $subPage[ 'menu_hidden' ] ) || ( isset( $subPage[ 'menu_hidden' ] ) && $subPage[ 'menu_hidden' ] != true ) ) {
								if ( MainWP_Menu::is_disable_menu_item( 3, 'Post' . $subPage[ 'slug' ] ) ) {
									continue;
								}
								?>
								<a href="<?php echo admin_url( 'admin.php?page=Post' . $subPage[ 'slug' ] ); ?>" class="mainwp-submenu"><?php echo esc_html($subPage[ 'title' ]); ?></a>
								<?php
							}
						}
					}
					?>
				</div>
			</div>
		</div>
		<?php
	}

	static function init_left_menu( $subPages = array() ) {

		MainWP_Menu::add_left_menu( array(
			'title'		 => __( 'Posts', 'mainwp' ),
			'parent_key' => 'mainwp_tab',
			'slug'		 => 'PostBulkManage',
			'href'		 => 'admin.php?page=PostBulkManage',
			'icon'		 => '<i class="file alternate icon"></i>'
		), 1 ); // level 1

		$init_sub_subleftmenu = array(
			array( 'title'		 => __( 'Manage Posts', 'mainwp' ),
				'parent_key' => 'PostBulkManage',
				'href'		 => 'admin.php?page=PostBulkManage',
				'slug'		 => 'PostBulkManage',
				'right'		 => 'manage_posts'
			),
			array( 'title'		 => __( 'Add New', 'mainwp' ),
				'parent_key' => 'PostBulkManage',
				'href'		 => 'admin.php?page=PostBulkAdd',
				'slug'		 => 'PostBulkAdd',
				'right'		 => 'manage_posts'
			)
		);
		MainWP_Menu::init_subpages_left_menu( $subPages, $init_sub_subleftmenu, 'PostBulkManage', 'Post' );

		foreach ( $init_sub_subleftmenu as $item ) {
			if ( MainWP_Menu::is_disable_menu_item( 3, $item[ 'slug' ] ) ) {
				continue;
			}
			MainWP_Menu::add_left_menu( $item, 2);
		}
	}

    public static function admin_post_thumbnail_html( $content, $post_id, $thumbnail_id ) {
         $post = get_post( $post_id );

         if (empty($post))
            return $content;

         if ( $post->post_type != 'bulkpost' && $post->post_type != 'bulkpage' ) {
            return $content;
         }

         return self::_wp_post_thumbnail_html($thumbnail_id, $post_id);
    }

    /**
    * Add post meta data defined in $_POST superglobal for post with given ID.
    *
    * @since 1.2.0
    *
    * @param int $post_ID
    * @return int|bool
    */

    static function add_meta( $post_ID ) {
        $post_ID = (int) $post_ID;

        $metakeyselect = isset($_POST['metakeyselect']) ? wp_unslash( trim( $_POST['metakeyselect'] ) ) : '';
        $metakeyinput = isset($_POST['metakeyinput']) ? wp_unslash( trim( $_POST['metakeyinput'] ) ) : '';
        $metavalue = isset($_POST['metavalue']) ? $_POST['metavalue'] : '';
        if ( is_string( $metavalue ) )
            $metavalue = trim( $metavalue );

        if ( ( ( '#NONE#' != $metakeyselect ) && ! empty( $metakeyselect ) ) || ! empty( $metakeyinput ) ) {
            /*
             * We have a key/value pair. If both the select and the input
             * for the key have data, the input takes precedence.
             */
            if ( '#NONE#' != $metakeyselect )
                $metakey = $metakeyselect;

            if ( $metakeyinput )
                $metakey = $metakeyinput; // default

            if ( is_protected_meta( $metakey, 'post' ) || ! current_user_can( 'add_post_meta', $post_ID, $metakey ) )
                return false;

            $metakey = wp_slash( $metakey );

            return add_post_meta( $post_ID, $metakey, $metavalue );
        }

        return false;
    } // add_meta

    static function ajax_add_meta() {

        MainWP_Post_Handler::Instance()->secure_request('mainwp_post_addmeta');

        $c = 0;
        $pid = (int) $_POST['post_id'];
        $post = get_post( $pid );

        if ( isset($_POST['metakeyselect']) || isset($_POST['metakeyinput']) ) {
            if ( !current_user_can( 'edit_post', $pid ) )
                wp_die( -1 );
            if ( isset($_POST['metakeyselect']) && '#NONE#' == $_POST['metakeyselect'] && empty($_POST['metakeyinput']) )
                wp_die( 1 );

            if ( ! $mid = self::add_meta( $pid ) ) {
                wp_send_json( array( 'error' => __( 'Please provide a custom field value.', 'mainwp' )));
            }

            $meta = get_metadata_by_mid( 'post', $mid );
            $pid = (int) $meta->post_id;
            $meta = get_object_vars( $meta );

            $data = self::_list_meta_row( $meta, $c );

        } else if ( isset($_POST['delete_meta']) &&  $_POST['delete_meta'] == 'yes' ) {
            $id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

            check_ajax_referer( "delete-meta_$id", 'meta_nonce' );

            if ( !$meta = get_metadata_by_mid( 'post', $id ) )
                wp_send_json(array( 'ok' => 1 ));

            if ( is_protected_meta( $meta->meta_key, 'post' ) || ! current_user_can( 'delete_post_meta',  $meta->post_id, $meta->meta_key ) )
                wp_die( -1 );

            if ( delete_meta( $meta->meta_id ) )
                wp_send_json(array( 'ok' => 1 ));

            wp_die( 0 );

        } else { // Update?
            $mid = (int) key( $_POST['meta'] );
            $key = wp_unslash( $_POST['meta'][$mid]['key'] );
            $value = wp_unslash( $_POST['meta'][$mid]['value'] );
            if ( '' == trim($key) )
                wp_send_json( array( 'error' => __( 'Please provide a custom field name.', 'mainwp' )));

            if ( ! $meta = get_metadata_by_mid( 'post', $mid ) )
                wp_die( 0 ); // if meta doesn't exist
            if ( is_protected_meta( $meta->meta_key, 'post' ) || is_protected_meta( $key, 'post' ) ||
                ! current_user_can( 'edit_post_meta', $meta->post_id, $meta->meta_key ) ||
                ! current_user_can( 'edit_post_meta', $meta->post_id, $key ) )
                wp_die( -1 );
            if ( $meta->meta_value != $value || $meta->meta_key != $key ) {
                if ( !$u = update_metadata_by_mid( 'post', $mid, $value, $key ) )
                    wp_die( 0 ); // We know meta exists; we also know it's unchanged (or DB error, in which case there are bigger problems).
            }

            $data = self::_list_meta_row( array(
                                'meta_key' => $key,
                                'meta_value' => $value,
                                'meta_id' => $mid
                            ), $c );
        }

        wp_send_json(array( 'result' => $data ));
    }


    /**
	 * @param string $shownPage The page slug shown at this moment
	 */
	public static function renderHeader( $shownPage = '', $post_id = null ) {
		$params = array(
			'title' => __( 'Posts', 'mainwp' )
		);
		MainWP_UI::render_top_header($params);

			$renderItems = array();

			if ( mainwp_current_user_can( 'dashboard', 'manage_posts' ) ) {
				$renderItems[] = array(
					'title' => __( 'Manage Posts', 'mainwp' ),
					'href' => "admin.php?page=PostBulkManage",
					'active' => ($shownPage == 'BulkManage') ? true : false
				);
				if ( $shownPage == 'BulkEdit' ) {
					$renderItems[] = array(
						'title' => __( 'Edit Post', 'mainwp' ),
						'href' => 'admin.php?page=PostBulkEdit&post_id=' . esc_attr( $post_id ),
						'active' => true
					);
				}
				if ( ! MainWP_Menu::is_disable_menu_item( 3, 'PostBulkAdd' ) ) {
					$renderItems[] = array(
						'title'		 => __( 'Add New', 'mainwp' ),
						'href'		 => "admin.php?page=PostBulkAdd",
						'active'	 => ($shownPage == 'BulkAdd') ? true : false
					);
				}
			}

			if ( isset( self::$subPages ) && is_array( self::$subPages ) ) {
				foreach ( self::$subPages as $subPage ) {
					 if ( MainWP_Menu::is_disable_menu_item( 3, 'Post' . $subPage[ 'slug' ] ) )
						 continue;

					 if ( isset( $subPage[ 'tab_link_hidden' ] ) && $subPage[ 'tab_link_hidden' ] == true ) {
						 $tab_link = '#';
					 } else {
						 $tab_link = 'admin.php?page=Post' . $subPage[ 'slug' ];
					 }

					 $item = array();
					 $item['title'] = $subPage['title'];
					 $item['href'] = $tab_link;
					 $item['active'] = ($subPage[ 'slug' ] == $shownPage) ? true : false;
					 $renderItems[] = $item;

				}
			}
			MainWP_UI::render_page_navigation( $renderItems, __CLASS__ );

	}

	/**
	 * @param string $shownPage The page slug shown at this moment
	 */
	public static function renderFooter( $shownPage ) {
		echo "</div>";
	}

	public static function render() {
		if ( !mainwp_current_user_can( 'dashboard', 'manage_posts' ) ) {
			mainwp_do_not_have_permissions( __( 'manage posts', 'mainwp' ) );
			return;
		}

		$cachedSearch = MainWP_Cache::getCachedContext( 'Post' );

		$selected_sites	 = $selected_groups = array();
		if ( $cachedSearch != null ) {
			if ( is_array( $cachedSearch[ 'sites' ] ) ) {
				$selected_sites = $cachedSearch[ 'sites' ];
			} else if ( is_array( $cachedSearch[ 'groups' ] ) ) {
				$selected_groups = $cachedSearch[ 'groups' ];
			}
		}

		//Loads the post screen via AJAX, which redirects to the "posting()" to really post the posts to the saved sites
		self::renderHeader( 'BulkManage' );

			?>
		<div class="ui alt mainwp-posts segment">

			<div class="mainwp-main-content">

				<div class="mainwp-actions-bar ui mini form">
					<div class="ui grid">
						<div class="ui two column row">
							<div class="column">
								<select class="ui dropdown" id="mainwp-bulk-actions">
									<option value="none"><?php _e( 'Bulk Actions', 'mainwp' ); ?></option>
									<option value="publish"><?php _e( 'Publish', 'mainwp' ); ?></option>
									<option value="unpublish"><?php _e( 'Unpublish', 'mainwp' ); ?></option>
									<option value="trash"><?php _e( 'Trash', 'mainwp' ); ?></option>
									<option value="restore"><?php _e( 'Restore', 'mainwp' ); ?></option>
									<option value="delete"><?php _e( 'Delete', 'mainwp' ); ?></option>
								</select>
								<button class="ui mini button" id="mainwp-do-posts-bulk-actions"><?php esc_html_e( 'Apply', 'mainwp' ); ?></button>
								<?php do_action( 'mainwp_posts_actions_bar_left' ); ?>
							</div>
							<div class="right aligned column">
								<?php do_action( 'mainwp_posts_actions_bar_right' ); ?>
							</div>
						</div>
					</div>
				</div>

				<div class="ui segment" id="mainwp-posts-table-wrapper">
					<?php MainWP_Post::renderTable( true ); ?>
				</div>
			</div>

			<div class="mainwp-side-content mainwp-no-padding">
				<div class="mainwp-select-sites">
					<div class="ui header"><?php _e( 'Select Sites', 'mainwp' ); ?></div>
					<?php MainWP_UI::select_sites_box( 'checkbox', true, true, 'mainwp_select_sites_box_left', '', $selected_sites, $selected_groups ); ?>
      	</div>
				<div class="ui divider"></div>
				<div class="mainwp-search-options">
					<div class="ui mini form">
						<div class="field">
							<select multiple="" class="ui fluid dropdown" id="mainwp_post_search_type">
								<option value=""><?php _e( 'Select status', 'mainwp' ); ?></option>
								<option value="publish"><?php _e( 'Published', 'mainwp' ); ?></option>
								<option value="pending"><?php _e( 'Pending', 'mainwp' ); ?></option>
								<option value="private"><?php _e( 'Private', 'mainwp' ); ?></option>
								<option value="future"><?php _e( 'Scheduled', 'mainwp' ); ?></option>
								<option value="draft"><?php _e( 'Draft', 'mainwp' ); ?></option>
								<option value="trash"><?php _e( 'Trash', 'mainwp' ); ?></option>
							</select>
						</div>
					</div>
				</div>
				<div class="ui divider"></div>
				<div class="mainwp-search-options">
					<div class="ui header"><?php _e( 'Search Options', 'mainwp' ); ?></div>
					<?php self::renderSearchOptions(); ?>
				</div>
				<div class="ui divider"></div>
				<div class="mainwp-search-submit">
					<input type="button" name="mainwp_show_posts" id="mainwp_show_posts" class="ui green big fluid button" value="<?php esc_attr_e( 'Show Posts', 'mainwp' ); ?>"/>
				</div>
			</div>
			<div style="clear:both"></div>

		</div>

		<?php

		if ( isset( $_REQUEST[ 'siteid' ] ) && isset( $_REQUEST[ 'postid' ] ) ) {
			echo '<script>jQuery(document).ready(function() { mainwp_show_post(' . intval( $_REQUEST[ 'siteid' ] ) . ', ' . intval( $_REQUEST[ 'postid' ] ) . ', undefined)});</script>';
		} else if ( isset( $_REQUEST[ 'siteid' ] ) && isset( $_REQUEST[ 'userid' ] ) ) {
			echo '<script>jQuery(document).ready(function() { mainwp_show_post(' . intval( $_REQUEST[ 'siteid' ] ) . ', undefined, ' . intval( $_REQUEST[ 'userid' ] ) . ')});</script>';
		}

		self::renderFooter( 'BulkManage' );
	}

	public static function renderSearchOptions() {
		$cachedSearch	 = MainWP_Cache::getCachedContext( 'Post' );
    $statuses  = isset( $cachedSearch['status'] ) ? $cachedSearch['status'] : array();

		?>
		<div class="ui mini form">
			<div class="field">
				<div class="ui input fluid">
					<input type="text" placeholder="<?php esc_attr_e( 'Containing keyword', 'mainwp' ); ?>" id="mainwp_post_search_by_keyword" class="text" value="<?php if ( $cachedSearch != null ) { echo esc_attr($cachedSearch[ 'keyword' ]); } ?>"/>
				</div>
			</div>
			<div class="field">
		<?php
		$searchon		 = 'title';
		if ( $cachedSearch != null ) {
			$searchon = $cachedSearch[ 'search_on' ];
		}
		?>
				<select class="ui dropdown fluid" id="mainwp_post_search_on">
					<option value=""><?php esc_html_e( 'Search in...', 'mainwp' ); ?></option>
					<option value="title" <?php echo $searchon == 'title' ? 'selected' : ''; ?>><?php esc_html_e( 'Title', 'mainwp' ); ?></option>
					<option value="content" <?php echo $searchon == 'content' ? 'selected' : ''; ?>><?php esc_html_e( 'Body', 'mainwp' ); ?></option>
					<option value="all" <?php echo $searchon == 'all' ? 'selected' : ''; ?>><?php esc_html_e( 'Title and Body', 'mainwp' ); ?></option>
				</select>
			</div>
			<div class="field">
				<label><?php _e( 'Date range', 'mainwp' ); ?></label>
				<div class="two fields">
					<div class="field">
            <div class="ui calendar mainwp_datepicker" >
              <div class="ui input left icon">
                <i class="calendar icon"></i>
                <input type="text" placeholder="<?php esc_attr_e( 'Date', 'mainwp' ); ?>" id="mainwp_post_search_by_dtsstart" value="<?php if ( $cachedSearch != null ) { echo esc_attr($cachedSearch[ 'dtsstart' ]); } ?>"/>
              </div>
            </div>
          </div>
					<div class="field">
            <div class="ui calendar mainwp_datepicker" >
              <div class="ui input left icon">
                <i class="calendar icon"></i>
                <input type="text" placeholder="<?php esc_attr_e( 'Date', 'mainwp' ); ?>" id="mainwp_post_search_by_dtsstop" value="<?php if ( $cachedSearch != null ) { echo esc_attr($cachedSearch[ 'dtsstop' ]); } ?>"/>
              </div>
            </div>
          </div>
				</div>
			</div>
			<?php if ( is_plugin_active( 'mainwp-custom-post-types/mainwp-custom-post-types.php' ) ): ?>
			<div class="field">
				<label><?php _e( 'Select post type', 'mainwp' ); ?></label><br/>
				<select class="ui dropdown fluid" id="mainwp_get_custom_post_types_select">
						<option value="any"><?php _e( 'All post types', 'mainwp' ); ?></option>
						<option value="post"><?php _e( 'Post', 'mainwp' ); ?></option>
						<?php
						foreach ( get_post_types( array( '_builtin' => false ) ) as $key ) {
							if ( !in_array( $key, MainWPCustomPostType::$default_post_types ) )
								echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $key ) . '</option>';
						}
						?>
					</select>
				</div>
			<?php endif; ?>
			<div class="field">
				<label><?php _e( 'Max posts to return', 'mainwp' ); ?></label>
				<input type="text" name="mainwp_maximumPosts"  id="mainwp_maximumPosts" value="<?php echo( ( get_option( 'mainwp_maximumPosts' ) === false ) ? 50 : get_option( 'mainwp_maximumPosts' ) ); ?>"/>
			</div>
		</div>
        <?php if ( is_array($statuses) && count($statuses) > 0 ) {
            $status = '';
            foreach($statuses as $st) {
                $status .= "'" . esc_attr($st) . "',";
            }
            $status = rtrim($status, ',');

            ?>
            <script type="text/javascript">
                jQuery( document ).ready( function () {
                    jQuery('#mainwp_post_search_type').dropdown('set selected',[<?php echo $status; ?>]);
                })
            </script>
		<?php
        }
	}

	public static function renderTable( $cached = true, $keyword = '', $dtsstart = '', $dtsstop = '', $status = '', $groups = '', $sites = '', $postId = 0, $userId = 0, $post_type = '', $search_on = 'all' ) {
	?>

		<div id="mainwp-message-zone"></div>

		<div id="mainwp-loading-posts-row" style="display: none;">
			<div class="ui active inverted dimmer">
				<div class="ui indeterminate large text loader"><?php _e( 'Loading Posts...', 'mainwp' ); ?></div>
			</div>
		</div>

		<table id="mainwp-posts-table" class="ui stackable selectable single line table" style="width:100%">
			<thead>
				<tr>
          <th class="no-sort collapsing check-column"><span class="ui checkbox"><input id="cb-select-all-top" type="checkbox" /></span></th>
		      <th id="mainwp-title"><?php esc_html_e( 'Title', 'mainwp' ); ?></th>
		      <th id="mainwp-author"><?php esc_html_e( 'Author', 'mainwp' ); ?></th>
		      <th id="mainwp-categories"><?php esc_html_e( 'Categories', 'mainwp' ); ?></th>
		      <th id="mainwp-tags"><?php esc_html_e( 'Tags', 'mainwp' ); ?></th>
          <?php if ( is_plugin_active( 'mainwp-custom-post-types/mainwp-custom-post-types.php' ) ): ?>
          	<th id="mainwp-post-type"><?php _e( 'Post Type', 'mainwp' ); ?></th>
          <?php endif; ?>
          <?php if ( is_plugin_active( 'mainwp-comments-extension/mainwp-comments-extension.php' ) ): ?>
          	<th id="mainwp-comments"><i class="comment icon"></i></th>
          <?php endif; ?>
          <th id="mainwp-date" class=""><?php esc_html_e( 'Date', 'mainwp' ); ?></th>
          <th id="mainwp-status" class=""><?php esc_html_e( 'Status', 'mainwp' ); ?></th>
					<?php if ( MainWP_Utility::enabled_wp_seo() ) : ?>
					<th id="mainwp-seo-links"><?php echo __( 'Links', 'mainwp' ); ?></th>
          <th id="mainwp-seo-linked"><?php esc_html_e( 'Linked', 'mainwp' ); ?></th>
					<th id="mainwp-seo-score"><?php esc_html_e( 'SEO Score', 'mainwp' ); ?></th>
          <th id="mainwp-seo-readability"><?php esc_html_e( 'Readability score', 'mainwp' ); ?></th>
					<?php endif; ?>
					<th id="mainwp-website"><?php esc_html_e( 'Site', 'mainwp' ); ?></th>
					<th id="mainwp-posts-actions" class="no-sort"></th>
				</tr>
			</thead>

			<tbody id="mainwp-posts-list">
      <?php
        if ( $cached ) {
            MainWP_Cache::echoBody( 'Post' );
        } else {
            MainWP_Post::renderTableBody( $keyword, $dtsstart, $dtsstop, $status, $groups, $sites, $postId, $userId, $post_type, $search_on );
        }
			?>
			</tbody>
		</table>
    <script type="text/javascript">
    jQuery( document ).ready( function () {
      jQuery('#mainwp-posts-table').DataTable({
				"colReorder" : true,
				"stateSave":  true,
				"pagingType": "full_numbers",
				"order": [],
				"scrollX" : true,
				"lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
				"columnDefs": [ {
					"targets": 'no-sort',
					"orderable": false
				} ],
				"preDrawCallback": function( settings ) {
				<?php if ( ! $cached ) { ?>
				jQuery('#mainwp-posts-table-wrapper table .ui.dropdown').dropdown();
				jQuery('#mainwp-posts-table-wrapper table .ui.checkbox' ).checkbox();
				<?php } ?>
				}
      } );
    } );
    </script>

		<?php
	}

	public static function renderTableBody( $keyword, $dtsstart, $dtsstop, $status, $groups, $sites, $postId, $userId, $post_type = '', $search_on = 'all' ) {
		MainWP_Cache::initCache( 'Post' );

		//Fetch all!
		//Build websites array
		$dbwebsites = array();
		if ( $sites != '' ) {
			foreach ( $sites as $k => $v ) {
				if ( MainWP_Utility::ctype_digit( $v ) ) {
					$website					 = MainWP_DB::Instance()->getWebsiteById( $v );
					$dbwebsites[ $website->id ]	 = MainWP_Utility::mapSite( $website, array(
						'id',
						'url',
						'name',
						'adminname',
						'nossl',
						'privkey',
						'nosslkey',
                        'http_user',
                        'http_pass'
					) );
				}
			}
		}
		if ( $groups != '' ) {
			foreach ( $groups as $k => $v ) {
				if ( MainWP_Utility::ctype_digit( $v ) ) {
					$websites	 = MainWP_DB::Instance()->query( MainWP_DB::Instance()->getSQLWebsitesByGroupId( $v ) );
					while ( $websites && ( $website	 = @MainWP_DB::fetch_object( $websites ) ) ) {
						if ( $website->sync_errors != '' ) {
							continue;
						}
						$dbwebsites[ $website->id ] = MainWP_Utility::mapSite( $website, array(
							'id',
							'url',
							'name',
							'adminname',
							'nossl',
							'privkey',
							'nosslkey',
                            'http_user',
                            'http_pass'
						) );
					}
					@MainWP_DB::free_result( $websites );
				}
			}
		}

		$output			 = new stdClass();
		$output->errors	 = array();
		$output->posts	 = 0;

		if ( count( $dbwebsites ) > 0 ) {
			$post_data = array(
				'keyword'	 => $keyword,
				'dtsstart'	 => $dtsstart,
				'dtsstop'	 => $dtsstop,
				'status'	 => $status,
				'search_on'	 => $search_on,
				'maxRecords' => ( ( get_option( 'mainwp_maximumPosts' ) === false ) ? 50 : get_option( 'mainwp_maximumPosts' ) ),
			);

			// Add support for custom post type
			if ( is_plugin_active( 'mainwp-custom-post-types/mainwp-custom-post-types.php' ) ) {
				$post_data[ 'post_type' ] = $post_type;
				if ( $post_type == 'any' ) {
					$post_data[ 'exclude_page_type' ] = 1; // to exclude pages in posts listing, custom post type extension
				}
			}

			if ( MainWP_Utility::enabled_wp_seo() ) {
				$post_data[ 'WPSEOEnabled' ] = 1;
			}

			if ( isset( $postId ) && ( $postId != '' ) ) {
				$post_data[ 'postId' ] = $postId;
			} else if ( isset( $userId ) && ( $userId != '' ) ) {
				$post_data[ 'userId' ] = $userId;
			}

			$post_data = apply_filters( 'mainwp_get_all_posts_data', $post_data );
			MainWP_Utility::fetchUrlsAuthed( $dbwebsites, 'get_all_posts', $post_data, array(
				MainWP_Post::getClassName(),
				'PostsSearch_handler',
			), $output );
		}

		MainWP_Cache::addContext( 'Post', array(
			'count'		   => $output->posts,
			'keyword'	   => $keyword,
			'dtsstart'	 => $dtsstart,
			'dtsstop'	 => $dtsstop,
			'status'	 => $status,
			'sites'		 => ($sites != '') ? $sites : '',
			'groups'	 => ($groups != '') ? $groups : '',
			'search_on'	 => $search_on
		) );

		//Sort if required
		if ( $output->posts == 0 ) {
			ob_start();
			?>
			<tr>
        <td colspan="999"><?php esc_html_e( 'Please use the search options to find wanted posts.', 'mainwp' ); ?></td>
      </tr>

			<?php
			$newOutput = ob_get_clean();
			echo $newOutput;
			MainWP_Cache::addBody( 'Post', $newOutput );

			return;
		}
	}

	private static function getStatus( $status ) {
		if ( $status == 'publish' ) {
			return 'Published';
		}

		return ucfirst( $status );
	}

	public static function PostsSearch_handler( $data, $website, &$output ) {
		if ( preg_match( '/<mainwp>(.*)<\/mainwp>/', $data, $results ) > 0 ) {
			$posts = unserialize( base64_decode( $results[ 1 ] ) );

            if(is_array($posts) && isset($posts['error'])) {
                $output->errors[ $website->id ] = $posts['error'];
                return;
            }

			unset( $results );

			$child_to_dash_array = array();

			if ( is_plugin_active( 'mainwp-custom-post-types/mainwp-custom-post-types.php' ) ) {
				$child_post_ids = array();
				foreach ( $posts as $post ) {
					$child_post_ids[] = $post[ 'id' ];
				}
				reset( $posts );

				$connections_ids = MainWPCustomPostTypeDB::Instance()->get_dash_post_ids_from_connections( $website->id, $child_post_ids );
				if ( !empty( $connections_ids ) ) {
					foreach ( $connections_ids as $key ) {
						$child_to_dash_array[ $key->child_post_id ] = $key->dash_post_id;
					}
				}
			}

			foreach ( $posts as $post ) {
				$raw_dts = '';
				if ( isset( $post[ 'dts' ] ) ) {
					$raw_dts = $post[ 'dts' ];
					if ( !stristr( $post[ 'dts' ], '-' ) ) {
						$post[ 'dts' ] = MainWP_Utility::formatTimestamp( MainWP_Utility::getTimestamp( $post[ 'dts' ] ) );
					}
				}

				if ( !isset( $post[ 'title' ] ) || ( $post[ 'title' ] == '' ) ) {
					$post[ 'title' ] = '(No Title)';
				}

				ob_start();
				?>

				<tr>
					<input class="postId" type="hidden" name="id" value="<?php echo esc_attr($post[ 'id' ]); ?>"/>
					<input class="allowedBulkActions" type="hidden" name="allowedBulkActions" value="|get_edit|trash|delete|<?php
					if ( $post[ 'status' ] == 'publish' ) {
					  echo 'unpublish|';
					}
					if ( $post[ 'status' ] == 'pending' ) {
					  echo 'approve|';
					}
					if ( $post[ 'status' ] == 'trash' ) {
					  echo 'restore|';
					}
					if ( $post[ 'status' ] == 'future' || $post[ 'status' ] == 'draft' ) {
					echo 'publish|';
					}
					?>"/>
					<input class="websiteId" type="hidden" name="id" value="<?php echo $website->id; ?>"/>

                    <td class="check-column"><span class="ui checkbox"><input type="checkbox" name="post[]" value="1"></span></td>

					<td class="title column-title">
						<strong>
						  <abbr title="<?php echo esc_attr( $post[ 'title' ] ); ?>">
						  	<?php if ( $post[ 'status' ] != 'trash' ) { ?>
						      <a class="row-title" href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo $website->id; ?>&location=<?php echo base64_encode( 'post.php?post=' . $post[ 'id' ] . '&action=edit' ); ?>" target="_blank">
										 <?php echo esc_html( $post[ 'title' ] ); ?>
									</a>
								<?php } else {
									 echo esc_html( $post[ 'title' ] );
								 } ?>
						  </abbr>
						</strong>
					</td>

					<td class="author column-author"><?php echo esc_html( $post[ 'author' ] ); ?></td>

					<td class="categories column-categories"><?php echo esc_attr( $post[ 'categories' ] ); ?></td>

					<td class="tags"><?php echo( $post[ 'tags' ] == '' ? 'No Tags' : $post[ 'tags' ] ); ?></td>

					<?php if ( is_plugin_active( 'mainwp-custom-post-types/mainwp-custom-post-types.php' ) ): ?>
						<td class="post-type column-post-type"><?php echo esc_html( $post[ 'post_type' ] ) ?></td>
					<?php endif; ?>

          <?php if ( is_plugin_active( 'mainwp-comments-extension/mainwp-comments-extension.php' ) ): ?>
					<td class="comments column-comments">
						<div class="post-com-count-wrapper">
							<a href="<?php echo admin_url( 'admin.php?page=CommentBulkManage&siteid=' . $website->id . '&postid=' . $post[ 'id' ] ); ?>" title="0 pending" class="post-com-count"><span class="comment-count"><abbr title="<?php echo esc_attr($post[ 'comment_count' ]); ?>"><?php echo esc_html($post[ 'comment_count' ]); ?></abbr></span></a>
						</div>
					</td>
          <?php endif; ?>

					<td class="date column-date"><abbr raw_value="<?php echo esc_attr( $raw_dts ); ?>" title="<?php echo esc_attr($post[ 'dts' ]); ?>"><?php echo esc_html($post[ 'dts' ]); ?></abbr></td>

					<td class="status column-status"><?php echo self::getStatus( $post[ 'status' ] ); ?></td>

					<?php if ( MainWP_Utility::enabled_wp_seo() ):
						$count_seo_links	 = $count_seo_linked	 = null;
						$seo_score			 = $readability_score	 = '';
						if ( isset( $post[ 'seo_data' ] ) ) {
							$seo_data			 = $post[ 'seo_data' ];
							$count_seo_links	 = esc_html( $seo_data[ 'count_seo_links' ] );
							$count_seo_linked	 = esc_html( $seo_data[ 'count_seo_linked' ] );
							$seo_score			 = $seo_data[ 'seo_score' ];
							$readability_score	 = $seo_data[ 'readability_score' ];
						}
						?>
						<td class="column-seo-links" ><abbr raw_value="<?php echo $count_seo_links !== null ? $count_seo_links : -1; ?>" title=""><?php echo $count_seo_links !== null ? $count_seo_links : ''; ?></abbr></td>
						<td class="column-seo-linked"><abbr raw_value="<?php echo $count_seo_linked !== null ? $count_seo_linked : -1; ?>" title=""><?php echo $count_seo_linked !== null ? $count_seo_linked : ''; ?></abbr></td>
						<td class="column-seo-score"><abbr raw_value="<?php echo $seo_score ? 1 : 0; ?>" title=""><?php echo $seo_score; ?></abbr></td>
						<td class="column-seo-readability"><abbr raw_value="<?php echo $readability_score ? 1 : 0; ?>" title=""><?php echo $readability_score; ?></abbr></td>
					<?php endif; ?>

					<td class="website column-website"><a href="<?php echo esc_url($website->url); ?>" target="_blank"><?php echo esc_html($website->url); ?></a></td>
					<td class="right aligned">
						<div class="ui right pointing dropdown icon mini basic green button" style="z-index: 999">
							<a href="javascript:void(0)"><i class="ellipsis horizontal icon"></i></a>
							<div class="menu">
								<?php if ( $post[ 'status' ] == 'future' || $post[ 'status' ] == 'draft' ) : ?>
							  	<a class="item post_submitpublish" href="#"><?php _e( 'Publish', 'mainwp' ); ?></a>
								<?php endif; ?>

								<?php if ( $post[ 'status' ] == 'pending' ) : ?>
									<a class="item post_submitapprove" href="#"><?php _e( 'Approve', 'mainwp' ); ?></a>
								<?php endif; ?>

								<?php if ( $post[ 'status' ] == 'publish' ) : ?>
									<a class="item post_submitunpublish" href="#"><?php _e( 'Unpublish', 'mainwp' ); ?></a>
									<a class="item mainwp-may-hide-referrer" href="<?php echo $website->url . ( substr( $website->url, - 1 ) != '/' ? '/' : '' ) . '?p=' . esc_attr($post[ 'id' ]); ?>" target="_blank" ><?php _e( 'View', 'mainwp' ); ?></a>
								<?php endif; ?>

								<?php if ( $post[ 'status' ] == 'trash' ) : ?>
									<a class="item post_submitrestore" href="#"><?php _e( 'Restore', 'mainwp' ); ?></a>
									<a class="item post_submitdelete_perm" href="#"><?php _e( 'Delete', 'mainwp' ); ?></a>
								<?php endif; ?>

								<?php if ( $post[ 'status' ] != 'trash' ): ?>
									<?php if ( isset( $child_to_dash_array[ $post[ 'id' ] ] ) ) { ?>
							       <a class="item" href="post.php?post=<?php echo (int) $child_to_dash_array[ $post[ 'id' ] ]; ?>&action=edit&select=<?php echo (int) $website->id; ?>"><?php _e( 'Edit', 'mainwp' ); ?></a>
									<?php } else { ?>
										<a class="item post_getedit" href="#"><?php _e( 'Edit', 'mainwp' ); ?></a>
									<?php } ?>
									<a class="item post_submitdelete" href="#"><?php _e( 'Trash', 'mainwp' ); ?></a>
								<?php endif; ?>
									<a class="item" href="<?php echo 'admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $website->id; ?>" data-tooltip="<?php esc_attr_e( 'Jump to the site WP Admin', 'mainwp' ); ?>"  data-position="bottom right"  data-inverted="" class="open_newwindow_wpadmin ui green basic icon button" target="_blank"><?php echo __( 'Go to WP Admin', 'mainwp' ) ?></a>									
							</div>
						</div>
					</td>
				</tr>
				<?php
				$newOutput = ob_get_clean();
				echo $newOutput;

				MainWP_Cache::addBody( 'Post', $newOutput );
				$output->posts ++;
			}
			unset( $posts );
		} else {
			$output->errors[ $website->id ] = MainWP_Error_Helper::getErrorMessage( new MainWP_Exception( 'NOMAINWP', $website->url ) );
		}
	}


/**
 * Outputs a single row of public meta data in the Custom Fields meta box.
 *
 * @since 2.5.0
 *
 * @staticvar string $update_nonce
 *
 * @param array $entry
 * @param int   $count
 * @return string
 */
static function _list_meta_row( $entry, &$count ) {
	static $update_nonce = '';

	if ( is_protected_meta( $entry['meta_key'], 'post' ) )
		return '';

	if ( ! $update_nonce )
		$update_nonce = wp_create_nonce( 'add-meta' );

	$r = '';
	++ $count;

	if ( is_serialized( $entry['meta_value'] ) ) {
		if ( is_serialized_string( $entry['meta_value'] ) ) {
			// This is a serialized string, so we should display it.
			$entry['meta_value'] = maybe_unserialize( $entry['meta_value'] );
		} else {
			// This is a serialized array/object so we should NOT display it.
			--$count;
			return '';
		}
	}

	$entry['meta_key'] = esc_attr($entry['meta_key']);
	$entry['meta_value'] = esc_textarea( $entry['meta_value'] ); // using a <textarea />
	$entry['meta_id'] = (int) $entry['meta_id'];

	$delete_nonce = wp_create_nonce( 'delete-meta_' . $entry['meta_id'] );

  	$r .= "\n\t<div class=\"two column row\" meta-id=\"" . $entry['meta_id'] . "\" >";
	$r .= "\n\t\t<div class=\"column\"><label for='meta-{$entry['meta_id']}-key'>" . __( 'Key' ) . "</label><input name='meta[{$entry['meta_id']}][key]' id='meta-{$entry['meta_id']}-key' type='text' size='20' value='{$entry['meta_key']}' />";
	$r .= "\n\t\t";
    $r .= "<input type=\"button\" onclick=\"mainwp_post_newmeta_submit('delete', this)\" class=\"ui mini button\" _ajax_nonce=\"$delete_nonce\" value=\"" . esc_attr__( 'Delete', 'mainwp' ) . "\">";
	$r .= "\n\t\t";
    $r .= "<input type=\"button\" onclick=\"mainwp_post_newmeta_submit('update', this)\" class=\"ui mini button\" value=\"" . esc_attr__( 'Update', 'mainwp' ) . "\">";
	$r .= "</div>";
	$r .= "\n\t\t<div class=\"column\"><label for='meta-{$entry['meta_id']}-value'>" . __( 'Value' ) . "</label><textarea name='meta[{$entry['meta_id']}][value]' id='meta-{$entry['meta_id']}-value' rows='2' cols='30'>{$entry['meta_value']}</textarea></div>\n\t</div>";
	return $r;
}


/**
 * Prints the form in the Custom Fields meta box.
 *
 * @since 1.2.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param WP_Post $post Optional. The post being edited.
 */
static function meta_form( $post = null ) {
	global $wpdb;
	$post = get_post( $post );

	/**
	 * Filters values for the meta key dropdown in the Custom Fields meta box.
	 *
	 * Returning a non-null value will effectively short-circuit and avoid a
	 * potentially expensive query against postmeta.
	 *
	 * @since 4.4.0
	 *
	 * @param array|null $keys Pre-defined meta keys to be used in place of a postmeta query. Default null.
	 * @param WP_Post    $post The current post object.
	 */
	$keys = apply_filters( 'postmeta_form_keys', null, $post );

	if ( null === $keys ) {
		/**
		 * Filters the number of custom fields to retrieve for the drop-down
		 * in the Custom Fields meta box.
		 *
		 * @since 2.1.0
		 *
		 * @param int $limit Number of custom fields to retrieve. Default 30.
		 */
		$limit = apply_filters( 'postmeta_form_limit', 30 );
		$sql = "SELECT DISTINCT meta_key
			FROM $wpdb->postmeta
			WHERE meta_key NOT BETWEEN '_' AND '_z'
			HAVING meta_key NOT LIKE %s
			ORDER BY meta_key
			LIMIT %d";
		$keys = $wpdb->get_col( $wpdb->prepare( $sql, $wpdb->esc_like( '_' ) . '%', $limit ) );
	}

	if ( $keys ) {
		natcasesort( $keys );
		$meta_key_input_id = 'metakeyselect';
	} else {
		$meta_key_input_id = 'metakeyinput';
	}
?>

<div class="two column row" id="mainwp-metaform-row">
    <div class="column">
        <label for="<?php echo $meta_key_input_id; ?>"><?php _ex( 'Name', 'meta name' ) ?></label>
<?php if ( $keys ) { ?>
<select id="metakeyselect" name="metakeyselect">
<option value="#NONE#"><?php _e( '&mdash; Select &mdash;' ); ?></option>
<?php

	foreach ( $keys as $key ) {
		if ( is_protected_meta( $key, 'post' ) || ! current_user_can( 'add_post_meta', $post->ID, $key ) )
			continue;
		echo "\n<option value='" . esc_attr($key) . "'>" . esc_html($key) . "</option>";
	}
?>
</select>
<input class="hide-if-js" type="text" id="metakeyinput" name="metakeyinput" value="" />
<a href="#postcustomstuff" class="hide-if-no-js" onclick="jQuery('#metakeyinput, #metakeyselect, #enternew, #cancelnew').toggle();return false;">
<span id="enternew"><?php _e('Enter new'); ?></span>
<span id="cancelnew" class="hidden"><?php _e('Cancel'); ?></span></a>
<?php } else { ?>
<input type="text" id="metakeyinput" name="metakeyinput" value="" />
<?php } ?>
</div>
<div class="column">
<label for="metavalue"><?php _e( 'Value' ) ?></label>
<textarea id="metavalue" name="metavalue" rows="2" cols="25"></textarea>
</div>
</div>


<div class="two column row">
    <div class="column">
        <input type="button" onclick="mainwp_post_newmeta_submit('add')" class="ui mini button" value="<?php esc_attr_e( 'Add Custom Field', 'mainwp' ); ?>">
    </div>
    <div class="column">
    </div>
</div>
<?php
}

    /**
     * Display custom fields form fields.
     *
     * @since 2.6.0
     *
     * @param object $post
     */
    static function post_custom_meta_box($post) {
            ?>
           <div class="ui secondary segment">
            <div class="ui header"><?php echo esc_html( 'Custom Fields', 'mainwp' ); ?></div>
            <div class="ui grid">
            <?php
            $metadata = has_meta($post->ID);
            foreach ( $metadata as $key => $value ) {
                if ( is_protected_meta( $metadata[ $key ][ 'meta_key' ], 'post' ) || ! current_user_can( 'edit_post_meta', $post->ID, $metadata[ $key ][ 'meta_key' ] ) )
                    unset( $metadata[ $key ] );
            }

            $count = 0;
            if ($metadata) {
                foreach ( $metadata as $entry ) {
                    echo self::_list_meta_row( $entry, $count );
                }
            }

            self::meta_form( $post ); ?>
            </div>
            </div>
            <?php
    }


    /**
     * Output HTML for the post thumbnail meta-box.
     *
     * @since 2.9.0
     *
     * @param int $thumbnail_id ID of the attachment used for thumbnail
     * @param mixed $post The post ID or object associated with the thumbnail, defaults to global $post.
     * @return string html
     */
    static function _wp_post_thumbnail_html( $thumbnail_id = null, $post = null ) {

        $_wp_additional_image_sizes = wp_get_additional_image_sizes();

        $post               = get_post( $post );
        $post_type_object   = get_post_type_object( $post->post_type );

        $thumb_ok = false;
        $upload_iframe_src  = get_upload_iframe_src( 'image', $post->ID );

        if ( $thumbnail_id && get_post( $thumbnail_id ) ) {
            $size = isset( $_wp_additional_image_sizes['post-thumbnail'] ) ? 'post-thumbnail' : array( 266, 266 );

            /**
             * Filters the size used to display the post thumbnail image in the 'Featured Image' meta box.
             *
             * Note: When a theme adds 'post-thumbnail' support, a special 'post-thumbnail'
             * image size is registered, which differs from the 'thumbnail' image size
             * managed via the Settings > Media screen. See the `$size` parameter description
             * for more information on default values.
             *
             * @since 4.4.0
             *
             * @param string|array $size         Post thumbnail image size to display in the meta box. Accepts any valid
             *                                   image size, or an array of width and height values in pixels (in that order).
             *                                   If the 'post-thumbnail' size is set, default is 'post-thumbnail'. Otherwise,
             *                                   default is an array with 266 as both the height and width values.
             * @param int          $thumbnail_id Post thumbnail attachment ID.
             * @param WP_Post      $post         The post object associated with the thumbnail.
             */
            $size = apply_filters( 'admin_post_thumbnail_size', $size, $thumbnail_id, $post );

            $thumbnail_html = wp_get_attachment_image( $thumbnail_id, $size );

            if ( ! empty( $thumbnail_html ) ) {
                $thumb_ok = true;

                $set_thumbnail_link = '<p class="hide-if-no-js"><div class="field"><a href="%s" id="set-post-thumbnail"%s class="thickbox">%s</a></div></p>';
                $content = '<div class="ui setment">';
                $content .= sprintf( $set_thumbnail_link,
                    esc_url( $upload_iframe_src ),
                    ' aria-describedby="set-post-thumbnail-desc"',
                    $thumbnail_html
                );
                $content .= '<p class="hide-if-no-js howto" id="set-post-thumbnail-desc">' . __( 'Click the image to edit or update' ) . '</p>';
                $content .= '<p class="hide-if-no-js"><a href="#" id="remove-post-thumbnail">' . esc_html( $post_type_object->labels->remove_featured_image ) . '</a></p>';
                $content .= '</div>';
            }
        }

        if ( !$thumb_ok ) {
            $set_thumbnail_link = '<p class="hide-if-no-js"><div class="field"><a href="%s" class="ui button fluid mini" id="set-post-thumbnail"%s class="thickbox">%s</a></div></p>';
            $content = sprintf( $set_thumbnail_link,
                esc_url( $upload_iframe_src ),
                '', // Empty when there's no featured image set, `aria-describedby` attribute otherwise.
                esc_html( $post_type_object->labels->set_featured_image )
            );
        }

        $content .= '<input type="hidden" id="_thumbnail_id" name="_thumbnail_id" value="' . esc_attr( $thumbnail_id ? $thumbnail_id : '-1' ) . '" />';


        $html = '<div class="ui header">' . __( 'Featured Image', 'mainwp' ) . '</div>';
        $html .= $content;

        /**
         * Filters the admin post thumbnail HTML markup to return.
         *
         * @since 2.9.0
         * @since 3.5.0 Added the `$post_id` parameter.
         * @since 4.6.0 Added the `$thumbnail_id` parameter.
         *
         * @param string $content      Admin post thumbnail HTML markup.
         * @param int    $post_id      Post ID.
         * @param int    $thumbnail_id Thumbnail ID.
         */
        return apply_filters( 'mainwp_admin_post_thumbnail_html', $html , $post->ID, $thumbnail_id );
    }

    static function post_thumbnail_meta_box( $post ) {
        $thumbnail_id = get_post_meta( $post->ID, '_thumbnail_id', true );
        echo self::_wp_post_thumbnail_html( $thumbnail_id, $post->ID );
    }

    // invisible fields
    static function touch_time( $post, $edit = 1, $for_post = 1, $tab_index = 0, $multi = 0 ) {
            global $wp_locale;

            $post = get_post( $post );

            if ( $for_post )
                $edit = ! ( in_array($post->post_status, array('draft', 'pending') ) && (!$post->post_date_gmt || '0000-00-00 00:00:00' == $post->post_date_gmt ) );

            $tab_index_attribute = '';
            if ( (int) $tab_index > 0 )
                $tab_index_attribute = " tabindex=\"$tab_index\"";

            // todo: Remove this?
            // echo '<label for="timestamp" style="display: block;"><input type="checkbox" class="checkbox" name="edit_date" value="1" id="timestamp"'.$tab_index_attribute.' /> '.__( 'Edit timestamp' ).'</label><br />';

            $time_adj = current_time('timestamp');
            $post_date = ($for_post) ? $post->post_date : get_comment()->comment_date;
            $jj = ($edit) ? mysql2date( 'd', $post_date, false ) : gmdate( 'd', $time_adj );
            $mm = ($edit) ? mysql2date( 'm', $post_date, false ) : gmdate( 'm', $time_adj );
            $aa = ($edit) ? mysql2date( 'Y', $post_date, false ) : gmdate( 'Y', $time_adj );
            $hh = ($edit) ? mysql2date( 'H', $post_date, false ) : gmdate( 'H', $time_adj );
            $mn = ($edit) ? mysql2date( 'i', $post_date, false ) : gmdate( 'i', $time_adj );
            $ss = ($edit) ? mysql2date( 's', $post_date, false ) : gmdate( 's', $time_adj );

            $cur_jj = gmdate( 'd', $time_adj );
            $cur_mm = gmdate( 'm', $time_adj );
            $cur_aa = gmdate( 'Y', $time_adj );
            $cur_hh = gmdate( 'H', $time_adj );
            $cur_mn = gmdate( 'i', $time_adj );

            $month = '<label><span class="screen-reader-text">' . __( 'Month' ) . '</span><select ' . ( $multi ? '' : 'id="mm" ' ) . 'name="mm"' . $tab_index_attribute . ">\n";
            for ( $i = 1; $i < 13; $i = $i +1 ) {
                $monthnum = zeroise($i, 2);
                $monthtext = $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) );
                $month .= "\t\t\t" . '<option value="' . $monthnum . '" data-text="' . $monthtext . '" ' . selected( $monthnum, $mm, false ) . '>';
                /* translators: 1: month number (01, 02, etc.), 2: month abbreviation */
                $month .= sprintf( __( '%1$s-%2$s' ), $monthnum, $monthtext ) . "</option>\n";
            }
            $month .= '</select></label>';

            $day = '<label><span class="screen-reader-text">' . __( 'Day' ) . '</span><input type="text" ' . ( $multi ? '' : 'id="jj" ' ) . 'name="jj" value="' . $jj . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" /></label>';
            $year = '<label><span class="screen-reader-text">' . __( 'Year' ) . '</span><input type="text" ' . ( $multi ? '' : 'id="aa" ' ) . 'name="aa" value="' . $aa . '" size="4" maxlength="4"' . $tab_index_attribute . ' autocomplete="off" /></label>';
            $hour = '<label><span class="screen-reader-text">' . __( 'Hour' ) . '</span><input type="text" ' . ( $multi ? '' : 'id="hh" ' ) . 'name="hh" value="' . $hh . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" /></label>';
            $minute = '<label><span class="screen-reader-text">' . __( 'Minute' ) . '</span><input type="text" ' . ( $multi ? '' : 'id="mn" ' ) . 'name="mn" value="' . $mn . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" /></label>';

            echo '<div class="timestamp-wrap">';
            /* translators: 1: month, 2: day, 3: year, 4: hour, 5: minute */
            printf( __( '%1$s %2$s, %3$s @ %4$s:%5$s' ), $month, $day, $year, $hour, $minute );

            echo '</div><input type="hidden" id="ss" name="ss" value="' . $ss . '" />';

            if ( $multi ) return;

            echo "\n\n";
            $map = array(
                'mm' => array( $mm, $cur_mm ),
                'jj' => array( $jj, $cur_jj ),
                'aa' => array( $aa, $cur_aa ),
                'hh' => array( $hh, $cur_hh ),
                'mn' => array( $mn, $cur_mn ),
            );
            foreach ( $map as $timeunit => $value ) {
                list( $unit, $curr ) = $value;

                echo '<input type="hidden" id="hidden_' . $timeunit . '" name="hidden_' . $timeunit . '" value="' . $unit . '" />' . "\n";
                $cur_timeunit = 'cur_' . $timeunit;
                echo '<input type="hidden" id="' . $cur_timeunit . '" name="' . $cur_timeunit . '" value="' . $curr . '" />' . "\n";
            }
    }

    static function do_meta_boxes( $screen, $context, $object ) {
            global $wp_meta_boxes;
            static $already_sorted = false;

            if ( empty( $screen ) )
                $screen = get_current_screen();
            elseif ( is_string( $screen ) )
                $screen = convert_to_screen( $screen );

            $page = $screen->id;

            // to show metaboxes to bulkpost/bulkpage pages
            if ($page == 'mainwp_page_PostBulkAdd' || $page == 'mainwp_page_PostBulkEdit') {
                $page = 'bulkpost';
            } else if ($page == 'mainwp_page_PageBulkAdd' || $page == 'mainwp_page_PageBulkEdit') {
                $page = 'bulkpage';
            }

            $hidden = get_hidden_meta_boxes( $screen );

            printf( '<div id="%s-sortables" class="meta-box-sortables">', esc_attr( $context ) );

            // Grab the ones the user has manually sorted. Pull them out of their previous context/priority and into the one the user chose
            if ( ! $already_sorted && $sorted = get_user_option( "meta-box-order_$page" ) ) {
                foreach ( $sorted as $widget_context => $ids ) {
                    foreach ( explode( ',', $ids ) as $id ) {
                        if ( $id && 'dashboard_browser_nag' !== $id ) {
                            add_meta_box( $id, null, null, $screen, $widget_context, 'sorted' );
                        }
                    }
                }
            }

            $already_sorted = true;

            $i = 0;

            if ( isset( $wp_meta_boxes[ $page ][ $context ] ) ) {
                foreach ( array( 'high', 'sorted', 'core', 'default', 'low' ) as $priority ) {
                    if ( isset( $wp_meta_boxes[ $page ][ $context ][ $priority ]) ) {
                        foreach ( (array) $wp_meta_boxes[ $page ][ $context ][ $priority ] as $box ) {
                            if ( false == $box || ! $box['title'] )
                                continue;

                            $block_compatible = true;
                            if ( is_array( $box[ 'args' ] ) ) {
                                // If a meta box is just here for back compat, don't show it in the block editor.
                                if ( $screen->is_block_editor() && isset( $box['args']['__back_compat_meta_box'] ) && $box['args']['__back_compat_meta_box'] ) {
                                    continue;
                                }

                                if ( isset( $box['args']['__block_editor_compatible_meta_box'] ) ) {
                                    $block_compatible = (bool) $box['args']['__block_editor_compatible_meta_box'];
                                    unset( $box['args']['__block_editor_compatible_meta_box'] );
                                }

                                // If the meta box is declared as incompatible with the block editor, override the callback function.
                                if ( ! $block_compatible && $screen->is_block_editor() ) {
                                    $box['old_callback'] = $box['callback'];
                                    $box['callback']     = 'do_block_editor_incompatible_meta_box';
                                }

                                if ( isset( $box['args']['__back_compat_meta_box'] ) ) {
                                    $block_compatible = $block_compatible || (bool) $box['args']['__back_compat_meta_box'];
                                    unset( $box['args']['__back_compat_meta_box'] );
                                }
                            }

                            $i++;
                            // get_hidden_meta_boxes() doesn't apply in the block editor.
                            $hidden_class = ''; //( ! $screen->is_block_editor() && in_array( $box['id'], $hidden ) ) ? ' hide-if-js' : '';
                            //' . postbox_classes($box['id'], $page) . $hidden_class . '
                            echo '<div id="' . $box['id'] . '" class="postbox " ' . ' ' . '>' . "\n";
                            if ( 'dashboard_browser_nag' != $box['id'] ) {
                                $widget_title = $box[ 'title' ];

                                if ( is_array( $box[ 'args' ] ) && isset( $box[ 'args' ][ '__widget_basename' ] ) ) {
                                    $widget_title = $box[ 'args' ][ '__widget_basename' ];
                                    // Do not pass this parameter to the user callback function.
                                    unset( $box[ 'args' ][ '__widget_basename' ] );
                                }

                                echo '<button type="button" class="handlediv" aria-expanded="true">';
                                echo '<span class="screen-reader-text">' . sprintf( __( 'Toggle panel: %s' ), $widget_title ) . '</span>';
                                echo '<span class="toggle-indicator" aria-hidden="true"></span>';
                                echo '</button>';
                            }
                            echo "<h2 class='hndle'><span>{$box['title']}</span></h2>\n";
                            echo '<div class="inside">' . "\n";

                            if ( WP_DEBUG && ! $block_compatible && 'edit' === $screen->parent_base && ! $screen->is_block_editor() && ! isset( $_GET['meta-box-loader'] ) ) {
                                $plugin = _get_plugin_from_callback( $box['callback'] );
                                if ( $plugin ) {
                                ?>
                                    <div class="error inline">
                                        <p>
                                            <?php
                                                /* translators: %s: the name of the plugin that generated this meta box. */
                                                printf( __( "This meta box, from the %s plugin, isn't compatible with the block editor." ), "<strong>{$plugin['Name']}</strong>" );
                                            ?>
                                        </p>
                                    </div>
                                <?php
                                }
                            }

                            call_user_func($box['callback'], $object, $box);
                            echo "</div>\n";
                            echo "</div>\n";
                        }
                    }
                }
            }

            echo "</div>";

            return $i;

        }

	public static function render_bulkpost( $post_id, $input_type) {
        global $post;
        $post = get_post( $post_id );

        if ( $post ) {
            $post_type = $post->post_type;
            $post_type_object = get_post_type_object( $post_type );
        }

        if ( ! $post_type_object || $input_type !== $post_type || ($post_type != 'bulkpost' && $post_type != 'bulkpage')) {
            echo __( 'Invalid post type.', 'mainwp' );
            return;
        }

        $post_ID = $post->ID;

        global $current_user;
        $user_ID = $current_user->ID;

        $_content_editor_dfw = $is_IE = false;
        $_wp_editor_expand = true;

        $form_action = 'mainwp_editpost'; // WP form action is: editpost, handle by admin_post_mainwp_editpost
        $nonce_action = 'update-post_' . $post_ID;

        $form_extra = "<input type='hidden' id='post_ID' name='post_ID' value='" . esc_attr($post_ID) . "' />";

        $referer = wp_get_referer();

        if ($post->post_status == 'auto-draft') {
            $note_title = ($post_type == 'bulkpost') ? __('Create New Bulkpost', 'mainwp') : __('Create New Bulkpage', 'mainwp');
        } else {
            $note_title = ($post_type == 'bulkpost') ? __('Edit Bulkpost', 'mainwp') : __('Edit Bulkpage', 'mainwp');
        }
        $message = '';
        if (isset($_GET['message']) && $_GET['message'] == 1) {
            if ($post_type == 'bulkpost') {
                $message = __( 'Post updated.', 'mainwp' );
            } else {
                $message = __( 'Page updated.', 'mainwp' );
            }
        }

		?>
		<div class="ui alt segment" id="mainwp-add-new-bulkpost">
			<form name="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" method="post" id="post" class="ui form">
                <?php wp_nonce_field($nonce_action); ?>
                <input type="hidden" id="user-id" name="user_ID" value="<?php echo (int) $user_ID ?>" />
                <input type="hidden" id="hiddenaction" name="action" value="<?php echo esc_attr( $form_action ) ?>" />
                <input type="hidden" id="originalaction" name="originalaction" value="<?php echo esc_attr( $form_action ) ?>" />
                <input type="hidden" id="post_author" name="post_author" value="<?php echo esc_attr( $post->post_author ); ?>" />
                <input type="hidden" id="post_type" name="post_type" value="<?php echo esc_attr( $post_type ) ?>" />
                <input type="hidden" id="original_post_status" name="original_post_status" value="<?php echo esc_attr( $post->post_status) ?>" />
                <input type="hidden" id="referredby" name="referredby" value="<?php echo $referer ? esc_url( $referer ) : ''; ?>" />
                <?php
               if ( 'draft' != get_post_status( $post ) )
                    wp_original_referer_field(true, 'previous');
                echo $form_extra;
                ?>
				<div class="mainwp-main-content">
					<div class="ui red message" id="mainwp-message-zone" style="display:none"></div>
                    <?php
                    if ($message) {
                        ?>
                            <div class="ui yellow message"><?php echo esc_html($message); ?></div>
                        <?php
                    }
                    ?>
					<h3 class="header"><?php echo esc_html($note_title); ?></h3>
					<div class="field">
						<label><?php echo __( 'Title', 'mainwp' ); ?></label>
				    <input type="text" name="post_title" id="title"  value="<?php echo ('Auto Draft' != $post->post_title) ? esc_attr( $post->post_title ) : ''; ?>" value="" autocomplete="off" spellcheck="true">
				  </div>
					<div class="field">
                        <div id="postdivrich" class="postarea<?php if ( $_wp_editor_expand ) { echo ' wp-editor-expand'; } ?>">
                            <?php
                            remove_editor_styles(); // stop custom theme styling interfering with the editor
                            wp_editor(
                                $post->post_content,
                                'content',
                                array(
                                    '_content_editor_dfw' => $_content_editor_dfw,
                                    'drag_drop_upload'    => true,
                                    'tabfocus_elements'   => 'content-html,save-post',
                                    'editor_height'       => 300,
                                    'tinymce'             => array(
                                        'resize'                  => false,
                                        'wp_autoresize_on'        => $_wp_editor_expand,
                                        'add_unload_trigger'      => false,
                                        'wp_keep_scroll_position' => ! $is_IE,
                                    ),
                                )
                            );

                            ?>

                            <table id="post-status-info"><tbody><tr>
                                <td id="wp-word-count" class="hide-if-no-js"><?php printf( __( 'Word count: %s' ), '<span class="word-count">0</span>' ); ?></td>
                                <td class="autosave-info">
                                <span class="autosave-message">&nbsp;</span>
                            <?php
                                if ( 'auto-draft' != $post->post_status ) {
                                    echo '<span id="last-edit">';
                                    if ( $last_user = get_userdata( get_post_meta( $post_ID, '_edit_last', true ) ) ) {
                                        /* translators: 1: Name of most recent post author, 2: Post edited date, 3: Post edited time */
                                        printf( __( 'Last edited by %1$s on %2$s at %3$s' ), esc_html( $last_user->display_name ), mysql2date( __( 'F j, Y' ), $post->post_modified ), mysql2date( __( 'g:i a' ), $post->post_modified ) );
                                    } else {
                                        /* translators: 1: Post edited date, 2: Post edited time */
                                        printf( __( 'Last edited on %1$s at %2$s' ), mysql2date( __( 'F j, Y' ), $post->post_modified ), mysql2date( __( 'g:i a' ), $post->post_modified ) );
                                    }
                                    echo '</span>';
                                } ?>
                                </td>
                                <td id="content-resize-handle" class="hide-if-no-js"><br /></td>
                            </tr></tbody></table>
                        </div>
					</div>
					<div class="field" id="add-slug-div">
						<label><?php echo __( 'Slug', 'mainwp' ); ?></label>
                        <?php MainWP_System::Instance()->metaboxes->add_slug( $post ); ?>
				  </div>
                <?php if ($post_type == 'bulkpost') { ?>
					<div class="field">
						<label><?php echo __( 'Excerpt', 'mainwp' ); ?></label>
                            <textarea rows="1" name="excerpt" id="excerpt"></textarea>
						<em><?php echo __( 'Excerpts are optional hand-crafted summaries of your content that can be used in your theme.', 'mainwp' ); ?></em>
                    </div>
					<div class="field">
						<label><?php echo __( 'Tags', 'mainwp' ); ?></label>
						<?php MainWP_System::Instance()->metaboxes->add_tags( $post ); ?>
						<em><?php echo __( 'Separate tags with commas', 'mainwp' ); ?></em>
				  </div>
                    <?php } ?>
                    <div class="field">
                <?php self::post_custom_meta_box($post); ?>
                  </div>

                <div class="field postbox-container">
                <?php

                do_action('mainwp_bulkpost_edit', $post, $post_type );

                self::do_meta_boxes(null, 'normal', $post);

                self::do_meta_boxes(null, 'advanced', $post);

                do_action( 'add_meta_boxes', $post_type, $post ); // to init metaboxes, for example WP SEO metabox
                self::do_meta_boxes( $post_type, 'normal', $post ); // to display metaboxes

                ?>
                </div>
            </div>
                <?php
                $sel_sites = $sel_groups = array();
                ?>
				<div class="mainwp-side-content mainwp-no-padding">
					<div class="mainwp-select-sites">
						<div class="ui header"><?php echo __( 'Select Sites', 'mainwp' ); ?></div>
						<?php MainWP_UI::select_sites_box( 'checkbox', true, true, '', '', $sel_sites, $sel_groups, false, $post_ID ); ?>
                        <input type="hidden" name="select_sites_nonce" id="select_sites_nonce" value="<?php echo wp_create_nonce( 'select_sites_' . $post->ID ); ?>" />
					</div>
					<div class="ui divider"></div>

        <?php if ( $post_type == 'bulkpost' )  { ?>
					<div class="mainwp-search-options">
						<div class="ui header"><?php echo __( 'Select Categories', 'mainwp' ); ?></div>
                    <?php
                    $categories = array();
                    if ( $post ) {
                        $categories = base64_decode( get_post_meta( $post->ID, '_categories', true ) );
                        $categories = explode( ',', $categories );
                    }
	 			    if ( !is_array( $categories ) ) {
	 				    $categories = array();
	 			    }

	 			    $uncat = __( 'Uncategorized', 'mainwp' );
	 			    $post_only = false;
                    if ( $post ) {
	 				   $post_only = get_post_meta( $post->ID, '_post_to_only_existing_categories', true );
                    }
						?>
						<input type="hidden" name="post_category_nonce" id="post_category_nonce" value="<?php echo esc_attr( wp_create_nonce( 'post_category_' . $post->ID ) ); ?>" />
						<div class="field">
							<div class="ui checkbox">
							  <input type="checkbox" name="post_only_existing" id="post_only_existing" value="1" <?php echo $post_only ? 'checked' : ''; ?>>
							  <label><?php esc_html_e( 'Post only to existing categories', 'mainwp' ); ?></label>
							</div>
						</div>
						<div class="field">
							<select name="post_category[]" id="categorychecklist" multiple="" class="ui fluid dropdown">
								<option value=""><?php echo __( 'Select categories', 'mainwp' ); ?></option>
								<?php if ( !in_array( $uncat, $categories ) ) : ?>
								<option value="<?php esc_attr_e( 'Uncategorized', 'mainwp' ); ?>" class="sitecategory"><?php esc_html_e( 'Uncategorized', 'mainwp' ); ?></option>
								<?php endif; ?>
								<?php foreach ( $categories as $cat ) : ?>
									<?php
									if ( empty( $cat ) ) {
										continue;
									}
									$cat_name = rawurldecode( $cat );
									?>
                                <option value="<?php echo esc_attr( $cat ); ?>" class="sitecategory"><?php echo esc_html( $cat_name ); ?></option>
								<?php endforeach; ?>
							</select>
                            <?php
                            $init_cats = '';
                            foreach( $categories as $cat ) {
                                $init_cats .= "'" . esc_attr( $cat ) . "',";
                            }
                            $init_cats = rtrim( $init_cats, ',' );
                            ?>
                            <script type="text/javascript">
                                jQuery( document ).ready( function () {
                                    jQuery( '#categorychecklist' ).dropdown( 'set selected', [<?php echo $init_cats; ?>] );
                                } );
                            </script>
						</div>
						<div class="field">
							<a href="#" id="category-add-toggle" class="ui button fluid mini"><?php echo esc_html( 'Create New Category', 'mainwp' ); ?></a>
						</div>
						<div class="field" id="newcategory-field" style="display:none">
							<input type="text" name="newcategory" id="newcategory" value="">
						</div>
						<div class="field" id="mainwp-category-add-submit-field" style="display:none">
							<input type="button" id="mainwp-category-add-submit" class="ui fluid basic green mini button" value="<?php esc_attr_e( 'Add New Category', 'mainwp' ); ?>">
						</div>
					</div>
					<div class="ui divider"></div>
        <?php } ?>
                    <div class="mainwp-search-options mainwp-post-featured-image" id="postimagediv">
                        <?php echo '<div class="inside">'; // must have to compatible with js ?>
                    <?php self::post_thumbnail_meta_box( $post ); ?>
                        <?php echo '</div>'; ?>
                    </div>
					<div class="ui divider"></div>
					<div class="mainwp-search-options">
						<div class="ui header"><?php echo __( 'Discussion', 'mainwp' ); ?></div>
						<div class="field">
							<div class="ui checkbox">
							  <input type="checkbox" name="comment_status" id="comment_status" value="open" <?php checked($post->comment_status, 'open'); ?>>
							  <label><?php esc_html_e( 'Allow comments', 'mainwp' ); ?></label>
							</div>
							<div class="ui checkbox">
							  <input type="checkbox" name="ping_status" id="ping_status" value="open" <?php checked($post->ping_status, 'open'); ?> >
							  <label><?php esc_html_e( 'Allow trackbacks and pingbacks', 'mainwp' ); ?></label>
							</div>
						</div>
					</div>
					<div class="ui divider"></div>
					<div class="mainwp-search-options">
						<div class="ui header"><?php echo __( 'Publish Options', 'mainwp' ); ?></div>
						<div class="field">
							<label><?php echo esc_html( 'Status', 'mainwp' ); ?></label>
							<select class="ui dropdown" name="mainwp_edit_post_status" id="post_status"> <?php  // to fix: saving pending status ?>
								<option value="draft" <?php  echo ($post->post_status == 'draft' || $post->post_status == 'publish' ) ? 'selected="selected"' : ''; ?>><?php echo esc_html( 'Draft', 'mainwp' ); ?></option>
								<option value="pending" <?php  echo ($post->post_status == 'pending') ? 'selected="selected"' : ''; ?>><?php echo esc_html( 'Pending review', 'mainwp' ); ?></option>
							</select>
						</div>

                         <?php
                            if ( 'private' == $post->post_status ) {
                                $post->post_password = '';
                                $visibility = 'private';
                                $visibility_trans = __('Private');
                            } elseif ( !empty( $post->post_password ) ) {
                                $visibility = 'password';
                                $visibility_trans = __('Password protected');
                            } elseif ( $post_type == 'post' && is_sticky( $post->ID ) ) {
                                $visibility = 'public';
                                $visibility_trans = __('Public, Sticky');
                            } else {
                                $visibility = 'public';
                                $visibility_trans = __('Public');
                            }?>

						<div class="grouped fields">
							<label><?php echo esc_html( 'Visibility', 'mainwp' ); ?></label>
							<div class="field">
								<div class="ui radio checkbox">
									<input type="radio" name="visibility" value="public" id="visibility-radio-public" <?php echo ($visibility == 'public') ? 'checked="checked"' : ''; ?>>
									<label><?php echo esc_html( 'Public', 'mainwp' ); ?></label>
								</div>
							</div>
							<div class="field" id="sticky-field">
								<div class="ui checkbox">
									<input type="checkbox" id="sticky" name="sticky" value="sticky"  <?php checked( is_sticky( $post->ID ) ); ?>  />
									<label><?php echo esc_html( 'Stick this post to the front page', 'mainwp' ); ?></label>
								</div>
							</div>
							<div class="field">
								<div class="ui radio checkbox">
									<input type="radio" name="visibility" value="password" id="visibility-radio-password" <?php echo ($visibility == 'password') ? 'checked="checked"' : ''; ?>>
									<label><?php echo esc_html( 'Password protected', 'mainwp' ); ?></label>
								</div>
							</div>
							<div class="field" id="post_password-field" <?php echo ($visibility == 'password') ? '' : 'style="display:none"'; ?>>
								<label><?php echo esc_html( 'Password', 'mainwp' ); ?></label>
								<input type="text" name="post_password" id="post_password" value="<?php echo esc_attr($post->post_password); ?>" />
							</div>
							<div class="field">
								<div class="ui radio checkbox">
									<input type="radio" name="visibility" value="private" id="visibility-radio-private" <?php echo ($visibility == 'private') ? 'checked="checked"' : ''; ?>>
									<label><?php echo esc_html( 'Private', 'mainwp' ); ?></label>
								</div>
							</div>
						</div>
						<div class="field">
							<label><?php echo esc_html( 'Publish', 'mainwp' ); ?></label>
							<select class="ui dropdown" name="post_timestamp" id="post_timestamp">
								<option value="immediately" selected="selected"><?php echo esc_html( 'Immediately', 'mainwp' ); ?></option>
								<option value="schedule"><?php echo esc_html( 'Schedule', 'mainwp' ); ?></option>
							</select>
						</div>

                        <div class="field" id="post_timestamp_value-field" style="display:none">
                            <div class="ui calendar mainwp_datepicker" id="schedule_post_datetime" >
                              <div class="ui input left icon">
                                <i class="calendar icon"></i>
                                <input type="text" placeholder="<?php esc_attr_e( 'Date', 'mainwp' ); ?>" id="post_timestamp_value" value="" />
                              </div>
                            </div>
                        </div>
                        <div style="display:none" id="timestampdiv">
                            <?php self::touch_time($post); ?>
                        </div>
					</div>
                    <?php
                    self::do_meta_boxes( $post_type, 'side', $post );
                    ?>
					<div class="ui divider"></div>
					<?php do_action('mainwp_edit_posts_before_submit_button'); ?>
					<div class="mainwp-search-submit" id="bulkpost-publishing-action">
						<input type="submit" name="publish" id="publish" class="ui big green fluid button" value="<?php esc_attr_e( 'Publish', 'mainwp' ); ?>">
					</div>
                    <?php do_action('mainwp_edit_posts_after_submit_button'); ?>
				</div>
			</form>
			<script type="text/javascript">
			jQuery( document ).ready( function () {
				jQuery( '#publish' ).on( 'click', function() {
					jQuery( '#mainwp-publish-dimmer' ).show();
				} );
			} );
			</script>
			<div class="ui active inverted dimmer" id="mainwp-publish-dimmer" style="display:none">
		    <div class="ui text loader"><?php esc_html_e( 'Publishing...', 'mainwp' ); ?></div>
		  </div>
			<div class="ui clearing hidden divider"></div>
		</div>
		<?php
		self::renderFooter( 'BulkAdd' );
	}

    public static function renderBulkAdd() {
		if ( !mainwp_current_user_can( 'dashboard', 'manage_posts' ) ) {
			mainwp_do_not_have_permissions( __( 'manage posts', 'mainwp' ) );
			return;
		}

        global $_mainwp_default_post_to_edit;
        $post_id = $_mainwp_default_post_to_edit ? $_mainwp_default_post_to_edit->ID : 0;
        self::render_addedit($post_id, 'BulkAdd');
    }

	public static function renderBulkEdit() {
		if ( !mainwp_current_user_can( 'dashboard', 'manage_posts' ) ) {
			mainwp_do_not_have_permissions( __( 'manage posts', 'mainwp' ) );
			return;
		}
        $post_id = isset($_GET['post_id']) ?  intval ($_GET['post_id']) : 0;
        self::render_addedit($post_id, 'BulkEdit');

	}

    public static function render_addedit($post_id, $what) {
        self::renderHeader( $what, $post_id);
        self::render_bulkpost( $post_id, 'bulkpost' );
        self::renderFooter( $what );
    }

	public static function hookPostsSearch_handler( $data, $website, &$output ) {
		$posts = array();
		if ( preg_match( '/<mainwp>(.*)<\/mainwp>/', $data, $results ) > 0 ) {
			$posts = unserialize( base64_decode( $results[ 1 ] ) );
			unset( $results );
		}
		$output->results[ $website->id ] = $posts;
	}

	public static function getCategories() {
		$websites = array();
		if ( isset( $_REQUEST[ 'sites' ] ) && ( $_REQUEST[ 'sites' ] != '' ) ) {
			$siteIds			 = explode( ',', urldecode( $_REQUEST[ 'sites' ] ) );
			$siteIdsRequested	 = array();
			foreach ( $siteIds as $siteId ) {
				$siteId = $siteId;
				if ( !MainWP_Utility::ctype_digit( $siteId ) ) {
					continue;
				}
				$siteIdsRequested[] = $siteId;
			}

			$websites = MainWP_DB::Instance()->getWebsitesByIds( $siteIdsRequested );
		} else if ( isset( $_REQUEST[ 'groups' ] ) && ( $_REQUEST[ 'groups' ] != '' ) ) {
			$groupIds			 = explode( ',', urldecode( $_REQUEST[ 'groups' ] ) );
			$groupIdsRequested	 = array();
			foreach ( $groupIds as $groupId ) {
				$groupId = $groupId;

				if ( !MainWP_Utility::ctype_digit( $groupId ) ) {
					continue;
				}
				$groupIdsRequested[] = $groupId;
			}

			$websites = MainWP_DB::Instance()->getWebsitesByGroupIds( $groupIdsRequested );
		}

		$selectedCategories	 = array();
		$selectedCategories2 = array();

		if ( isset( $_REQUEST[ 'selected_categories' ] ) && ( $_REQUEST[ 'selected_categories' ] != '' ) ) {
			$selectedCategories = explode( ',', urldecode( $_REQUEST[ 'selected_categories' ] ) );
		}

//		if ( isset( $_REQUEST[ 'post_id' ] ) ) {
//			$post_id = (int) $_REQUEST[ 'post_id' ];
//			if ( current_user_can( 'edit_post', $post_id ) ) {
//				$selectedCategories2 = get_post_meta( $post_id, '_categories', true );
//			}
//		}

		if ( !is_array( $selectedCategories ) )
			$selectedCategories	 = array();

//		if ( !is_array( $selectedCategories2 ) )
//			$selectedCategories2 = array();

		$allCategories = array( 'Uncategorized' );
		if ( count( $websites ) > 0 ) {
			foreach ( $websites as $website ) {
				$cats = json_decode( $website->categories, true );
				if ( is_array( $cats ) && ( count( $cats ) > 0 ) ) {
					$allCategories = array_unique( array_merge( $allCategories, $cats ) );
				}
			}
		}
        // to fixed issue missing selected categories
        $allCategories = array_unique( array_merge( $allCategories, $selectedCategories ) );

		if ( count( $allCategories ) > 0 ) {
			natcasesort( $allCategories );
			foreach ( $allCategories as $category ) {
                //echo '<li class="popular-category sitecategory"><label class="selectit"><input value="' . $category . '" type="checkbox" name="post_category[]" ' . ( in_array( $category, $selectedCategories ) || in_array( $category, $selectedCategories2 ) ? 'checked' : '' ) . '> ' . $category . '</label></li>';
				echo '<option value="' . esc_html( $category ) . '" class="sitecategory">' . esc_html( $category ) . '</option>';
			}
		}
		die();
	}

	public static function posting() {
		$succes_message = '';
		if ( isset( $_GET[ 'id' ] ) ) {
			$edit_id = get_post_meta( $_GET[ 'id' ], '_mainwp_edit_post_id', true );
			if ( $edit_id ) {
				$succes_message = __( 'Post has been updated successfully', 'mainwp' );
			} else {
				$succes_message = __( 'New post created', 'mainwp' );
			}
		}

		//Posts the saved sites
		?>
		<div class="ui modal" id="mainwp-posting-post-modal">
      <div class="header"><?php $edit_id ? _e( 'Edit Post', 'mainwp' ) : _e( 'New Post', 'mainwp' ) ?></div>
      <div class="scrolling content">
				<?php
				do_action( 'mainwp_bulkpost_before_post', $_GET[ 'id' ] );

				$skip_post = false;
				if ( isset( $_GET[ 'id' ] ) ) {
					if ( 'yes' == get_post_meta( $_GET[ 'id' ], '_mainwp_skip_posting', true ) ) {
						$skip_post = true;
						wp_delete_post( $_GET[ 'id' ], true );
					}
				}

			if ( !$skip_post ) {
				if ( isset( $_GET[ 'id' ] ) ) {
					$id		 = intval($_GET[ 'id' ]);
					$post	 = get_post( $id );
					if ( $post ) {
						//                die('<pre>'.print_r($post, 1).'</pre>');
						$selected_by	 = get_post_meta( $id, '_selected_by', true );
						$selected_sites	 = unserialize( base64_decode( get_post_meta( $id, '_selected_sites', true ) ) );
						$selected_groups = unserialize( base64_decode( get_post_meta( $id, '_selected_groups', true ) ) );

						/** @deprecated */
						$post_category = base64_decode( get_post_meta( $id, '_categories', true ) );

						$post_tags	 = base64_decode( get_post_meta( $id, '_tags', true ) );
						$post_slug	 = base64_decode( get_post_meta( $id, '_slug', true ) );
						$post_custom = get_post_custom( $id );
						//                if (isset($post_custom['_tags'])) $post_custom['_tags'] = base64_decode(trim($post_custom['_tags']));

						$galleries			 = get_post_gallery( $id, false );
						$post_gallery_images = array();

						if ( is_array( $galleries ) && isset( $galleries[ 'ids' ] ) ) {
							$attached_images = explode( ',', $galleries[ 'ids' ] );
							foreach ( $attached_images as $attachment_id ) {
								$attachment = get_post( $attachment_id );
								if ( $attachment ) {
									$post_gallery_images[] = array(
										'id'			 => $attachment_id,
										'alt'			 => get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ),
										'caption'		 => $attachment->post_excerpt,
										'description'	 => $attachment->post_content,
										'src'			 => $attachment->guid,
										'title'			 => $attachment->post_title
									);
								}
							}
						}

						include_once( ABSPATH . 'wp-includes' . DIRECTORY_SEPARATOR . 'post-thumbnail-template.php' );
						$featured_image_id	 = get_post_thumbnail_id( $id );
						$post_featured_image = null;
						$featured_image_data = null;
						$mainwp_upload_dir	 = wp_upload_dir();

                        $post_status		 = get_post_meta( $id, '_edit_post_status', true );
                        // to support saving as pending
                        if ($post_status != 'pending') {
                            $post_status = $post->post_status;
                        }
                        $post_status = apply_filters('mainwp_posting_bulkpost_post_status', $post_status, $id ); // to support post plus extension
						$new_post			 = array(
							'post_title'	 => $post->post_title,
							'post_content'	 => $post->post_content,
							'post_status'	 => $post_status,
							'post_date'		 => $post->post_date,
							'post_date_gmt'	 => $post->post_date_gmt,
							'post_tags'		 => $post_tags,
							'post_name'		 => $post_slug,
							'post_excerpt'	 => $post->post_excerpt,
							'comment_status' => $post->comment_status,
							'ping_status'	 => $post->ping_status,
							'mainwp_post_id'		 => $post->ID,
						);

						if ( $featured_image_id != null ) { //Featured image is set, retrieve URL
							$img				 = wp_get_attachment_image_src( $featured_image_id, 'full' );
							$post_featured_image = $img[ 0 ];
							$attachment			 = get_post( $featured_image_id );
							$featured_image_data = array(
								'alt'			 => get_post_meta( $featured_image_id, '_wp_attachment_image_alt', true ),
								'caption'		 => $attachment->post_excerpt,
								'description'	 => $attachment->post_content,
								'title'			 => $attachment->post_title
							);
						}

						$dbwebsites = array();
						if ( $selected_by == 'site' ) { //Get all selected websites
							foreach ( $selected_sites as $k ) {
								if ( MainWP_Utility::ctype_digit( $k ) ) {
									$website					 = MainWP_DB::Instance()->getWebsiteById( $k );
									$dbwebsites[ $website->id ]	 = MainWP_Utility::mapSite( $website, array(
										'id',
										'url',
										'name',
										'adminname',
										'nossl',
										'privkey',
										'nosslkey',
                                        'http_user',
                                        'http_pass'
									) );
								}
							}
						} else { //Get all websites from the selected groups
							foreach ( $selected_groups as $k ) {
								if ( MainWP_Utility::ctype_digit( $k ) ) {
									$websites	 = MainWP_DB::Instance()->query( MainWP_DB::Instance()->getSQLWebsitesByGroupId( $k ) );
									while ( $websites && ( $website	 = @MainWP_DB::fetch_object( $websites ) ) ) {
										if ( $website->sync_errors != '' ) {
											continue;
										}
										$dbwebsites[ $website->id ] = MainWP_Utility::mapSite( $website, array(
											'id',
											'url',
											'name',
											'adminname',
											'nossl',
											'privkey',
											'nosslkey',
                                            'http_user',
                                            'http_pass'
										) );
									}
									@MainWP_DB::free_result( $websites );
								}
							}
						}

						$output			 = new stdClass();
						$output->ok		 = array();
						$output->errors	 = array();
						$startTime      = time();

						if ( count( $dbwebsites ) > 0 ) {
							$post_data = array(
								'new_post'				 => base64_encode( serialize( $new_post ) ),
								'post_custom'			 => base64_encode( serialize( $post_custom ) ),
								'post_category'			 => base64_encode( $post_category ),
								'post_featured_image'	 => base64_encode( $post_featured_image ),
								'post_gallery_images'	 => base64_encode( serialize( $post_gallery_images ) ),
								'mainwp_upload_dir'		 => base64_encode( serialize( $mainwp_upload_dir ) ),
								'featured_image_data'	 => base64_encode( serialize( $featured_image_data ) ),
							);
							MainWP_Utility::fetchUrlsAuthed( $dbwebsites, 'newpost', $post_data, array(
								MainWP_Bulk_Add::getClassName(),
								'PostingBulk_handler',
							), $output );
						}

						$failed_posts = array();
						foreach ( $dbwebsites as $website ) {
							if ( isset($output->ok[ $website->id ]) && ( $output->ok[ $website->id ] == 1 ) && ( isset( $output->added_id[ $website->id ] ) ) ) {
								do_action( 'mainwp-post-posting-post', $website, $output->added_id[ $website->id ], ( isset( $output->link[ $website->id ] ) ? $output->link[ $website->id ] : null ) ); // deprecated from 4.0
								do_action( 'mainwp-bulkposting-done', $post, $website, $output );
							} else {
								$failed_posts[] = $website->id;
							}
						}

                        // to support extensions, for example: boilerplace, post plus  ...
                        $after_posting = apply_filters('mainwp-after-posting-bulkpost-result', false, $post, $dbwebsites, $output );

//						$del_post	 = true;
//						$saved_draft = get_post_meta( $id, '_saved_as_draft', true ); // to support post plus extension
//						if ( $saved_draft == 'yes' ) {
//							if ( count( $failed_posts ) > 0 ) {
//								$del_post = false;
//								update_post_meta( $post->ID, '_selected_sites', base64_encode( serialize( $failed_posts ) ) );
//								update_post_meta( $post->ID, '_selected_groups', '' );
//								wp_update_post( array( 'ID' => $id, 'post_status' => 'draft' ) );
//							}
//						}

                        if ( $after_posting == false ) {
                            ?>
                            <div class="ui relaxed list">
                                <?php foreach ( $dbwebsites as $website ) {
                                    ?>
                                    <div class="item"><a href="<?php echo admin_url( 'admin.php?page=managesites&dashboard=' . $website->id ); ?>"><?php echo stripslashes( $website->name ); ?></a>
                                    : <?php echo( isset( $output->ok[ $website->id ] ) && $output->ok[ $website->id ] == 1 ? $succes_message . ' <a href="' . $output->link[ $website->id ] . '" class="mainwp-may-hide-referrer" target="_blank">View Post</a>' : $output->errors[ $website->id ] ); ?>
                                    </div>
                            <?php } ?>
                            </div>
                        <?php
                        }

                        $do_not_del = get_post_meta( $id, '_bulkpost_do_not_del', true );
						if ( $do_not_del !== 'yes') { // check $del_post to compatible
							wp_delete_post( $id, true );
						}
						
						
						$countSites = 0;
						$countRealItems = 0;
						foreach ( $dbwebsites as $website ) {
							if ( isset( $output->ok[ $website->id ] ) && $output->ok[ $website->id ] == 1 ) {
								$countSites++;
								$countRealItems++;
							}
						}

						if ( ! empty( $countSites ) ) {
							$seconds = ( time() - $startTime );
							MainWP_Twitter::updateTwitterInfo( 'new_post', $countSites, $seconds, $countRealItems, $startTime, 1 );
						}

						if ( MainWP_Twitter::enabledTwitterMessages() ) {
							$twitters = MainWP_Twitter::getTwitterNotice( 'new_post' );
							if ( is_array( $twitters ) ) {
								foreach ( $twitters as $timeid => $twit_mess ) {
									if ( ! empty( $twit_mess ) ) {
										$sendText = MainWP_Twitter::getTwitToSend( 'new_post', $timeid );
										?>
										<div class="mainwp-tips ui info message twitter" style="margin:0">
											<i class="ui close icon mainwp-dismiss-twit"></i><span class="mainwp-tip" twit-what="new_post" twit-id="<?php echo $timeid; ?>"><?php echo $twit_mess; ?></span>&nbsp;<?php MainWP_Twitter::genTwitterButton( $sendText ); ?>
										</div>
										<?php
									}
								}
							}
						}

				} // if ($post)
			} else {
				?>
					<div class="error">
						<p>
							<strong><?php _e( 'ERROR', 'mainwp' ); ?></strong>: <?php _e( 'An undefined error occured!', 'mainwp' ); ?>
						</p>
					</div>
				<?php
			}
		} // no skip posting
		?>
        </div>
        <div class="actions">
            <a href="admin.php?page=PostBulkAdd" class="ui green button"><?php _e( 'New Post', 'mainwp' ); ?></a>
            <div class="ui cancel button"><?php _e( 'Close', 'mainwp' ); ?></div>
        </div>
    </div>
    <div class="ui active inverted dimmer" id="mainwp-posting-running">
      <div class="ui indeterminate large text loader"><?php _e( 'Running ...', 'mainwp' ); ?></div>
    </div>
        <script type="text/javascript">
            jQuery( document ).ready( function () {
                jQuery( "#mainwp-posting-running" ).hide();
                jQuery( "#mainwp-posting-post-modal" ).modal({
                    closable: true,
                    onHide: function() {
                        location.href = 'admin.php?page=PostBulkManage';
                    }
                }).modal( 'show' );
            });
        </script>
		<?php
	}

	public static function PostsGetTerms_handler( $data, $website, &$output ) {
		if ( preg_match( '/<mainwp>(.*)<\/mainwp>/', $data, $results ) > 0 ) {
			$result						 = $results[ 1 ];
			$cats						 = unserialize( base64_decode( $result ) );
			$output->cats[ $website->id ]	 = is_array( $cats ) ? $cats : array();
		} else {
			$output->errors[ $website->id ] = MainWP_Error_Helper::getErrorMessage( new MainWP_Exception( 'NOMAINWP', $website->url ) );
		}
	}

	public static function getTerms( $websiteid, $prefix = '', $what = 'site', $gen_type = 'post' ) {
		$output			 = new stdClass();
		$output->errors	 = array();
		$output->cats	 = array();
		$dbwebsites		 = array();
		if ( $what == 'group' ) {
			$input_name = 'groups_selected_cats_' . $prefix . '[]';
		} else {
			$input_name = 'sites_selected_cats_' . $prefix . '[]';
		}

		if ( !empty( $websiteid ) ) {
			if ( MainWP_Utility::ctype_digit( $websiteid ) ) {
				$website					 = MainWP_DB::Instance()->getWebsiteById( $websiteid );
				$dbwebsites[ $website->id ]	 = MainWP_Utility::mapSite( $website, array(
					'id',
					'url',
					'name',
					'adminname',
					'nossl',
					'privkey',
					'nosslkey',
                    'http_user',
                    'http_pass'
				) );
			}
		}

		if ( $gen_type == 'post' ) {
			$bkc_option_path = 'default_keywords_post';
			$keyword_option	 = 'keywords_page';
		} else if ( $gen_type == 'page' ) {
			$bkc_option_path = 'default_keywords_page';
			$keyword_option	 = 'keywords_page';
		}

		if ( $prefix == 'bulk' ) {
			$opt			 = apply_filters( 'mainwp-get-options', $value			 = '', 'mainwp_content_extension', 'bulk_keyword_cats', $bkc_option_path );
			$selected_cats	 = unserialize( base64_decode( $opt ) );
		} else { // is number 0,1,2, ...
			$opt	 = apply_filters( 'mainwp-get-options', $value	 = '', 'mainwp_content_extension', $keyword_option );
			if ( is_array( $opt ) && is_array( $opt[ $prefix ] ) ) {
				$selected_cats = unserialize( base64_decode( $opt[ $prefix ][ 'selected_cats' ] ) );
			}
		}
		$selected_cats	 = is_array( $selected_cats ) ? $selected_cats : array();
		$ret			 = '';
		if ( count( $dbwebsites ) > 0 ) {
			$opt		 = apply_filters( 'mainwp-get-options', $value		 = '', 'mainwp_content_extension', 'taxonomy' );
			$post_data	 = array(
				'taxonomy' => base64_encode( $opt ),
			);
			MainWP_Utility::fetchUrlsAuthed( $dbwebsites, 'get_terms', $post_data, array(
				MainWP_Post::getClassName(),
				'PostsGetTerms_handler',
			), $output );
			foreach ( $dbwebsites as $siteid => $website ) {
				$cats = array();
				if ( is_array( $selected_cats[ $siteid ] ) ) {
					foreach ( $selected_cats[ $siteid ] as $val ) {
						$cats[] = $val[ 'term_id' ];
					}
				}
				if ( !empty( $output->errors[ $siteid ] ) ) {
					$ret .= '<p> ' . __( 'Error - ', 'mainwp' ) . $output->errors[ $siteid ] . '</p>';
				} else {
					if ( count( $output->cats[ $siteid ] ) > 0 ) {
						foreach ( $output->cats[ $siteid ] as $cat ) {
							if ( $cat->term_id ) {
								if ( in_array( $cat->term_id, $cats ) ) {
									$checked = ' checked="checked" ';
								} else {
									$checked = '';
								}
								$ret .= '<div class="mainwp_selected_sites_item ' . (!empty( $checked ) ? 'selected_sites_item_checked' : '' ) . '"><input type="checkbox" name="' . $input_name . '" value="' . $siteid . ',' . $cat->term_id . ',' . stripslashes( $cat->name ) . '" ' . $checked . '/><label>' . $cat->name . '</label></div>';
							}
						}
					} else {
						$ret .= '<p>No categories have been found!</p>';
					}
				}
			}
		} else {
			$ret .= '<p>' . __( 'ERROR: ', 'mainwp' ) . ' no site</p>';
		}
		echo $ret;
	}

	public static function getPost() {
		$postId		 = $_POST[ 'postId' ];
		$postType	 = $_POST[ 'postType' ];
		$websiteId	 = $_POST[ 'websiteId' ];

		if ( !MainWP_Utility::ctype_digit( $postId ) ) {
			die( json_encode( array( 'error' => 'Invalid request!' ) ) );
		}
		if ( !MainWP_Utility::ctype_digit( $websiteId ) ) {
			die( json_encode( array( 'error' => 'Invalid request!' ) ) );
		}

		$website = MainWP_DB::Instance()->getWebsiteById( $websiteId );
		if ( !MainWP_Utility::can_edit_website( $website ) ) {
			die( json_encode( array( 'error' => 'You can not edit this website!' ) ) );
		}

		try {
			$information = MainWP_Utility::fetchUrlAuthed( $website, 'post_action', array(
				'action'	 => 'get_edit',
				'id'		 => $postId,
				'post_type'	 => $postType
			) );
		} catch ( MainWP_Exception $e ) {
			die( json_encode( array( 'error' => MainWP_Error_Helper::getErrorMessage( $e ) ) ) );
		}

		if ( is_array( $information ) && isset( $information[ 'error' ] ) ) {
			die( json_encode( array( 'error' => $information[ 'error' ] ) ) );
		}

		if ( !isset( $information[ 'status' ] ) || ( $information[ 'status' ] != 'SUCCESS' ) ) {
			die( json_encode( array( 'error' => 'Unexpected error.' ) ) );
		} else {
			$ret = MainWP_Post::newPost( $information[ 'my_post' ] );
			if ( is_array( $ret ) && isset( $ret[ 'id' ] ) ) {
				update_post_meta( $ret[ 'id' ], '_selected_sites', base64_encode( serialize( array( $websiteId ) ) ) );
				update_post_meta( $ret[ 'id' ], '_mainwp_edit_post_site_id', $websiteId );
			}
			//die( json_encode( $ret ) );
            wp_send_json( $ret );
		}
	}

	static function newPost( $post_data = array() ) {
		//Read form data
		$new_post			 = maybe_unserialize( base64_decode( $post_data[ 'new_post' ] ) );
		$post_custom		 = maybe_unserialize( base64_decode( $post_data[ 'post_custom' ] ) );
		$post_category		 = rawurldecode( isset( $post_data[ 'post_category' ] ) ? base64_decode( $post_data[ 'post_category' ] ) : null );
		$post_tags			 = rawurldecode( isset( $new_post[ 'post_tags' ] ) ? $new_post[ 'post_tags' ] : null );
		$post_featured_image = base64_decode( $post_data[ 'post_featured_image' ] );
		$post_gallery_images = base64_decode( $post_data[ 'post_gallery_images' ] );
		$upload_dir			 = maybe_unserialize( base64_decode( $post_data[ 'child_upload_dir' ] ) );
		return MainWP_Post::createPost( $new_post, $post_custom, $post_category, $post_featured_image, $upload_dir, $post_tags, $post_gallery_images ); // to edit
	}

	static function createPost( $new_post, $post_custom, $post_category, $post_featured_image, $upload_dir, $post_tags,
							 $post_gallery_images ) {
		global $current_user;

		if ( !isset( $new_post[ 'edit_id' ] ) )
			return array( 'error' => 'Empty post id' );

		$post_author			 = $current_user->ID;
		$new_post[ 'post_author' ] = $post_author;
		$new_post[ 'post_type' ]	 = isset( $new_post[ 'post_type' ] ) && ($new_post[ 'post_type' ] == 'page') ? 'bulkpage' : 'bulkpost';

		//Search for all the images added to the new post
		//some images have a href tag to click to navigate to the image.. we need to replace this too
		$foundMatches = preg_match_all( '/(<a[^>]+href=\"(.*?)\"[^>]*>)?(<img[^>\/]*src=\"((.*?)(png|gif|jpg|jpeg))\")/ix', $new_post[ 'post_content' ], $matches, PREG_SET_ORDER );
		if ( $foundMatches > 0 ) {
			//We found images, now to download them so we can start balbal
			foreach ( $matches as $match ) {
				$hrefLink	 = $match[ 2 ];
				$imgUrl		 = $match[ 4 ];

				if ( !isset( $upload_dir[ 'baseurl' ] ) || ( 0 !== strripos( $imgUrl, $upload_dir[ 'baseurl' ] ) ) ) {
					continue;
				}

				if ( preg_match( '/-\d{3}x\d{3}\.[a-zA-Z0-9]{3,4}$/', $imgUrl, $imgMatches ) ) {
					$search			 = $imgMatches[ 0 ];
					$replace		 = '.' . $match[ 6 ];
					$originalImgUrl	 = str_replace( $search, $replace, $imgUrl );
				} else {
					$originalImgUrl = $imgUrl;
				}

				try {
					$downloadfile	 = MainWP_Utility::uploadImage( $originalImgUrl );
					$localUrl		 = $downloadfile[ 'url' ];

					$linkToReplaceWith = dirname( $localUrl );
					if ( '' !== $hrefLink ) {
						$server		 = get_option( 'mainwp_child_server' );
						$serverHost	 = parse_url( $server, PHP_URL_HOST );
						if ( !empty( $serverHost ) && strpos( $hrefLink, $serverHost ) !== false ) {
							$serverHref					 = 'href="' . $serverHost;
							$replaceServerHref			 = 'href="' . parse_url( $localUrl, PHP_URL_SCHEME ) . '://' . parse_url( $localUrl, PHP_URL_HOST );
							$new_post[ 'post_content' ]	 = str_replace( $serverHref, $replaceServerHref, $new_post[ 'post_content' ] );
						}
					}
					$lnkToReplace = dirname( $imgUrl );
					if ( 'http:' !== $lnkToReplace && 'https:' !== $lnkToReplace ) {
						$new_post[ 'post_content' ] = str_replace( $lnkToReplace, $linkToReplaceWith, $new_post[ 'post_content' ] );
					}
				} catch ( Exception $e ) {

				}
			}
		}

		if ( has_shortcode( $new_post[ 'post_content' ], 'gallery' ) ) {
			if ( preg_match_all( '/\[gallery[^\]]+ids=\"(.*?)\"[^\]]*\]/ix', $new_post[ 'post_content' ], $matches, PREG_SET_ORDER ) ) {
				$replaceAttachedIds = array();
				if ( is_array( $post_gallery_images ) ) {
					foreach ( $post_gallery_images as $gallery ) {
						if ( isset( $gallery[ 'src' ] ) ) {
							try {
								$upload = MainWP_Utility::uploadImage( $gallery[ 'src' ], $gallery, true ); //Upload image to WP, check if existed
								if ( null !== $upload ) {
									$replaceAttachedIds[ $gallery[ 'id' ] ] = $upload[ 'id' ];
								}
							} catch ( Exception $e ) {

							}
						}
					}
				}
				if ( count( $replaceAttachedIds ) > 0 ) {
					foreach ( $matches as $match ) {
						$idsToReplace		 = $match[ 1 ];
						$idsToReplaceWith	 = "";
						$originalIds		 = explode( ',', $idsToReplace );
						foreach ( $originalIds as $attached_id ) {
							if ( !empty( $originalIds ) && isset( $replaceAttachedIds[ $attached_id ] ) ) {
								$idsToReplaceWith .= $replaceAttachedIds[ $attached_id ] . ",";
							}
						}
						$idsToReplaceWith = rtrim( $idsToReplaceWith, "," );
						if ( !empty( $idsToReplaceWith ) ) {
							$new_post[ 'post_content' ] = str_replace( '"' . $idsToReplace . '"', '"' . $idsToReplaceWith . '"', $new_post[ 'post_content' ] );
						}
					}
				}
			}
		}

		$is_sticky = false;
		if ( isset( $new_post[ 'is_sticky' ] ) ) {
			$is_sticky = !empty( $new_post[ 'is_sticky' ] ) ? true : false;
			unset( $new_post[ 'is_sticky' ] );
		}
		$edit_id = $new_post[ 'edit_id' ];
		unset( $new_post[ 'edit_id' ] );

		$wp_error				 = null;
		//Save the post to the wp
		remove_filter( 'content_save_pre', 'wp_filter_post_kses' );  // to fix brake scripts or html
		$post_status			 = $new_post[ 'post_status' ];
		$new_post[ 'post_status' ] = 'auto-draft';
		$new_post_id			 = wp_insert_post( $new_post, $wp_error );

		//Show errors if something went wrong
		if ( is_wp_error( $wp_error ) ) {
			return array( 'error' => $wp_error->get_error_message() );
		}

		if ( empty( $new_post_id ) ) {
			return array( 'error' => 'Undefined error' );
		}

		wp_update_post( array( 'ID' => $new_post_id, 'post_status' => $post_status ) );

		foreach ( $post_custom as $meta_key => $meta_values ) {
			foreach ( $meta_values as $meta_value ) {
				add_post_meta( $new_post_id, $meta_key, $meta_value );
			}
		}

		// update meta for bulkedit
		update_post_meta( $new_post_id, '_mainwp_edit_post_id', $edit_id ); // ID of post on the child site, editing
		update_post_meta( $new_post_id, '_slug', base64_encode( $new_post[ 'post_name' ] ) );
		if ( isset( $post_category ) && '' !== $post_category ) {
			update_post_meta( $new_post_id, '_categories', base64_encode( $post_category ) );
		}

		if ( isset( $post_tags ) && '' !== $post_tags ) {
			update_post_meta( $new_post_id, '_tags', base64_encode( $post_tags ) );
		}
		if ( $is_sticky ) {
			update_post_meta( $new_post_id, '_sticky', base64_encode( 'sticky' ) );
		}
		//end//
		//If featured image exists - set it
		if ( null !== $post_featured_image ) {
			try {
				$upload = MainWP_Utility::uploadImage( $post_featured_image ); //Upload image to WP

				if ( null !== $upload ) {
					update_post_meta( $new_post_id, '_thumbnail_id', $upload[ 'id' ] ); //Add the thumbnail to the post!
				}
			} catch ( Exception $e ) {

			}
		}

		$ret[ 'success' ]	 = true;
		$ret[ 'id' ]		 = $new_post_id;
		return $ret;
	}

	public static function testPost() {
		do_action( 'mainwp-do-action', 'test_post' );
	}

	public static function setTerms( $postId, $cat_id, $taxonomy, $websiteIdEnc ) {
		if ( !MainWP_Utility::ctype_digit( $postId ) ) {
			return;
		}
		$websiteId = $websiteIdEnc;
		if ( !MainWP_Utility::ctype_digit( $websiteId ) ) {
			return;
		}

		$website = MainWP_DB::Instance()->getWebsiteById( $websiteId );
		if ( !MainWP_Utility::can_edit_website( $website ) ) {
			return;
		}

		try {
			$information = MainWP_Utility::fetchUrlAuthed( $website, 'set_terms', array(
				'id'		 => base64_encode( $postId ),
				'terms'		 => base64_encode( $cat_id ),
				'taxonomy'	 => base64_encode( $taxonomy ),
			) );
		} catch ( MainWP_Exception $e ) {
			return;
		}
		if ( !isset( $information[ 'status' ] ) || ( $information[ 'status' ] != 'SUCCESS' ) ) {
			return;
		}
	}

	public static function insertComments( $postId, $comments, $websiteId ) {
		if ( !MainWP_Utility::ctype_digit( $postId ) ) {
			return;
		}
		if ( !MainWP_Utility::ctype_digit( $websiteId ) ) {
			return;
		}
		$website = MainWP_DB::Instance()->getWebsiteById( $websiteId );
		if ( !MainWP_Utility::can_edit_website( $website ) ) {
			return;
		}
		try {
			MainWP_Utility::fetchUrlAuthed( $website, 'insert_comment', array(
				'id'		 => $postId,
				'comments'	 => base64_encode( serialize( $comments ) ),
			) );
		} catch ( MainWP_Exception $e ) {
			return;
		}

		return;
	}

	public static function add_sticky_handle( $post_id ) {
		// OK, we're authenticated: we need to find and save the data
		$post = get_post( $post_id );
		if ( $post->post_type == 'bulkpost' && isset( $_POST[ 'sticky' ] ) ) {
			update_post_meta( $post_id, '_sticky', base64_encode( $_POST[ 'sticky' ] ) );

			return base64_encode( $_POST[ 'sticky' ] );
		}

		if ( $post->post_type == 'bulkpost' && isset( $_POST[ 'mainwp_edit_post_status' ] ) ) {
			update_post_meta( $post_id, '_edit_post_status', $_POST[ 'mainwp_edit_post_status' ] );
		}

		return $post_id;
	}

	// Hook the section help content to the Help Sidebar element
	public static function mainwp_help_content() {
		if ( isset( $_GET['page'] ) && ( $_GET['page'] == "PostBulkManage" || $_GET['page'] == "PostBulkAdd" ) ) {
			?>
			<p><?php echo __( 'If you need help with managing posts, please review following help documents', 'mainwp' ); ?></p>
			<div class="ui relaxed bulleted list">
				<div class="item"><a href="https://mainwp.com/help/docs/manage-posts/" target="_blank">Manage Posts</a></div>
				<div class="item"><a href="https://mainwp.com/help/docs/manage-posts/create-a-new-post/" target="_blank">Create a New Post</a></div>
				<div class="item"><a href="https://mainwp.com/help/docs/manage-posts/edit-an-existing-post/" target="_blank">Edit an Existing Post</a></div>
				<div class="item"><a href="https://mainwp.com/help/docs/manage-posts/change-status-of-an-existing-post/" target="_blank">Change Status of an Existing Post</a></div>
				<div class="item"><a href="https://mainwp.com/help/docs/manage-posts/view-an-existing-post/" target="_blank">View an Existing Post</a></div>
				<div class="item"><a href="https://mainwp.com/help/docs/manage-posts/delete-posts/" target="_blank">Delete Post(s)</a></div>
			</div>
			<?php
		}
	}

}
