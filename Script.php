<?php

namespace Northrook\Asset;

use Northrook\HTML\Element;

class Script extends StaticAsset
{
    public function __construct(
        string $source,
        array  $attributes = [],
        bool   $inline = false,
    ) {
        parent::__construct( 'script', $source, $attributes, $inline, );
    }
    
    protected function build() : Element {
        return new Element( 'script' );
    }
}