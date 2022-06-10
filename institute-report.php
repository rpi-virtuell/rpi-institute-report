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
        add_action('init', array($this, 'create_question_block'));
        add_action('gform_after_save_form', array($this, 'create_taxonomies_and_blocks'), 10, 2);
        add_action('gform_after_submission', array($this, 'create_report_section'), 10, 2);
        add_action('blocksy:loop:card:end', array($this, 'add_parent_report_link'));
        add_action('blocksy:single:content:bottom', array($this, 'add_editing_button_to_report_head'));
        add_action('edit_post', array($this, 'update_report_sections_with_parent'), 10, 2);
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
            "capability_type" => "post",
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
        $report_parts = array();
        $content = '';
        $institute = get_term_by('id', $entry[23], 'institute');
        $vintage = get_term_by('name', date('Y'), 'vintage');
        if (!empty($institute) && !is_wp_error($institute)) {
            $institute_name = $institute->name;
        } else {
            $institute_name = "";
        }

        $report = wp_insert_post(array(
            'post_title' => $form['title'] . " : " . $institute_name . " : " . date('Y'),
            'post_type' => 'rpi_report',
            'post_status' => 'publish'
        ));

        if (is_array($form['fields'])) {
            foreach ($form['fields'] as $field) {
                if (is_a($field, 'GF_Field_Textarea')) {
                    $answer = $entry[$field->id];
                    $report_part = wp_insert_post(array(
                        'post_title' => $field->adminLabel . " : " . $institute_name . " : " . date('Y'),
                        'post_status' => 'publish',
                        'post_type' => 'rpi_report_section',
                        'post_content' => $answer,
                        'meta_input' => array('report_parent' => $report),
                    ));
                    $question = get_term_by('name', $field->adminLabel, 'question');
                    wp_set_object_terms($report_part, $institute->slug, 'institute');
                    wp_set_object_terms($report_part, $vintage->slug, 'vintage');
                    wp_set_object_terms($report_part, $question->slug, 'question');
                    $content .= '<!-- wp:lazyblock/report-question {"term_slug":"' . $field->id . '", "lock":{"move":true, "remove":true}} /-->'
                        . '<p>'
                        . $answer
                        . '</p>';
                    if (!is_wp_error($report_part)) {
                        $report_parts[] = $report_part;
                    }
                }
            }

            wp_update_post(array(
                'ID' => $report,
                'post_content' => $content,
                'meta_input' => array('report_parts' => $report_parts)
            ));
            wp_set_object_terms($report, $institute->slug, 'institute');
            wp_set_object_terms($report, $vintage->slug, 'vintage');

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

    public function update_report_sections_with_parent($post_ID, $post)
    {
        if (get_post_type($post_ID) === 'rpi_report' && is_a($post, 'WP_Post')) {
            $report_parts = array();
            $report_blocks = parse_blocks($post->post_content);
            $report_section_ids = get_post_meta($post_ID, 'report_parts', true);
            $terms = wp_get_post_terms($post_ID, 'institute');
            $institute = reset($terms);
            $terms = wp_get_post_terms($post_ID, 'vintage');
            $vintage = reset($terms);
            foreach ($report_blocks as $blockkey => $report_block) {
                if ($report_block['blockname'] === 'lazyblock/report-question') {
                    if ($report_blocks[$blockkey + 1]['blockname'] === null) {
                        $question = get_term_by('slug', 'question', $report_block['attrs']['term_slug']);
                        if (is_a($question, 'WP_Term')) {
                            $report_part = wp_insert_post(array(
                                'post_title' => $question->name . " : " . $institute->name . " : " . $vintage,
                                'post_status' => 'publish',
                                'post_type' => 'rpi_report_section',
                                'post_content' => $report_blocks[$blockkey + 1]['innerHTML'],
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
            wp_update_post(array(
                'ID' => $post_ID,
                'meta_input' => array('report_parts' => $report_parts)
            ));
            foreach ($report_section_ids as $report_section_id) {
                wp_delete_post($report_section_id);
            }
        }
    }

}

new InstituteReport();