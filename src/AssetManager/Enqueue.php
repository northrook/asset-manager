<?php

namespace Northrook\AssetManager;

use Northrook\AssetManager;
use Northrook\Core\Trait\SingletonClass;
use Northrook\Core\Trait\StaticClass;

final class Enqueue
{
    use SingletonClass;

    private static AssetManager $instance;

    public function __construct( AssetManager $assetManager ) {
        $this->instantiationCheck();
        Enqueue::$instance = $assetManager;
    }

    public static function stylesheet() : void {}

    public static function script() : void {}

}