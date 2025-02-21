<?php

namespace Core\Asset;

use Core\Asset;
use Core\AssetManager\InlinableAsset;
use Core\View\Element;
use Core\View\Element\Attributes;
use Support\Minify\StylesheetMinifier;
use Stringable;

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

    protected function compile() : void
    {
        if ( $this->compiled ) {
            return;
        }

        $sources = [];

        foreach ( $this->source as $source ) {
            if ( \str_contains( $source, '*' ) ) {
                $glob = \glob( $source );
                dump( $glob );
            }
            else {
                dump( $source );
            }
        }

        $this->minifier->setSource( ...$sources );
        $this->minifier->minify( $this->reference->name );

        dump( $this->minifier->getReport() );

        $this->compiled = $this->minifier->content;
    }

    /**
     * @param array<string, null|bool|int|string>|Attributes $attributes
     *
     * @return Element
     */
    public function element( array|Attributes $attributes = [] ) : Element
    {
        $this->element ??= match ( $this->prefersInline ) {
            true => new Element(
                tag : 'style',
                // content : $compiledCSS,
            ),
            default => new Element( 'link' ),
        };

        if ( $attributes ) {
            $this->element->attributes->merge( $attributes );
        }

        return $this->element;
    }

    public function getSourcePath() : string
    {
        // Compile, save to public and return full path
        return __METHOD__;
    }

    public function getSourceUrl() : string
    {
        // Compile, save to public and return relative URL
        return __METHOD__;
    }
}
