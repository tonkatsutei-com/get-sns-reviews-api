<?php
declare(strict_types=1);
if (!defined('ABSPATH')) {
    exit;
}
class Get_SNS_Reviews_API_Settings {
    public const PAGE_SLUG = 'get-sns-reviews-api';
    public const SAVE_ACTION = 'get_sns_reviews_api_save';
    public const RESET_QUOTA_ACTION = 'get_sns_reviews_api_reset_quota';
    public const REVIEWS_TEST_ACTION = 'get_sns_reviews_api_reviews_test';
    public const BULK_TEST_ACTION = 'get_sns_reviews_api_bulk_test';
    public const MARK_PASSED_ACTION = 'get_sns_reviews_api_mark_passed';
    public const RESET_PASSED_ACTION = 'get_sns_reviews_api_reset_passed';
    public const IMPORT_ACTION = 'get_sns_reviews_api_import';
    private const TEST_RESULT_PREFIX = 'get_sns_reviews_api_test_result_';
    private const BULK_RESULT_PREFIX = 'get_sns_reviews_api_bulk_result_';
    private const IMPORT_RESULT_PREFIX = 'get_sns_reviews_api_import_result_';
    private const LAST_SERVICE_OPTION_KEY = 'get_sns_reviews_api_last_service';
    private const NONCE_ACTION = 'get_sns_reviews_api_settings';
    private const NONCE_NAME = 'get_sns_reviews_api_nonce';
    public static function init(): void {
        add_action('admin_menu', [self::class, 'add_menu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
        add_action('admin_post_' . self::SAVE_ACTION, [self::class, 'handle_save']);
        add_action('admin_post_' . self::RESET_QUOTA_ACTION, [self::class, 'handle_reset_quota']);
        add_action('admin_post_' . self::REVIEWS_TEST_ACTION, [self::class, 'handle_reviews_test']);
        add_action('admin_post_' . self::BULK_TEST_ACTION, [self::class, 'handle_bulk_test']);
        add_action('admin_post_' . self::MARK_PASSED_ACTION, [self::class, 'handle_mark_passed']);
        add_action('admin_post_' . self::RESET_PASSED_ACTION, [self::class, 'handle_reset_passed']);
        add_action('admin_post_' . self::IMPORT_ACTION, [self::class, 'handle_import']);
    }
    public static function add_menu(): void {
        add_menu_page(
            __('Get SNS Reviews API 設定', 'get-sns-reviews-api'),
            __('Get SNS Reviews API', 'get-sns-reviews-api'),
            'manage_options',
            self::PAGE_SLUG,
            [self::class, 'render_page'],
            'dashicons-star-filled',
            58.2
        );
    }
    public static function enqueue_assets(string $hook): void {
        if ($hook !== 'toplevel_page_' . self::PAGE_SLUG) {
            return;
        }
        wp_enqueue_style(
            'get-sns-reviews-api-admin',
            GET_SNS_REVIEWS_API_URL . 'assets/css/admin.css',
            [],
            GET_SNS_REVIEWS_API_VERSION
        );
        wp_enqueue_script(
            'get-sns-reviews-api-admin',
            GET_SNS_REVIEWS_API_URL . 'assets/js/admin.js',
            [],
            GET_SNS_REVIEWS_API_VERSION,
            true
        );
        wp_add_inline_script(
            'get-sns-reviews-api-admin',
            'window.getsnsrevSupport = ' . wp_json_encode(self::build_support_map()) . ';',
            'before'
        );
    }
    private static function build_support_map(): array {
        $map = [];
        foreach (Get_SNS_Reviews_API::service_ids() as $service) {
            foreach (Get_SNS_Reviews_API::sns_ids() as $sns) {
                $map[$service][$sns] = Get_SNS_Reviews_API::service_supports($service, $sns);
            }
        }
        return $map;
    }
    public static function handle_save(): void {
        self::guard();
        $service = self::posted_service();
        $posted  = isset($_POST['api_key']) ? trim((string) wp_unslash($_POST['api_key'])) : '';
        $state     = Get_SNS_Reviews_API::get_service($service);
        $saved     = isset($state['api_key']) ? (string) $state['api_key'] : '';
        $is_masked = ($saved !== '' && $posted === Get_SNS_Reviews_API::mask_api_key($saved));
        $api_key = $is_masked ? $saved : $posted;
        Get_SNS_Reviews_API::save_service($service, [
            'api_key' => $api_key,
        ]);
        if ($api_key === '') {
            Get_SNS_Reviews_API::save_service($service, [
                'status'    => 'empty',
                'message'   => '',
                'tested_at' => '',
            ]);
            self::redirect('cleared', $service);
        }
        $result = Get_SNS_Reviews_API_Client::test($service, $api_key);
        Get_SNS_Reviews_API::save_service($service, [
            'status'    => $result['ok'] ? 'ok' : 'error',
            'message'   => $result['message'],
            'tested_at' => current_time('mysql'),
        ]);
        self::redirect($result['ok'] ? 'test_ok' : 'test_error', $service);
    }
    public static function handle_reset_quota(): void {
        self::guard();
        $service = self::posted_service();
        Get_SNS_Reviews_API::save_service($service, [
            'quota_exhausted'    => false,
            'quota_exhausted_at' => '',
        ]);
        self::redirect('quota_reset', $service);
    }
    public static function handle_reviews_test(): void {
        self::guard();
        $service = isset($_POST['getsnsrev_service']) ? sanitize_key(wp_unslash((string) $_POST['getsnsrev_service'])) : '';
        if (!in_array($service, Get_SNS_Reviews_API::service_ids(), true)) {
            self::redirect('invalid_service', '', 'getsnsrev-section-reviews-test');
        }
        self::remember_last_service($service);
        $sns = isset($_POST['getsnsrev_sns']) ? sanitize_key(wp_unslash((string) $_POST['getsnsrev_sns'])) : '';
        if (!array_key_exists($sns, Get_SNS_Reviews_API::sns())) {
            self::redirect('reviews_test_invalid_sns', '', 'getsnsrev-section-reviews-test');
        }
        $keyword = isset($_POST['getsnsrev_keyword']) ? trim((string) wp_unslash($_POST['getsnsrev_keyword'])) : '';
        if ($keyword === '') {
            self::redirect('reviews_test_invalid', '', 'getsnsrev-section-reviews-test');
        }
        $max_reviews = isset($_POST['getsnsrev_max_reviews']) ? max(1, min(100, (int) wp_unslash($_POST['getsnsrev_max_reviews']))) : 10;
        $reviews = Get_SNS_Reviews_API::reviews_with($service, $sns, $keyword, [
            'max_reviews' => $max_reviews,
            'no_cache'    => self::posted_no_cache(),
        ]);
        $previous = get_transient(self::test_result_key());
        $previous = is_array($previous) ? $previous : [];
        $keywords    = (isset($previous['keywords']) && is_array($previous['keywords'])) ? $previous['keywords'] : [];
        $keywords[$sns] = $keyword;
        set_transient(
            self::test_result_key(),
            [
                'sns'        => $sns,
                'keyword'        => $keyword,
                'keywords'       => $keywords,
                'service'     => $service,
                'max_reviews' => $max_reviews,
                'from_cache'  => Get_SNS_Reviews_API::last_reviews_from_cache(),
                'items'       => $reviews,
                'error'       => Get_SNS_Reviews_API::last_reviews_error(),
                'at'          => current_time('mysql'),
            ],
            HOUR_IN_SECONDS
        );
        self::redirect('reviews_test_done', '', 'getsnsrev-section-reviews-test');
    }
    public static function handle_bulk_test(): void {
        self::guard();
        $service = isset($_POST['getsnsrev_service']) ? sanitize_key(wp_unslash((string) $_POST['getsnsrev_service'])) : '';
        if (!in_array($service, Get_SNS_Reviews_API::service_ids(), true)) {
            self::redirect('invalid_service', '', 'getsnsrev-section-reviews-test');
        }
        self::remember_last_service($service);
        $max_reviews = isset($_POST['getsnsrev_max_reviews']) ? max(1, min(100, (int) wp_unslash($_POST['getsnsrev_max_reviews']))) : 10;
        $keywords = [];
        foreach (Get_SNS_Reviews_API::sns_ids() as $sns) {
            $posted = isset($_POST['getsnsrev_keyword_' . $sns]) ? trim((string) wp_unslash($_POST['getsnsrev_keyword_' . $sns])) : '';
            if ($posted !== '') {
                $keywords[$sns] = $posted;
            }
        }
        if ($keywords === []) {
            self::redirect('bulk_test_empty', '', 'getsnsrev-section-reviews-test');
        }
        $results = []; 
        $raws    = []; 
        foreach ($keywords as $sns => $keyword) {
            if (!Get_SNS_Reviews_API::service_supports($service, $sns)) {
                $results[$sns] = ['status' => 'unsupported', 'count' => 0, 'error' => ''];
                continue;
            }
            if (Get_SNS_Reviews_API::is_passed($service, $sns)) {
                $results[$sns] = ['status' => 'passed', 'count' => 0, 'error' => ''];
                continue;
            }
            $reviews = Get_SNS_Reviews_API::reviews_with($service, $sns, $keyword, [
                'max_reviews' => $max_reviews,
                'no_cache'    => true,
            ]);
            $results[$sns] = [
                'status'     => 'tested',
                'count'      => count($reviews),
                'error'      => Get_SNS_Reviews_API::last_reviews_error(),
                'from_cache' => Get_SNS_Reviews_API::last_reviews_from_cache(),
                'reviews'    => $reviews,
            ];
            $raw = Get_SNS_Reviews_API_Client::last_raw();
            if ($raw !== '') {
                $raws[$sns] = $raw;
            }
        }
        self::save_bulk_results($service, $keywords, $max_reviews, $results, $raws);
        $light = [];
        foreach ($results as $sns => $row) {
            $light[$sns] = [
                'status'     => $row['status'],
                'count'      => $row['count'],
                'error'      => $row['error'],
                'from_cache' => $row['from_cache'] ?? false,
                'reviews'    => (isset($row['reviews']) && is_array($row['reviews'])) ? $row['reviews'] : [],
            ];
        }
        set_transient(
            self::BULK_RESULT_PREFIX . get_current_user_id(),
            [
                'service'     => $service,
                'keywords'       => $keywords,
                'max_reviews' => $max_reviews,
                'results'     => $light,
                'at'          => current_time('mysql'),
            ],
            HOUR_IN_SECONDS
        );
        self::redirect('bulk_test_done', '', 'getsnsrev-section-reviews-test');
    }
    private static function remember_last_service(string $service): void {
        if ($service === '') {
            return;
        }
        update_option(self::LAST_SERVICE_OPTION_KEY, $service);
    }
    private static function default_service(array $result): string {
        if (isset($result['service']) && (string) $result['service'] !== '') {
            return (string) $result['service'];
        }
        $service = get_option(self::LAST_SERVICE_OPTION_KEY, '');
        return is_string($service) ? $service : '';
    }
    public static function handle_mark_passed(): void {
        self::guard();
        $service = isset($_POST['getsnsrev_service']) ? sanitize_key(wp_unslash((string) $_POST['getsnsrev_service'])) : '';
        if (!in_array($service, Get_SNS_Reviews_API::service_ids(), true)) {
            self::redirect('invalid_service', '', 'getsnsrev-section-reviews-test');
        }
        $sns = isset($_POST['getsnsrev_sns']) ? sanitize_key(wp_unslash((string) $_POST['getsnsrev_sns'])) : '';
        if (!array_key_exists($sns, Get_SNS_Reviews_API::sns())) {
            self::redirect('reviews_test_invalid_site', '', 'getsnsrev-section-reviews-test');
        }
        Get_SNS_Reviews_API::mark_passed($service, $sns);
        self::redirect('passed_marked', '', 'getsnsrev-section-reviews-test');
    }
    public static function handle_reset_passed(): void {
        self::guard();
        Get_SNS_Reviews_API::reset_passed();
        self::redirect('passed_reset', '', 'getsnsrev-section-reviews-test');
    }
    public static function handle_import(): void {
        self::guard();
        $result = Get_SNS_Reviews_API::import_from_amzn();
        if (!$result['available']) {
            self::redirect('import_unavailable', '', 'getsnsrev-section-import');
        }
        set_transient(
            self::IMPORT_RESULT_PREFIX . get_current_user_id(),
            $result,
            HOUR_IN_SECONDS
        );
        self::redirect('import_done', '', 'getsnsrev-section-import');
    }
    private static function save_bulk_results(string $service, array $keywords, int $max_reviews, array $results, array $raws): void {
        $dir = GET_SNS_REVIEWS_API_DIR . 'tools/results';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return;
        }
        $raw_dir = $dir . '/raw';
        if (!is_dir($raw_dir)) {
            mkdir($raw_dir, 0755, true);
        }
        $service_label = (string) (Get_SNS_Reviews_API::services()[$service]['label'] ?? $service);
        $lines   = [];
        $lines[] = '=== 一括取得 ' . current_time('mysql') . '（サービス ' . $service_label . '・取得件数 ' . $max_reviews . '） ===';
        foreach ($keywords as $sns => $keyword) {
            $sns_label = (string) (Get_SNS_Reviews_API::site($sns)['label'] ?? $sns);
            $row        = $results[$sns] ?? [];
            $lines[] = '';
            $lines[] = '■ ' . $sns_label . ' / ' . $keyword;
            $lines[] = '  結果: ' . self::bulk_status_text($row);
        }
        file_put_contents($dir . '/latest-summary.txt', implode("\n", $lines) . "\n");
        $payload = [
            'at'            => current_time('mysql'),
            'service'       => $service,
            'service_label' => $service_label,
            'max_reviews'   => $max_reviews,
            'keywords'         => $keywords,
            'results'       => [],
        ];
        foreach ($results as $sns => $row) {
            $payload['results'][$sns] = [
                'status'  => $row['status'],
                'count'   => $row['count'],
                'error'   => $row['error'],
                'reviews' => $row['reviews'] ?? [],
            ];
        }
        file_put_contents(
            $dir . '/latest-results.json',
            (string) wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
        foreach ($raws as $sns => $raw) {
            $raw = (string) $raw;
            $ext = self::raw_file_extension($raw);
            $stale = $raw_dir . '/' . $sns . '_' . $service . '.' . ($ext === 'json' ? 'html' : 'json');
            if (is_file($stale)) {
                @unlink($stale);
            }
            file_put_contents($raw_dir . '/' . $sns . '_' . $service . '.' . $ext, $raw);
        }
    }
    private static function raw_file_extension(string $raw): string {
        $head = ltrim($raw);
        return ($head !== '' && ($head[0] === '{' || $head[0] === '[')) ? 'json' : 'html';
    }
    private static function bulk_status_text(array $row): string {
        $status = (string) ($row['status'] ?? '');
        if ($status === 'unsupported') {
            return __('非対応', 'get-sns-reviews-api');
        }
        if ($status === 'passed') {
            return __('合格（スキップ）', 'get-sns-reviews-api');
        }
        if ($status === 'untested') {
            return __('未取得（再取得できます）', 'get-sns-reviews-api');
        }
        if (($row['error'] ?? '') !== '') {
            return sprintf(
                __('エラー（%s）', 'get-sns-reviews-api'),
                (string) $row['error']
            );
        }
        return sprintf(
            __('%d件', 'get-sns-reviews-api'),
            (int) ($row['count'] ?? 0)
        );
    }
    private static function test_result_key(): string {
        return self::TEST_RESULT_PREFIX . get_current_user_id();
    }
    private static function posted_no_cache(): bool {
        return isset($_POST['getsnsrev_no_cache']) && (string) wp_unslash($_POST['getsnsrev_no_cache']) === '1';
    }
    private static function guard(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('この操作を実行する権限がありません。', 'get-sns-reviews-api'));
        }
        check_admin_referer(self::NONCE_ACTION, self::NONCE_NAME);
    }
    private static function posted_service(): string {
        $service = isset($_POST['service']) ? sanitize_key(wp_unslash((string) $_POST['service'])) : '';
        if (!in_array($service, Get_SNS_Reviews_API::service_ids(), true)) {
            self::redirect('invalid_service');
        }
        return $service;
    }
    private static function redirect(string $notice, string $service = '', string $fragment = ''): void {
        $args = ['page' => self::PAGE_SLUG, 'getsnsrev_notice' => $notice];
        if ($service !== '') {
            $args['getsnsrev_service'] = $service;
        }
        if ($fragment === '' && $service !== '') {
            $fragment = 'getsnsrev-section-' . $service;
        }
        $url = add_query_arg($args, admin_url('admin.php'));
        if ($fragment !== '') {
            $url .= '#' . $fragment;
        }
        wp_safe_redirect($url);
        exit;
    }
    public static function render_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        $settings = Get_SNS_Reviews_API::get_settings();
        $sections = [];
        foreach (Get_SNS_Reviews_API::services() as $id => $definition) {
            $state      = $settings['services'][$id] ?? [];
            $sections[] = self::build_section_model($id, $definition, $state);
        }
        $renderer = new Get_SNS_Reviews_API_Renderer(GET_SNS_REVIEWS_API_DIR . 'templates');
        $model = [
            'page_title'       => esc_html__('Get SNS Reviews API 設定', 'get-sns-reviews-api'),
            'intro'            => esc_html__('複数のスクレイピングサービスを切り替えて、X・Instagram・Facebook の投稿（商品レビュー相当）をキーワードで取得します。', 'get-sns-reviews-api'),
            'notice'           => self::build_notice_model(),
            'import'           => self::build_import_model(),
            'services_heading' => esc_html__('スクレイピングサービス設定', 'get-sns-reviews-api'),
            'sections'         => $sections,
            'reviews_test'     => self::is_local_site() ? self::build_reviews_test_model() : [],
            'renderer'         => $renderer,
        ];
        echo $renderer->render('settings-page', $model);
    }
    private static function is_local_site(): bool {
        $host = wp_parse_url(home_url(), PHP_URL_HOST);
        return is_string($host) && str_contains($host, '.ton');
    }
    private static function build_notice_model(): array {
        $notice = isset($_GET['getsnsrev_notice']) ? sanitize_key(wp_unslash((string) $_GET['getsnsrev_notice'])) : '';
        if ($notice === '') {
            return ['show' => false, 'type' => '', 'message' => ''];
        }
        $service = isset($_GET['getsnsrev_service']) ? sanitize_key(wp_unslash((string) $_GET['getsnsrev_service'])) : '';
        $label   = self::service_label($service);
        $map = [
            'test_ok'                  => ['success', sprintf(__('%s の API キーを保存し、通信テストに成功しました。', 'get-sns-reviews-api'), $label)],
            'test_error'               => ['error', sprintf(__('%s の API キーを保存しましたが、通信テストに失敗しました。下の結果をご確認ください。', 'get-sns-reviews-api'), $label)],
            'cleared'                  => ['success', sprintf(__('%s の API キーを削除しました。', 'get-sns-reviews-api'), $label)],
            'quota_reset'              => ['success', sprintf(__('%s の「上限到達」を解除しました。', 'get-sns-reviews-api'), $label)],
            'invalid_service'          => ['error', __('不明なサービスが指定されました。', 'get-sns-reviews-api')],
            'reviews_test_done'        => ['success', __('投稿の取得を実行しました。結果をご確認ください。', 'get-sns-reviews-api')],
            'reviews_test_invalid'     => ['error', __('キーワードを入力してください。', 'get-sns-reviews-api')],
            'reviews_test_invalid_sns' => ['error', __('SNS を選択してください。', 'get-sns-reviews-api')],
            'import_done'              => ['success', self::import_notice_text()],
            'import_unavailable'       => ['error', __('Free Amzn Creators API が有効化されていないため、インポートできません。', 'get-sns-reviews-api')],
            'bulk_test_done'           => ['success', __('一括取得を実行しました。結果をご確認ください（取得結果と生データは tools/results/ に保存しました）。', 'get-sns-reviews-api')],
            'bulk_test_empty'          => ['error', __('キーワードが 1 つも入力されていません。', 'get-sns-reviews-api')],
            'passed_marked'            => ['success', __('「合格」として記録しました。次回の一括取得ではスキップされます。', 'get-sns-reviews-api')],
            'passed_reset'             => ['success', __('「合格」の記録をすべて解除しました。', 'get-sns-reviews-api')],
        ];
        if (!isset($map[$notice])) {
            return ['show' => false, 'type' => '', 'message' => ''];
        }
        return [
            'show'    => true,
            'type'    => esc_attr($map[$notice][0]),
            'message' => esc_html($map[$notice][1]),
        ];
    }
    private static function import_notice_text(): string {
        $result = get_transient(self::IMPORT_RESULT_PREFIX . get_current_user_id());
        if (!is_array($result)) {
            return __('インポートを実行しました。', 'get-sns-reviews-api');
        }
        return sprintf(
            __('インポートを実行しました（取り込み: %1$d 件 / 変更なし: %2$d 件）。', 'get-sns-reviews-api'),
            (int) ($result['imported'] ?? 0),
            (int) ($result['skipped'] ?? 0)
        );
    }
    private static function service_label(string $service): string {
        $services = Get_SNS_Reviews_API::services();
        return isset($services[$service]['label']) ? (string) $services[$service]['label'] : '';
    }
    private static function sns_display_label(string $sns): string {
        $snss = Get_SNS_Reviews_API::sns();
        return isset($snss[$sns]['label']) ? (string) $snss[$sns]['label'] : '';
    }
    private static function build_import_model(): array {
        $available = Get_SNS_Reviews_API::amzn_import_available();
        return [
            'id'            => 'import',
            'heading'       => esc_html__('Free Amzn Creators API からのインポート', 'get-sns-reviews-api'),
            'description'   => esc_html__('同梱の「Free Amzn Creators API」で保存済みの API キーを取り込みます。取り込み先が空のサービスだけにコピーし、すでに保存済みのサービスは上書きしません。', 'get-sns-reviews-api'),
            'available'     => $available,
            'status'        => $available
                ? esc_html__('Free Amzn Creators API が有効です。インポートできます。', 'get-sns-reviews-api')
                : esc_html__('Free Amzn Creators API が有効化されていないため、インポートできません（ボタンは無効です）。', 'get-sns-reviews-api'),
            'button_label'  => esc_html__('Free Amzn Creators API からAPI情報をインポートする', 'get-sns-reviews-api'),
            'form_action'   => esc_url(admin_url('admin-post.php')),
            'import_action' => esc_attr(self::IMPORT_ACTION),
            'nonce_name'    => esc_attr(self::NONCE_NAME),
            'nonce_value'   => esc_attr(wp_create_nonce(self::NONCE_ACTION)),
        ];
    }
    private static function build_reviews_test_model(): array {
        $result = get_transient(self::test_result_key());
        $result = is_array($result) ? $result : [];
        $notice = isset($_GET['getsnsrev_notice']) ? sanitize_key(wp_unslash((string) $_GET['getsnsrev_notice'])) : '';
        $open = true;
        $service_options = [];
        foreach (Get_SNS_Reviews_API::services() as $id => $definition) {
            $service_options[] = [
                'value' => esc_attr($id),
                'label' => esc_html((string) $definition['label']),
            ];
        }
        $keywords = (isset($result['keywords']) && is_array($result['keywords'])) ? $result['keywords'] : [];
        if (isset($result['sns'], $result['keyword']) && !isset($keywords[$result['sns']])) {
            $keywords[$result['sns']] = (string) $result['keyword'];
        }
        $sns_rows = [];
        foreach (Get_SNS_Reviews_API::sns() as $id => $definition) {
            $sns_rows[] = [
                'sns'            => esc_attr($id),
                'label'          => esc_html((string) $definition['label']),
                'input_id'       => esc_attr('getsnsrev-reviews-keyword-' . $id),
                'placeholder'    => esc_attr((string) ($definition['keyword_example'] ?? ($definition['keyword_label'] ?? ''))),
                'keyword_value'  => esc_attr((string) ($keywords[$id] ?? '')),
                'keyword_help'   => esc_html((string) ($definition['keyword_help'] ?? '')),
            ];
        }
        return [
            'id'                  => 'reviews-test',
            'heading'             => esc_html__('投稿取得テスト（開発用）', 'get-sns-reviews-api'),
            'description'         => esc_html__('指定 SNS・キーワードの投稿（商品レビュー相当）を、指定サービスで取得します（無料枠を消費します）。ローカル開発サイトでのみ表示します。', 'get-sns-reviews-api'),
            'open'                => $open,
            'sns_rows'           => $sns_rows,
            'service_label'       => esc_html__('接続先（サービス）', 'get-sns-reviews-api'),
            'service_options'     => $service_options,
            'service_selected'    => esc_attr(self::default_service($result)),
            'max_label'           => esc_html__('取得件数', 'get-sns-reviews-api'),
            'max_value'           => esc_attr(isset($result['max_reviews']) ? (string) $result['max_reviews'] : '10'),
            'submit_label'        => esc_html__('取得', 'get-sns-reviews-api'),
            'no_cache_label'      => esc_html__('キャッシュを無視して取得する', 'get-sns-reviews-api'),
            'no_cache_help'       => esc_html__('オンにすると 24H キャッシュ（投稿の結果と、検索エンジンから集めた URL 一覧）を参照せず実 API を呼びます（無料枠を消費します）。', 'get-sns-reviews-api'),
            'form_action'         => esc_url(admin_url('admin-post.php')),
            'reviews_test_action' => esc_attr(self::REVIEWS_TEST_ACTION),
            'nonce_name'          => esc_attr(self::NONCE_NAME),
            'nonce_value'         => esc_attr(wp_create_nonce(self::NONCE_ACTION)),
            'bulk_button_label'   => esc_html__('一括取得', 'get-sns-reviews-api'),
            'bulk_fetching_label' => esc_html__('取得中…', 'get-sns-reviews-api'),
            'bulk_help'           => esc_html__('選択中のサービスで、キーワードを入力した全 SNS の投稿を一括取得します（非対応・合格済みはスキップ）。結果と生データは tools/results/ に保存されます。', 'get-sns-reviews-api'),
            'bulk_action'         => esc_attr(self::BULK_TEST_ACTION),
            'result'              => self::build_reviews_result_model($result),
            'bulk'                => self::build_bulk_result_model(),
        ];
    }
    private static function build_reviews_result_model($reviews): array {
        $reviews = is_array($reviews) ? $reviews : [];
        $items   = (isset($reviews['items']) && is_array($reviews['items'])) ? $reviews['items'] : [];
        $service    = (string) ($reviews['service'] ?? '');
        $label      = self::service_label($service);
        $sns       = (string) ($reviews['sns'] ?? '');
        $sns_label = self::sns_display_label($sns);
        $rows = self::build_review_rows($items);
        return [
            'has_result'    => $reviews !== [],
            'from_cache'    => !empty($reviews['from_cache']),
            'source_label'  => esc_html__('取得元', 'get-sns-reviews-api'),
            'source_text'   => esc_html(!empty($reviews['from_cache']) ? __('キャッシュ（API は呼んでいません）', 'get-sns-reviews-api') : __('実 API（無料枠を消費）', 'get-sns-reviews-api')),
            'source_class'  => esc_attr(!empty($reviews['from_cache']) ? 'is-cache' : 'is-api'),
            'heading'       => esc_html__('レビュー（取得結果）', 'get-sns-reviews-api'),
            'sns'          => esc_html($sns_label !== '' ? $sns_label : $sns),
            'keyword'          => esc_html((string) ($reviews['keyword'] ?? '')),
            'service'       => esc_html($label !== '' ? $label : $service),
            'at'            => esc_html((string) ($reviews['at'] ?? '')),
            'error'         => esc_html((string) ($reviews['error'] ?? '')),
            'count'         => count($rows),
            'count_label'   => esc_html__('件数', 'get-sns-reviews-api'),
            'sns_label'    => esc_html__('SNS', 'get-sns-reviews-api'),
            'keyword_label'    => esc_html__('キーワード', 'get-sns-reviews-api'),
            'service_label' => esc_html__('使用サービス', 'get-sns-reviews-api'),
            'at_label'      => esc_html__('取得日時', 'get-sns-reviews-api'),
            'columns'       => self::review_columns(),
            'link_label'    => esc_html__('開く', 'get-sns-reviews-api'),
            'not_found'     => esc_html__('レビューを取得できませんでした。', 'get-sns-reviews-api'),
            'rows'          => $rows,
        ];
    }
    private static function build_review_rows(array $items): array {
        $rows = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $author = (string) ($item['author_name'] ?? '');
            if (!empty($item['is_verified'])) {
                $author .= '（' . __('認証アカウント', 'get-sns-reviews-api') . '）';
            }
            $rows[] = [
                'author'      => esc_html($author),
                'text'        => esc_html((string) ($item['review_text'] ?? '')),
                'posted_date' => esc_html((string) ($item['review_posted_date'] ?? '')),
                'likes'       => esc_html(number_format_i18n((int) ($item['likes'] ?? 0))),
                'comments'    => esc_html(number_format_i18n((int) ($item['comments'] ?? 0))),
                'url'         => esc_url((string) ($item['url'] ?? '')),
            ];
        }
        return $rows;
    }
    private static function review_columns(): array {
        return [
            esc_html__('投稿者', 'get-sns-reviews-api'),
            esc_html__('本文', 'get-sns-reviews-api'),
            esc_html__('投稿日', 'get-sns-reviews-api'),
            esc_html__('いいね', 'get-sns-reviews-api'),
            esc_html__('コメント', 'get-sns-reviews-api'),
            esc_html__('URL', 'get-sns-reviews-api'),
        ];
    }
    private static function build_bulk_result_model(): array {
        $bulk   = get_transient(self::BULK_RESULT_PREFIX . get_current_user_id());
        $bulk   = is_array($bulk) ? $bulk : [];
        $keywords  = (isset($bulk['keywords']) && is_array($bulk['keywords'])) ? $bulk['keywords'] : [];
        $result = (isset($bulk['results']) && is_array($bulk['results'])) ? $bulk['results'] : [];
        $service = (string) ($bulk['service'] ?? '');
        $label   = self::service_label($service);
        $rows = [];
        foreach ($keywords as $sns => $keyword) {
            $row    = (isset($result[$sns]) && is_array($result[$sns])) ? $result[$sns] : [];
            $status = (string) ($row['status'] ?? '');
            $passed = Get_SNS_Reviews_API::is_passed($service, (string) $sns);
            if ($status === 'passed' && !$passed) {
                $status = 'untested';
                $row    = ['status' => 'untested', 'count' => 0, 'error' => ''];
            } elseif ($passed && $status !== 'passed') {
                $status = 'passed';
                $row    = ['status' => 'passed', 'count' => 0, 'error' => ''];
            }
            $sns_label = self::sns_display_label($sns);
            $review_rows = self::build_review_rows((isset($row['reviews']) && is_array($row['reviews'])) ? $row['reviews'] : []);
            $rows[] = [
                'sns'         => esc_html($sns_label !== '' ? $sns_label : $sns),
                'keyword'         => esc_html((string) $keyword),
                'status'       => esc_html(self::bulk_status_text($row)),
                'status_class' => esc_attr(self::bulk_status_class($status, (string) ($row['error'] ?? ''))),
                'can_mark'     => ($status === 'tested'),
                'mark_sns'    => esc_attr($sns),
                'reviews'      => [
                    'heading'    => esc_html__('レビュー一覧', 'get-sns-reviews-api'),
                    'count'      => count($review_rows),
                    'columns'    => self::review_columns(),
                    'rows'       => $review_rows,
                    'link_label' => esc_html__('開く', 'get-sns-reviews-api'),
                ],
            ];
        }
        return [
            'has_result'    => $bulk !== [],
            'heading'       => esc_html__('一括取得の結果', 'get-sns-reviews-api'),
            'service_label' => esc_html__('サービス', 'get-sns-reviews-api'),
            'service'       => esc_html($label !== '' ? $label : $service),
            'at'            => esc_html((string) ($bulk['at'] ?? '')),
            'at_label'      => esc_html__('取得日時', 'get-sns-reviews-api'),
            'columns'       => [
                esc_html__('SNS', 'get-sns-reviews-api'),
                esc_html__('キーワード', 'get-sns-reviews-api'),
                esc_html__('結果', 'get-sns-reviews-api'),
                esc_html__('合格', 'get-sns-reviews-api'),
            ],
            'mark_label'    => esc_html__('合格にする', 'get-sns-reviews-api'),
            'mark_action'   => esc_attr(self::MARK_PASSED_ACTION),
            'mark_service'  => esc_attr($service),
            'reset_label'   => esc_html__('合格をすべてリセット', 'get-sns-reviews-api'),
            'reset_action'  => esc_attr(self::RESET_PASSED_ACTION),
            'form_action'   => esc_url(admin_url('admin-post.php')),
            'nonce_name'    => esc_attr(self::NONCE_NAME),
            'nonce_value'   => esc_attr(wp_create_nonce(self::NONCE_ACTION)),
            'no_result'     => esc_html__('一括取得の結果はありません。', 'get-sns-reviews-api'),
            'saved_note'    => esc_html__('取得結果と生データは tools/results/ に保存されました（Cline が確認できます）。', 'get-sns-reviews-api'),
            'rows'          => $rows,
        ];
    }
    private static function bulk_status_class(string $status, string $error): string {
        if ($status === 'unsupported' || $status === 'untested') {
            return 'is-empty';
        }
        if ($status === 'passed') {
            return 'is-ok';
        }
        if ($error !== '') {
            return 'is-error';
        }
        return 'is-api';
    }
    private static function build_section_model(string $id, array $definition, array $state): array {
        $status    = isset($state['status']) ? (string) $state['status'] : 'empty';
        $exhausted = Get_SNS_Reviews_API::is_quota_exhausted($id);
        $tested_at = isset($state['tested_at']) ? (string) $state['tested_at'] : '';
        $api_key        = isset($state['api_key']) ? (string) $state['api_key'] : '';
        $api_key_masked = Get_SNS_Reviews_API::mask_api_key($api_key);
        $approval_note = ['show' => false, 'text' => '', 'url' => '', 'url_label' => ''];
        return [
            'id'                 => esc_attr($id),
            'label'              => esc_html((string) $definition['label']),
            'description'        => esc_html((string) $definition['description']),
            'docs_url'           => esc_url((string) $definition['docs_url']),
            'docs_label'         => esc_html__('公式サイト', 'get-sns-reviews-api'),
            'free_quota_label'   => esc_html__('無料枠', 'get-sns-reviews-api'),
            'free_quota_value'   => esc_html((string) $definition['free_quota']),
            'api_key_label'      => esc_html__('API キー', 'get-sns-reviews-api'),
            'api_key_value'      => esc_attr($api_key_masked),
            'api_key_is_masked'  => $api_key_masked !== '',
            'api_key_help'       => esc_html__('保存時に通信テスト（認証のみ・無料枠を消費しません）を実行します。保存済みのキーは前後 3 文字のみ表示し、そのまま保存すると変更されません。新しいキーに変えるときは入力し直し、空にして保存すると削除します。', 'get-sns-reviews-api'),
            'save_button_label'  => esc_html__('保存して通信テスト', 'get-sns-reviews-api'),
            'status_label'       => esc_html__('通信テスト', 'get-sns-reviews-api'),
            'status_class'       => esc_attr(self::status_class($status)),
            'status_text'        => esc_html(self::status_text($status)),
            'tested_at_label'    => esc_html__('最終テスト', 'get-sns-reviews-api'),
            'tested_at_value'    => esc_html($tested_at !== '' ? $tested_at : '—'),
            'message'            => esc_html(isset($state['message']) ? (string) $state['message'] : ''),
            'approval_note'      => $approval_note,
            'quota_label'        => esc_html__('無料枠の状態', 'get-sns-reviews-api'),
            'quota_text'         => esc_html($status !== 'ok' ? __('未テスト', 'get-sns-reviews-api') : ($exhausted ? __('上限到達', 'get-sns-reviews-api') : __('利用可能', 'get-sns-reviews-api'))),
            'quota_class'        => esc_attr($status !== 'ok' ? 'is-empty' : ($exhausted ? 'is-exhausted' : 'is-available')),
            'show_quota_reset'   => $exhausted,
            'quota_button_label' => esc_html__('上限到達を解除', 'get-sns-reviews-api'),
            'form_action'        => esc_url(admin_url('admin-post.php')),
            'save_action'        => esc_attr(self::SAVE_ACTION),
            'reset_quota_action' => esc_attr(self::RESET_QUOTA_ACTION),
            'nonce_name'         => esc_attr(self::NONCE_NAME),
            'nonce_value'        => esc_attr(wp_create_nonce(self::NONCE_ACTION)),
        ];
    }
    private static function status_class(string $status): string {
        return match ($status) {
            'ok'    => 'is-ok',
            'error' => 'is-error',
            default => 'is-empty',
        };
    }
    private static function status_text(string $status): string {
        return match ($status) {
            'ok'    => __('成功', 'get-sns-reviews-api'),
            'error' => __('失敗', 'get-sns-reviews-api'),
            default => __('未テスト', 'get-sns-reviews-api'),
        };
    }
}
