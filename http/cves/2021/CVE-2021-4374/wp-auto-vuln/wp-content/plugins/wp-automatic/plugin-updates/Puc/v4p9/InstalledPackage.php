<?php
if ( !class_exists('Puc_v4p9_InstalledPackage', false) ):

	/**
	 * This class represents a currently installed plugin or theme.
	 *
	 * Not to be confused with the "package" field in WP update API responses that contains
	 * the download URL of a the new version.
	 */
	abstract class Puc_v4p9_InstalledPackage {
		/**
		 * @var Puc_v4p9_UpdateChecker
		 */
		protected $updateChecker;

		public function __construct($updateChecker) {
			$this->updateChecker = $updateChecker;
		}

		/**
		 * Get the currently installed version of the plugin or theme.
		 *
		 * @return string|null Version number.
		 */
		abstract public function getInstalledVersion();

		/**
		 * Get the full path of the plugin or theme directory (without a trailing slash).
		 *
		 * @return string
		 */
		abstract public function getAbsoluteDirectoryPath();

		/**
		 * Check whether a regular file exists in the package's directory.
		 *
		 * @param string $relativeFileName File name relative to the package directory.
		 * @return bool
		 */
		public function fileExists($relativeFileName) {
			return is_file(
				$this->getAbsoluteDirectoryPath()
				. DIRECTORY_SEPARATOR
				. ltrim($relativeFileName, '/\\')
			);
		}

		/* -------------------------------------------------------------------
		 * File header parsing
		 * -------------------------------------------------------------------
		 */

		/**
		 * Parse plugin or theme metadata from the header comment.
		 *
		 * This is basically a simplified version of the get_file_data() function from /wp-includes/functions.php.
		 * It's intended as a utility for subclasses that detect updates by parsing files in a VCS.
		 *
		 * @param string|null $content File contents.
		 * @return string[]
		 */
		public function getFileHeader($content) {
			$content = (string)$content;

			//WordPress only looks at the first 8 KiB of the file, so we do the same.
			$content = substr($content, 0, 8192);
			//Normalize line endings.
			$content = str_replace("\r", "\n", $content);

			$headers = $this->getHeaderNames();
			$results = array();
			foreach ($headers as $field => $name) {
				$success = preg_match('/^[ \t\/*#@]*' . preg_quote($name, '/') . ':(.*)$/mi', $content, $matches);

				if ( ($success === 1) && $matches[1] ) {
					$value = $matches[1];
					if ( function_exists('_cleanup_header_comment') ) {
						$value = _cleanup_header_comment($value);
					}
					$results[$field] = $value;
				} else {
					$results[$field] = '';
				}
			}

			return $results;
		}

		/**
		 * @return array Format: ['HeaderKey' => 'Header Name']
		 */
		abstract protected function getHeaderNames();

		/**
		 * Get the value of a specific plugin or theme header.
		 *
		 * @param string $headerName
		 * @return string Either the value of the header, or an empty string if the header doesn't exist.
		 */
		abstract public function getHeaderValue($headerName);

	}
endif;
