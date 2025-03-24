<?php
/*
Plugin Name: Project Image Uploader
Description: Upload images with project name, description, auto-rename, and preview. Only for Admins and agenti_luxury role.
Version: 1.0
Author: Dagmar -daggy- Sporck
*/

add_action('admin_menu', function () {
    if (current_user_can('administrator') || current_user_can('agenti_luxury')) {
        add_menu_page(
            'Project Image Uploader',
            'Project Upload',
            'read',
            'project-image-uploader',
            'piu_render_upload_page',
            'dashicons-format-image',
            25
        );
    }
});

function piu_enqueue_assets($hook) {
    if ($hook !== 'toplevel_page_project-image-uploader') return;

    wp_enqueue_script('piu-script', plugin_dir_url(__FILE__) . 'upload.js', [], false, true);
    wp_enqueue_style('piu-style', plugin_dir_url(__FILE__) . 'upload.css');

    wp_localize_script('piu-script', 'piu_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('piu_nonce')
    ]);
}
add_action('admin_enqueue_scripts', 'piu_enqueue_assets');

function piu_render_upload_page() {
    ?>
    <div class="wrap">
        <h1>Project Image Uploader</h1>
        <form id="piu-form">
            <label for="projectName"><strong>Project Name:</strong></label>
            <input type="text" id="projectName" name="projectName" required>
            <br><br>
            <input type="file" id="imageUpload" name="images[]" multiple accept="image/*" required>
            <div id="previewContainer"></div>
            <button type="submit" class="button button-primary">Upload Images</button>
        </form>
        <div id="uploadResult"></div>
    </div>
    <?php
}

add_action('wp_ajax_piu_handle_upload', function () {
    check_ajax_referer('piu_nonce', 'security');

    if (!current_user_can('administrator') && !current_user_can('agenti_luxury')) {
        wp_send_json_error('Unauthorized');
    }

    $project_name = sanitize_text_field($_POST['projectName']);
    $descriptions = $_POST['descriptions'];

    $uploaded = [];

    foreach ($_FILES['images']['name'] as $i => $name) {
        $tmp_name = $_FILES['images']['tmp_name'][$i];
        $desc = sanitize_title($descriptions[$i]);
        $original_ext = pathinfo($name, PATHINFO_EXTENSION);
        $base_name = sanitize_title($project_name) . '_' . $desc;

        $nn = 1;
        $final_name = $base_name . '-' . str_pad($nn, 2, '0', STR_PAD_LEFT) . '.' . $original_ext;
        while (file_exists(wp_upload_dir()['path'] . '/' . $final_name)) {
            $nn++;
            $final_name = $base_name . '-' . str_pad($nn, 2, '0', STR_PAD_LEFT) . '.' . $original_ext;
        }

        $file_array = [
            'name'     => $final_name,
            'type'     => $_FILES['images']['type'][$i],
            'tmp_name' => $tmp_name,
            'error'    => 0,
            'size'     => $_FILES['images']['size'][$i]
        ];

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $id = media_handle_sideload($file_array, 0);

        if (is_wp_error($id)) {
            $uploaded[] = ['error' => $id->get_error_message()];
        } else {
            wp_update_post([
                'ID' => $id,
                'post_title' => $project_name . ' ' . $descriptions[$i],
                'post_excerpt' => $descriptions[$i],
                'post_content' => ''
            ]);
            update_post_meta($id, '_wp_attachment_image_alt', $descriptions[$i]);
            $uploaded[] = ['id' => $id, 'url' => wp_get_attachment_url($id)];
        }
    }

    wp_send_json_success($uploaded);
});
