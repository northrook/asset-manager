<?php

declare(strict_types=1);

namespace Core\Service\AssetManager\Asset;

use InvalidArgumentException;
use ReflectionEnum;
use ReflectionException;

enum Type
{
    private const array MAP = [
        // Core Asset Types
        'css'   => self::STYLE,
        'scss'  => self::STYLE,
        'js'    => self::SCRIPT,
        'mjs'   => self::SCRIPT,
        'png'   => self::IMAGE,
        'jpg'   => self::IMAGE,
        'jpeg'  => self::IMAGE,
        'gif'   => self::IMAGE,
        'svg'   => self::IMAGE,
        'webp'  => self::IMAGE,
        'mp4'   => self::VIDEO,
        'mov'   => self::VIDEO,
        'webm'  => self::VIDEO,
        'mp3'   => self::AUDIO,
        'wav'   => self::AUDIO,
        'ogg'   => self::AUDIO,
        'woff'  => self::FONT,
        'woff2' => self::FONT,
        'ttf'   => self::FONT,
        'otf'   => self::FONT,

        // Document Asset Types
        'doc'  => self::DOCUMENT,
        'docx' => self::DOCUMENT,
        'pdf'  => self::DOCUMENT,
        'csv'  => self::DATA,
        'json' => self::DATA,
        'xml'  => self::DATA,
        'yml'  => self::DATA,
        'sql'  => self::DATA,
        'txt'  => self::TEXT,
        'md'   => self::TEXT,
        'rtf'  => self::TEXT,
        'xls'  => self::SPREADSHEET,
        'xlsx' => self::SPREADSHEET,
        'ppt'  => self::PRESENTATION,
        'pptx' => self::PRESENTATION,

        // Archive Asset Types
        'zip' => self::ARCHIVE,
        'rar' => self::ARCHIVE,
        'tar' => self::ARCHIVE,
        'gz'  => self::ARCHIVE,

        // Executable Asset Types
        'exe' => self::EXECUTABLE,
        'bat' => self::EXECUTABLE,
        'sh'  => self::EXECUTABLE,
        'deb' => self::PACKAGE,
        'rpm' => self::PACKAGE,

        // Code Asset Types
        'php'   => self::SOURCE,
        'html'  => self::SOURCE,
        'py'    => self::SOURCE,
        'cpp'   => self::SOURCE,
        'env'   => self::CONFIG,
        'ini'   => self::CONFIG,
        'yaml'  => self::CONFIG,
        'twig'  => self::TEMPLATE,
        'latte' => self::TEMPLATE,
        'view'  => self::TEMPLATE,
        'blade' => self::TEMPLATE,

        // Design and Media Asset Types
        'obj'    => self::MODEL,
        'psd'    => self::DESIGN,
        'sketch' => self::DESIGN,
        'ai'     => self::VECTOR,
        'eps'    => self::VECTOR,
        'penpot' => self::LAYOUT,
        'tga'    => self::TEXTURE,
        'bmp'    => self::TEXTURE,

        // Miscellaneous
        'log' => self::LOG,
        'bak' => self::BACKUP,
        'pem' => self::CERTIFICATE,
        'crt' => self::CERTIFICATE,
        'md5' => self::CHECKSUM,
        'ico' => self::ICON,
    ];

    // Core Asset Types
    case STYLE;
    case SCRIPT;
    case IMAGE;
    case VIDEO;
    case AUDIO;
    case FONT;

    // Document Asset Types
    case DOCUMENT;
    case DATA;
    case TEXT;
    case SPREADSHEET;
    case PRESENTATION;

    // Archive Asset Types
    case ARCHIVE;

    // Executable Asset Types
    case EXECUTABLE;
    case PACKAGE;

    // Code Asset Types
    case SOURCE;
    case CONFIG;
    case TEMPLATE;

    // Design and Media Asset Types
    case MODEL;
    case DESIGN;
    case VECTOR;
    case LAYOUT;
    case TEXTURE;

    // Miscellaneous Asset Types
    case LOG;
    case BACKUP;
    case CERTIFICATE;
    case CHECKSUM;
    case ICON;

    /**
     * @param string|Type $string
     * @param bool        $throwOnInvalid
     *
     * @return ($throwOnInvalid is true ? static : null|static)
     */
    public static function from( string|Type $string, bool $throwOnInvalid = false ) : ?static
    {
        if ( $string instanceof self ) {
            return $string;
        }

        $type = Type::MAP[\trim( \strtolower( $string ), ". \n\r\t\v\0" )] ?? null;

        if ( ! $type ) {
            $reflection = new ReflectionEnum( self::class );

            if ( $reflection->hasCase( $string ) ) {
                try {
                    $type = $reflection->getCase( $string )->getValue();
                }
                catch ( ReflectionException ) {
                    $type = null;
                }
            }
        }

        if ( ! $type && $throwOnInvalid ) {
            $enum    = self::class;
            $message = "Could not derive {$enum} from string: '{$string}'.";
            throw new InvalidArgumentException( $message );
        }

        /** @var null|static */
        return $type;
    }
}
