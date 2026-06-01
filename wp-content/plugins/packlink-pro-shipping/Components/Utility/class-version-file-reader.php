<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Components\Utility;

/**
 * Class Version_File_Reader
 *
 * @package Packlink\WooCommerce\Components\Utility
 */
class Version_File_Reader {
	const MIGRATION_FILE_PREFIX = 'upgrade-';
	/**
	 * Migrations directory.
	 *
	 * @var string
	 */
	private $migrations_directory;
	/**
	 * Version number.
	 *
	 * @var string
	 */
	private $version;
	/**
	 * Files for execution.
	 *
	 * @var array
	 */
	private $sorted_files_for_execution = array();
	/**
	 * Pointer.
	 *
	 * @var int
	 */
	private $pointer = 0;

	/**
	 * Version_File_Reader constructor.
	 *
	 * @param string $migration_directory Migration directory.
	 * @param string $version Version.
	 */
	public function __construct( $migration_directory, $version ) {
		$this->migrations_directory = $migration_directory;
		$this->version              = $version;
	}

	/**
	 * Execute next migration script file from list of files for execution
	 *
	 * @return void
	 */
	public function execute() {
		if ( ! $this->has_next() ) {
			return;
		}

		include $this->migrations_directory . $this->sorted_files_for_execution[ $this->pointer ];
		$this->pointer ++;
	}

	/**
	 * Returns TRUE if there is next migration file to execute, FALSE otherwise.
	 *
	 * @return bool Has next migration to execute.
	 */
	public function has_next() {
		if ( empty( $this->sorted_files_for_execution ) ) {
			$this->sort_files();
		}

		return isset( $this->sorted_files_for_execution[ $this->pointer ] );
	}

	/**
	 * Sort and filter files for execution
	 */
	private function sort_files() {
		$files = array_diff( scandir( $this->migrations_directory, 0 ), array( '.', '..' ) );
		if ( $files ) {
			$self = $this;
			usort(
				$files,
				function ( $file1, $file2 ) use ( $self ) {
					$file_1_version = $self->get_file_version( $file1 );
					$file_2_version = $self->get_file_version( $file2 );

					return version_compare( $file_1_version, $file_2_version );
				}
			);

			foreach ( $files as $file ) {
				$file_version = $this->get_file_version( $file );
				if ( version_compare( $this->version, $file_version, '<' ) ) {
					$this->sorted_files_for_execution[] = $file;
				}
			}
		}
	}

	/**
	 * Get file version based on file name
	 *
	 * @param string $file File name.
	 *
	 * @return string
	 */
	private function get_file_version( $file ) {
		return str_ireplace( array( self::MIGRATION_FILE_PREFIX, '.php' ), '', $file );
	}
}
