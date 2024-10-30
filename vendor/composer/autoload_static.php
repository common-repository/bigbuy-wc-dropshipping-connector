<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit875c7a2c3565900f270f934748bc497e
{
    public static $files = array (
        '7b11c4dc42b3b3023073cb14e519683c' => __DIR__ . '/..' . '/ralouphie/getallheaders/src/getallheaders.php',
    );

    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'WcMipConnector\\Monolog\\' => 23,
            'WcMipConnector\\' => 15,
        ),
        'P' => 
        array (
            'Psr\\SimpleCache\\' => 16,
            'Psr\\Log\\' => 8,
        ),
        'C' => 
        array (
            'Cocur\\Slugify\\' => 14,
        ),
        'A' => 
        array (
            'Automattic\\WooCommerce\\' => 23,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'WcMipConnector\\Monolog\\' => 
        array (
            0 => __DIR__ . '/../..' . '/lib/monolog/monolog/src/Monolog',
        ),
        'WcMipConnector\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
        'Psr\\SimpleCache\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/simple-cache/src',
        ),
        'Psr\\Log\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/log/Psr/Log',
        ),
        'Cocur\\Slugify\\' => 
        array (
            0 => __DIR__ . '/..' . '/cocur/slugify/src',
        ),
        'Automattic\\WooCommerce\\' => 
        array (
            0 => __DIR__ . '/..' . '/automattic/woocommerce/src/WooCommerce',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit875c7a2c3565900f270f934748bc497e::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit875c7a2c3565900f270f934748bc497e::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit875c7a2c3565900f270f934748bc497e::$classMap;

        }, null, ClassLoader::class);
    }
}