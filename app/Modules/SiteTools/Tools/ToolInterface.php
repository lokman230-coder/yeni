<?php

declare(strict_types=1);

namespace App\Modules\SiteTools\Tools;

interface ToolInterface
{
    public function slug(): string;
    public function label(): string;
    public function description(): string;
    public function icon(): string;
    public function inputPlaceholder(): string;
    public function run(string $input): array;
}
