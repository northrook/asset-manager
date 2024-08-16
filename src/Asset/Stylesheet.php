<?php

declare( strict_types = 1 );

namespace Northrook\Asset;

use Northrook\AssetManager\Asset\StaticAsset;
use Northrook\HTML\Element;
use Northrook\Logger\Log;
use Northrook\Minify;

/**
 */
final class Stylesheet extends StaticAsset
{
    protected const TYPE      = 'stylesheet';
    protected const EXTENSION = '.css';

    public function __construct(
        protected readonly string $source,
        protected array           $attributes = [],
        public readonly bool      $inline = false,
        protected readonly ?int   $persistence = null,
    ) {
        $this->assetID( [ $source, ... $attributes, $inline ] );
        $this->compileStaticAsset();
    }

    protected function build() : string {

        $this->createPublicAssetFile( $this->source );

        $asset = $this->publicAssetFile( $this->source );

        if ( !$asset->exists ) {
            Log::error(
                'Requested asset {href} could not be loaded.\nThe file does not exist.', [ 'href' => $this->source ],
            );
            return '';

        }

        return match ( $this->inline ) {
            true  => $this->inlineStylesheet(),
            false => $this->linkStylesheet(),
        };
    }

    private function inlineStylesheet() : string {
        return ( new Element(
            'style',
            $this->attributes(),
            Minify::CSS( $this->publicFile->readContent() ),
        ) )->toString();
    }

    private function linkStylesheet() : string {
        $this->attributes( set : [ 'href' => $this->publicUrl() ] );
        return ( new Element( 'link', $this->attributes ) )->toString();
    }
}