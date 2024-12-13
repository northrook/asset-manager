<?php

namespace Core\Service\AssetManager\Model;

use Core\Service\AssetManager\Compiler\ScannedAssetReference;
use Core\Service\AssetManager\Compiler\AssetModel;

final class ScriptAsset extends AssetModel
{
    public static function fromReference( ScannedAssetReference $reference ) : self
    {
        return new self();
    }
}
