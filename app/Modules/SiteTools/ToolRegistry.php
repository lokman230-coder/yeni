<?php

declare(strict_types=1);

namespace App\Modules\SiteTools;

use App\Modules\SiteTools\Tools\ToolInterface;
use App\Modules\SiteTools\Tools\{
    WhoisTool, DnsCheckTool, SslCheckTool, SpeedTestTool, SeoAnalyzeTool,
    SiteAnalyzeTool, SecurityHeadersTool, IpLookupTool, PingTool, HttpHeaderTool,
    RobotsCheckTool, SitemapCheckTool, MetaAnalyzeTool, LinkAnalyzeTool,
    ImageAltTool, DomainValueTool
};

final class ToolRegistry
{
    /** @return ToolInterface[] */
    public static function all(): array
    {
        return [
            new WhoisTool(),
            new DnsCheckTool(),
            new SslCheckTool(),
            new DomainValueTool(),
            new SeoAnalyzeTool(),
            new SiteAnalyzeTool(),
            new SpeedTestTool(),
            new SecurityHeadersTool(),
            new IpLookupTool(),
            new PingTool(),
            new HttpHeaderTool(),
            new RobotsCheckTool(),
            new SitemapCheckTool(),
            new MetaAnalyzeTool(),
            new LinkAnalyzeTool(),
            new ImageAltTool(),
        ];
    }

    public static function find(string $slug): ?ToolInterface
    {
        foreach (self::all() as $t) {
            if ($t->slug() === $slug) return $t;
        }
        return null;
    }
}
