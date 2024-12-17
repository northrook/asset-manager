<?php

namespace Core\Service\AssetManager\Model;

use Core\Service\AssetManager\{Asset, AssetModel};
use Core\Service\AssetManager\Asset\AssetInterface;
final class ImageAsset extends AssetModel
{
    public function render( ?array $attributes = null ) : AssetInterface
    {
        // dump( $this );
        return new Asset(
            $this->getName(),
            $this->assetID(),
            $this->getType(),
            '<img src="#" alt="" />',
        );
    }
}
