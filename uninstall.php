<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}
delete_option('get_sns_reviews_api_settings');
delete_option('get_sns_reviews_api_status');
delete_option('get_sns_reviews_api_last_service');
global $wpdb;
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
        $wpdb->esc_like('_transient_get_sns_reviews_api_') . '%',
        $wpdb->esc_like('_transient_timeout_get_sns_reviews_api_') . '%'
    )
);
