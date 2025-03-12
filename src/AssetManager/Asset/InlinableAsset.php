<?php

declare(strict_types=1);

namespace Core\AssetManager\Asset;

/**
 * @phpstan-require-extends \Core\Asset\AbstractAsset
 */
trait InlinableAsset
{
    protected ?bool $prefersInline = null;

    public function prefersInline( ?bool $set = true ) : self
    {
        $this->prefersInline = $set;
        return $this;
    }
}
