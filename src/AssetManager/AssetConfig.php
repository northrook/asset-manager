<?php

declare(strict_types=1);

namespace Core\AssetManager;

use Core\AssetManager\AssetConfig\AssetRegistrationConfig;
use Core\Interface\PathfinderInterface;

final readonly class AssetConfig
{
    public AssetRegistrationConfig $register;

    public function __construct(
        public array        $assetDirectories,
        PathfinderInterface $pathfinder,
    ) {
        $this->register = new AssetRegistrationConfig(
            $this,
            $pathfinder,
        );
    }
}
