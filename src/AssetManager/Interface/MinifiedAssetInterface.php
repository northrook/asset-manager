<?php

namespace Core\AssetManager\Interface;

use Support\Minify;

interface MinifiedAssetInterface
{
    public function getMinifier() : Minify;
}
