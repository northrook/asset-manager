<?php

namespace Core\Service\AssetManager\Model;

use Core\Service\AssetManager\Compiler\AssetReference;

final class ScriptAsset extends AssetModel
{
    public static function fromReference( AssetReference $reference ) : ScriptAsset
    {
        return new self();
    }
}
