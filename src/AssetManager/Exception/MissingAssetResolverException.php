<?php

declare(strict_types=1);

namespace Core\AssetManager\Exception;

use Core\Asset\Type;
use InvalidArgumentException;
use Throwable;

final class MissingAssetResolverException extends InvalidArgumentException
{
    public readonly Type $type;

    /**
     * @param string         $asset
     * @param null|Throwable $previous
     */
    public function __construct(
        public readonly string $asset,
        ?Throwable             $previous = null,
    ) {
        $this->type = Type::from( $asset );

        $message = "The '{$this->type->name()}' asset '{$this->asset}' has no registered resolver.";

        parent::__construct( $message, 500, $previous );
    }
}
