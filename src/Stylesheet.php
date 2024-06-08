<?php

namespace Northrook\Asset;

use Northrook\Asset\Runtime\Asset;
use Northrook\Support\Minify;
use Northrook\Logger\Log;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

final class Stylesheet extends Asset
{
    protected bool $inline = false;

    /**
     *
     * @param string  $source
     * @param array{
     *     id: string,
     *     async: bool,
     *     defer: bool,
     *     fetchpriority: 'high'|'low'|'auto'
     *    }           $attributes
     * @param array   $meta
     * @param bool    $inline
     * @param ?int    $ttl
     *
     * @return Stylesheet
     */
    public static function asset(
        string $source,
        array  $attributes = [
            'rel' => 'stylesheet',
        ],
        array  $meta = [],
        bool   $inline = false,
        ?int   $ttl = null,
    ) : Stylesheet {
        return new Stylesheet( $source, $attributes, $meta, [ 'inline' => $inline ], $ttl );
    }

    public function render() : string {

        $inline = $this->attributes[ 'inline' ] ?? false;

        if ( $inline ) {
            unset( $this->attributes[ 'inline' ] );
        }

        $attributes = $this->attributeString();

        return $inline ? "<style $attributes>{$inline}</style>" : "<link $attributes>";
    }


    protected function assetAttributes() : array {

        $inline = $this->inline ? $this->inlineAssetSource() : false;

        if ( $inline ) {
            return [ 'inline' => $inline ];
        }

        return [ 'href' => $this->href() ];
    }

    private function href() : string {
        return $this->getAssetUrl() . '?v=' . $this->version();
    }

    private function inlineAssetSource() : ?string {

        try {
            return Minify::styles( ( new Filesystem() )->readFile( $this->source->value ) );
        }
        catch ( IOException $IOException ) {
            Log::Warning(
                'Unable to inline {asset}. File could not be read into memory.',
                [
                    'asset'     => $this->source->value,
                    'message'   => $IOException->getMessage(),
                    'exception' => $IOException,
                ],
            );
        }

        return null;
    }
}