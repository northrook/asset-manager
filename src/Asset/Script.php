<?php

namespace Core\Asset;

use Core\AssetManager\Asset;
use Core\View\Element;
use Support\Minify\JavaScriptMinifier;
use InvalidArgumentException;

final class Script extends Asset
{
    public const Type TYPE = Type::SCRIPT;

    public readonly JavaScriptMinifier $minifier;

    protected bool $compiled = false;

    protected ?string $source = null;

    public function element() : Element
    {
        return $this->element ?? new Element();
    }

    public function compile( bool $mergeImportStatements = false ) : void
    {
        if ( $this->compiled ) {
            return;
        }

        if ( ! isset( $this->minifier ) ) {
            $this->minifier = new JavaScriptMinifier();
        }

        if ( ! $this->source ) {
            $this->addSource( $this->reference->source );
        }

        $this->minifier->setSource(
            $this->source ?? throw new InvalidArgumentException(
                'No Source!',
            ),
        );
        $this->minifier->minify(
            $this->reference->name,
            $mergeImportStatements,
        );

        $this->compiled = true;
        $this->source   = $this->minifier->content;
    }

    protected function construct( bool $rebuild ) : void
    {
        $publicPath = $this->pathfinder->getPath(
            "{$this->publicRootKey}/{$this->fileName( 'js' )}",
        );

        $this->compile();

        if ( ! $publicPath->exists() || $this->minifier->usedCache() ) {
            $publicPath->save( $this->source );
        }
    }

    public function setMinifier( JavaScriptMinifier $minifier ) : self
    {
        $this->minifier ??= $minifier;

        return $this;
    }

    public function addSource( array $source ) : self
    {
        $this->source = \current( $source ) ?: null;
        return $this;
    }
}
