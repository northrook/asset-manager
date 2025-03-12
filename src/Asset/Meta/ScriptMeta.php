<?php

namespace Core\Asset\Meta;

use Core\AssetManager\Interface\AssetMetaInterface;

final class ScriptMeta implements AssetMetaInterface
{
    public bool $defer = true;

    public bool $async = false;

    public bool $prefersInline = false;

    public bool $mergeImportStatements = false;
}
