<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class InventarisQrCodeGenerator
{
    public function svg(string $payload, int $size = 180): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size, 0),
            new SvgImageBackEnd
        );

        $svg = (new Writer($renderer))->writeString($payload);

        // Strip XML declaration for safe inline HTML embedding.
        return trim((string) preg_replace('/<\?xml[^>]*\?>/', '', $svg));
    }
}
