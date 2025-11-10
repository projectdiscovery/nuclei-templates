<?php
if ( !class_exists('Puc_v4p9_Vcs_Reference', false) ):

	/**
	 * This class represents a VCS branch or tag. It's intended as a read only, short-lived container
	 * that only exists to provide a limited degree of type checking.
	 *
	 * @property string $name
	 * @property string|null version
	 * @property string $downloadUrl
	 * @property string $updated
	 *
	 * @property string|null $changelog
	 * @property int|null $downloadCount
	 */
	class Puc_v4p9_Vcs_Reference {
		private $properties = array();

		public function __construct($properties = array()) {
			$this->properties = $properties;
		}

		/**
		 * @param string $name
		 * @return mixed|null
		 */
		public function __get($name) {
			return array_key_exists($name, $this->properties) ? $this->properties[$name] : null;
		}

		/**
		 * @param string $name
		 * @param mixed $value
		 */
		public function __set($name, $value) {
			$this->properties[$name] = $value;
		}

		/**
		 * @param string $name
		 * @return bool
		 */
		public function __isset($name) {
			return isset($this->properties[$name]);
		}

	}

endif;
