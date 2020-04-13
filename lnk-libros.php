<?php

/**
 Plugin Name: LNK Libros
 Plugin URI: https://github.com/linkerx/lnk-wp-libros
 Description: Tipo de Dato Libro para Wordpress
 Version: 1
 Author: Diego
 Author URI: https://linkerx.com.ar/
 License: GPL2
 */

/**
 * Genera el tipo de dato formulario
 */
function lnk_libro_create_type(){
    register_post_type(
        'libro',
        array(
            'labels' => array(
                'name' => __('Libros','libros_name'),
                'singular_name' => __('Libro','libros_singular_name'),
                'menu_name' => __('Libros','libros_menu_name'),
                'all_items' => __('Lista de Libros','libros_all_items'),
            ),
            'description' => 'Tipo de dato de libro',
            'public' => true,
            'exclude_from_search' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 8,
            'support' => array(
                'title',
                'excerpt',
                'editor',
                'thumbnail',
                'revisions'
            ),
            "capability_type" => 'libros',
            "map_meta_cap" => true
        )
    );
}
add_action('init', 'lnk_libro_create_type');
add_post_type_support('libro', array('thumbnail','excerpt'));

function lnk_register_libro_taxonomies(){

    /**
     * Ramo
     */
    $labels = array(
        'name' => "Generos",
        'singular_name' => "Genero",
    );
    $args = array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'update_count_callback' => '_update_post_term_count',
        'query_var' => true,
        'rewrite' => array('slug'=>'genero'),
    );
    register_taxonomy('genero','libro',$args);
}
add_action( 'init', 'lnk_register_libro_taxonomies');

/**
 * agrega columnas al listado de formularios
 */
function lnk_libro_add_columns($columns) {
    global $post_type;
    if($post_type == 'libro'){
        $columns['lnk_libro_tapa'] = "Tapa";
        $columns['lnk_libro_isbn'] = "ISBN";
        $columns['lnk_libro_pdf'] = "PDF";
    }
    return $columns;
}
add_filter ('manage_posts_columns', 'lnk_libro_add_columns');

function lnk_libro_show_columns_values($column_name) {
    global $wpdb, $post;
    $id = $post->ID;

    if($post->post_type == 'libro'){
        $id = $post->ID;
        if($column_name === 'lnk_libro_tapa'){
            // imagen destacada
        } elseif($column_name === 'lnk_libro_isbn'){
            print get_post_meta($id,'lnk-isbn',true);
        } elseif($column_name === 'lnk_libro_pdf'){
            print get_post_meta($id,'lnk-pdf',true);
        }
    }
}
add_action ('manage_posts_custom_column', 'lnk_libro_show_columns_values');

/**
 * Agrega los hooks para los datos meta en el editor de libros
 */
function lnk_libro_custom_meta() {
    global $post;
    if($post->post_type == 'libro'){
        add_meta_box('lnk_libro_pdf',"Archivo PDF del Libro", 'lnk_libro_pdf_meta_box', null, 'normal','core');
    }
}
add_action ('add_meta_boxes','lnk_libro_custom_meta');

function lnk_libro_pdf_meta_box() {
    global $post;
    wp_nonce_field(plugin_basename(__FILE__), 'lnk_libro_pdf_nonce');

    if($archivo = get_post_meta( $post->ID, 'lnk_libro_pdf', true )) {
        print "PDF CARGADO: ".$archivo['url'];
    }

    $html = '<p class="description">';

    $html .= 'Seleccione su PDF aqui para reemplazar el existente.';

    $html .= '</p>';
    $html .= '<input type="file" id="lnk_libro_pdf" name="lnk_libro_pdf" value="" size="25">';
    echo $html;
}

function lnk_libro_update_edit_form() {
    echo ' enctype="multipart/form-data"';
}
add_action('post_edit_form_tag', 'lnk_libro_update_edit_form');

function lnk_libro_save_post_meta($id) {
    global $wpdb,$post_type;
    if($post_type == 'libro'){
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
                return $id;
        if (defined('DOING_AJAX') && DOING_AJAX)
                return $id;

        if(!empty($_FILES['lnk_libro_pdf']['name'])) {
            $supported_types = array('application/pdf');
            $arr_file_type = wp_check_filetype(basename($_FILES['lnk_libro_pdf']['name']));
            $uploaded_type = $arr_file_type['type'];

            if(in_array($uploaded_type, $supported_types)) {
                $upload = wp_upload_bits($_FILES['lnk_libro_pdf']['name'], null, file_get_contents($_FILES['lnk_libro_pdf']['tmp_name']));
                if(isset($upload['error']) && $upload['error'] != 0) {
                    wp_die('There was an error uploading your file. The error is: ' . $upload['error']);
                } else {
                    update_post_meta($id, 'lnk_libro_pdf', $upload);
                }
            }
            else {
                wp_die("The file type that you've uploaded is not a PDF.");
            }
        }
    }


}
add_action('save_post','lnk_libro_save_post_meta');
