<?php

if ( !class_exists('Puc_v4p9_Utils', false) ):

	class Puc_v4p9_Utils {
		/**
		 * Get a value from a nested array or object based on a path.
		 *
		 * @param array|object|null $collection Get an entry from this array.
		 * @param array|string $path A list of array keys in hierarchy order, or a string path like "foo.bar.baz".
		 * @param mixed $default The value to return if the specified path is not found.
		 * @param string $separator Path element separator. Only applies to string paths.
		 * @return mixed
		 */
		public static function get($collection, $path, $default = null, $separator = '.') {
			if ( is_string($path) ) {
				$path = explode($separator, $path);
			}

			//Follow the $path into $input as far as possible.
			$currentValue = $collection;
			foreach ($path as $node) {
				if ( is_array($currentValue) && isset($currentValue[$node]) ) {
					$currentValue = $currentValue[$node];
				} else if ( is_object($currentValue) && isset($currentValue->$node) ) {
					$currentValue = $currentValue->$node;
				} else {
					return $default;
				}
			}

			return $currentValue;
		}

		/**
		 * Get the first array element that is not empty.
		 *
		 * @param array $values
		 * @param mixed|null $default Returns this value if there are no non-empty elements.
		 * @return mixed|null
		 */
		public static function findNotEmpty($values, $default = null) {
			if ( empty($values) ) {
				return $default;
			}

			foreach ($values as $value) {
				if ( !empty($value) ) {
					return $value;
				}
			}

			return $default;
		}

		/**
		 * Check if the input string starts with the specified prefix.
		 *
		 * @param string $input
		 * @param string $prefix
		 * @return bool
		 */
		public static function startsWith($input, $prefix) {
			$length = strlen($prefix);
			return (substr($input, 0, $length) === $prefix);
		}
	}

endif;
