<?php

declare(strict_types=1);

namespace Core\AssetManager\Config;

use Core\Asset\Meta\{AssetMeta, ImageMeta, ScriptMeta, StyleMeta};
use Core\Asset\Type;
use Core\AssetManager\Compiler\AssetValidationTrait;
use Core\AssetManager\Interface\AssetMetaInterface;
use Core\Interface\DataInterface;
use Core\Pathfinder\Path;
use Stringable;
use Symfony\Component\DependencyInjection\Loader\Configurator\ReferenceConfigurator;
use InvalidArgumentException;
use const Support\AUTO;

/**
 * @internal
 */
final class AssetRegistration implements DataInterface, Stringable
{
    use AssetValidationTrait;

    /** @var string `type.name` */
    public readonly string $name;

    public readonly Type $type;

    public readonly AssetMetaInterface $meta;

    /** @var array<string, bool> */
    private array $service;

    /**
     * @param string              $name
     * @param string[]            $source
     * @param string[]            $service
     * @param ?AssetMetaInterface $meta
     */
    public function __construct(
        string              $name,
        private array       $source = [],
        array               $service = [],
        ?AssetMetaInterface $meta = AUTO,
    ) {
        \assert(
            $this->isName( $name ),
            "Asset names must only contain ASCII characters, underscores and dashes. {$name} provided.",
        );

        $this->name = $name;
        $this->type = Type::from( $name );
        $this->meta = $meta ?? $this::getDefaultMeta( $this->type );

        $this->service = \array_fill_keys( $service, true );
    }

    public function __toString() : string
    {
        return $this->name;
    }

    /**
     * @return array<array-key, string>
     */
    public function getSource() : array
    {
        return $this->source;
    }

    /**
     * Returns a list of services.
     *
     * @return string[]
     */
    public function getServices() : array
    {
        return \array_keys( \array_filter( $this->service ) );
    }

    /**
     * @param array<array-key, bool|string>|ReferenceConfigurator|string|string[] $service
     *
     * @return void
     */
    public function addService( string|array|ReferenceConfigurator $service ) : void
    {
        if ( $service instanceof ReferenceConfigurator ) {
            $service = $service->__toString();
        }

        foreach ( (array) $service as $key => $value ) {
            if ( \is_int( $key ) ) {
                \assert(
                    \is_string( $value ),
                    'Service name must be a string.',
                );

                $this->service[$value] ??= true;

                continue;
            }
            if ( $value === true ) {
                unset( $this->service[$key] );
            }

            \assert(
                \is_bool( $value ),
                'Service status must be a boolean.',
            );

            $this->service[$key] = $value;
        }
    }

    public function addSource(
        string|Path     $source,
        null|int|string $key = AUTO,
    ) : void {
        $path = new Path( $source );

        $typeError = match ( $this->type ) {
            Type::IMAGE => $path->getExtension(),
            default     => $this->type !== Type::from( $path->getExtension(), true ),
        };

        // \assert( $path instanceof Path );
        //
        // if ( ! $path->exists() ) {
        //     \mkdir(
        //             $path->getRealPath(),
        //             0777,
        //             true,
        //     );
        // }

        if ( $typeError ) {
            $expected = \implode( ', ', $this->type->extensions() );
            $reason   = match ( $this->type ) {
                Type::IMAGE => "'directory', but got '{$path}'.\nImage directories must not contain period characters.",
                default     => "'{$expected}' but got '{$path->getExtension()}' from '".( $key ?? (string) $path )."'",
            };

            $message = "Invalid source type for '{$this->name}'.\nExpected {$reason}.";
            throw new InvalidArgumentException( $message );
        }

        if ( \is_int( $key ) ) {
            $key = -1 * \abs( $key );

            if ( \array_key_exists( $key, $this->source ) ) {
                $key--;
            }
        }
        else {
            $key = \count( $this->source );
        }

        $this->source[$key] = (string) $path;

        \ksort( $this->source );
    }

    public static function getDefaultMeta( ?Type $type ) : AssetMetaInterface
    {
        return match ( $type ) {
            Type::IMAGE  => new ImageMeta(),
            Type::SCRIPT => new ScriptMeta(),
            Type::STYLE  => new StyleMeta(),
            default      => new AssetMeta(),
        };
    }
}
