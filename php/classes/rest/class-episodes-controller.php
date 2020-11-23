<?php

namespace SeriouslySimplePodcasting\Rest;

use WP_REST_Controller;
use WP_REST_Posts_Controller;
use WP_REST_Server;
use WP_Query;

/**
 * Custom endpoint for querying for multiple post-types for episodes.
 * Mimics `WP_REST_Posts_Controller` as closely as possible.
 *
 * New filters:
 *  - `rest_episode_query` Filters the query arguments as generated
 *    from the request parameters.
 *
 * @author Jonathan Bossenger
 * @package Seriously Simple Podcasting
 * @since 1.19.12
 */

/**
 * Extended from the code shared by Ruben Vreeken, (https://github.com/Rayraz)
 * on stackoverflow (http://stackoverflow.com/questions/38059805/query-multiple-post-types-using-wp-rest-api-v2-wordpress)
 */

/**
 * Class Episodes_Controller
 */
class Episodes_Controller extends WP_REST_Controller {

	public $namespace;
	public $rest_base;
	public $post_types;

	public function __construct() {
		$this->namespace  = 'ssp/v1';
		$this->rest_base  = '/episodes';
		$this->post_types = ssp_post_types();
	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			$this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
			)
		);
	}

	/**
	 * Check if a given request has access to get items
	 *
	 * @return bool
	 */
	public function get_items_permissions_check( $request ) {
		return true;
	}

	/**
	 * Get a collection of items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$args                        = array();
		$args['author__in']          = $request['author'];
		$args['author__not_in']      = $request['author_exclude'];
		$args['menu_order']          = $request['menu_order'];
		$args['offset']              = $request['offset'];
		$args['order']               = $request['order'];
		$args['orderby']             = $request['orderby'];
		$args['paged']               = $request['page'];
		$args['post__in']            = $request['include'];
		$args['post__not_in']        = $request['exclude'];
		$args['posts_per_page']      = $request['per_page'];
		$args['name']                = $request['slug'];
		$args['post_type']           = $this->post_types;
		$args['post_parent__in']     = $request['parent'];
		$args['post_parent__not_in'] = $request['parent_exclude'];
		$args['post_status']         = $request['status'];
		$args['s']                   = $request['search'];

		$args['date_query'] = array();
		// Set before into date query. Date query must be specified as an array
		// of an array.
		if ( isset( $request['before'] ) ) {
			$args['date_query'][0]['before'] = $request['before'];
		}

		// Set after into date query. Date query must be specified as an array
		// of an array.
		if ( isset( $request['after'] ) ) {
			$args['date_query'][0]['after'] = $request['after'];
		}

		if ( is_array( $request['filter'] ) ) {
			$args = array_merge( $args, $request['filter'] );
			unset( $args['filter'] );
		}

		// Ensure array of post_types
		if ( ! is_array( $args['post_type'] ) ) {
			$args['post_type'] = array( $args['post_type'] );
		}

		/**
		 * Filter the query arguments for a request.
		 *
		 * Enables adding extra arguments or setting defaults for a post
		 * collection request.
		 *
		 * @see https://developer.wordpress.org/reference/classes/wp_user_query/
		 *
		 * @param array $args Key value array of query var to query value.
		 * @param WP_REST_Request $request The request used.
		 *
		 * @var Function
		 */
		$args       = apply_filters( "rest_episode_query", $args, $request );
		$query_args = $this->prepare_items_query( $args, $request );

		// Get taxonomies for each of the requested post_types
		$taxonomies = wp_list_filter( get_object_taxonomies( $query_args['post_type'], 'objects' ), array( 'show_in_rest' => true ) );

		// Construct taxonomy query
		foreach ( $taxonomies as $taxonomy ) {
			$base = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;

			if ( ! empty( $request[ $base ] ) ) {
				$query_args['tax_query'][] = array(
					'taxonomy'         => $taxonomy->name,
					'field'            => 'term_id',
					'terms'            => $request[ $base ],
					'include_children' => false,
				);
			}
		}

		// Execute the query
		$posts_query  = new WP_Query();
		$query_result = $posts_query->query( $query_args );

		// Handle query results
		$posts = array();
		foreach ( $query_result as $post ) {

			// Get PostController for Post Type
			$posts_controller = new WP_REST_Posts_Controller( $post->post_type );

			if ( ! $posts_controller->check_read_permission( $post ) ) {
				continue;
			}

			$data    = $posts_controller->prepare_item_for_response( $post, $request );
			$posts[] = $posts_controller->prepare_response_for_collection( $data );
		}

		// Calc total post count
		$page        = (int) $query_args['paged'];
		$total_posts = $posts_query->found_posts;

		// Out-of-bounds, run the query again without LIMIT for total count
		if ( $total_posts < 1 ) {
			unset( $query_args['paged'] );
			$count_query = new WP_Query();
			$count_query->query( $query_args );
			$total_posts = $count_query->found_posts;
		}

		// Calc total page count
		$max_pages = ceil( $total_posts / (int) $query_args['posts_per_page'] );

		// Construct response
		$response = rest_ensure_response( $posts );
		$response->header( 'X-WP-Total', (int) $total_posts );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		// Construct base url for pagination links
		$request_params = $request->get_query_params();
		if ( ! empty( $request_params['filter'] ) ) {
			// Normalize the pagination params.
			unset( $request_params['filter']['posts_per_page'] );
			unset( $request_params['filter']['paged'] );
		}
		$base = add_query_arg( $request_params, rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ) );

		// Create link for previous page, if needed
		if ( $page > 1 ) {
			$prev_page = $page - 1;
			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}
			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}

		// Create link for next page, if needed
		if ( $max_pages > $page ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );
			$response->link_header( 'next', $next_link );
		}

		return $response;
	}

	/**
	 * Determine the allowed query_vars for a get_items() response and prepare
	 * for WP_Query.
	 *
	 * @param  array $prepared_args
	 * @param  WP_REST_Request $request
	 *
	 * @return array            $query_args
	 */
	protected function prepare_items_query( $prepared_args = array(), $request = null ) {
		$query_args = array();

		foreach ( $prepared_args as $key => $value ) {
			/**
			 * Filters the query_vars used in get_items() for the constructed query.
			 *
			 * The dynamic portion of the hook name, `$key`, refers to the query_var key.
			 *
			 * @since 4.7.0
			 *
			 * @param string $value The query_var value.
			 */
			$query_args[ $key ] = apply_filters( "rest_query_var-{$key}", $value ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		}

		if ( ! in_array( 'post', $this->post_types ) || ! isset( $query_args['ignore_sticky_posts'] ) ) {
			$query_args['ignore_sticky_posts'] = true;
		}

		// Map to proper WP_Query orderby param.
		if ( isset( $query_args['orderby'] ) && isset( $request['orderby'] ) ) {
			$orderby_mappings = array(
				'id'            => 'ID',
				'include'       => 'post__in',
				'slug'          => 'post_name',
				'include_slugs' => 'post_name__in',
			);

			if ( isset( $orderby_mappings[ $request['orderby'] ] ) ) {
				$query_args['orderby'] = $orderby_mappings[ $request['orderby'] ];
			}
		}

		return $query_args;
	}

	/**
	 * Get all the WP Query vars that are allowed for the API request.
	 *
	 * @return array
	 */
	protected function get_allowed_query_vars( $post_types ) {
		global $wp;
		$edit_posts = true;

		/**
		 * Filter the publicly allowed query vars.
		 *
		 * Allows adjusting of the default query vars that are made public.
		 *
		 * @param array  Array  of allowed WP_Query query vars.
		 *
		 * @var Function
		 */
		$valid_vars = apply_filters( 'query_vars', $wp->public_query_vars );

		/**
		 * We allow 'private' query vars for authorized users only.
		 *
		 * It the user has `edit_posts` capabilty for *every* requested post
		 * type, we also allow use of private query parameters, which are only
		 * undesirable on the frontend, but are safe for use in query strings.
		 *
		 * To disable anyway, use `add_filter( 'rest_private_query_vars',
		 * '__return_empty_array' );`
		 *
		 * @param array $private_query_vars Array of allowed query vars for
		 *                                    authorized users.
		 *
		 * @var boolean
		 */
		foreach ( $post_types as $post_type ) {
			$post_type_obj = get_post_type_object( $post_type );
			if ( ! current_user_can( $post_type_obj->cap->edit_posts ) ) {
				$edit_posts = false;
				break;
			}
		}
		if ( $edit_posts ) {
			$private    = apply_filters( 'rest_private_query_vars', $wp->private_query_vars );
			$valid_vars = array_merge( $valid_vars, $private );
		}

		// Define our own in addition to WP's normal vars.
		$rest_valid = array(
			'author__in',
			'author__not_in',
			'ignore_sticky_posts',
			'menu_order',
			'offset',
			'post__in',
			'post__not_in',
			'post_parent',
			'post_parent__in',
			'post_parent__not_in',
			'posts_per_page',
			'date_query',
		);
		$valid_vars = array_merge( $valid_vars, $rest_valid );

		/**
		 * Filter allowed query vars for the REST API.
		 *
		 * This filter allows you to add or remove query vars from the final
		 * allowed list for all requests, including unauthenticated ones. To
		 * alter the vars for editors only, {@see rest_private_query_vars}.
		 *
		 * @param array {
		 *    Array of allowed WP_Query query vars.
		 *
		 * @param string $allowed_query_var The query var to allow.
		 * }
		 */
		$valid_vars = apply_filters( 'rest_query_vars', $valid_vars );

		return $valid_vars;
	}

	/**
	 * Get the query params for collections of attachments.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['context']['default'] = 'view';

		$params['after'] = array(
			'description'       => __( 'Limit response to resources published after a given ISO8601 compliant date.' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['author']         = array(
			'description'       => __( 'Limit result set to posts assigned to specific authors.' ),
			'type'              => 'array',
			'default'           => array(),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['author_exclude'] = array(
			'description'       => __( 'Ensure result set excludes posts assigned to specific authors.' ),
			'type'              => 'array',
			'default'           => array(),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['before']  = array(
			'description'       => __( 'Limit response to resources published before a given ISO8601 compliant date.' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['exclude'] = array(
			'description'       => __( 'Ensure result set excludes specific ids.' ),
			'type'              => 'array',
			'default'           => array(),
			'sanitize_callback' => 'wp_parse_id_list',
		);
		$params['include'] = array(
			'description'       => __( 'Limit result set to specific ids.' ),
			'type'              => 'array',
			'default'           => array(),
			'sanitize_callback' => 'wp_parse_id_list',
		);

		$params['menu_order'] = array(
			'description'       => __( 'Limit result set to resources with a specific menu_order value.' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['offset']  = array(
			'description'       => __( 'Offset the result set by a specific number of items.' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['order']   = array(
			'description'       => __( 'Order sort attribute ascending or descending.' ),
			'type'              => 'string',
			'default'           => 'desc',
			'enum'              => array( 'asc', 'desc' ),
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['orderby'] = array(
			'description'       => __( 'Sort collection by object attribute.' ),
			'type'              => 'string',
			'default'           => 'date',
			'enum'              => array(
				'date',
				'id',
				'include',
				'title',
				'slug',
			),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['orderby']['enum'][] = 'menu_order';

		$params['parent']         = array(
			'description'       => __( 'Limit result set to those of particular parent ids.' ),
			'type'              => 'array',
			'sanitize_callback' => 'wp_parse_id_list',
			'default'           => array(),
		);
		$params['parent_exclude'] = array(
			'description'       => __( 'Limit result set to all items except those of a particular parent id.' ),
			'type'              => 'array',
			'sanitize_callback' => 'wp_parse_id_list',
			'default'           => array(),
		);

		$params['slug']   = array(
			'description'       => __( 'Limit result set to posts with a specific slug.' ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['status'] = array(
			'default'           => 'publish',
			'description'       => __( 'Limit result set to posts assigned a specific status.' ),
			'sanitize_callback' => 'sanitize_key',
			'type'              => 'string',
			'validate_callback' => array( $this, 'validate_user_can_query_private_statuses' ),
		);
		$params['filter'] = array(
			'description' => __( 'Use WP Query arguments to modify the response; private query vars require appropriate authorization.' ),
		);

		$taxonomies = wp_list_filter( get_object_taxonomies( get_post_types( array(), 'names' ), 'objects' ), array( 'show_in_rest' => true ) );
		foreach ( $taxonomies as $taxonomy ) {
			$base = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;

			$params[ $base ] = array(
				'description'       => sprintf( __( 'Limit result set to all items that have the specified term assigned in the %s taxonomy.' ), $base ),
				'type'              => 'array',
				'sanitize_callback' => 'wp_parse_id_list',
				'default'           => array(),
			);
		}

		return $params;
	}

	/**
	 * Validate whether the user can query private statuses
	 *
	 * @param  mixed $value
	 * @param  WP_REST_Request $request
	 * @param  string $parameter
	 *
	 * @return WP_Error|boolean
	 */
	public function validate_user_can_query_private_statuses( $value, $request, $parameter ) {
		if ( 'publish' === $value ) {
			return true;
		}

		foreach ( $this->post_types as $post_type ) {
			$post_type_obj = get_post_type_object( $post_type );
			if ( ! current_user_can( $post_type_obj->cap->edit_posts ) ) {
				return new WP_Error( 'rest_forbidden_status', __( 'Status is forbidden' ), array(
					'status'    => rest_authorization_required_code(),
					'post_type' => $post_type_obj->name,
				) );
			}
		}

		return true;
	}

}
