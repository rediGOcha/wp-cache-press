<?php
defined( 'ABSPATH' ) || die( 'Cheatin&#8217; uh?' );

if ( defined( 'AIOSEOP_VERSION' ) ) :
	$all_in_one_seo_xml_options = get_option( 'aioseop_options' );
	/**
	 * Improvement with All in One SEO Pack: auto-detect the XML sitemaps for the preload option
	 *
	 * @since 2.8
	 * @author Remy Perona
	 */
	if ( isset( $all_in_one_seo_xml_options['modules']['aiosp_feature_manager_options']['aiosp_feature_manager_enable_sitemap'] ) && 'on' === $all_in_one_seo_xml_options['modules']['aiosp_feature_manager_options']['aiosp_feature_manager_enable_sitemap'] ) {
		/**
		 * Add All in One SEO Sitemap option to WP Rocket options
		 *
		 * @since 2.8
		 * @author Remy Perona
		 *
		 * @param Array $options Array of WP Rocket options.
		 * @return Array Updated array of WP Rocket options
		 */
		function rocket_add_all_in_one_seo_sitemap_option( $options ) {
			$options['all_in_one_seo_xml_sitemap'] = 0;

			return $options;
		}
		add_filter( 'rocket_first_install_options', 'rocket_add_all_in_one_seo_sitemap_option' );

		/**
		 * Sanitize the AIO SEO option value
		 *
		 * @since 2.8
		 * @author Remy Perona
		 *
		 * @param Array $inputs Array of inputs values.
		 * @return Array Updated array of inputs $values
		 */
		function rocket_all_in_one_seo_sitemap_option_sanitize( $inputs ) {
			$inputs['all_in_one_seo_xml_sitemap'] = ! empty( $inputs['all_in_one_seo_xml_sitemap'] ) ? 1 : 0;

			return $inputs;
		}
		add_filter( 'rocket_inputs_sanitize', 'rocket_all_in_one_seo_sitemap_option_sanitize' );

		/**
		 * Add All in One SEO Sitemap to the preload list
		 *
		 * @since 2.8
		 * @author Remy Perona
		 *
		 * @param Array $sitemaps Array of sitemaps to preload.
		 * @return Array Updated array of sitemaps to preload
		 */
		function rocket_add_all_in_one_seo_sitemap( $sitemaps ) {
			if ( get_rocket_option( 'all_in_one_seo_xml_sitemap', false ) ) {
				$all_in_one_seo_xml = get_option( 'aioseop_options' );
				$sitemaps[] = trailingslashit( home_url() ) . $all_in_one_seo_xml['modules']['aiosp_sitemap_options']['aiosp_sitemap_filename'] . '.xml';
			}

			return $sitemaps;
		}
		add_filter( 'rocket_sitemap_preload_list', 'rocket_add_all_in_one_seo_sitemap' );

		/**
		 * Add All in One SEO Sitemap sub-option on WP Rocket settings page
		 *
		 * @since 2.8
		 * @author Remy Perona
		 *
		 * @param Array $options Array of WP Rocket options.
		 * @return Array Updated array of WP Rocket options
		 */
		function rocket_sitemap_preload_all_in_one_seo_option( $options ) {
			$options[] = array(
				'parent'        => 'sitemap_preload',
				 'type'         => 'checkbox',
				 'label'        => __( 'All in One SEO XML sitemap', 'rocket' ),
				 'label_for'    => 'all_in_one_seo_xml_sitemap',
				 'label_screen' => sprintf( __( 'Preload the sitemap from the %s plugin', 'rocket' ), 'All in One SEO Pack' ),
				 'default'      => 0,
			 );
			 $options[] = array(
				 'parent'       => 'sitemap_preload',
				 'type'			=> 'helper_description',
				 'name'			=> 'all_in_one_seo_xml_sitemap_desc',
				 'description'  => sprintf( __( 'We automatically detected the sitemap generated by the %s plugin. You can check the option to preload it.', 'rocket' ), 'All in One SEO Pack' ),
			 );
			return $options;
		}
		add_filter( 'rocket_sitemap_preload_options', 'rocket_sitemap_preload_all_in_one_seo_option' );
	}
endif;
