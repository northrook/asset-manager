<?php

declare(strict_types=1);

namespace Core\Service\AssetManager\Interface;

use Core\{PathfinderInterface,
    Service\AssetManager\Asset\AssetInterface,
    Service\AssetManager\Asset\Type,
    Service\AssetManager\Asset\AssetReference,
    SettingsInterface
};
use Core\Service\AssetManager\Compiler\ScannedAssetReference;
use RuntimeException;

interface AssetModelInterface
{
    public function getName() : string; // style.{name}.{?variant}

    public function getPublicPath() : string;

    /**
     * @return string[]
     */
    public function getSources() : array;

    public function getType() : Type;

    public function getReference() : AssetReference;

    public static function fromReference( ScannedAssetReference $reference ) : self;

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
}