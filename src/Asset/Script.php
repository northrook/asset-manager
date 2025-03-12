<?php

namespace Core\Asset;

use Core\Asset\AbstractAsset;
use Core\AssetManager\Asset\InlinableAsset;
use Core\AssetManager\Interface\MinifiedAssetInterface;
use Core\View\Element;
use Core\View\Element\Attributes;
use Support\{JavaScriptMinifier, Minify};
use InvalidArgumentException;
use Stringable;

final class Script extends AbstractAsset implements MinifiedAssetInterface
{
    use InlinableAsset;

    public const Type TYPE = Type::SCRIPT;

    public readonly JavaScriptMinifier $minifier;

    public ?string $compiled = null;

    protected ?string $source = null;

    protected ?bool $mergeImportStatements = null;

    protected function initialize() : void
    {
        $this->minifier = new JavaScriptMinifier( $this->cache, $this->logger );
        $this->source   = \current( $this->reference->source ) ?: null;
    }

    public function mergeImportStatements( bool $set = true ) : self
    {
        $this->mergeImportStatements = $set;
        return $this;
    }

    public function setSource( string|Stringable $source ) : self
    {
        $this->source = (string) $source;
        return $this;
    }

    /**
     * @param array<string, null|bool|int|string>|Attributes $attributes
     *
     * @return Element
     */
    public function element( array|Attributes $attributes = [] ) : Element
    {
        $this->compile();

        $this->element ??= $this->prefersInline
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
            ->set( 'asset-name', $this->reference->name )
            ->set( 'asset-id', $this->assetID );

        return $this->element;
    }

    public function compile( bool $mergeImportStatements = false ) : self
    {
        if ( $this->compiled ) {
            return $this;
        }
        $this->mergeImportStatements ??= $mergeImportStatements;

        $this->minifier->setSource( $this->source ?? throw new InvalidArgumentException( 'No Source!' ) );

        if ( $this->mergeImportStatements ) {
            $this->minifier->bundleImportStatements();
        }

        $this->minifier->minify( $this->reference->name );

        $this->compiled = $this->minifier->__toString();

        $this->path = $this->pathfinder->get(
            "{$this->publicAssetsDirectory}/{$this->fileName( 'js' )}",
        );

        if ( ! $this->minifier->usedCache() || ! \file_exists( $this->path ) ) {
            \file_put_contents( $this->path, $this->compiled );
        }

        return $this;
    }

    public function getSourcePath() : string
    {
        // Compile, save to public and return full path
        return $this->compile()->path;
    }

    public function getMinifier() : Minify
    {
        return $this->minifier;
    }
}
