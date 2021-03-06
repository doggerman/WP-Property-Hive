<?php
/**
 * PH_Shortcodes class.
 *
 * @class 		PH_Shortcodes
 * @version		1.0.0
 * @package		PropertyHive/Classes
 * @category	Class
 * @author 		PropertyHive
 */
class PH_Shortcodes {

	/**
	 * Init shortcodes
	 */
	public static function init() {
		// Define shortcodes
		$shortcodes = array(
			/*'property'                    => __CLASS__ . '::property',
			'property_page'               => __CLASS__ . '::property_page',*/
			'properties'                   => __CLASS__ . '::properties',
			'recent_properties'            => __CLASS__ . '::recent_properties',
			'featured_properties'          => __CLASS__ . '::featured_properties',
			'similar_properties'           => __CLASS__ . '::similar_properties',
			'property_search_form'         => __CLASS__ . '::property_search_form',
		);

		foreach ( $shortcodes as $shortcode => $function ) {
			add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function );
		}
	}

	/**
	 * Shortcode Wrapper
	 *
	 * @param mixed $function
	 * @param array $atts (default: array())
	 * @return string
	 */
	public static function shortcode_wrapper(
		$function,
		$atts    = array(),
		$wrapper = array(
			'class'  => 'propertyhive',
			'before' => null,
			'after'  => null
		)
	) {
		ob_start();

		$before 	= empty( $wrapper['before'] ) ? '<div class="' . esc_attr( $wrapper['class'] ) . '">' : $wrapper['before'];
		$after 		= empty( $wrapper['after'] ) ? '</div>' : $wrapper['after'];

		echo $before;
		call_user_func( $function, $atts );
		echo $after;

		return ob_get_clean();
	}

	/**
	 * Output property search form
	 *
	 * @param array $atts
	 * @return string
	 */
	public static function property_search_form( $atts ) {
		$atts = shortcode_atts( array(
			'id' 				=> 'shortcode'
		), $atts );

		$form_controls = ph_get_search_form_fields();

		$form_controls = apply_filters( 'propertyhive_search_form_fields_' . $atts['id'], $form_controls );

		ob_start();

		ph_get_template( 'global/search-form.php', array( 'form_controls' => $form_controls, 'id' => $atts['id'] ) );

		return ob_get_clean();
	}

	/**
	 * List multiple properties shortcode
	 *
	 * @param array $atts
	 * @return string
	 */
	public static function properties( $atts ) {
		$atts = shortcode_atts( array(
			'columns' 			=> '2',
			'orderby' 			=> 'meta_value_num',
			'order'  			=> 'desc',
			'meta_key' 			=> '_price_actual',
			'ids'     			=> '',
			'department'		=> '', // residential-sales / residential-lettings,
			'marketing_flag'	=> '',
			'posts_per_page'	=> 10
		), $atts );

		$meta_query = array(
			array(
				'key' 		=> '_on_market',
				'value' 	=> 'yes',
			)
		);

		if ( isset($atts['department']) && $atts['department'] != '' )
		{
			$meta_query[] = array(
				'key' => '_department',
				'value' => $atts['department'],
				'compare' => '='
			);
		}

		$tax_query = array();

		if ( isset($atts['marketing_flag']) && $atts['marketing_flag'] != '' )
		{
			$tax_query[] = array(
                'taxonomy'  => 'marketing_flag',
                'terms' => array( $atts['marketing_flag'] )
            );
		}

		$args = array(
			'post_type'           => 'property',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => 1,
			'orderby'             => $atts['orderby'],
			'order'               => $atts['order'],
			'posts_per_page'      => $atts['posts_per_page'],
			'meta_query'		  => $meta_query,
			'tax_query'		  	  => $tax_query
		);

		if ( ! empty( $atts['ids'] ) ) {
			$args['post__in'] = array_map( 'trim', explode( ',', $atts['ids'] ) );
		}

		ob_start();

		//$properties = new WP_Query( apply_filters( 'propertyhive_properties_query', $args, $atts ) );
		$properties = new WP_Query( apply_filters( 'propertyhive_properties_query', $args, $atts ) );

		$propertyhive_loop['columns'] = $atts['columns'];

		if ( $properties->have_posts() ) : ?>

			<?php propertyhive_property_loop_start(); ?>

				<?php while ( $properties->have_posts() ) : $properties->the_post(); ?>

					<?php ph_get_template_part( 'content', 'property' ); ?>

				<?php endwhile; // end of the loop. ?>

			<?php propertyhive_property_loop_end(); ?>

		<?php endif;

		wp_reset_postdata();

		return '<div class="propertyhive columns-' . $atts['columns'] . '">' . ob_get_clean() . '</div>';
	}

	/**
	 * Recent Properties shortcode
	 *
	 * @access public
	 * @param array $atts
	 * @return string
	 */
	public static function recent_properties( $atts ) {
		global $propertyhive_loop;

		$atts = shortcode_atts( array(
			'per_page' 		=> '12',
			'columns' 		=> '4',
			'department' 	=> '',
			'orderby' 		=> 'date',
			'order' 		=> 'desc'
		), $atts );

		$meta_query = PH()->query->get_meta_query();

		if ( isset($atts['department']) && $atts['department'] != '' )
		{
			$meta_query[] = array(
				'key' => '_department',
				'value' => $atts['department'],
				'compare' => '='
			);
		}

		$args = array(
			'post_type'				=> 'property',
			'post_status'			=> 'publish',
			'ignore_sticky_posts'	=> 1,
			'posts_per_page' 		=> $atts['per_page'],
			'orderby' 				=> $atts['orderby'],
			'order' 				=> $atts['order'],
			'meta_query' 			=> $meta_query
		);

		ob_start();

		$properties = new WP_Query( apply_filters( 'propertyhive_shortcode_properties_query', $args, $atts ) );

		$propertyhive_loop['columns'] = $atts['columns'];

		if ( $properties->have_posts() ) : ?>

			<?php propertyhive_property_loop_start(); ?>

				<?php while ( $properties->have_posts() ) : $properties->the_post(); ?>

					<?php ph_get_template_part( 'content', 'property-recent' ); ?>

				<?php endwhile; // end of the loop. ?>

			<?php propertyhive_property_loop_end(); ?>

		<?php endif;

		wp_reset_postdata();

		return '<div class="propertyhive columns-' . $atts['columns'] . '">' . ob_get_clean() . '</div>';
	}

	/**
	 * Output featured properties
	 *
	 * @access public
	 * @param array $atts
	 * @return string
	 */
	public static function featured_properties( $atts ) {
		global $propertyhive_loop;

		$atts = shortcode_atts( array(
			'per_page' 	=> '12',
			'columns' 	=> '4',
			'orderby' 	=> 'rand',
			'order' 	=> 'desc'
		), $atts );

		$args = array(
			'post_type'				=> 'property',
			'post_status' 			=> 'publish',
			'ignore_sticky_posts'	=> 1,
			'posts_per_page' 		=> $atts['per_page'],
			'orderby' 				=> $atts['orderby'],
			'order' 				=> $atts['order'],
			'meta_query'			=> array(
				array(
					'key' 		=> '_on_market',
					'value' 	=> 'yes',
				),
				array(
					'key' 		=> '_featured',
					'value' 	=> 'yes'
				)
			)
		);

		ob_start();

		$properties = new WP_Query( apply_filters( 'propertyhive_shortcode_properties_query', $args, $atts ) );

		$propertyhive_loop['columns'] = $atts['columns'];

		if ( $properties->have_posts() ) : ?>

			<?php propertyhive_property_loop_start(); ?>

				<?php while ( $properties->have_posts() ) : $properties->the_post(); ?>

					<?php ph_get_template_part( 'content', 'property-featured' ); ?>

				<?php endwhile; // end of the loop. ?>

			<?php propertyhive_property_loop_end(); ?>

		<?php endif;

		wp_reset_postdata();

		return '<div class="propertyhive columns-' . $atts['columns'] . '">' . ob_get_clean() . '</div>';
	}

	/**
	 * Output similar properties
	 *
	 * @param array $atts
	 * @return string
	 */
	public static function similar_properties( $atts ) {

		$atts = shortcode_atts( array(
			'per_page' => '2',
			'columns' 	     => '2',
			'orderby'        => 'rand',
			'order'        => 'asc',
			'property_id'    => '',
		), $atts );

		if ($atts['property_id'] != '')
		{
			$department = get_post_meta( $atts['property_id'], '_department', true );
			$price = get_post_meta( $atts['property_id'], '_price_actual', true );
			$lower_price = $price - ($price / 10);
			$higher_price = $price + ($price / 10);
			$bedrooms = get_post_meta( $atts['property_id'], '_bedrooms', true );

			$args = array(
				'post_type'				=> 'property',
				'post__not_in' 			=> array($atts['property_id']),
				'post_status' 			=> 'publish',
				'ignore_sticky_posts'	=> 1,
				'posts_per_page' 		=> $atts['per_page'],
				'orderby' 				=> $atts['orderby'],
				'order' 				=> $atts['order'],
			);

			$meta_query = array();

			$meta_query[] = array(
				'key' 		=> '_department',
				'value' 	=> $department,
			);

			$meta_query[] = array(
				'key' 		=> '_on_market',
				'value' 	=> 'yes',
			);

			$meta_query[] = array(
				'key' 		=> '_bedrooms',
				'value' 	=> $bedrooms,
				'type'      => 'NUMERIC'
			);

			$meta_query[] = array(
				'key' 		=> '_price_actual',
				'value' 	=> $lower_price,
				'compare'   => '>=',
				'type'      => 'NUMERIC'
			);

			$meta_query[] = array(
				'key' 		=> '_price_actual',
				'value' 	=> $higher_price,
				'compare'   => '<=',
				'type'      => 'NUMERIC'
			);

			$args['meta_query'] = $meta_query;

			ob_start();

			$properties = new WP_Query( apply_filters( 'propertyhive_shortcode_properties_query', $args, $atts ) );

			$propertyhive_loop['columns'] = $atts['columns'];

			if ( $properties->have_posts() ) : ?>

				<?php propertyhive_property_loop_start(); ?>

					<?php while ( $properties->have_posts() ) : $properties->the_post(); ?>

						<?php ph_get_template_part( 'content', 'property-featured' ); ?>

					<?php endwhile; // end of the loop. ?>

				<?php propertyhive_property_loop_end(); ?>

			<?php endif;

			wp_reset_postdata();
		}
		else
		{
			echo 'No property_id passed into similar_properties shortcode';
		}

		return '<div class="propertyhive columns-' . $atts['columns'] . '">' . ob_get_clean() . '</div>';
	}
}
