<?php

namespace Northrook;


use Northrook\Core\Trait\SingletonClass;

final class AssetManager
{
    use SingletonClass;

    public function __construct()
    {
        $this->instantiationCheck();

        AssetManager::$instance = $this;
    }
}