<?php

/*
Plugin Name: rpi Insitute Report
Plugin URI: https://github.com/rpi-virtuell/rpi-institute-report
Description: Wordpress Plugin to handle institute reports
Version: 1.0
Author: Daniel Reintanz
Author URI: https://github.com/FreelancerAMP
License: A "Slug" license name e.g. GPL2
*/

class InstituteReport
{
    function __construct()
    {
        add_action('init', array($this, 'register_custom_post_types'));
    }

    public function register_custom_post_types()
    {
        /**
         * Post Type: report
         */

        $labels = [
            "name" => __("Berichte", "twentytwentytwo"),
            "singular_name" => __("Bericht", "twentytwentytwo"),
        ];

        $args = [
            "label" => __("Berichte", "twentytwentytwo"),
            "labels" => $labels,
            "description" => "",
            "public" => true,
            "publicly_queryable" => true,
            "show_ui" => true,
            "show_in_rest" => true,
            "rest_base" => "",
            "rest_controller_class" => "WP_REST_Posts_Controller",
            "has_archive" => true,
            "show_in_menu" => true,
            "show_in_nav_menus" => true,
            "delete_with_user" => false,
            "exclude_from_search" => false,
            'capability_type' => array('report', 'report'),
            "capabilities" => array(),
            "map_meta_cap" => true,
            "hierarchical" => false,
            "can_export" => true,
            "rewrite" => ["slug" => "report", "with_front" => true],
            "query_var" => true,
            "menu_icon" => "dashicons-list-view",
            "supports" => [
                'author'
            ],
            'taxonomies' => ['',],
            "show_in_graphql" => false,
        ];

        register_post_type("report", $args);


        /********************************************************************************
         * Post Type: report section
         */

        $labels = [
            "name" => __("Berichts Teile", "blocksy"),
            "singular_name" => __("Berichts Teil", "blocksy"),
        ];

        $args = [
            "label" => __("Berichts Teile", "blocksy"),
            "labels" => $labels,
            "description" => "",
            "public" => true,
            "publicly_queryable" => true,
            "show_ui" => true,
            "show_in_rest" => true,
            "rest_base" => "",
            "rest_controller_class" => "WP_REST_Posts_Controller",
            "has_archive" => false,
            "show_in_menu" => true,
            "show_in_nav_menus" => true,
            "delete_with_user" => false,
            "exclude_from_search" => false,
            "capability_type" => "post",
            "map_meta_cap" => true,
            "hierarchical" => false,
            "can_export" => false,
            "rewrite" => ["slug" => "report_section", "with_front" => true],
            "query_var" => true,
            "menu_icon" => "dashicons-yes-alt",
            "supports" => ["title", "editor", "thumbnail"],
            "show_in_graphql" => false,
            'taxonomies' => ['institut, jahrgang, frage']
        ];

        register_post_type("report_section", $args);


    }
}

new InstituteReport();