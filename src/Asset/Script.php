<?php

namespace Northrook\Asset;


use Northrook\HTML\Element;
use Northrook\Logger\Log;
use Northrook\Minify;

/**
 */
final class Script extends StaticAsset
{
    protected const TYPE      = 'script';
    protected const EXTENSION = '.js';

    public function __construct(
        protected readonly string $source,
        protected array           $attributes = [],
        public readonly bool      $inline = false,
        protected readonly ?int   $persistence = null,
    ) {
        $this->initializeAsset( [ $source, ... $attributes, $inline ] );
        $this->buildAsset();
    }

    protected function build() : string {

        $asset = $this->publicAssetFile( $this->source );

        dump( $asset );
        if ( !$asset->exists ) {
            Log::error(
                'Requested asset {href} could not be loaded.\nThe file does not exist.', [ 'href' => $this->source ],
            );
            return '';

        }

        return match ( $this->inline ) {
            true  => $this->inlineScript(),
            false => $this->linkedScript(),
        };
    }

    private function inlineScript() : string {
        return ( new Element(
            'script',
            $this->attributes(),
            Minify::JS( $this->publicFile->readContent() ),
        ) )->toString();
    }

    private function linkedScript() : string {
        return ( new Element(
            'script',
            $this->attributes( set : [ 'src' => $this->publicUrl() ] ),
        ) )->toString();
    }
}