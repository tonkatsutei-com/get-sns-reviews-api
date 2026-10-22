<?php
declare(strict_types=1);
if (!defined('ABSPATH')) {
    exit;
}
class Get_SNS_Reviews_API_Update_Checker {
    public const REPOSITORY_URL = 'https://github.com/tonkatsutei-com/get-sns-reviews-api';
    public const SLUG = 'get-sns-reviews-api';
    public const CHECK_PERIOD = 12;
    public static function register(): void {
        if (defined('GET_SNS_REVIEWS_API_AUTO_UPDATE') && !GET_SNS_REVIEWS_API_AUTO_UPDATE) {
            return;
        }
        $loader = GET_SNS_REVIEWS_API_DIR . 'lib/plugin-update-checker/plugin-update-checker.php';
        if (!is_readable($loader)) {
            return;
        }
        require_once $loader;
        if (!class_exists('YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory')) {
            return;
        }
        $checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
            self::REPOSITORY_URL,
            GET_SNS_REVIEWS_API_PLUGIN_FILE,
            self::SLUG,
            self::CHECK_PERIOD
        );
        $checker->setBranch('main');
    }
}
