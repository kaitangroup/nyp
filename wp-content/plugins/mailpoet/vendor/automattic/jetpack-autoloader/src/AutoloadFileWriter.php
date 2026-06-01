<?php
namespace Automattic\Jetpack\Autoloader;
if (!defined('ABSPATH')) exit;
use Composer\IO\IOInterface;
class AutoloadFileWriter {
 const COMMENT = <<<AUTOLOADER_COMMENT
AUTOLOADER_COMMENT;
 public static function copyAutoloaderFiles( $io, $outDir, $suffix ) {
 $renameList = array(
 'autoload.php' => '../autoload_packages.php',
 );
 $ignoreList = array(
 'AutoloadGenerator.php',
 'AutoloadProcessor.php',
 'CustomAutoloaderPlugin.php',
 'ManifestGenerator.php',
 'AutoloadFileWriter.php',
 );
 // Copy all of the autoloader files.
 $files = scandir( __DIR__ );
 foreach ( $files as $file ) {
 // Only PHP files will be copied.
 if ( substr( $file, -4 ) !== '.php' ) {
 continue;
 }
 if ( in_array( $file, $ignoreList, true ) ) {
 continue;
 }
 $newFile = $renameList[ $file ] ?? $file;
 $content = self::prepareAutoloaderFile( $file, $suffix );
 $written = file_put_contents( $outDir . '/' . $newFile, $content );
 if ( $io ) {
 if ( $written ) {
 $io->writeError( " <info>Generated: $newFile</info>" );
 } else {
 $io->writeError( " <error>Error: $newFile</error>" );
 }
 }
 }
 }
 private static function prepareAutoloaderFile( $filename, $suffix ) {
 $header = self::COMMENT;
 $header .= PHP_EOL;
 if ( $suffix === 'Current' ) {
 // Unit testing.
 $header .= 'namespace Automattic\Jetpack\Autoloader\jpCurrent;';
 } else {
 $header .= 'namespace Automattic\Jetpack\Autoloader\jp' . $suffix . '\al' . preg_replace( '/[^0-9a-zA-Z]/', '_', AutoloadGenerator::VERSION ) . ';';
 }
 $header .= PHP_EOL . PHP_EOL;
 $sourceLoader = fopen( __DIR__ . '/' . $filename, 'r' );
 $file_contents = stream_get_contents( $sourceLoader );
 return str_replace(
 '/* HEADER */',
 $header,
 $file_contents
 );
 }
}
