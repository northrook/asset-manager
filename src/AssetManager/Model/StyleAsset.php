<?php

namespace Core\Service\AssetManager\Model;

use Core\Service\AssetManager\Compiler\AssetReference;

final class StyleAsset extends AssetModel
{
    public static function fromReference( AssetReference $reference ) : StyleAsset
    {
        return new self();
    }
}
