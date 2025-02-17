<?php

declare(strict_types=1);

namespace Core\AssetManager\Config;

use Core\Asset\Type;
use Core\Interface\DataInterface;
use Core\Pathfinder\Path;
use Symfony\Component\DependencyInjection\Loader\Configurator\ReferenceConfigurator;
use const Support\AUTO;
use InvalidArgumentException;

/**
 * @internal
 */
final class AssetRegistration implements DataInterface
{
    /** @var string `type.name` */
    public readonly string $name;

    public readonly Type $type;

    /** @var string[] */
    private array $source = [];

    /** @var array<string, bool> */
    private array $service = [];

    public function __construct( string $name )
    {
        \assert(
            \ctype_alpha( \str_replace( ['.', '-'], '', $name ) ),
            "Asset names must only contain ASCII characters, underscores and dashes. {$name} provided.",
        );
        $this->name = $name;
        $this->type = Type::from( $name, true );
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
    public function addService(
        string|array|ReferenceConfigurator $service,
    ) : void {
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
        string|Path $source,
        ?string     $key = AUTO,
    ) : void {
        $path = new Path( $source );

        $typeError = match ( $this->type ) {
            Type::IMAGE => $path->getExtension(),
            default     => $this->type !== Type::from( $path->getExtension() ),
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
            $expected = $this->type->extensions( true );
            $reason   = match ( $this->type ) {
                Type::IMAGE => "'directory', but got '{$path}'.\nImage directories must not contain period characters.",
                default     => "'{$expected}' but got '{$path->getExtension()}' from '".( $key ?? (string) $path )."'",
            };

            $message = "Invalid source type for '{$this->name}'.\nExpected {$reason}.";
            throw new InvalidArgumentException( $message );
        }

        if ( $key ) {
            $this->source[$key] = (string) $path;
        }
        else {
            $this->source[] = (string) $path;
        }
    }
}
