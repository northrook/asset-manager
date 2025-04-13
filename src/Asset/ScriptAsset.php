<?php

namespace Core\Asset;

use Core\Asset\Meta\ScriptMeta;
use Core\AssetManager\Asset\InlinableAsset;
use Core\AssetManager\AssetDefinition;
use Core\AssetManager\Interface\{AssetMetaInterface, MinifiedAssetInterface};
use Core\View\Element;
use Support\{JavaScriptMinifier};
use Psr\Cache\CacheItemPoolInterface;
use Stringable;
use InvalidArgumentException;
use function Support\file_save;

/**
 * @property-read ScriptMeta $meta
 */
final class ScriptAsset extends AssetDefinition implements MinifiedAssetInterface
{
    use InlinableAsset;

    public const Type TYPE = Type::SCRIPT;

    private readonly JavaScriptMinifier $minifier;

    protected ?string $path = null;

    protected ?string $source = null;

    public ?string $compiled = null;

    /**
     * @param string                  $name
     * @param array<array-key,string> $source
     * @param null|AssetMetaInterface $meta
     */
    public function __construct(
        string              $name,
        array               $source,
        ?AssetMetaInterface $meta = null,
    ) {
        $this->source = \array_shift( $source );
        parent::__construct( $name, $meta );
    }

    protected function initialize() : void {}

    public function mergeImportStatements( bool $set = true ) : self
    {
        \assert( \property_exists( $this->meta, 'mergeImportStatements' ) );

        $this->meta->mergeImportStatements = $set;

        return $this;
    }

    public function setSource( string|Stringable $source ) : self
    {
        $this->source = (string) $source;
        return $this;
    }

    public function compile() : MinifiedAssetInterface
    {
        if ( $this->compiled ) {
            return $this;
        }

        $this->getMinifier()->setSource(
            $this->source ?? throw new InvalidArgumentException( 'No Source!' ),
        );

        if ( $this->meta->mergeImportStatements ) {
            $this->getMinifier()->bundleImportStatements();
        }

        $this->getMinifier()->minify( $this->name );

        $this->compiled = $this->getMinifier()->__toString();

        $this->path ??= $this->getSourcePath();

        if ( ! $this->getMinifier()->usedCache() || ! \file_exists( $this->path ) ) {
            file_save( $this->path, $this->compiled );
        }

        return $this;
    }

    public function element( mixed ...$attributes ) : Element
    {
        $this->compile();

        $this->element ??= $this->meta->prefersInline
                ? new Element(
                    tag     : 'script',
                    content : $this->compiled,
                )
                : new Element(
                    tag : 'script',
                    src : $this->getSourceUrl( true ),
                );

        if ( $attributes ) {
            $this->element->attributes->merge( $attributes );
        }

        $this->element->attributes
            ->set( 'asset-name', $this->name )
            ->set( 'asset-id', $this->assetID );

        return $this->element;
    }

    protected function export() : array
    {
        return [
            'source' => $this->source,
            'path'   => $this->path,
        ];
    }

    public function getSourcePath() : string
    {
        return $this->path ??= $this->pathfinder->get(
            "dir.public.assets/{$this->fileName( 'js' )}",
        );
    }

    public function getSourceUrl( bool $version = false ) : string
    {
        return $this->pathfinder->get(
            $this->getSourcePath(),
            'dir.public',
        ).( $version ? "?v={$this->assetID}" : '' );
    }

    public function getMinifier() : JavaScriptMinifier
    {
        return $this->minifier ??= new JavaScriptMinifier(
            cachePool : $this->cache instanceof CacheItemPoolInterface ? $this->cache : null,
            logger    : $this->logger,
        );
    }
}
