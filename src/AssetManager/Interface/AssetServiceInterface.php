<?php

namespace Core\AssetManager\Interface;

interface AssetServiceInterface
{
    public function __invoke( AssetInterface $asset ) : AssetInterface;
}
