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
        //TODO: checkboxes für mehrere Jahrgänge:  rpi_report/?vintage=2023,2022 sucht Inhalte für 2022 und 2023

        add_action('init', array($this, 'force_create_report_with_form'));
        add_action('init', array($this, 'register_custom_post_types'));
        add_action('admin_init', array($this, 'add_reporter_role'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('init', array($this, 'create_question_block'));
        add_action('gform_after_save_form', array($this, 'create_taxonomies_and_blocks'), 10, 2);
        add_action('gform_after_submission', array($this, 'create_report'), 10, 2);
        add_action('blocksy:loop:before', array($this, 'display_questions'));
        add_action('blocksy:loop:before', array($this, 'add_vintage_select_boxes'));
        add_action('blocksy:loop:card:start', array($this, 'add_parent_report_link'));
        add_action('blocksy:single:content:bottom', array($this, 'add_editing_button_to_report_head'));
        add_action('save_post_rpi_report', array($this, 'update_report_sections_with_parent'), 10, 3);
        add_action('trashed_post', array($this, 'delete_report_sections'), 10, 2);

        add_action('pre_get_posts', array($this, 'query_vintage'));
        add_filter( 'get_terms',  array($this, 'filter_questions'), 10,4 );
        add_shortcode('go_to_last_post', array($this, 'go_to_last_post'));

        add_action('enqueue_block_assets', array($this, 'blockeditor_js'));
        add_action('wp_enqueue_scripts', array($this, 'report_frontend_js'));
        add_action('wp_enqueue_scripts', array($this, 'frontpage_jquery'));

    }
    public function get_question_terms(){

        $vintage = get_query_var('vintage');
        $trans_slug= is_array($vintage)?implode('_',$vintage):$vintage;
        $trans_slug='question_terms_'.$trans_slug;

        if(!$terms = get_transient($trans_slug)){
            $search_args = array(
                'post_type'      => 'rpi_report_section', // Der Beitragstyp, nach dem gesucht wird. Ändere dies gegebenenfalls, wenn du nach anderen Beitragstypen suchst.
                'post_status'    => 'publish', // Nur veröffentlichte Beiträge abrufen
                'tax_query'      => array(
                    array(
                        'taxonomy' => 'vintage', // Die gewünschte Taxonomie "vintage"
                        'field'    => 'slug',    // Das Feld, nach dem gesucht wird (hier verwenden wir den Slug des Terms).
                        'terms'    => get_query_var('vintage'),    // Der Term, nach dem gesucht wird (hier '2023').
                    ),
                ),
                'fields'         => 'ids', // Wir wollen nur die Post-IDs zurückgeben.
                'posts_per_page' => -1,   // Hier setzen wir -1, um alle passenden Beiträge zu erhalten.
            );


            $allowed_post_ids = get_posts($search_args);

            $terms = get_terms(array(
                'taxonomy'   => 'question',
                'object_ids' =>$allowed_post_ids
            ));
            set_transient($trans_slug,$terms, DAY_IN_SECONDS );
        }

        return $terms;

    }

    public function display_questions(){
        if(is_tax()){
            $active = ('' === get_query_var('question'))?'active ':'';

            echo '<div class="ct-dynamic-filter " data-type="buttons"><a href="/rpi_report_section/" class="'.$active.'">Alle</a>';
            foreach ($this->get_question_terms() as $term){
                if(is_a($term,'WP_Term')){
                    $active = ($term->slug === get_query_var('question'))?'active ':'';
                    $url = home_url('question').'/'.$term->slug.'?vintage='.get_query_var('vintage');
                    ?>
                    <a href="<?php echo $url ;?>" class="<?php echo $active; ?>"><?php echo $term->name ;?></a>
                    <?php
                }

            }
            echo '</div>';
        }


    }

    public function filter_questions($terms, $taxonomy, $query_vars, $term_query){

        return $terms;
    }

    /**
     * pre_get_posts  action
     *
     * manipuliert die WP_Query args: vintage wird auf aktuelles Jahr gesetzt, sofern nicht in der Url angefordert
     *
     * @param WP_Query $query
     */
    public function query_vintage(WP_Query $query)
    {
        if (!is_admin() && $query->is_main_query() && get_query_var('vintage')) {
            $query->set('post_type', 'rpi_report_section');
            if(is_tax('vintage')){
                $query->set('post_type', 'rpi_report');
            }
        }elseif (!is_admin() && $query->is_main_query() && !get_query_var('vintage') && isset($_GET['current']) && 'year'===$_GET['current']) {
                $query->set('vintage', date('Y'));
        }else {
            if (!is_admin() && $query->is_main_query()) {
                if(is_tax('institute')){
                    $query->set('post_type', 'rpi_report');
                }
                if(is_tax('question') && !get_query_var('vintage')){
                    $query->set('vintage', date('Y'));
                }
            }
        }

    }


    /**
     * Stellt sicher, das ein Bericht nur über das formular erstellt werden kann
     * /post-new.php?post_type=rpi_report -> /eingabeformular
     */
    public function force_create_report_with_form()
    {
        if (strpos($_SERVER['SCRIPT_NAME'], 'post-new.php') > 0 && $_GET['post_type'] === 'rpi_report') {
            wp_redirect(home_url() . '/eingabeformular');
        }
    }


    public function register_custom_post_types()
    {
        /**
         * Post Type: report
         */

        $labels = [
            "name" => __("Jahresberichte", "blocksy"),
            "menu_name" => __("Berichte", "blocksy"),
            "singular_name" => __("Bericht", "blocksy"),
            "archives" => __("Jahresberichte der Institute", "blocksy"),
        ];

        $args = [
            "label" => __("Jahresberichte der Institute", "blocksy"),
            "labels" => $labels,
            "description" => "Zum Ausdrucken bitte die Druckfunktion des Browsers nutzen.",
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
            "name" => __("Teilberichte", "blocksy"),
            "singular_name" => __("Teilbericht", "blocksy"),
            "archives" => __("Fragen", "blocksy"),
        ];

        $args = [
            "label" => __("Teilberichte", "blocksy"),
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

    /**
     * edit Rechte für CPT rpi_report setzen
     */
    public function add_reporter_role()
    {

        //neue Rolle Reporter:in erstellen
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
        $role->add_cap('delete_published_reports');
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
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M11 18h2v-2h-2v2zm1-16C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm0-14c-2.21 0-4 1.79-4 4h2c0-1.1.9-2 2-2s2 .9 2 2c0 2-3 1.75-3 5h2c0-2.25 3-2.5 3-5 0-2.21-1.79-4-4-4z"/></svg>',
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

    /**
     * generiert einen Beitrag des CPT rpi_report aus dem Formular
     *
     * @param array $entry //GravityForm Eintrag
     * @param $form //GravityForm Formular
     */
    public function create_report($entry, $form)
    {
        $title = $form["title"] ? $form["title"] : false;

        if ($title !== "Jahresbericht") {
            return;
        }

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

            $title .= " : " . $institute_name . " : " . date('Y');


            $report = wp_insert_post(array(
                'post_title' => $title,
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

    public function add_vintage_select_boxes()
    {

        if (get_post_type() === 'rpi_report_section') {
            echo '<div style="margin-bottom: 15px" class="vintage-filter">';
            echo '<form action="/rpi_report_section/" method="get" name="vintage-form" id="vintage">';
            $vintages = get_terms(array('taxonomy' => 'vintage'));
            foreach ($vintages as $vintage)
                if (is_a($vintage, 'WP_Term')) {
                    echo '<div style="margin: 5px" class="button">';
                    echo '<input type="checkbox" id="' . $vintage->slug . '" name="vintage" value="' . $vintage->slug . '">';
                    echo '<label style="margin: 0 0 0 5px" " for="' . $vintage->slug . '"> Jahrgang ' . $vintage->slug . '</label><br>';
                    echo '</div>';
                }
            echo '<input type="submit" id="vintage-button" style="margin: 5px" value="Vergleichen" class="button">';
            echo '</form>';
            echo '</div>';
        }
    }


    public function add_parent_report_link()
    {

        if (get_post_type() === 'rpi_report_section') {
            $parent_id = get_post_meta(get_the_ID(), 'report_parent', true);
            $institutes = get_the_terms(get_the_ID(), 'institute');
            $institute = reset($institutes);
            $vintages = get_the_terms(get_the_ID(), 'vintage');
            $vintage = reset($vintages);
            echo '<div class="report-parent-link" ><a href="' . get_the_permalink($parent_id) . '">' . $institute->name . '</a>';
            //if (get_query_var('vintage')) {
                echo '<a class="vintage-card-link" href="?vintage=' . $vintage->slug . '">' . $vintage->slug . '</a>';
            //}
            echo '</div>';


        }
    }

    public function add_editing_button_to_report_head()
    {
        if (get_post_type() === 'rpi_report' && current_user_can('edit_post', get_the_ID())) {
            echo '<div style="text-align: center; margin-top: 30px"><a class="button" href="' . get_edit_post_link(get_the_ID()) . '">Bericht bearbeiten</a></div>';
        }
    }

    /**
     * on_save_rpi_repost action
     *
     * itteriert alle Blöcke des Berichtes und schreibt jeweils die Frage und alle nachfolgenden Blockinhalte,
     * die keine Fragen sind, mit Hilfe von create_report_part() in den CPT rpi_report_section
     *
     * @param $post_ID
     * @param WP_Post $post
     * @param $update
     *
     * @return void
     */
    public function update_report_sections_with_parent($post_ID, WP_Post $post, $update)
    {
        if ($update) {
            $report_parts = array();
            $report_blocks = parse_blocks($post->post_content);
            $terms = wp_get_post_terms($post_ID, 'institute');
            $institute = reset($terms);
            $terms = wp_get_post_terms($post_ID, 'vintage');
            $vintage = reset($terms);
            if (!$vintage) {
                $vintage = get_term_by('slug', date('Y'), 'vintage');
            }

            $report_section_ids = get_post_meta($post_ID, 'report_parts', true);
            foreach ($report_section_ids as $report_section_id) {
                wp_delete_post($report_section_id, true);
            }


            $part_content = '';
            $question_slug = '';

            foreach ($report_blocks as $block_key => $report_block) {
                if ($report_block['blockName'] === 'lazyblock/report-question') {

                    //falls vorhanden, Inhalte zur letzten Frage in Teilbericht
                    $report_parts[] = $this->create_report_part($post, $institute, $vintage, $question_slug, $part_content);

                    $question_slug = $report_block['attrs']['term_slug'];
                    //Sammlung zurücksetzen
                    $part_content = '';

                    /*
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
                    */
                } else {
                    //Sammle alle Blockinhlate, die unterhalb einer Frage stehen in
                    $part_content .= $report_block['innerHTML'];
                }

            }
            //Inhalte zur letzten Frage in Teilbericht
            $report_parts[] = $this->create_report_part($post, $institute, $vintage, $question_slug, $part_content);

            update_post_meta($post_ID, 'report_parts', $report_parts);

        }
    }

    /**
     * trashed_post action
     * löscht alle Teilberichte, wenn der Bericht gelöscht wird
     *
     * @param $post_ID
     * @param WP_Post $post
     *
     * @return void
     */
    public function delete_report_sections($post_ID)
    {

        if ('rpi_report' == get_post_type($post_ID)) {

            $report_section_ids = get_post_meta($post_ID, 'report_parts', true);
            foreach ($report_section_ids as $report_section_id) {
                wp_delete_post($report_section_id, true);
            }
        }
    }

    /**
     * Schreibt Frage und Antwort in einen den Post_Type rpi_report_section
     *
     * @param WP_Post $report //Bericht
     * @param WP_Term $institute //Institut Taxononomie
     * @param WP_Term $vintage //Jahrgang Taxononomie
     * @param string $question_slug //slug des WP_TERM Question
     * @param string $content //alle Inhalte unterhalb einer Frage
     *
     * @return int|WP_Error
     */
    private function create_report_part(WP_Post $report, WP_Term $institute, WP_Term $vintage, $question_slug, $content)
    {
        if (!empty(trim(strip_tags($content))) && $report->post_status === 'publish') {
            $question = get_term_by('slug', $question_slug, 'question');
            if (is_a($question, 'WP_Term')) {

                $report_part_id = wp_insert_post(array(
                    'post_author' => $report->post_author,
                    'post_title' => $question->name . " : " . $institute->name . " : " . $vintage->name,
                    'post_status' => 'publish',
                    'post_type' => 'rpi_report_section',
                    'post_content' => $content,
                    'meta_input' => array('report_parent' => $report->ID),
                ));
                wp_set_object_terms($report_part_id, $institute->slug, 'institute');
                wp_set_object_terms($report_part_id, $vintage->slug, 'vintage');
                wp_set_object_terms($report_part_id, $question->slug, 'question');
            }
        }
        return $report_part_id;
    }

    /**
     * @param array $atts //param type set the post_type
     *
     * @return string  //HTML Link zum letzten Beitrags des Autors
     */
    public function go_to_last_post($atts = array('type' => 'post'))
    {

        $post_type = $atts['type'];

        $posts = get_posts(array(
            'post_type' => $post_type,
            'numberposts' => 1,
            'author' => get_current_user_id()
        ));

        $post = reset($posts);
        return '<a class="button" href="' . get_the_permalink($post) . '">Beitrag anzeigen</a>';
    }

    function blockeditor_js()
    {
        if (!is_admin()) return;
        wp_enqueue_script(
            'custom_editor',
            plugin_dir_url(__FILE__) . '/custom_editor.js',
            array(),
            '1.0',
            true
        );

    }

    function report_frontend_js()
    {
        wp_enqueue_script(
            'report_frontend',
            plugin_dir_url(__FILE__) . '/report_frontend.js',
            array(),
            '1.1',
            true
        );
        wp_enqueue_style(
            'report_frontend_style',
            plugin_dir_url(__FILE__) . '/custom.css',
            array(),
            '1.1'
        );
    }

    function frontpage_jquery()
    {
        wp_enqueue_script('jquery');
    }

}

new InstituteReport();
