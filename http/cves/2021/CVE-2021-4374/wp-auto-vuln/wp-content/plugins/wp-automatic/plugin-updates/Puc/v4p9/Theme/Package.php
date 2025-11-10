<?php
if ( !class_exists('Puc_v4p9_Theme_Package', false) ):

	class Puc_v4p9_Theme_Package extends Puc_v4p9_InstalledPackage {
		/**
		 * @var string Theme directory name.
		 */
		protected $stylesheet;

		/**
		 * @var WP_Theme Theme object.
		 */
		protected $theme;

		public function __construct($stylesheet, $updateChecker) {
			$this->stylesheet = $stylesheet;
			$this->theme = wp_get_theme($this->stylesheet);

			parent::__construct($updateChecker);
		}

		public function getInstalledVersion() {
			return $this->theme->get('Version');
		}

		public function getAbsoluteDirectoryPath() {
			if ( method_exists($this->theme, 'get_stylesheet_directory') ) {
				return $this->theme->get_stylesheet_directory(); //Available since WP 3.4.
			}
			return get_theme_root($this->stylesheet) . '/' . $this->stylesheet;
		}

		/**
		 * Get the value of a specific plugin or theme header.
		 *
		 * @param string $headerName
		 * @param string $defaultValue
		 * @return string Either the value of the header, or $defaultValue if the header doesn't exist or is empty.
		 */
		public function getHeaderValue($headerName, $defaultValue = '') {
			$value = $this->theme->get($headerName);
			if ( ($headerName === false) || ($headerName === '') ) {
				return $defaultValue;
			}
			return $value;
		}

		protected function getHeaderNames() {
			return array(
				'Name'        => 'Theme Name',
				'ThemeURI'    => 'Theme URI',
				'Description' => 'Description',
				'Author'      => 'Author',
				'AuthorURI'   => 'Author URI',
				'Version'     => 'Version',
				'Template'    => 'Template',
				'Status'      => 'Status',
				'Tags'        => 'Tags',
				'TextDomain'  => 'Text Domain',
				'DomainPath'  => 'Domain Path',
			);
		}
	}

endif;
