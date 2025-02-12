<?php

namespace Core\Asset;

use Core\AssetManager\Asset;
use Core\AssetManager\Compiler\JavascriptAssetCompiler;
use Core\View\Element;
use Northrook\JavaScriptMinifier;

final class Script extends Asset
{
    public const Type TYPE = Type::SCRIPT;

    protected ?string $source = null;

    public function element() : Element
    {
        return $this->element ?? new Element();
    }

    protected function construct( bool $rebuild ) : void
    {
        $publicPath = $this->pathfinder->getPath(
            "{$this->publicRootKey}/{$this->fileName( 'js' )}",
        );

        if ( ! $publicPath->exists() || $rebuild ) {
            $this->compile();
        }

    }

    public function addSource( array $source ) : self
    {
        $this->source = \current( $source );
        return $this;
    }

    protected function compile() : string
    {
        $this->source ??= \current( $this->reference->source );
        dump($this->source);

        $compiler = new JavascriptAssetCompiler(
            $this->source,
        );

        $sources[] = $compiler->compile();

        dump( \get_defined_vars() );

        return ( new JavaScriptMinifier( $sources ) )->minify();
    }
}
