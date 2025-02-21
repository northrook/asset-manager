<?php

declare(strict_types=1);

namespace Core\AssetManager\Exception;

use Core\Asset\Type;
use Core\AssetManager\Config\AssetReference;
use InvalidArgumentException;
use Throwable;

final class UnknownAssetTypeException extends InvalidArgumentException
{
    /**
     * @param AssetReference|Type $asset
     * @param null|Throwable      $previous
     */
    public function __construct(
        Type|AssetReference $asset,
        ?Throwable          $previous = null,
    ) {
        $type = $asset instanceof Type ? $asset->name : $asset->type->name;

        $message = "Unknown Asset 'Type::{$type}'";

        if ( $asset instanceof AssetReference ) {
            $message .= " for '{$asset->name}'.";
        }
        else {
            $message .= '.';
        }

        parent::__construct( $message, 500, $previous );
    }
}
