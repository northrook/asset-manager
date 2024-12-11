<?php

namespace Core\Service\AssetManager\Asset;

interface AssetConfigurationInterface
{
    /**
     * @return array{name: string, model: class-string<AssetModelInterface>, sources: string[], source: string, type: string}
     */
    public function getConfiguration() : array;
}
