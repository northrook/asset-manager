<?php

namespace Core\Asset\Meta;

use Core\AssetManager\Interface\AssetMetaInterface;
use const Support\AUTO;

final class ImageMeta implements AssetMetaInterface
{
    /** @var array<int,array<int, int|string>|int> */
    protected array $sizes = [
        160,
        320,
        480,
        640,
        800,
        960,
        1_120,
        1_280,
        1_440,
    ];

    protected ?string $caption = null;

    public function getCaption() : ?string
    {
        return $this->caption;
    }

    public function setCaption( ?string $caption ) : self
    {
        $this->caption = $caption;
        return $this;
    }

    /**
     * @return array<int,array<int, int|string>|int>
     */
    public function getSizes() : array
    {
        return $this->sizes;
    }

    public function addSize( int $width, ?int $height = AUTO, ?string $position = AUTO ) : self
    {
        $size = \array_filter( [$width, $height, $position] );

        $this->sizes[] = \count( $size ) === 1 ? $width : $size;
        return $this;
    }
}
