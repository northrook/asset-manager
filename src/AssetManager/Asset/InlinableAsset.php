<?php

declare(strict_types=1);

namespace Core\AssetManager\Asset;

use Core\AssetManager\AssetDefinition;

/**
 * @phpstan-require-extends AssetDefinition
 */
trait InlinableAsset
{
    public function prefersInline( bool $set = true ) : self
    {
        \assert(
            \property_exists( $this->meta, 'prefersInline' ),
            "The use of 'InlinableAsset' requires the 'AssetMeta' to have a 'prefersInline' property.'",
        );

        $this->meta->prefersInline = $set;
        return $this;
    }
}
