<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Slug;
use PHPUnit\Framework\TestCase;

final class SlugTest extends TestCase
{
    public function test_turkish_characters(): void
    {
        $this->assertSame('turkce-slug-uretimi', Slug::make('Türkçe Slug Üretimi'));
        $this->assertSame('sanliurfa', Slug::make('Şanlıurfa'));
        $this->assertSame('gida-guvenligi', Slug::make('Gıda güvenliği'));
    }

    public function test_special_chars_stripped(): void
    {
        $this->assertSame('hello-world', Slug::make('Hello, World!'));
        $this->assertSame('a-b-c', Slug::make('a & b @ c'));
    }

    public function test_empty_input_falls_back(): void
    {
        $this->assertSame('item', Slug::make('!!!'));
    }
}
