<?php
namespace Automattic\Jetpack\Autoloader;
if (!defined('ABSPATH')) exit;
use Composer\Composer;
use Composer\Config;
use Composer\Installer\InstallationManager;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Util\Filesystem;
use Composer\Util\PackageSorter;
class AutoloadGenerator {
 const VERSION = '5.0.8';
 private $io;
 private $filesystem;
 public function __construct( IOInterface $io ) {
 $this->io = $io;
 $this->filesystem = new Filesystem();
 }
 public function dump(
 Composer $composer,
 Config $config,
 InstalledRepositoryInterface $localRepo,
 PackageInterface $mainPackage,
 InstallationManager $installationManager,
 $targetDir,
 $scanPsrPackages = false,
 $suffix = null
 ) {
 $this->filesystem->ensureDirectoryExists( $config->get( 'vendor-dir' ) );
 $packageMap = $composer->getAutoloadGenerator()->buildPackageMap( $installationManager, $mainPackage, $localRepo->getCanonicalPackages() );
 $autoloads = $this->parseAutoloads( $packageMap, $mainPackage );
 // Convert the autoloads into a format that the manifest generator can consume more easily.
 $basePath = $this->filesystem->normalizePath( realpath( getcwd() ) );
 $vendorPath = $this->filesystem->normalizePath( realpath( $config->get( 'vendor-dir' ) ) );
 $processedAutoloads = $this->processAutoloads( $autoloads, $scanPsrPackages, $vendorPath, $basePath );
 unset( $packageMap, $autoloads );
 // Make sure none of the legacy files remain that can lead to problems with the autoloader.
 $this->removeLegacyFiles( $vendorPath );
 // Write all of the files now that we're done.
 $this->writeAutoloaderFiles( $vendorPath . '/jetpack-autoloader/', $suffix );
 $this->writeManifests( $vendorPath . '/' . $targetDir, $processedAutoloads );
 if ( ! $scanPsrPackages ) {
 $this->io->writeError( '<warning>You are generating an unoptimized autoloader. If this is a production build, consider using the -o option.</warning>' );
 }
 }
 public function parseAutoloads( array $packageMap, PackageInterface $mainPackage ) {
 $rootPackageMap = array_shift( $packageMap );
 $sortedPackageMap = $this->sortPackageMap( $packageMap );
 $sortedPackageMap[] = $rootPackageMap;
 array_unshift( $packageMap, $rootPackageMap );
 $psr0 = $this->parseAutoloadsType( $packageMap, 'psr-0', $mainPackage );
 $psr4 = $this->parseAutoloadsType( $packageMap, 'psr-4', $mainPackage );
 $classmap = $this->parseAutoloadsType( array_reverse( $sortedPackageMap ), 'classmap', $mainPackage );
 $files = $this->parseAutoloadsType( $sortedPackageMap, 'files', $mainPackage );
 krsort( $psr0 );
 krsort( $psr4 );
 return array(
 'psr-0' => $psr0,
 'psr-4' => $psr4,
 'classmap' => $classmap,
 'files' => $files,
 );
 }
 protected function sortPackageMap( array $packageMap ) {
 $packages = array();
 $paths = array();
 foreach ( $packageMap as $item ) {
 list( $package, $path ) = $item;
 $name = $package->getName();
 $packages[ $name ] = $package;
 $paths[ $name ] = $path;
 }
 $sortedPackages = PackageSorter::sortPackages( $packages );
 $sortedPackageMap = array();
 foreach ( $sortedPackages as $package ) {
 $name = $package->getName();
 $sortedPackageMap[] = array( $packages[ $name ], $paths[ $name ] );
 }
 return $sortedPackageMap;
 }
 protected function getFileIdentifier( PackageInterface $package, $path ) {
 return md5( $package->getName() . ':' . $path );
 }
 protected function getPathCode( Filesystem $filesystem, $basePath, $vendorPath, $path ) {
 if ( ! $filesystem->isAbsolutePath( $path ) ) {
 $path = $basePath . '/' . $path;
 }
 $path = $filesystem->normalizePath( $path );
 $baseDir = '';
 if ( 0 === strpos( $path . '/', $vendorPath . '/' ) ) {
 $path = substr( $path, strlen( $vendorPath ) );
 $baseDir = '$vendorDir';
 if ( false !== $path ) {
 $baseDir .= ' . ';
 }
 } else {
 $path = $filesystem->normalizePath( $filesystem->findShortestPath( $basePath, $path, true ) );
 if ( ! $filesystem->isAbsolutePath( $path ) ) {
 $baseDir = '$baseDir . ';
 $path = '/' . $path;
 }
 }
 if ( strpos( $path, '.phar' ) !== false ) {
 $baseDir = "'phar://' . " . $baseDir;
 }
 // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
 return $baseDir . ( ( false !== $path ) ? var_export( $path, true ) : '' );
 }
 protected function parseAutoloadsType( array $packageMap, $type, PackageInterface $mainPackage ) {
 $autoloads = array();
 foreach ( $packageMap as $item ) {
 list($package, $installPath) = $item;
 $autoload = $package->getAutoload();
 $version = $package->getVersion(); // Version of the class comes from the package - should we try to parse it?
 // Store our own actual package version, not "dev-trunk" or whatever.
 if ( $package->getName() === 'automattic/jetpack-autoloader' ) {
 $version = self::VERSION;
 }
 if ( $package === $mainPackage ) {
 $autoload = array_merge_recursive( $autoload, $package->getDevAutoload() );
 }
 if ( null !== $package->getTargetDir() && $package !== $mainPackage ) {
 $installPath = substr( $installPath, 0, -strlen( '/' . $package->getTargetDir() ) );
 }
 if ( in_array( $type, array( 'psr-4', 'psr-0' ), true ) && isset( $autoload[ $type ] ) && is_array( $autoload[ $type ] ) ) {
 foreach ( $autoload[ $type ] as $namespace => $paths ) {
 $paths = is_array( $paths ) ? $paths : array( $paths );
 foreach ( $paths as $path ) {
 $relativePath = empty( $installPath ) ? ( empty( $path ) ? '.' : $path ) : $installPath . '/' . $path;
 $autoloads[ $namespace ][] = array(
 'path' => $relativePath,
 'version' => $version,
 );
 }
 }
 }
 if ( 'classmap' === $type && isset( $autoload['classmap'] ) && is_array( $autoload['classmap'] ) ) {
 foreach ( $autoload['classmap'] as $paths ) {
 $paths = is_array( $paths ) ? $paths : array( $paths );
 foreach ( $paths as $path ) {
 $relativePath = empty( $installPath ) ? ( empty( $path ) ? '.' : $path ) : $installPath . '/' . $path;
 $autoloads[] = array(
 'path' => $relativePath,
 'version' => $version,
 );
 }
 }
 }
 if ( 'files' === $type && isset( $autoload['files'] ) && is_array( $autoload['files'] ) ) {
 foreach ( $autoload['files'] as $paths ) {
 $paths = is_array( $paths ) ? $paths : array( $paths );
 foreach ( $paths as $path ) {
 $relativePath = empty( $installPath ) ? ( empty( $path ) ? '.' : $path ) : $installPath . '/' . $path;
 $autoloads[ $this->getFileIdentifier( $package, $path ) ] = array(
 'path' => $relativePath,
 'version' => $version,
 );
 }
 }
 }
 }
 return $autoloads;
 }
 private function processAutoloads( $autoloads, $scanPsrPackages, $vendorPath, $basePath ) {
 $processor = new AutoloadProcessor(
 function ( $path, $excludedClasses, $namespace ) use ( $basePath ) {
 $dir = $this->filesystem->normalizePath(
 $this->filesystem->isAbsolutePath( $path ) ? $path : $basePath . '/' . $path
 );
 // Composer 2.4 changed the name of the class.
 if ( class_exists( \Composer\ClassMapGenerator\ClassMapGenerator::class ) ) {
 if ( ! is_dir( $dir ) && ! is_file( $dir ) ) {
 return array();
 }
 $generator = new \Composer\ClassMapGenerator\ClassMapGenerator();
 $generator->scanPaths( $dir, $excludedClasses, 'classmap', empty( $namespace ) ? null : $namespace );
 return $generator->getClassMap()->getMap();
 }
 return \Composer\Autoload\ClassMapGenerator::createMap(
 $dir,
 $excludedClasses,
 null, // Don't pass the IOInterface since the normal autoload generation will have reported already.
 empty( $namespace ) ? null : $namespace
 );
 },
 function ( $path ) use ( $basePath, $vendorPath ) {
 return $this->getPathCode( $this->filesystem, $basePath, $vendorPath, $path );
 }
 );
 return array(
 'psr-4' => $processor->processPsr4Packages( $autoloads, $scanPsrPackages ),
 'classmap' => $processor->processClassmap( $autoloads, $scanPsrPackages ),
 'files' => $processor->processFiles( $autoloads ),
 );
 }
 private function removeLegacyFiles( $outDir ) {
 $files = array(
 'autoload_functions.php',
 'class-autoloader-handler.php',
 'class-classes-handler.php',
 'class-files-handler.php',
 'class-plugins-handler.php',
 'class-version-selector.php',
 );
 foreach ( $files as $file ) {
 $this->filesystem->remove( $outDir . '/' . $file );
 }
 }
 private function writeAutoloaderFiles( $outDir, $suffix ) {
 $this->io->writeError( "<info>Generating jetpack autoloader ($outDir)</info>" );
 // We will remove all autoloader files to generate this again.
 $this->filesystem->emptyDirectory( $outDir );
 // Write the autoloader files.
 AutoloadFileWriter::copyAutoloaderFiles( $this->io, $outDir, $suffix );
 }
 private function writeManifests( $outDir, $processedAutoloads ) {
 $this->io->writeError( "<info>Generating jetpack autoloader manifests ($outDir)</info>" );
 $manifestFiles = array(
 'classmap' => 'jetpack_autoload_classmap.php',
 'psr-4' => 'jetpack_autoload_psr4.php',
 'files' => 'jetpack_autoload_filemap.php',
 );
 foreach ( $manifestFiles as $key => $file ) {
 // Make sure the file doesn't exist so it isn't there if we don't write it.
 $this->filesystem->remove( $outDir . '/' . $file );
 if ( empty( $processedAutoloads[ $key ] ) ) {
 continue;
 }
 $content = ManifestGenerator::buildManifest( $key, $file, $processedAutoloads[ $key ] );
 if ( empty( $content ) ) {
 continue;
 }
 if ( file_put_contents( $outDir . '/' . $file, $content ) ) {
 $this->io->writeError( " <info>Generated: $file</info>" );
 } else {
 $this->io->writeError( " <error>Error: $file</error>" );
 }
 }
 }
}
