<?php

namespace Core\Asset\Meta;

use Core\AssetManager\Interface\AssetMetaInterface;

final class StyleMeta implements AssetMetaInterface
{
    public bool $prefersInline = false;
}
