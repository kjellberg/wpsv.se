<?php

/**
 * Functions of bbPress's Default theme
 *
 * @package bbPress
 * @subpackage BBP_Theme_Compat
 * @since bbPress (r3732)
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// Add bootstrap classes for forum rows
function add_bootstrap_class( $classes ) {

	$classes[] = 'row';
	return $classes;
}
	
add_filter( 'bbp_get_forum_class', 'add_bootstrap_class' );

/** Theme Setup ***************************************************************/

if ( !class_exists( 'BBP_Default' ) ) :

/**
 * Loads bbPress Default Theme functionality
 *
 * This is not a real theme by WordPress standards, and is instead used as the
 * fallback for any WordPress theme that does not have bbPress templates in it.
 *
 * To make your custom theme bbPress compatible and customize the templates, you
 * can copy these files into your theme without needing to merge anything
 * together; bbPress should safely handle the rest.
 *
 * See @link BBP_Theme_Compat() for more.
 *
 * @since bbPress (r3732)
 *
 * @package bbPress
 * @subpackage BBP_Theme_Compat
 */
class BBP_Default extends BBP_Theme_Compat {

	/** Functions *************************************************************/

	/**
	 * The main bbPress (Default) Loader
	 *
	 * @since bbPress (r3732)
	 *
	 * @uses BBP_Default::setup_globals()
	 * @uses BBP_Default::setup_actions()
	 */
	public function __construct( $properties = array() ) {

		parent::__construct( bbp_parse_args( $properties, array(
			'id'      => 'default',
			'name'    => __( 'bbPress Default', 'bbpress' ),
			'version' => bbp_get_version(),
			'dir'     => trailingslashit( bbpress()->themes_dir . 'default' ),
			'url'     => trailingslashit( bbpress()->themes_url . 'default' ),
		), 'default_theme' ) );

		$this->setup_actions();
	}

	/**
	 * Setup the theme hooks
	 *
	 * @since bbPress (r3732)
	 * @access private
	 *
	 * @uses add_filter() To add various filters
	 * @uses add_action() To add various actions
	 */
	private function setup_actions() {

		/** Scripts ***********************************************************/

		add_action( 'bbp_enqueue_scripts',         array( $this, 'enqueue_styles'          ) ); // Enqueue theme CSS
		add_action( 'bbp_enqueue_scripts',         array( $this, 'enqueue_scripts'         ) ); // Enqueue theme JS
		add_filter( 'bbp_enqueue_scripts',         array( $this, 'localize_topic_script'   ) ); // Enqueue theme script localization
		add_action( 'bbp_ajax_favorite',           array( $this, 'ajax_favorite'           ) ); // Handles the ajax favorite/unfavorite
		add_action( 'bbp_ajax_subscription',       array( $this, 'ajax_subscription'       ) ); // Handles the ajax subscribe/unsubscribe
		add_action( 'bbp_ajax_forum_subscription', array( $this, 'ajax_forum_subscription' ) ); // Handles the forum ajax subscribe/unsubscribe
		/** Template Wrappers *************************************************/

		add_action( 'bbp_before_main_content', array( $this, 'before_main_content' ) ); // Top wrapper HTML
		add_action( 'bbp_after_main_content',  array( $this, 'after_main_content'  ) ); // Bottom wrapper HTML

		/** Override **********************************************************/

		do_action_ref_array( 'bbp_theme_compat_actions', array( &$this ) );

		/** EpicWebs Additions ************************************************/

		add_action( 'admin_menu', array( $this, 'epicwebs_theme_menu'      ) );
		add_action( 'wp_head',    array( $this, 'epicweb_hide_sidebar_css' ) );
		add_filter( 'wp_footer',  array( $this, 'epicweb_add_forum_link'   ) , 1000 );

	}

	/**
	 * Inserts HTML at the top of the main content area to be compatible with
	 * the Twenty Twelve theme.
	 *
	 * @since bbPress (r3732)
	 */
	public function before_main_content() {
	?>

		<div id="bbp-container">
			<div id="bbp-content" role="main">

	<?php
	}

	/**
	 * Inserts HTML at the bottom of the main content area to be compatible with
	 * the Twenty Twelve theme.
	 *
	 * @since bbPress (r3732)
	 */
	public function after_main_content() {
	?>

			</div><!-- #bbp-content -->
		</div><!-- #bbp-container -->

	<?php
	}

	/**
	 * Load the theme CSS
	 *
	 * @since bbPress (r3732)
	 *
	 * @uses wp_enqueue_style() To enqueue the styles
	 */
	public function enqueue_styles() {

		// RTL and/or minified
		$suffix  = is_rtl() ? '-rtl' : '';
		$suffix .= defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Get and filter the bbp-default style
		$styles = apply_filters( 'bbp_default_styles', array(
			'bbp-default' => array(
				'file'         => 'css/bbpress' . $suffix . '.css',
				'dependencies' => array()
			)
		) );

		// Enqueue the styles
		foreach ( $styles as $handle => $attributes ) {
			bbp_enqueue_style( $handle, $attributes['file'], $attributes['dependencies'], $this->version, 'screen' );
		}
	}

	/**
	 * Enqueue the required Javascript files
	 *
	 * @since bbPress (r3732)
	 *
	 * @uses bbp_is_single_forum() To check if it's the forum page
	 * @uses bbp_is_single_topic() To check if it's the topic page
	 * @uses bbp_thread_replies() To check if threaded replies are enabled
	 * @uses bbp_is_single_user_edit() To check if it's the profile edit page
	 * @uses wp_enqueue_script() To enqueue the scripts
	 */
	public function enqueue_scripts() {

		// Setup scripts array
		$scripts = array();

		// Minified
		$suffix  = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Always pull in jQuery for TinyMCE shortcode usage
		if ( bbp_use_wp_editor() ) {
			$scripts['bbpress-editor'] = array(
				'file'         => 'js/editor' . $suffix . '.js',
				'dependencies' => array( 'jquery' )
			);
		}

		// Forum-specific scripts
		if ( bbp_is_single_forum() ) {
			$scripts['bbpress-forum'] = array(
				'file'         => 'js/forum' . $suffix . '.js',
				'dependencies' => array( 'jquery' )
			);
		}

		// Topic-specific scripts
		if ( bbp_is_single_topic() ) {

			// Topic favorite/unsubscribe
			$scripts['bbpress-topic'] = array(
				'file'         => 'js/topic' . $suffix . '.js',
				'dependencies' => array( 'jquery' )
			);

			// Hierarchical replies
			if ( bbp_thread_replies() ) {
				$scripts['bbpress-reply'] = array(
					'file'         => 'js/reply' . $suffix . '.js',
					'dependencies' => array( 'jquery' )
				);
			}
		}

		// User Profile edit
		if ( bbp_is_single_user_edit() ) {
			$scripts['bbpress-user'] = array(
				'file'         => 'js/user' . $suffix . '.js',
				'dependencies' => array( 'user-query' )
			);
		}

		// Filter the scripts
		$scripts = apply_filters( 'bbp_default_scripts', $scripts );

		// Enqueue the scripts
		foreach ( $scripts as $handle => $attributes ) {
			bbp_enqueue_script( $handle, $attributes['file'], $attributes['dependencies'], $this->version, 'screen' );
		}
	}

	/**
	 * Load localizations for topic script
	 *
	 * These localizations require information that may not be loaded even by init.
	 *
	 * @since bbPress (r3732)
	 *
	 * @uses bbp_is_single_forum() To check if it's the forum page
	 * @uses bbp_is_single_topic() To check if it's the topic page
	 * @uses is_user_logged_in() To check if user is logged in
	 * @uses bbp_get_current_user_id() To get the current user id
	 * @uses bbp_get_forum_id() To get the forum id
	 * @uses bbp_get_topic_id() To get the topic id
	 * @uses bbp_get_favorites_permalink() To get the favorites permalink
	 * @uses bbp_is_user_favorite() To check if the topic is in user's favorites
	 * @uses bbp_is_subscriptions_active() To check if the subscriptions are active
	 * @uses bbp_is_user_subscribed() To check if the user is subscribed to topic
	 * @uses bbp_get_topic_permalink() To get the topic permalink
	 * @uses wp_localize_script() To localize the script
	 */
	public function localize_topic_script() {

		// Single forum
		if ( bbp_is_single_forum() ) {
			wp_localize_script( 'bbpress-forum', 'bbpForumJS', array(
				'bbp_ajaxurl'        => bbp_get_ajax_url(),
				'generic_ajax_error' => __( 'Something went wrong. Refresh your browser and try again.', 'bbpress' ),
				'is_user_logged_in'  => is_user_logged_in(),
				'subs_nonce'         => wp_create_nonce( 'toggle-subscription_' . get_the_ID() )
			) );

		// Single topic
		} elseif ( bbp_is_single_topic() ) {
			wp_localize_script( 'bbpress-topic', 'bbpTopicJS', array(
				'bbp_ajaxurl'        => bbp_get_ajax_url(),
				'generic_ajax_error' => __( 'Something went wrong. Refresh your browser and try again.', 'bbpress' ),
				'is_user_logged_in'  => is_user_logged_in(),
				'fav_nonce'          => wp_create_nonce( 'toggle-favorite_' .     get_the_ID() ),
				'subs_nonce'         => wp_create_nonce( 'toggle-subscription_' . get_the_ID() )
			) );
		}
	}

	/**
	 * AJAX handler to Subscribe/Unsubscribe a user from a forum
	 *
	 * @since bbPress (r5155)
	 *
	 * @uses bbp_is_subscriptions_active() To check if the subscriptions are active
	 * @uses bbp_is_user_logged_in() To check if user is logged in
	 * @uses bbp_get_current_user_id() To get the current user id
	 * @uses current_user_can() To check if the current user can edit the user
	 * @uses bbp_get_forum() To get the forum
	 * @uses wp_verify_nonce() To verify the nonce
	 * @uses bbp_is_user_subscribed() To check if the forum is in user's subscriptions
	 * @uses bbp_remove_user_subscriptions() To remove the forum from user's subscriptions
	 * @uses bbp_add_user_subscriptions() To add the forum from user's subscriptions
	 * @uses bbp_ajax_response() To return JSON
	 */
	public function ajax_forum_subscription() {

		// Bail if subscriptions are not active
		if ( ! bbp_is_subscriptions_active() ) {
			bbp_ajax_response( false, __( 'Subscriptions are no longer active.', 'bbpress' ), 300 );
		}

		// Bail if user is not logged in
		if ( ! is_user_logged_in() ) {
			bbp_ajax_response( false, __( 'Please login to subscribe to this forum.', 'bbpress' ), 301 );
		}

		// Get user and forum data
		$user_id = bbp_get_current_user_id();
		$id      = intval( $_POST['id'] );

		// Bail if user cannot add favorites for this user
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			bbp_ajax_response( false, __( 'You do not have permission to do this.', 'bbpress' ), 302 );
		}

		// Get the forum
		$forum = bbp_get_forum( $id );

		// Bail if forum cannot be found
		if ( empty( $forum ) ) {
			bbp_ajax_response( false, __( 'The forum could not be found.', 'bbpress' ), 303 );
		}

		// Bail if user did not take this action
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'toggle-subscription_' . $forum->ID ) ) {
			bbp_ajax_response( false, __( 'Are you sure you meant to do that?', 'bbpress' ), 304 );
		}

		// Take action
		$status = bbp_is_user_subscribed( $user_id, $forum->ID ) ? bbp_remove_user_subscription( $user_id, $forum->ID ) : bbp_add_user_subscription( $user_id, $forum->ID );

		// Bail if action failed
		if ( empty( $status ) ) {
			bbp_ajax_response( false, __( 'The request was unsuccessful. Please try again.', 'bbpress' ), 305 );
		}

		// Put subscription attributes in convenient array
		$attrs = array(
			'forum_id' => $forum->ID,
			'user_id'  => $user_id
		);

		// Action succeeded
		bbp_ajax_response( true, bbp_get_forum_subscription_link( $attrs, $user_id, false ), 200 );
	}

	/**
	 * AJAX handler to add or remove a topic from a user's favorites
	 *
	 * @since bbPress (r3732)
	 *
	 * @uses bbp_get_current_user_id() To get the current user id
	 * @uses current_user_can() To check if the current user can edit the user
	 * @uses bbp_get_topic() To get the topic
	 * @uses wp_verify_nonce() To verify the nonce & check the referer
	 * @uses bbp_is_user_favorite() To check if the topic is user's favorite
	 * @uses bbp_remove_user_favorite() To remove the topic from user's favorites
	 * @uses bbp_add_user_favorite() To add the topic from user's favorites
	 * @uses bbp_ajax_response() To return JSON
	 */
	public function ajax_favorite() {

		// Bail if favorites are not active
		if ( ! bbp_is_favorites_active() ) {
			bbp_ajax_response( false, __( 'Favorites are no longer active.', 'bbpress' ), 300 );
		}

		// Bail if user is not logged in
		if ( !is_user_logged_in() ) {
			bbp_ajax_response( false, __( 'Please login to make this topic a favorite.', 'bbpress' ), 301 );
		}

		// Get user and topic data
		$user_id = bbp_get_current_user_id();
		$id      = !empty( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;

		// Bail if user cannot add favorites for this user
		if ( !current_user_can( 'edit_user', $user_id ) ) {
			bbp_ajax_response( false, __( 'You do not have permission to do this.', 'bbpress' ), 302 );
		}

		// Get the topic
		$topic = bbp_get_topic( $id );

		// Bail if topic cannot be found
		if ( empty( $topic ) ) {
			bbp_ajax_response( false, __( 'The topic could not be found.', 'bbpress' ), 303 );
		}

		// Bail if user did not take this action
		if ( !isset( $_POST['nonce'] ) || !wp_verify_nonce( $_POST['nonce'], 'toggle-favorite_' . $topic->ID ) ) {
			bbp_ajax_response( false, __( 'Are you sure you meant to do that?', 'bbpress' ), 304 );
		}

		// Take action
		$status = bbp_is_user_favorite( $user_id, $topic->ID ) ? bbp_remove_user_favorite( $user_id, $topic->ID ) : bbp_add_user_favorite( $user_id, $topic->ID );

		// Bail if action failed
		if ( empty( $status ) ) {
			bbp_ajax_response( false, __( 'The request was unsuccessful. Please try again.', 'bbpress' ), 305 );
		}

		// Put subscription attributes in convenient array
		$attrs = array(
			'topic_id' => $topic->ID,
			'user_id'  => $user_id
		);

		// Action succeeded
		bbp_ajax_response( true, bbp_get_user_favorites_link( $attrs, $user_id, false ), 200 );
	}

	/**
	 * AJAX handler to Subscribe/Unsubscribe a user from a topic
	 *
	 * @since bbPress (r3732)
	 *
	 * @uses bbp_is_subscriptions_active() To check if the subscriptions are active
	 * @uses bbp_get_current_user_id() To get the current user id
	 * @uses current_user_can() To check if the current user can edit the user
	 * @uses bbp_get_topic() To get the topic
	 * @uses wp_verify_nonce() To verify the nonce
	 * @uses bbp_is_user_subscribed() To check if the topic is in user's subscriptions
	 * @uses bbp_remove_user_subscriptions() To remove the topic from user's subscriptions
	 * @uses bbp_add_user_subscriptions() To add the topic from user's subscriptions
	 * @uses bbp_ajax_response() To return JSON
	 */
	public function ajax_subscription() {

		// Bail if subscriptions are not active
		if ( !bbp_is_subscriptions_active() ) {
			bbp_ajax_response( false, __( 'Subscriptions are no longer active.', 'bbpress' ), 300 );
		}

		// Bail if user is not logged in
		if ( !is_user_logged_in() ) {
			bbp_ajax_response( false, __( 'Please login to subscribe to this topic.', 'bbpress' ), 301 );
		}

		// Get user and topic data
		$user_id = bbp_get_current_user_id();
		$id      = intval( $_POST['id'] );

		// Bail if user cannot add favorites for this user
		if ( !current_user_can( 'edit_user', $user_id ) ) {
			bbp_ajax_response( false, __( 'You do not have permission to do this.', 'bbpress' ), 302 );
		}

		// Get the topic
		$topic = bbp_get_topic( $id );

		// Bail if topic cannot be found
		if ( empty( $topic ) ) {
			bbp_ajax_response( false, __( 'The topic could not be found.', 'bbpress' ), 303 );
		}

		// Bail if user did not take this action
		if ( !isset( $_POST['nonce'] ) || !wp_verify_nonce( $_POST['nonce'], 'toggle-subscription_' . $topic->ID ) ) {
			bbp_ajax_response( false, __( 'Are you sure you meant to do that?', 'bbpress' ), 304 );
		}

		// Take action
		$status = bbp_is_user_subscribed( $user_id, $topic->ID ) ? bbp_remove_user_subscription( $user_id, $topic->ID ) : bbp_add_user_subscription( $user_id, $topic->ID );

		// Bail if action failed
		if ( empty( $status ) ) {
			bbp_ajax_response( false, __( 'The request was unsuccessful. Please try again.', 'bbpress' ), 305 );
		}

		// Put subscription attributes in convenient array
		$attrs = array(
			'topic_id' => $topic->ID,
			'user_id'  => $user_id
		);

		// Action succeeded
		bbp_ajax_response( true, bbp_get_user_subscribe_link( $attrs, $user_id, false ), 200 );
	}

	public function epicwebs_bbp_list_forums( $args = '' ) {

		// Define used variables
		$output = $sub_forums = $topic_count = $reply_count = $counts = '';
		$i = 0;
		$count = array();

		// Defaults and arguments
		$defaults = array (
			'before'            => '<li class="bbp-forums-list">',
			'after'             => '</li>',
			'link_before'       => '',
			'link_after'        => '',
			'count_before'      => '',
			'count_after'       => '',
			'count_sep'         => '',
			'separator'         => '',
			'forum_id'          => '',
			'show_topic_count'  => true,
			'show_reply_count'  => true,
			'show_freshness_link'  => true,
		);
		$r = bbp_parse_args( $args, $defaults, 'list_forums' );
		extract( $r, EXTR_SKIP );

		// Bail if there are no subforums
		if ( !bbp_get_forum_subforum_count( $forum_id ) )
			return;

		// Loop through forums and create a list
		$sub_forums = bbp_forum_get_subforums( $forum_id );
		if ( !empty( $sub_forums ) ) {

			// Total count (for separator)
			$total_subs = count( $sub_forums );
			foreach ( $sub_forums as $sub_forum ) {
				$i++; // Separator count

				// Get forum details
				$count       = array();
				$show_sep    = $total_subs > $i ? $separator : '';
				$permalink   = bbp_get_forum_permalink( $sub_forum->ID );
				$title       = bbp_get_forum_title( $sub_forum->ID );
				$description = bbp_get_forum_content( $sub_forum->ID );

				// Show topic count
				if ( !empty( $show_topic_count ) && !bbp_is_forum_category( $sub_forum->ID ) ) {
					$count['topic'] = bbp_get_forum_topic_count( $sub_forum->ID );
				}

				// Show reply count
				if ( !empty( $show_reply_count ) && !bbp_is_forum_category( $sub_forum->ID ) ) {
					$count['reply'] = bbp_get_forum_reply_count( $sub_forum->ID );
				}

				// Counts to show
				if ( !empty( $count ) ) {
					$counts = $count_before . implode( $count_sep, $count ) . $count_after;
				}

				if ( !empty( $show_freshness_link ) ) {
					$freshness_link = "<li class='bbp-forum-freshness col-lg-3'>" . BBP_Default::epicwebs_get_last_poster_block( $sub_forum->ID ) . "</li>";
				}

				// Build this sub forums link
				if ($i % 2) { $class = "odd-forum-row"; } else { $class = "even-forum-row"; }
				$output .= "<ul class='{$class} bbp-forums-list row'>" . $link_before . '<li class="bbp-forum-info col-lg-8"><a href="' . $permalink . '" class="bbp-forum-title" rel="bookmark">' . $title . '</a><div class="bbp-forum-content"><i class="fa fa-info-circle"></i> <p>' . $description . '</p></div></li><li class="bbp-forum-topic-count col-lg-1">' . $counts . '</li>' . $freshness_link . $link_after . "</ul>";
			}

			// Output the list
			echo apply_filters( 'bbp_list_forums', $before . $output . $after, $args );
		}
	}

	/* Generate a list of topics a user has started, but with a limit argument */
	public function epicwebs_bbp_get_user_topics_started( $user_id = 0, $limit = 3, $max_num_pages = 1 ) {

		// Validate user
		$user_id = bbp_get_user_id( $user_id );
		if ( empty( $user_id ) )
			return false;

		// Query defaults
		$default_query = array(
			'author'         => $user_id,
			'show_stickies'  => false,
			'order'          => 'DESC',
			'posts_per_page' => $limit,
			'max_num_pages' => $max_num_pages
		);

		// Try to get the topics
		$query = bbp_has_topics( $default_query );
		if ( empty( $query ) )
			return false;

		return apply_filters( 'bbp_get_user_topics_started', $query, $user_id );
	}

	/** Last poster / freshness block for forums */
	public function epicwebs_last_poster_block( $subforum_id = "" ) {
		echo BBP_Default::epicwebs_get_last_poster_block( $subforum_id = "" );
	}

		public function epicwebs_get_last_poster_block( $subforum_id = "" ) {

			if ( !empty( $subforum_id ) ) {
				// Main forum display with sub forums
				$output = "<div class='last-posted-topic-title'>";
				$output .= "<a href='". bbp_get_forum_last_topic_permalink( $subforum_id ) ."'>" . bbp_get_topic_last_reply_title( bbp_get_forum_last_active_id( $subforum_id ) ) . "</a>";
				$output .= "</div>";
				$output .= "<div class='last-posted-topic-user'>av ";
					$author_id = bbp_get_forum_last_reply_author_id( $subforum_id );
				$output .= "<span class=\"bbp-author-avatar\">" . get_avatar( $author_id, '14' ) . "&nbsp;</span>";
				$output .= bbp_get_user_profile_link( $author_id );
				$output .= "</div>";
				$output .= "<div class='last-posted-topic-time'>";
				$output .= bbp_get_forum_last_active_time( $subforum_id );
				$output .= "</div>";
			} else {
				// forum category display (no sub forums list)
				$output = "<div class='last-posted-topic-title'>";
				$output .= "<a href='". bbp_get_forum_last_topic_permalink() ."'>" . bbp_get_topic_last_reply_title( bbp_get_forum_last_active_id() ) . "</a>";
				$output .= "</div>";
				$output .= "<div class='last-posted-topic-user'>av ";
				$output .= "<span class=\"bbp-author-avatar\">" . get_avatar( bbp_get_forum_last_reply_author_id(), '14' ) . "&nbsp;</span>";
				$output .= bbp_get_user_profile_link( bbp_get_forum_last_reply_author_id() );
				$output .= "</div>";
				$output .= "<div class='last-posted-topic-time'>";
				$output .= bbp_get_forum_last_active_time();
				$output .= "</div>";
			}

			return $output;

		}

	/* Last poster / freshness block for topics */
	public function epicwebs_last_poster_block_topics() {
		echo BBP_Default::teamop_get_last_poster_block_topics();
	}

		public function teamop_get_last_poster_block_topics() {

				$output .= "<div class='last-posted-topic-user'>";
				$output .= bbp_get_reply_author_link( array( 'post_id' => bbp_get_topic_last_active_id(), 'size' => '14' ) );
				$output .= "</div>";
				$output .= "<div class='last-posted-topic-time'>";
				$output .= bbp_get_topic_last_active_time( bbp_get_topic_last_active_id() );
				$output .= "</div>";

			return $output;

		}
}
new BBP_Default();
endif;