<?php

/**
 * Plugin Name: Custom Fields REST API
 * Description: Adds custom fields to posts and exposes them via the REST API.
 * Version: 1.0.0
 * Author: Святослав
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register custom fields for posts.
 */
function cf_register_custom_fields()
{
    // Register repeater meta field.
    register_post_meta('post', 'dishes', [
        'type'         => 'array',
        'description'  => 'Repeater Field',
        'single'       => true,
        'show_in_rest' => [
            'schema' => [
                'type'  => 'array',
                'items' => [
                    'type'       => 'object',
                    'properties' => [
                        'title' => ['type' => 'string'],
                        'description' => ['type' => 'string'],
                        'price' => ['type' => 'number'],
                        'video_review' => ['type' => 'string'],
                    ],
                ],
            ],
        ],
    ]);

    // Register meta fields for posts.
    register_post_meta('post', 'coordinates', [
        'type'         => 'string',
        'description'  => 'coordinates',
        'single'       => true,
        'show_in_rest' => true, // Expose in REST API.
    ]);

    register_post_meta('post', 'address', [
        'type'         => 'string',
        'description'  => 'address',
        'single'       => true,
        'show_in_rest' => true,
    ]);

    register_post_meta('post', 'custom_field_3', [
        'type'         => 'integer',
        'description'  => 'Custom Field 3',
        'single'       => true,
        'show_in_rest' => true,
    ]);
}
add_action('init', 'cf_register_custom_fields');

/**
 * Add custom fields to the post editor.
 */
function cf_add_custom_fields_to_post_editor()
{
    add_meta_box(
        'cf_custom_fields',
        'Custom Fields',
        'cf_render_custom_fields_meta_box',
        'post',
        'side'
    );
}
add_action('add_meta_boxes', 'cf_add_custom_fields_to_post_editor');

/**
 * Render custom fields meta box.
 *
 * @param WP_Post $post The post object.
 */
function cf_render_custom_fields_meta_box($post)
{
    // Get the current values of custom fields.
    $coordinates = get_post_meta($post->ID, 'coordinates', true);
    $address = get_post_meta($post->ID, 'address', true);
    $custom_field_3 = get_post_meta($post->ID, 'custom_field_3', true);

    // Render input fields.
?>
<label for="coordinates">Coordinates:</label>
<input type="text" id="coordinates" name="coordinates" value="<?php echo esc_attr($coordinates); ?>" class="widefat">

<label for="address">Address:</label>
<input type="text" id="address" name="address" value="<?php echo esc_attr($address); ?>" class="widefat">

<label for="cf_custom_field_3">Custom Field 3:</label>
<input type="number" id="cf_custom_field_3" name="cf_custom_field_3" value="<?php echo esc_attr($custom_field_3); ?>"
    class="widefat">
<?php
}

/**
 * Save custom fields when the post is saved.
 *
 * @param int $post_id The ID of the post being saved.
 */
function cf_save_custom_fields($post_id)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!isset($_POST['coordinates']) || !isset($_POST['address']) || !isset($_POST['cf_custom_field_3'])) {
        return;
    }

    update_post_meta($post_id, 'coordinates', sanitize_text_field($_POST['coordinates']));
    update_post_meta($post_id, 'address', sanitize_text_field($_POST['address']));
    update_post_meta($post_id, 'custom_field_3', intval($_POST['cf_custom_field_3']));
}
add_action('save_post', 'cf_save_custom_fields');

/**
 * Add custom fields meta box to post editor.
 */
function cf_add_repeater_meta_box()
{
    add_meta_box(
        'cf_repeater_meta_box',
        'Dishes',
        'cf_render_repeater_meta_box',
        'post',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'cf_add_repeater_meta_box');

function cf_render_repeater_meta_box($post)
{
    $dishes = get_post_meta($post->ID, 'dishes', true) ?: [];

    // Add a nonce for security.
    wp_nonce_field('cf_save_repeater_field', 'cf_repeater_nonce');

?>
<div id="cf-repeater-wrapper">
    <h4>Dishes</h4>
    <div id="cf-repeater-fields">
        <?php foreach ($dishes as $index => $dish) : ?>
        <div class="cf-repeater-item">
            <input type="text" name="cf_repeater_field[<?php echo $index; ?>][title]" placeholder="Title"
                value="<?php echo esc_attr($dish['title'] ?? ''); ?>" class="widefat">
            <textarea name="cf_repeater_field[<?php echo $index; ?>][description]" placeholder="Description"
                class="widefat"><?php echo esc_textarea($dish['description'] ?? ''); ?></textarea>
            <input type="number" step="0.01" name="cf_repeater_field[<?php echo $index; ?>][price]" placeholder="Price"
                value="<?php echo esc_attr($dish['price'] ?? ''); ?>" class="widefat">
            <input type="url" name="cf_repeater_field[<?php echo $index; ?>][video_review]"
                placeholder="Video Review URL" value="<?php echo esc_url($dish['video_review'] ?? ''); ?>"
                class="widefat">
            <button type="button" class="cf-remove-repeater">Remove</button>
        </div>
        <?php endforeach; ?>
    </div>
    <button type="button" id="cf-add-repeater" class="button">Add Item</button>
</div>

<style>
.cf-repeater-item {
    margin-bottom: 20px;
    padding: 10px;
    border: 1px solid #ddd;
    background: #f9f9f9;
}

.cf-repeater-item input,
.cf-repeater-item textarea {
    margin-bottom: 10px;
}

.cf-remove-repeater {
    background: #d9534f;
    color: #fff;
    border: none;
    padding: 5px 10px;
    cursor: pointer;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const wrapper = document.getElementById('cf-repeater-fields');
    const addBtn = document.getElementById('cf-add-repeater');

    addBtn.addEventListener('click', function() {
        const newIndex = wrapper.children.length;
        const newItem = document.createElement('div');
        newItem.classList.add('cf-repeater-item');
        newItem.innerHTML = `
                    <input type="text" name="cf_repeater_field[${newIndex}][title]" placeholder="Title" class="widefat">
                    <textarea name="cf_repeater_field[${newIndex}][description]" placeholder="Description" class="widefat"></textarea>
                    <input type="number" step="0.01" name="cf_repeater_field[${newIndex}][price]" placeholder="Price" class="widefat">
                    <input type="url" name="cf_repeater_field[${newIndex}][video_review]" placeholder="Video Review URL" class="widefat">
                    <button type="button" class="cf-remove-repeater">Remove</button>
                `;
        wrapper.appendChild(newItem);
    });

    wrapper.addEventListener('click', function(e) {
        if (e.target.classList.contains('cf-remove-repeater')) {
            e.target.parentElement.remove();
        }
    });
});
</script>
<?php
}

/**
 * Save repeater field data.
 *
 * @param int $post_id The ID of the post being saved.
 */
function cf_save_repeater_field($post_id)
{
    // Verify nonce.
    if (!isset($_POST['cf_repeater_nonce']) || !wp_verify_nonce($_POST['cf_repeater_nonce'], 'cf_save_repeater_field')) {
        return;
    }

    // Prevent saving during autosave.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Save repeater field.
    if (isset($_POST['cf_repeater_field']) && is_array($_POST['cf_repeater_field'])) {
        $repeater_field = array_map(function ($dish) {
            return [
                'title' => sanitize_text_field($dish['title'] ?? ''),
                'description' => sanitize_textarea_field($dish['description'] ?? ''),
                'price' => floatval($dish['price'] ?? 0),
                'video_review' => esc_url_raw($dish['video_review'] ?? ''),
            ];
        }, $_POST['cf_repeater_field']);
        update_post_meta($post_id, 'dishes', $repeater_field);
    } else {
        delete_post_meta($post_id, 'dishes');
    }
}
add_action('save_post', 'cf_save_repeater_field');