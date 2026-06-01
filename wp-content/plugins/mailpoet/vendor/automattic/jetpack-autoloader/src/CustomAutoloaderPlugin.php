<?php
namespace Automattic\Jetpack\Autoloader;
if (!defined('ABSPATH')) exit;
use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
class CustomAutoloaderPlugin implements PluginInterface, EventSubscriberInterface {
 private $io;
 private $composer;
 public function activate( Composer $composer, IOInterface $io ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
 $this->composer = $composer;
 $this->io = $io;
 }
 public function deactivate( Composer $composer, IOInterface $io ) {
 }
 public function uninstall( Composer $composer, IOInterface $io ) {
 }
 public static function getSubscribedEvents() {
 return array(
 ScriptEvents::POST_AUTOLOAD_DUMP => 'postAutoloadDump',
 );
 }
 public function postAutoloadDump( Event $event ) {
 // When the autoloader is not required by the root package we don't want to execute it.
 // This prevents unwanted transitive execution that generates unused autoloaders or
 // at worst throws fatal executions.
 if ( ! $this->isRequiredByRoot() ) {
 return;
 }
 $config = $this->composer->getConfig();
 if ( 'vendor' !== $config->raw()['config']['vendor-dir'] ) {
 $this->io->writeError( "\n<error>An error occurred while generating the autoloader files:", true );
 $this->io->writeError( 'The project\'s composer.json or composer environment set a non-default vendor directory.', true );
 $this->io->writeError( 'The default composer vendor directory must be used.</error>', true );
 exit( 0 );
 }
 $installationManager = $this->composer->getInstallationManager();
 $repoManager = $this->composer->getRepositoryManager();
 $localRepo = $repoManager->getLocalRepository();
 $package = $this->composer->getPackage();
 $optimize = $event->getFlags()['optimize'];
 $suffix = $this->determineSuffix();
 $generator = new AutoloadGenerator( $this->io );
 $generator->dump( $this->composer, $config, $localRepo, $package, $installationManager, 'composer', $optimize, $suffix );
 }
 private function determineSuffix() {
 $config = $this->composer->getConfig();
 $vendorPath = $config->get( 'vendor-dir' );
 // Command line.
 $suffix = $config->get( 'autoloader-suffix' );
 if ( $suffix ) {
 return $suffix;
 }
 // Reuse our own suffix, if any.
 if ( is_readable( $vendorPath . '/autoload_packages.php' ) ) {
 $content = file_get_contents( $vendorPath . '/autoload_packages.php' );
 if ( preg_match( '/^namespace Automattic\\\\Jetpack\\\\Autoloader\\\\jp([^;\s]+?)(?:\\\\al[^;\s]+)?;/m', $content, $match ) ) {
 return $match[1];
 }
 }
 // Reuse Composer's suffix, if any.
 if ( is_readable( $vendorPath . '/autoload.php' ) ) {
 $content = file_get_contents( $vendorPath . '/autoload.php' );
 if ( preg_match( '{ComposerAutoloaderInit([^:\s]+)::}', $content, $match ) ) {
 return $match[1];
 }
 }
 // Generate a random suffix.
 return md5( uniqid( '', true ) );
 }
 private function isRequiredByRoot() {
 $package = $this->composer->getPackage();
 $requires = $package->getRequires();
 if ( ! is_array( $requires ) ) { // @phan-suppress-current-line PhanRedundantCondition -- Earlier Composer versions may not have guaranteed this.
 $requires = array();
 }
 $devRequires = $package->getDevRequires();
 if ( ! is_array( $devRequires ) ) { // @phan-suppress-current-line PhanRedundantCondition -- Earlier Composer versions may not have guaranteed this.
 $devRequires = array();
 }
 $requires = array_merge( $requires, $devRequires );
 if ( empty( $requires ) ) {
 $this->io->writeError( "\n<error>The package is not required and this should never happen?</error>", true );
 exit( 0 );
 }
 foreach ( $requires as $require ) {
 if ( 'automattic/jetpack-autoloader' === $require->getTarget() ) {
 return true;
 }
 }
 return false;
 }
}
