<?php

namespace Core\Asset;

use Core\Asset;
use Core\AssetManager\InlinableAsset;
use Core\AssetManager\Interface\MinifiedAssetInterface;
use Core\View\Element;
use Core\View\Element\Attributes;
use Support\JavaScriptMinifier;
use InvalidArgumentException;
use Stringable;
use Support\Minify;

final class Script extends Asset implements MinifiedAssetInterface
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

        $this->element ??= match ( $this->prefersInline ) {
            true => new Element(
                tag     : 'script',
                content : $this->compiled,
            ),
            default => new Element( 'script', ['src' => $this->getSourceUrl()] ),
        };

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

        return $this;
    }

    public function getSourcePath() : string
    {
        $this->compile();

        $path = $this->pathfinder->get( "{$this->publicAssetsDirectory}/{$this->fileName( 'js' )}" );

        if ( $this->minifier->usedCache() && \file_exists( $path ) ) {
            return $path;
        }

        \file_put_contents( $path, $this->compiled );

        // Compile, save to public and return full path
        return $path;
    }

    public function getMinifier() : Minify
    {
        return $this->minifier;
    }
}
