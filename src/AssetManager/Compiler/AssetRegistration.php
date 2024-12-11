<?php

namespace Core\Service\AssetManager\Compiler;

// private service
// looped through by the

use Support\{Interface\DataObject, Normalize, Str};
use Core\Service\AssetManager\Asset\{AssetModelInterface, Source, Type};

/**
 * @param string   $name
 * @param string[] $sources
 * @param Source   $origin
 * @param Type     $type
 * @param ?bool    $prefersInline
 * @param ?bool    $prefersRemoteOrigin
 *
 * @return array{array{name: string, sources: string|string[], origin: Source, type: Type, prefersInline: null|bool, prefersRemoteOrigin: null|bool}}
 */
function asset(
    string       $name,
    string|array $sources,
    Source       $origin,
    Type         $type,
    ?bool        $prefersInline = null,
    ?bool        $prefersRemoteOrigin = null,
) : array {
    return [
        [
            'name'                => $name,
            'sources'             => $sources,
            'origin'              => $origin,
            'type'                => $type,
            'prefersInline'       => $prefersInline,
            'prefersRemoteOrigin' => $prefersRemoteOrigin,
        ],
    ];
}

final readonly class AssetRegistration extends DataObject implements AssetRegistrationInterface
{
    public string $name;

    /** @var class-string<AssetModelInterface> */
    public string $model;

    /** @var string[] */
    public array $sources;

    /**
     * @param string   $name
     * @param string[] $sources
     * @param Source   $origin
     * @param Type     $type
     * @param ?bool    $prefersInline
     * @param ?bool    $prefersRemoteOrigin
     */
    final public function __construct(
        string        $name,
        string|array  $sources,
        public Source $origin,
        public Type   $type,
        public ?bool  $prefersInline = null,
        public ?bool  $prefersRemoteOrigin = null,
    ) {
        $this->name    = $this->validateName( $name );
        $this->sources = $this->assignSources( \is_string( $sources ) ? [$sources] : $sources );
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
     * - Glob Pattern
     * - List of filePaths
     *
     * @param array<array-key, string> $sources
     *
     * @return string[]
     */
    private function assignSources( array $sources ) : array
    {
        $sourcePaths = [];

        foreach ( $sources as $index => $source ) {
            $sourcePaths[$index] = $source;
        }

        return $sourcePaths;
    }
}
