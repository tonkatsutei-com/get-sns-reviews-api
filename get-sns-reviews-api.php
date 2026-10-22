<?php
/**
 * Plugin Name:       Get SNS Reviews API
 * Description:       Apify（他に Bright Data 等も設定可能）を使い、X・Instagram・Facebook の投稿（商品レビュー相当）をキーワードで取得する API を他プラグインへ提供します。
 * Version:           0.11.0
 * Requires at least: 7.0
 * Requires PHP:      8.0
 * Author:            Tonkatsutei
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       get-sns-reviews-api
 *
 * このファイルはプラグインの入口です。外部公開の REST エンドポイントは持たず、
 * 他プラグインから呼び出せる PHP のクラスメソッド（Get_SNS_Reviews_API::reviews 等）を提供します。
 *
 * @package Get_SNS_Reviews_API
 */
declare(strict_types=1);
if (!defined('ABSPATH')) {
    exit;
}
define('GET_SNS_REVIEWS_API_DIR', plugin_dir_path(__FILE__));
define('GET_SNS_REVIEWS_API_URL', plugin_dir_url(__FILE__));
define('GET_SNS_REVIEWS_API_PLUGIN_FILE', __FILE__);
require_once GET_SNS_REVIEWS_API_DIR . 'includes/version.php';
require_once GET_SNS_REVIEWS_API_DIR . 'includes/class-get-sns-reviews-api-client.php';
require_once GET_SNS_REVIEWS_API_DIR . 'includes/class-get-sns-reviews-api.php';
require_once GET_SNS_REVIEWS_API_DIR . 'includes/class-get-sns-reviews-api-renderer.php';
require_once GET_SNS_REVIEWS_API_DIR . 'includes/class-get-sns-reviews-api-settings.php';
require_once GET_SNS_REVIEWS_API_DIR . 'includes/class-get-sns-reviews-api-update-checker.php';
Get_SNS_Reviews_API_Update_Checker::register();
Get_SNS_Reviews_API_Settings::init();
