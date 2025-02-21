<?php

declare(strict_types=1);

namespace Core\AssetManager;

/**
 * @phpstan-require-extends \Core\Asset
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
