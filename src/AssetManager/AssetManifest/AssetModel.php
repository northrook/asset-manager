<?php

declare(strict_types=1);

namespace Core\Service\AssetManager\AssetManifest;

use Core\Service\AssetManager\Asset\Source;
use Core\Service\AssetManager\Asset\Type;
use Core\Service\AssetManager\AssetManifest\AssetModelInterface;
use JetBrains\PhpStorm\Deprecated;
use Stringable;
use Support\Normalize;
use function String\hashKey;

#[Deprecated]
abstract class AssetModel implements AssetModelInterface
{
    public readonly string $name;

    /** @var string[] */
    public readonly array $sources;

    public readonly Source $source;

    public readonly Type $type;

    protected readonly string $assetID;

    /**
     * @param string                $name
     * @param string[]|Stringable[] $sources
     * @param Source|string         $source
     * @param string|Type           $type
     */
    public function __construct(
        string        $name,
        array         $sources,
        string|Source $source,
        string|Type   $type,
    ) {
        $this->name    = $this->validateName( $name );
        $this->sources = $this->validateSources( $sources );
        $this->source  = Source::from( $source, true );
        $this->type    = Type::from( $type, true );
    }

    /**
     * @param string                $name
     * @param string[]|Stringable[] $sources
     * @param Source|string         $source
     *
     * @return array{name: string, sources: string[], source: string, type: string}
     */
    abstract public static function register(
        string        $name,
        array         $sources,
        string|Source $source,
    ) : array;

    /**
     * @return array{name: string, sources: string[], source: string, type: string}
     */
    final public function getConfiguration() : array
    {
        return [
            'name'    => $this->name,
            'sources' => $this->sources,
            'source'  => $this->source->name,
            'type'    => $this->type->name,
        ];
    }

    private function validateName( string $name ) : string
    {
        return Normalize::key( $name, '.', 64, true );
    }

    /**
     * @param string[]|Stringable[] $sources
     *
     * @return string[]
     */
    private function validateSources( array $sources ) : array
    {
        foreach ( $sources as $index => $path ) {
            $sources[$index] = (string) $path;
        }
        /** @var string[] $sources */
        return $sources;
    }

    final protected function assetID( ?string $assetID ) : string
    {
        $this->assetID = $assetID ?? hashKey(
            [$this::class, $this->name, $this->type->name, $this->source->name, ...$this->sources],
            'implode',
        );

        \assert(
            \strlen( $this->assetID ) === 16 && \ctype_alnum( $this->assetID ),
            'Asset ID must be 16 alphanumeric characters; ['.\strlen(
                $this->assetID,
            )."] `{$this->assetID}` given",
        );

        return $this->assetID;
    }
}
