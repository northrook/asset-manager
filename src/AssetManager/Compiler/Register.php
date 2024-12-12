<?php

declare(strict_types=1);

namespace Core\Service\AssetManager\Compiler;

use Core\Service\AssetManager\Asset\{AssetConfigurationInterface, AssetModelInterface, Source, Type};
use Core\Service\AssetManager\Model\{ScriptAsset, StyleAsset};
use Support\{Normalize, Str};
use function Support\implements_interface;
use InvalidArgumentException;

final readonly class Register implements AssetConfigurationInterface
{
    public string $name;

    /** @var class-string<AssetModelInterface> */
    public string $model;

    /** @var string[] */
    public array $sources;

    public Source $source;

    /**
     * @param string                        $name
     * @param class-string                  $model
     * @param string[]                      $sources
     * @param 'cdn'|'local'|'remote'|Source $source
     * @param Type                          $type
     */
    final public function __construct(
        string        $name,
        string        $model,
        string|array  $sources,
        string|Source $source,
        public Type   $type,
    ) {
        $this->name    = $this->validateName( $name );
        $this->model   = $this->validateModelClass( $model );
        $this->sources = $this->assignSources( $sources );
        $this->source  = Source::from( $source, true );
    }

    /**
     * @return array{name: string, model: class-string<AssetModelInterface>, sources: string[], source: string, type: string}
     */
    final public function getConfiguration() : array
    {
        return [
            'name'    => $this->name,
            'model'   => $this->model,
            'sources' => $this->sources,
            'source'  => $this->source->name,
            'type'    => $this->type->name,
        ];
    }

    /**
     * @param string                        $name
     * @param string[]                      $sources
     * @param 'cdn'|'local'|'remote'|Source $source
     * @param ?bool                         $prefersInline
     * @param ?bool                         $prefersRemoteOrigin
     *
     * @return self
     */
    public static function stylesheet(
        string        $name,
        string|array  $sources,
        string|Source $source = Source::LOCAL,
        ?bool         $prefersInline = null,
        ?bool         $prefersRemoteOrigin = null,
    ) : self {
        return new self(
            $name,
            StyleAsset::class,
            $sources,
            $source,
            Type::STYLE,
        );
    }

    /**
     * @param string                        $name
     * @param string[]                      $sources
     * @param 'cdn'|'local'|'remote'|Source $source
     *
     * @return self
     */
    public static function script(
        string        $name,
        string|array  $sources,
        string|Source $source,
    ) : self {
        return new self(
            $name,
            ScriptAsset::class,
            $sources,
            $source,
            Type::SCRIPT,
        );
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function validateName( string $name ) : string
    {
        $name = Str::end( $name, ".{$this->type->name}" );
        return Normalize::key(
            string                   : $name,
            separator                : '.',
            characterLimit           : 64,
            throwOnIllegalCharacters : true,
        );
    }

    /**
     * @param class-string $model
     *
     * @return class-string<AssetModelInterface>
     */
    private function validateModelClass( string $model ) : string
    {
        if ( ! \class_exists( $model ) ) {
            $message = "Unable to register '{$this->name}'";
            $reason  = ", model class '{$model}' does not exist.";
            throw new InvalidArgumentException( $message.$reason );
        }

        if ( implements_interface( $model, AssetModelInterface::class ) ) {
            $interface = AssetModelInterface::class;
            $message   = "Unable to register '{$this->name}', model class '{$model}'";
            $reason    = ", does not implement the required {$interface}.";
            throw new InvalidArgumentException( $message.$reason );
        }

        /** @var class-string<AssetModelInterface> $model */
        return $model;
    }

    /**
     * - Glob Pattern
     * - List of filePaths
     *
     * @param array<array-key, string>|string $from
     *
     * @return string[]
     */
    private function assignSources( string|array $from ) : array
    {
        $sources = [];

        foreach ( ( \is_array( $from ) ? $from : [$from] ) as $index => $source ) {
            $sources[$index] = $source;
        }

        return $sources;
    }
}
