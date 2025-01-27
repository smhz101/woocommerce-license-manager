<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit53e1f4ec2badef8598845b97b6789daf {

	public static $prefixLengthsPsr4 = array(
		'W' =>
		array(
			'WCLM\\' => 5,
		),
	);

	public static $prefixDirsPsr4 = array(
		'WCLM\\' =>
		array(
			0 => __DIR__ . '/../..' . '/includes',
		),
	);

	public static $classMap = array(
		'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
	);

	public static function getInitializer( ClassLoader $loader ) {
		return \Closure::bind(
			function () use ( $loader ) {
				$loader->prefixLengthsPsr4 = ComposerStaticInit53e1f4ec2badef8598845b97b6789daf::$prefixLengthsPsr4;
				$loader->prefixDirsPsr4    = ComposerStaticInit53e1f4ec2badef8598845b97b6789daf::$prefixDirsPsr4;
				$loader->classMap          = ComposerStaticInit53e1f4ec2badef8598845b97b6789daf::$classMap;
			},
			null,
			ClassLoader::class
		);
	}
}
