<?php

declare(strict_types=1);

namespace Core\Service\AssetManager\Model;

use Core\Service\AssetManager\Compiler\AssetReference;
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

    abstract public static function fromReference( AssetReference $reference ) : AssetModel;
}
