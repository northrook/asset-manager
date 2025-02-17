<?php

declare(strict_types=1);

namespace Core\AssetManager\Config;

use Core\Asset\Type;
use Core\Interface\DataObject;
use Stringable;

final readonly class AssetReference extends DataObject implements Stringable
{
    /** @var string `type.name` */
    public string $name;

    public Type $type;

    /** @var string[] */
    public array $source;

    /** @var string[] */
    public array $servicePass;

    /**
     * @param string          $name
     * @param string|string[] $sources
     * @param string|string[] $servicePass
     */
    public function __construct(
        string       $name,
        string|array $sources,
        string|array $servicePass = [],
    ) {
        $sources = (array) $sources;
        \ksort( $sources );
        $this->source      = $sources;
        $this->servicePass = (array) $servicePass;
        \assert(
            \ctype_alpha( \str_replace( ['.', '-'], '', $name ) ),
            "Asset names must only contain ASCII characters, underscores and dashes. {$name} provided.",
        );

        $this->name = $name;
        $this->type = Type::from( $name, true );
    }

    public function __toString()
    {
        return $this->name;
    }
}
