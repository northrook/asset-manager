<?php

declare(strict_types=1);

namespace Core\Service\AssetManager\Model\Trait;

use Core\Service\AssetManager\AssetModel;

/**
 * @phpstan-require-extends AssetModel
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
