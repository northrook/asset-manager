<?php

declare(strict_types=1);

namespace Core\AssetManager\Compiler;

use Attribute;
use Core\Asset\Type;
use Core\Symfony\DependencyInjection\Autodiscover;

/**
 * @internal
 * @abstract-attribute
 */
abstract class AssetAttribute extends Autodiscover
{
    public function __construct(
        public readonly Type $type,
    ) {
        parent::__construct(
            tag      : [
                'core.asset.discovery',
                'monolog.logger' => ['channel' => 'assets'],
            ],
            lazy     : false,
            public   : false,
            autowire : true,
        );
    }
}
