<?php

namespace Core\Service\AssetManager\Exception;

use Core\Service\AssetManager\Asset\Type;
use InvalidArgumentException;

final class InvalidAssetTypeException extends InvalidArgumentException
{
    public readonly string $type;

    public function __construct( Type $type, ?string $message = null )
    {
        $this->type = $type->name;

        $message ??= \sprintf( 'Invalid asset type: %s', $this->type );

        parent::__construct( $message, 500 );
    }
}
