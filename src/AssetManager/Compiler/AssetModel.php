<?php

declare(strict_types=1);

namespace Core\Service\AssetManager\Compiler;

use Core\Service\AssetManager\Compiler\ScannedAssetReference;
use Core\Service\AssetManager\Interface\AssetModelInterface;
use Northrook\ArrayStore;

abstract class AssetModel implements AssetModelInterface
{
    protected readonly ArrayStore $model;

    protected readonly string $name;

    /**
     */
    public function __construct()
    {
    }

    abstract public static function fromReference( ScannedAssetReference $reference ) : self;
}
