<?php

declare(strict_types=1);

namespace Core\Service\AssetManager\Asset;

use Core\{PathfinderInterface, SettingsInterface};
use RuntimeException;

interface AssetModelInterface
{
    /**
     * @template Setting of array<string, mixed>|null|bool|float|int|string|\UnitEnum
     *
     * @param PathfinderInterface         $pathfinder
     * @param ?SettingsInterface<Setting> $settings
     * @param ?string                     $assetID
     *
     * @return self
     *
     * @throws RuntimeException
     */
    public function build(
        PathfinderInterface $pathfinder,
        ?SettingsInterface  $settings = null,
        ?string             $assetID = null,
    ) : self;

    /**
     * @param null|array<string, array<array-key|string>|string> $attributes
     *
     * @return AssetInterface
     */
    public function render( ?array $attributes = null ) : AssetInterface;

    /**
     * Get the asset version.
     *
     * @return string
     */
    public function version() : string;
}
