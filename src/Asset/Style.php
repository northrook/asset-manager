<?php

namespace Core\Asset;

use Core\Asset;
use Core\AssetManager\InlinableAsset;
use Core\View\Element;
use Core\View\Element\Attributes;
use Support\Minify\StylesheetMinifier;
use Stringable;
use function Support\isPath;

final class Style extends Asset
{
    use InlinableAsset;

    public const Type TYPE = Type::STYLE;

    public readonly StylesheetMinifier $minifier;

    public ?string $compiled = null;

    /** @var array<array-key,string> */
    protected array $source;

    protected function initialize() : void
    {
        $this->minifier = new StylesheetMinifier( $this->cache, $this->logger );
        $this->source   = $this->reference->source;
    }

    public function addSource( string|Stringable $path, bool $prepend = false ) : self
    {
        if ( $prepend ) {
            $this->source = [(string) $path, ...$this->source];
        }
        else {
            $this->source[] = (string) $path;
        }

        return $this;
    }

    public function compile() : self
    {
        if ( $this->compiled ) {
            return $this;
        }

        foreach ( $this->source as $key => $source ) {
            $isPath = isPath( $source );
            if ( $isPath ) {
                if ( \glob( $source ) ) {
                    $this->minifier->setSource( ...\glob( $source ) );
                }
                elseif ( \file_exists( $source ) ) {
                    $this->minifier->setSource( $source );
                }
                else {
                    $this->logger?->notice( 'Source {source} does not exist.', ['source' => $source] );
                }
            }
            else {
                $this->minifier->setSource( $source );
            }
        }

        // dump( $this->source);

        $this->minifier->minify( $this->reference->name );

        $this->compiled = $this->minifier->content;

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
                tag     : 'style',
                content : $this->compiled,
            ),
            default => new Element( 'link', ['rel' => 'stylesheet', 'href' => $this->getSourceUrl()] ),
        };

        if ( $attributes ) {
            $this->element->attributes->merge( $attributes );
        }

        $this->element->attributes
            ->set( 'asset-name', $this->reference->name )
            ->set( 'asset-id', $this->assetID );

        return $this->element;
    }

    public function getSourcePath() : string
    {
        $this->compile();

        $path = $this->pathfinder->get( "{$this->publicAssetsDirectory}/{$this->fileName( 'css' )}" );

        if ( $this->minifier->usedCache() && \file_exists( $path ) ) {
            return $path;
        }

        \file_put_contents( $path, $this->compiled );

        // Compile, save to public and return full path
        return $path;
    }

    public function getSourceUrl() : string
    {
        return $this->pathfinder->get( $this->getSourcePath(), $this->publicAssetsDirectory );
    }
}
