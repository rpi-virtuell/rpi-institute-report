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

        //TODO: vintage query muss hinzugefügt werden damit automatisch nach dem momentanen erstellungs jahr gesucht wird

        add_action('init', array($this, 'register_custom_post_types'));
        add_action('admin_init', array($this, 'add_reporter_role'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('init', array($this, 'create_question_block'));
        add_action('gform_after_save_form', array($this, 'create_taxonomies_and_blocks'), 10, 2);
        add_action('gform_after_submission', array($this, 'create_report_section'), 10, 2);
        add_action('blocksy:loop:card:end', array($this, 'add_parent_report_link'));
        add_action('blocksy:single:content:bottom', array($this, 'add_editing_button_to_report_head'));
        add_action('save_post', array($this, 'update_report_sections_with_parent'), 10, 3);
    }


    public function register_custom_post_types()
    {
        /**
         * Post Type: report
         */

        $labels = [
            "name" => __("Berichte", "blocksy"),
            "singular_name" => __("Bericht", "blocksy"),
        ];

        $args = [
            "label" => __("Berichte", "blocksy"),
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
            'capability_type' => array('report', 'reports'),
            "capabilities" => array(
                'edit_posts' => 'edit_reports',
                'edit_others_posts' => 'edit_others_reports',
                'read_private_posts' => 'read_private_reports',
                'publish_posts' => 'publish_reports',
                'read_post' => 'read_report',
                'delete_others_posts' => 'delete_others_reports',
                'delete_published_posts' => 'delete_published_reports',
                'delete_posts' => 'delete_reports',
            ),
            "map_meta_cap" => true,
            "hierarchical" => false,
            "can_export" => false,
            "rewrite" => ["slug" => "rpi_report", "with_front" => true],
            "query_var" => true,
            "menu_icon" => "dashicons-media-spreadsheet",
            "supports" => ["title", "author", "editor", "custom-fields", "page-attributes"],
            'taxonomies' => ['institute', 'vintage'],
            "show_in_graphql" => false,
        ];

        register_post_type("rpi_report", $args);


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
            "has_archive" => true,
            "show_in_menu" => true,
            "show_in_nav_menus" => true,
            "delete_with_user" => false,
            "exclude_from_search" => false,
            "capability_type" => "post",
            "map_meta_cap" => true,
            "hierarchical" => false,
            "can_export" => false,
            "rewrite" => ["slug" => "rpi_report_section", "with_front" => true],
            "query_var" => true,
            "menu_icon" => "dashicons-media-text",
            "supports" => ["title", "author", "editor", "custom-fields", "page-attributes"],
            "show_in_graphql" => false,
            'taxonomies' => ['institute', 'vintage', 'question']
        ];
        register_post_type("rpi_report_section", $args);


    }

    public function add_reporter_role()
    {

        add_role('reporter', 'Reporter:in');

        $role = get_role('reporter');
        $role->add_cap('read');
        $role->add_cap('level_2');
        $role->add_cap('level_1');
        $role->add_cap('level_0');
        $role->add_cap('read_report');
        $role->add_cap('edit_reports');
        $role->add_cap('edit_published_reports');
        $role->add_cap('delete_reports');
        $role->add_cap('publish_reports');


        $roles = ['administrator', 'editor'];

        foreach ($roles as $roleslug) {

            $role = get_role($roleslug);


            $role->add_cap('read_report');
            $role->add_cap('edit_reports');
            $role->add_cap('edit_published_reports');
            $role->add_cap('delete_reports');
            $role->add_cap('publish_reports');

            $role->add_cap('edit_others_reports');
            $role->add_cap('edit_published_reports');
            $role->add_cap('delete_published_reports');
            $role->add_cap('read_private_reports');
            $role->add_cap('edit_private_reports');
            $role->add_cap('delete_private_reports');
            $role->add_cap('delete_others_reports');

        }


    }

    function register_taxonomies()
    {

        /**
         * Taxonomy: Fragen.
         */

        $labels = [
            "name" => __("Fragen", "blocksy"),
            "singular_name" => __("Frage", "blocksy"),
        ];


        $args = [
            "label" => __("Fragen", "blocksy"),
            "labels" => $labels,
            "public" => true,
            "publicly_queryable" => true,
            "hierarchical" => true,
            "show_ui" => true,
            "show_in_menu" => true,
            "show_in_nav_menus" => true,
            "query_var" => true,
            "rewrite" => ['slug' => 'question', 'with_front' => true,],
            "show_admin_column" => true,
            "show_in_rest" => true,
            "show_tagcloud" => false,
            "rest_base" => "question",
            "rest_controller_class" => "WP_REST_Terms_Controller",
            "rest_namespace" => "wp/v2",
            "show_in_quick_edit" => true,
            "sort" => true,
            "show_in_graphql" => false,
        ];
        register_taxonomy("question", ["rpi_report_section"], $args);

        /**
         * Taxonomy: Institute.
         */

        $labels = [
            "name" => __("Institute", "blocksy"),
            "singular_name" => __("Institut", "blocksy"),
        ];


        $args = [
            "label" => __("Institute", "blocksy"),
            "labels" => $labels,
            "public" => true,
            "publicly_queryable" => true,
            "hierarchical" => true,
            "show_ui" => true,
            "show_in_menu" => true,
            "show_in_nav_menus" => true,
            "query_var" => true,
            "rewrite" => ['slug' => 'institute', 'with_front' => true, 'hierarchical' => true,],
            "show_admin_column" => true,
            "show_in_rest" => true,
            "show_tagcloud" => false,
            "rest_base" => "institute",
            "rest_controller_class" => "WP_REST_Terms_Controller",
            "rest_namespace" => "wp/v2",
            "show_in_quick_edit" => true,
            "sort" => false,
            "show_in_graphql" => false,
        ];
        register_taxonomy("institute", ["rpi_report", "rpi_report_section"], $args);

        /**
         * Taxonomy: Jahrgänge.
         */

        $labels = [
            "name" => __("Jahrgänge", "blocksy"),
            "singular_name" => __("Jahrgang", "blocksy"),
        ];


        $args = [
            "label" => __("Jahrgänge", "blocksy"),
            "labels" => $labels,
            "public" => true,
            "publicly_queryable" => true,
            "hierarchical" => true,
            "show_ui" => true,
            "show_in_menu" => true,
            "show_in_nav_menus" => true,
            "query_var" => true,
            "rewrite" => ['slug' => 'vintage', 'with_front' => true, 'hierarchical' => true,],
            "show_admin_column" => true,
            "show_in_rest" => true,
            "show_tagcloud" => false,
            "rest_base" => "vintage",
            "rest_controller_class" => "WP_REST_Terms_Controller",
            "rest_namespace" => "wp/v2",
            "show_in_quick_edit" => true,
            "sort" => true,
            "show_in_graphql" => false,
        ];
        register_taxonomy("vintage", ["rpi_report", "rpi_report_section"], $args);
    }

    public function create_question_block()
    {
        if (function_exists('lazyblocks')) :

            lazyblocks()->add_block(array(
                'id' => 186,
                'title' => 'Frage',
                'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
    <rect opacity="0.25" width="15" height="15" rx="4" transform="matrix(-1 0 0 1 22 7)" fill="currentColor" />
    <rect width="15" height="15" rx="4" transform="matrix(-1 0 0 1 17 2)" fill="currentColor" />
    </svg>
    ',
                'keywords' => array(),
                'slug' => 'lazyblock/report-question',
                'description' => '',
                'category' => 'text',
                'category_label' => 'text',
                'supports' => array(
                    'customClassName' => true,
                    'anchor' => false,
                    'align' => array(
                        0 => 'wide',
                        1 => 'full',
                    ),
                    'html' => false,
                    'multiple' => true,
                    'inserter' => true,
                ),
                'ghostkit' => array(
                    'supports' => array(
                        'spacings' => false,
                        'display' => false,
                        'scrollReveal' => false,
                        'frame' => false,
                        'customCSS' => false,
                    ),
                ),
                'controls' => array(
                    'control_a7288540c9' => array(
                        'type' => 'hidden',
                        'name' => 'term_slug',
                        'default' => '',
                        'label' => '',
                        'help' => '',
                        'child_of' => '',
                        'placement' => 'inspector',
                        'width' => '100',
                        'hide_if_not_selected' => 'false',
                        'save_in_meta' => 'false',
                        'save_in_meta_name' => '',
                        'required' => 'false',
                        'placeholder' => '',
                        'characters_limit' => '',
                    ),
                ),
                'code' => array(
                    'output_method' => 'php',
                    'editor_html' => '',
                    'editor_callback' => '',
                    'editor_css' => '',
                    'frontend_html' => '<h3>
    <?php
    $term = get_term_by(\'slug\',$attributes[\'term_slug\'],\'question\');
    if (is_a($term,\'WP_Term\')) {
        echo($term->description);
    }
    ?>
    </h3>',
                    'frontend_callback' => '',
                    'frontend_css' => '',
                    'show_preview' => 'always',
                    'single_output' => true,
                ),
                'condition' => array(),
            ));

        endif;

    }

    public function create_taxonomies_and_blocks($form, $is_new)
    {
        if (is_array($form['fields'])) {
            foreach ($form['fields'] as $field) {
                if (is_a($field, 'GF_Field_Textarea')) {
                    wp_insert_term($field->adminLabel, 'question', array('slug' => $field->id, 'description' => $field->label));
                }
            }
        }

    }

    public function create_report_section($entry, $form)
    {
        $content = '';
        $institute = get_term_by('id', $entry[23], 'institute');
        $vintage = get_term_by('name', date('Y'), 'vintage');
        if (!empty($institute) && !is_wp_error($institute)) {
            $institute_name = $institute->name;
        } else {
            $institute_name = "";
        }

        if (is_array($form['fields'])) {
            foreach ($form['fields'] as $field) {
                if (is_a($field, 'GF_Field_Textarea')) {
                    $answer = $entry[$field->id];
                    $content .= '<!-- wp:lazyblock/report-question {"term_slug":"' . $field->id . '", "lock":{"move":true, "remove":true}} /-->'
                        . '<p>'
                        . $answer
                        . '</p>';
                }
            }

            $report = wp_insert_post(array(
                'post_title' => $form['title'] . " : " . $institute_name . " : " . date('Y'),
                'post_type' => 'rpi_report',
                'post_content' => $content,
            ));
            wp_set_object_terms($report, $institute->slug, 'institute');
            wp_set_object_terms($report, $vintage->slug, 'vintage');
            wp_update_post(array(
                'ID' => $report,
                'post_status' => 'publish'));
        }
    }

    public function add_parent_report_link()
    {
        if (get_post_type() === 'rpi_report_section') {
            $parent_id = get_post_meta(get_the_ID(), 'report_parent', true);
            $institutes = get_the_terms(get_the_ID(), 'institute');
            $institute = reset($institutes);
            echo '<div class="report-parent-link" ><a href="' . get_the_permalink($parent_id) . '">' . $institute->name . '</a></div>';
        }
    }

    public function add_editing_button_to_report_head()
    {
        if (get_post_type() === 'rpi_report' && current_user_can('edit_post', get_the_ID())) {
            echo '<div style="text-align: center; margin-top: 30px"><a class="button" href="' . get_edit_post_link(get_the_ID()) . '">Bericht bearbeiten</a></div>';
        }
    }

    public function update_report_sections_with_parent($post_ID, $post, $update)
    {
        if (get_post_type($post_ID) === 'rpi_report' && is_a($post, 'WP_Post') && $update) {
            $report_parts = array();
            $report_blocks = parse_blocks($post->post_content);
            $report_section_ids = get_post_meta($post_ID, 'report_parts', true);
            $terms = wp_get_post_terms($post_ID, 'institute');
            $institute = reset($terms);
            $terms = wp_get_post_terms($post_ID, 'vintage');
            $vintage = reset($terms);
            foreach ($report_blocks as $block_key => $report_block) {
                if ($report_block['blockName'] === 'lazyblock/report-question') {
                    if ($report_blocks[$block_key + 1]['blockName'] === null) {
                        $question = get_term_by('slug', $report_block['attrs']['term_slug'], 'question');
                        if (is_a($question, 'WP_Term') && !empty(trim(strip_tags($report_blocks[$block_key + 1]['innerHTML'])))) {
                            $report_part = wp_insert_post(array(
                                'post_author' => $post->post_author,
                                'post_title' => $question->name . " : " . $institute->name . " : " . $vintage->name,
                                'post_status' => 'publish',
                                'post_type' => 'rpi_report_section',
                                'post_content' => $report_blocks[$block_key + 1]['innerHTML'],
                                'meta_input' => array('report_parent' => $post_ID),
                            ));
                            wp_set_object_terms($report_part, $institute->slug, 'institute');
                            wp_set_object_terms($report_part, $vintage->slug, 'vintage');
                            wp_set_object_terms($report_part, $question->slug, 'question');
                            $report_parts[] = $report_part;
                        }
                    }
                }
            }
            update_post_meta($post_ID, 'report_parts', $report_parts);
            foreach ($report_section_ids as $report_section_id) {
                wp_delete_post($report_section_id, true);
            }
        }
    }

}

new InstituteReport();