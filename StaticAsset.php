<?php

namespace Northrook\Asset;

use Northrook\HTML\Element;
use Stringable;
use Symfony\Contracts\Cache\CacheInterface;
use function Northrook\hashKey;

abstract class StaticAsset implements Stringable
{
    private static ?CacheInterface $cache = null;

    protected readonly Element $element;

    public readonly string $assetID;

    public function __construct(
        public readonly string $type,
        string                 $source,
        array                  $attributes,
        public readonly bool   $inline,
    ) {
        $this->assetID = hashKey( [ $type, $source, ... $attributes, $inline ] );
    }

    /**
     * Build the asset. Must return valid HTML.
     *
     * @return Element
     */
    abstract protected function build() : Element;

    final public function getHtml( bool $forceRecompile = false ) : string {
        return ( $this->element ??= $this->build() )->toString();
    }

    final public function __toString() : string {
        return $this->getHtml();
    }

    final public static function setCacheAdapter( CacheInterface $adapter ) : void {
        static::$cache ??= $adapter;
    }
}