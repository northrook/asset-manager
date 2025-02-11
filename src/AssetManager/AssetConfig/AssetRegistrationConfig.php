<?php

namespace Core\AssetManager\AssetConfig;

use Core\AssetManager\{AssetConfig, AssetReference};

final class AssetRegistrationConfig
{
    public array $assets = [];

    public function __construct( private readonly AssetConfig $config ) {}

    public function style(
        string       $name,
        string|array $source,
    ) : self {
        $this->assets[$name] = new AssetReference();
        return $this;
    }

    public function script(
        string $name,
        string $source,
    ) : self {
        $this->assets[$name] = new AssetReference();
        return $this;
    }

    public function imageDirectory(
        string $directoryPath,
    ) : self {
        $this->assets[$directoryPath] = new AssetReference();
        return $this;
    }

    public function __invoke() : AssetConfig
    {
        return $this->config;
    }
}
