<?php
namespace Automattic\Jetpack\Autoloader;
if (!defined('ABSPATH')) exit;
class AutoloadProcessor {
 private $classmapScanner;
 private $pathCodeTransformer;
 public function __construct( $classmapScanner, $pathCodeTransformer ) {
 $this->classmapScanner = $classmapScanner;
 $this->pathCodeTransformer = $pathCodeTransformer;
 }
 public function processClassmap( $autoloads, $scanPsrPackages ) {
 // We can't scan PSR packages if we don't actually have any.
 if ( empty( $autoloads['psr-4'] ) ) {
 $scanPsrPackages = false;
 }
 if ( empty( $autoloads['classmap'] ) && ! $scanPsrPackages ) {
 return null;
 }
 $excludedClasses = null;
 if ( ! empty( $autoloads['exclude-from-classmap'] ) ) {
 $excludedClasses = '{(' . implode( '|', $autoloads['exclude-from-classmap'] ) . ')}';
 }
 $processed = array();
 if ( $scanPsrPackages ) {
 foreach ( $autoloads['psr-4'] as $namespace => $sources ) {
 $namespace = empty( $namespace ) ? null : $namespace;
 foreach ( $sources as $source ) {
 $classmap = call_user_func( $this->classmapScanner, $source['path'], $excludedClasses, $namespace );
 foreach ( $classmap as $class => $path ) {
 $processed[ $class ] = array(
 'version' => $source['version'],
 'path' => call_user_func( $this->pathCodeTransformer, $path ),
 );
 }
 }
 }
 }
 if ( ! empty( $autoloads['psr-0'] ) ) {
 foreach ( $autoloads['psr-0'] as $namespace => $sources ) {
 $namespace = empty( $namespace ) ? null : $namespace;
 foreach ( $sources as $source ) {
 $classmap = call_user_func( $this->classmapScanner, $source['path'], $excludedClasses, $namespace );
 foreach ( $classmap as $class => $path ) {
 $processed[ $class ] = array(
 'version' => $source['version'],
 'path' => call_user_func( $this->pathCodeTransformer, $path ),
 );
 }
 }
 }
 }
 if ( ! empty( $autoloads['classmap'] ) ) {
 foreach ( $autoloads['classmap'] as $package ) {
 $classmap = call_user_func( $this->classmapScanner, $package['path'], $excludedClasses, null );
 foreach ( $classmap as $class => $path ) {
 $processed[ $class ] = array(
 'version' => $package['version'],
 'path' => call_user_func( $this->pathCodeTransformer, $path ),
 );
 }
 }
 }
 ksort( $processed );
 return $processed;
 }
 public function processPsr4Packages( $autoloads, $scanPsrPackages ) {
 if ( $scanPsrPackages || empty( $autoloads['psr-4'] ) ) {
 return null;
 }
 $processed = array();
 foreach ( $autoloads['psr-4'] as $namespace => $packages ) {
 $namespace = empty( $namespace ) ? null : $namespace;
 $paths = array();
 foreach ( $packages as $package ) {
 $paths[] = call_user_func( $this->pathCodeTransformer, $package['path'] );
 }
 $processed[ $namespace ] = array(
 'version' => $package['version'],
 'path' => $paths,
 );
 }
 return $processed;
 }
 public function processFiles( $autoloads ) {
 if ( empty( $autoloads['files'] ) ) {
 return null;
 }
 $processed = array();
 foreach ( $autoloads['files'] as $file_id => $package ) {
 $processed[ $file_id ] = array(
 'version' => $package['version'],
 'path' => call_user_func( $this->pathCodeTransformer, $package['path'] ),
 );
 }
 return $processed;
 }
}
