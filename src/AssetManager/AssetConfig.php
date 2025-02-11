<?php

namespace Core\AssetManager;

use Core\AssetManager\AssetConfig\AssetRegistrationConfig;

final class AssetConfig
{
    public readonly AssetRegistrationConfig $register;

    public function __construct()
    {
        $this->register = new AssetRegistrationConfig( $this );
    }
}
