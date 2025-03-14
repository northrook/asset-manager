<?php

namespace Core\AssetManager\Interface;

use Support\Minify;

/**
 * @phpstan-require-extends \Core\AssetManager\AssetDefinition
 */
interface MinifiedAssetInterface
{
    public function compile() : self;

    public function getMinifier() : Minify;
}
