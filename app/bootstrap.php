<?php

$config = json_decode( file_get_contents( __DIR__ . '/config.json' ), true );

if( json_last_error() != JSON_ERROR_NONE )
{
	echo "Invalid configuration data found in 'app/config.json'" . PHP_EOL;
	exit( 1 );
}

/*
 * Autoload
 */
$autoload = require_once( __DIR__ . '/autoload.php' );

foreach( $autoload as $module => $path )
{
	$baseDir = dirname( dirname( __FILE__ ) );
	$file = "{$baseDir}/{$path}/Autoloader.php";

	if( file_exists( $file ) )
	{
		include_once( $file );
		call_user_func( "{$module}\Autoloader::register" );
	}
	else
	{
		$install = strtolower( readline( "{$appName}: Install missing dependencies [yes]? " ) );

		switch ( $install )
		{
			case '':
			case 'y':
			case 'yes':
				chdir( __DIR__ );
				exec( 'git submodule update --init --recursive', $output, $exitCode );
				exit( $exitCode );
				break;

			default:
				exit( 1 );
				break;
		}
	}
}

/*
 * App configuration
 */
require_once( __DIR__ . '/app.php' );

if( !isset( $app ) )
{
	echo "Invalid app instantiation in 'app/app.php'" . PHP_EOL;
	exit( 1  );
}

/*
 * Register commands
 */
require_once( __DIR__ . '/commands.php' );

if( isset( $commands ) && is_array( $commands ) )
{
	foreach( $commands as $command )
	{
		if( $command instanceof Huxtable\CLI\Command )
		{
			$app->registerCommand( $command );
		}
		else
		{
			echo "Invalid command registered in 'app/commands.php'".PHP_EOL;
			exit( 1 );
		}
	}
}

// Attempt to run the requested command
$app->run();

// Stop application and exit
$app->stop();
