<?php

declare(strict_types=1);

namespace Core\AssetManager\Compiler;

use Core\Asset\Type;
use Core\Symfony\DependencyInjection\Autodiscover;
use InvalidArgumentException;

/**
 * If provided a `name`, it will match against registered assets by name.
 * If provided a `type`, it will match against all registered assets of that type.
 *
 * Asset names must resolve to a valid asset and type.
 */
final class AssetAttribute extends Autodiscover
{
    /** @var ?string `type.name` */
    public ?string $name;

    public Type $type;

    public function __construct(
        ?string $name = null,
        ?Type   $type = null,
    ) {
        if ( ! ( $name || $type ) ) {
            throw new InvalidArgumentException(
                'Either a name or a type must be provided.',
            );
        }

        $this->name = $name;
        $this->type = $type ?? Type::from( $name, true );

        parent::__construct(
            tag      : [
                'asset.service_locator',
                'monolog.logger' => ['channel' => 'assets'],
            ],
            lazy     : false,
            public   : false,
            autowire : true,
        );
    }
}
