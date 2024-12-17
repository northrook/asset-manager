<?php

declare(strict_types=1);

namespace Core\Service\AssetManager\Asset;

use Core\{PathfinderInterface, SettingsInterface};
use RuntimeException;

interface AssetModelInterface
{
    public static function fromReference( AssetReference $reference, PathfinderInterface $pathfinder ) : self;

    /**
     * @template Setting of array<string, mixed>|null|bool|float|int|string|\UnitEnum
     *
     * @param ?string                     $assetID
     * @param ?SettingsInterface<Setting> $settings
     *
     * @return self
     *
     * @throws RuntimeException
     */
    public function build( ?string $assetID = null, ?SettingsInterface $settings = null ) : self;

    public function getName() : string; // {type}.{name}.{dir|variant}

    public function getPublicPath() : string;

    /**
     * @return string[]
     */
    public function getSources() : array;

    public function getType() : Type;

    public function getReference() : ?AssetReference;

    /**
     * @param null|array<string, null|bool|float|int|string> $attributes
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
