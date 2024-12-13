<?php

declare(strict_types=1);

namespace Core\Service\AssetManager\Asset;

use Core\Service\AssetManager\Interface\AssetModelInterface;
use Support\Interface\DataObject;
use Support\Normalize;
use Stringable;
use function Support\implements_interface;

/**
 * Created by the {@see \Core\Service\AssetManager\AssetCompiler}, stored in the {@see \Core\Service\AssetManager\AssetManifest}.
 *
 * Can be used retrieve detailed information about the asset, or recreate it from source.
 *
 * @internal
 *
 * @author Martin Nielsen
 */
final readonly class AssetReference extends DataObject
{
    public string $name;

    public string $relativeUrl;

    public Properties $properties;

    /**
     * @param string|Stringable                 $name
     * @param string|Stringable                 $relativeUrl
     * @param string                            $sourcePath  // if dir, use content
     * @param class-string<AssetModelInterface> $model
     * @param Type                              $type
     * @param array                             $properties
     */
    public function __construct(
        string|Stringable $name,
        string|Stringable $relativeUrl,
        public string     $sourcePath,
        public string     $model,
        public Type       $type,
        array             $properties,
    ) {
        $this->name        = Normalize::key( (string) $name, '.' );
        $this->relativeUrl = Normalize::url( (string) $relativeUrl );
        \assert( implements_interface( $this->model, AssetModelInterface::class ) );
        \assert( '/' === $this->relativeUrl[0] );
        $this->properties = new Properties( $properties );
    }
}
