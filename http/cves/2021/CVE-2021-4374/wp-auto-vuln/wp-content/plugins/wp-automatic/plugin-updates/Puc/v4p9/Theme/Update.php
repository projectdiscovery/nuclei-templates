<?php

if ( !class_exists('Puc_v4p9_Theme_Update', false) ):

	class Puc_v4p9_Theme_Update extends Puc_v4p9_Update {
		public $details_url = '';

		protected static $extraFields = array('details_url');

		/**
		 * Transform the metadata into the format used by WordPress core.
		 * Note the inconsistency: WP stores plugin updates as objects and theme updates as arrays.
		 *
		 * @return array
		 */
		public function toWpFormat() {
			$update = array(
				'theme' => $this->slug,
				'new_version' => $this->version,
				'url' => $this->details_url,
			);

			if ( !empty($this->download_url) ) {
				$update['package'] = $this->download_url;
			}

			return $update;
		}

		/**
		 * Create a new instance of Theme_Update from its JSON-encoded representation.
		 *
		 * @param string $json Valid JSON string representing a theme information object.
		 * @return self New instance of ThemeUpdate, or NULL on error.
		 */
		public static function fromJson($json) {
			$instance = new self();
			if ( !parent::createFromJson($json, $instance) ) {
				return null;
			}
			return $instance;
		}

		/**
		 * Create a new instance by copying the necessary fields from another object.
		 *
		 * @param StdClass|Puc_v4p9_Theme_Update $object The source object.
		 * @return Puc_v4p9_Theme_Update The new copy.
		 */
		public static function fromObject($object) {
			$update = new self();
			$update->copyFields($object, $update);
			return $update;
		}

		/**
		 * Basic validation.
		 *
		 * @param StdClass $apiResponse
		 * @return bool|WP_Error
		 */
		protected function validateMetadata($apiResponse) {
			$required = array('version', 'details_url');
			foreach($required as $key) {
				if ( !isset($apiResponse->$key) || empty($apiResponse->$key) ) {
					return new WP_Error(
						'tuc-invalid-metadata',
						sprintf('The theme metadata is missing the required "%s" key.', $key)
					);
				}
			}
			return true;
		}

		protected function getFieldNames() {
			return array_merge(parent::getFieldNames(), self::$extraFields);
		}

		protected function getPrefixedFilter($tag) {
			return parent::getPrefixedFilter($tag) . '_theme';
		}
	}

endif;
