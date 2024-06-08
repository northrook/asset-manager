<?php

namespace Northrook\Asset\Runtime;

use Northrook\HTML\Attributes;
use Northrook\Logger\Log;
use Northrook\Support\Str;
use Northrook\Type\Path;
use Northrook\Type\URL;
use Stringable;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\Cache\ItemInterface;


/**
 * - Properties assigned by {@see Asset} are only available during generation.
 * - Properties defined in extending assets will be available at runtime.
 *
 *
 * @internal
 */
abstract class Asset implements Stringable
{
    public string $test = 'test';
    private array $meta;

    protected readonly string $assetHTML;

    protected readonly Manager    $manager;
    protected readonly Path | URL $source;

    protected string $path;

    /**
     * @param string  $source      A path to the asset source file
     * @param array   $attributes  Element attributes
     * @param array   $meta        Asset meta data
     * @param ?int    $ttl         Cache expiresAfter value, in seconds
     */
    final protected function __construct(
        string          $source,
        protected array $attributes = [],
        array           $meta = [],
        array           $properties = [],
        ?int            $ttl = null,
    ) {

        $asset = Manager::cache()->get(
            $this::cacheKey( $source, $attributes ),
            function ( ItemInterface $asset ) use (
                $source, $properties, $meta, $ttl,
            ) {
                $this->initialize( $source, $meta, $properties );

                // TODO : Get default TTL from the Core\Settings
                $asset->expiresAfter( $ttl ?? 30 );

                $this->attributes = array_filter(
                    array_merge( $this->attributes, $this->assetAttributes() ),
                    static fn ( $v ) => $v !== null,
                );

                // Ensure the asset has an ID, unless explicitly set to false
                $this->attributes[ 'id' ] ??= $this->attributeID();

                return [
                    'html'       => $this->render(),
                    'attributes' => $this->attributes,
                ];
            },
        );

        $this->assetHTML  = $asset[ 'html' ];
        $this->attributes = $asset[ 'attributes' ];
    }

    private
    function assignProperties(
        array $properties,
    ) : void {
        foreach ( $properties as $name => $value ) {
            if ( property_exists( $this, $name ) ) {
                $this->{$name} = $value;
            }
            else {
                Log::Warning(
                    'Could promote property {name} when constructing the {asset} asset. The property was not defined. Ensure that a {property} {name} is defined in {class}',
                    [
                        'name'     => $name,
                        'asset'    => $this->typeFromClass(),
                        'property' => 'protected|public',
                        'class'    => $this::class,
                    ],
                );
            }
        }
    }

    private
    function initialize(
        string $source,
        array  $meta = [],
        array  $properties = [],
    ) : void {

        $this->assignProperties( $properties );

        $this->manager = Manager::get();

        $this->source = $this->setSource( $source );

        $type       = $this->typeFromClass();
        $assetID    = $this::ID( $type, $source );
        $this->meta = [ 'assetID' => $assetID, 'type' => $type, 'source' => $source ] + $meta;

        $this->manager->asset(
                     $this::class,
                     $this->meta,
            source : (string) $this->source,
        );
    }

    abstract protected function render() : string;

    public function string() : string {
        return $this->assetHTML;
    }

    final public function print() : void {
        echo $this->string() . PHP_EOL;
    }

    abstract protected function assetAttributes() : array;

    final public function __toString() : string {
        return $this->string();
    }

    final protected function getAssetUrl() : string {
        $path = substr( $this->getPublicAsset(), strlen( $this->manager->publicRoot ) );
        return '/' . ltrim( str_replace( '\\', '/', $path ), '/' );
    }

    final protected function version() : int {
        return filemtime( $this->getPublicAsset() ) ?: time();
    }

    final protected function getPublicAsset() : ?string {

        if ( isset( $this->path ) ) {
            return $this->path;
        }


        $filesystem = new Filesystem();

        $path = $this->getPublicDirectory()->append( $this->source->basename );

        if ( !$filesystem->exists( $path->value ) ) {
            $filesystem->copy( $this->source->value, $path->value );
        }

        if ( $filesystem->exists( $path->value ) ) {
            return $this->path = $path->value;
        }

        return null;
    }

    /**
     * Retrieve the public directory for the asset.
     *
     * @return Path
     */
    final protected function getPublicDirectory() : Path {
        $publicRoot   = new Path( $this->manager->publicAssets );
        $subdirectory = rtrim( $this->meta[ 'type' ], " \n\r\t\v\0s" ) . 's';
        return $publicRoot->append( $subdirectory );
    }

    private function setSource( string $source ) : Path | URL {
        return Str::isURL( $source ) ? new URL( $source ) : new Path( $source );
    }

    public static function cacheKey( string $source, array $attributes ) : string {
        return hash( 'xxh128', $source . print_r( $attributes, true ) );
    }

    public static function ID( string $type, string $source ) : string {
        $generated = base64_encode( $type . hash( 'xxh128', $source ) );
        return strtoupper( rtrim( $generated, '=' ) );
    }

    private function typeFromClass() : string {
        return strtolower( substr( $this::class, strrpos( $this::class, '\\' ) + 1 ) );
    }

    final protected function attributeString() : string {
        return new Attributes( $this->attributes );
    }

    final protected function attributeID() : ?string {

        // Ensure the Asset has an ID, unless explicitly set to false
        if ( false === ( $this->attributes[ 'id' ] ?? null ) ) {
            return null;
        }
        $id = $this->attributes[ 'id' ] ?? null;

        $id = ( $id === true || !$id ) ? $this->source->filename : $id;

        return Str::key( [ $this->meta[ 'type' ], $id ] );
    }
}