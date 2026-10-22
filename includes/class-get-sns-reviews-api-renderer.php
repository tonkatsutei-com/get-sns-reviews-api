<?php
declare(strict_types=1);
if (!defined('ABSPATH')) {
    exit;
}
class Get_SNS_Reviews_API_Renderer {
    private string $template_dir;
    public function __construct(string $template_dir) {
        $this->template_dir = rtrim($template_dir, '/\\');
    }
    public function render(string $name, array $model = []): string {
        $file = $this->template_dir . '/' . $name . '.php';
        if (!is_readable($file)) {
            return '';
        }
        ob_start();
        (static function (string $__file, array $__model): void {
            extract($__model, EXTR_SKIP);
            include $__file;
        })($file, $model);
        return (string) ob_get_clean();
    }
}
