<?php

if (!function_exists('ao_ensure_blog_schema')) {
    function ao_ensure_blog_schema(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $path = dirname(__DIR__, 2) . '/modules/blog/install.sql';
        if (is_file($path)) {
            try {
                db()->exec((string)file_get_contents($path));
            } catch (Throwable $e) {
                error_log('[ao] '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine());
            }
        }
    }
}
