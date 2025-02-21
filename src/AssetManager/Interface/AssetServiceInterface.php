<?php

namespace Core\AssetManager\Interface;

interface AssetServiceInterface
{
    /**
     * @param AssetInterface $asset
     *
     * @return AssetInterface
     */
    public function __invoke( AssetInterface $asset ) : AssetInterface;
}
