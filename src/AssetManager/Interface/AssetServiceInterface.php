<?php

namespace Core\AssetManager\Interface;

/**
 * @template T as AssetInterface
 */
interface AssetServiceInterface
{
    /**
     * @param T $asset
     *
     * @return T
     */
    public function __invoke( AssetInterface $asset ) : AssetInterface;
}
