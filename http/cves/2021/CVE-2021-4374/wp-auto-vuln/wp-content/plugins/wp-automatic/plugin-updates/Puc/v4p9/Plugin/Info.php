<?php
if ( !class_exists('Puc_v4p9_Plugin_Info', false) ):

	/**
	 * A container class for holding and transforming various plugin metadata.
	 *
	 * @author Janis Elsts
	 * @copyright 2016
	 * @access public
	 */
	class Puc_v4p9_Plugin_Info extends Puc_v4p9_Metadata {
		//Most fields map directly to the contents of the plugin's info.json file.
		//See the relevant docs for a description of their meaning.
		public $name;
		public $slug;
		public $version;
		public $homepage;
		public $sections = array();
		public $download_url;

		public $banners;
		public $icons = array();
		public $translations = array();

		public $author;
		public $author_homepage;

		public $requires;
		public $tested;
		public $upgrade_notice;

		public $rating;
		public $num_ratings;
		public $downloaded;
		public $active_installs;
		public $last_updated;

		public $id = 0; //The native WP.org API returns numeric plugin IDs, but they're not used for anything.

		public $filename; //Plugin filename relative to the plugins directory.

		/**
		 * Create a new instance of Plugin Info from JSON-encoded plugin info
		 * returned by an external update API.
		 *
		 * @param string $json Valid JSON string representing plugin info.
		 * @return self|null New instance of Plugin Info, or NULL on error.
		 */
		public static function fromJson($json){
			$instance = new self();

			if ( !parent::createFromJson($json, $instance) ) {
				return null;
			}

			//json_decode decodes assoc. arrays as objects. We want them as arrays.
			$instance->sections = (array)$instance->sections;
			$instance->icons = (array)$instance->icons;

			return $instance;
		}

		/**
		 * Very, very basic validation.
		 *
		 * @param StdClass $apiResponse
		 * @return bool|WP_Error
		 */
		protected function validateMetadata($apiResponse) {
			if (
				!isset($apiResponse->name, $apiResponse->version)
				|| empty($apiResponse->name)
				|| empty($apiResponse->version)
			) {
				return new WP_Error(
					'puc-invalid-metadata',
					"The plugin metadata file does not contain the required 'name' and/or 'version' keys."
				);
			}
			return true;
		}


		/**
		 * Transform plugin info into the format used by the native WordPress.org API
		 *
		 * @return object
		 */
		public function toWpFormat(){
			$info = new stdClass;

			//The custom update API is built so that many fields have the same name and format
			//as those returned by the native WordPress.org API. These can be assigned directly.
			$sameFormat = array(
				'name', 'slug', 'version', 'requires', 'tested', 'rating', 'upgrade_notice',
				'num_ratings', 'downloaded', 'active_installs', 'homepage', 'last_updated',
			);
			foreach($sameFormat as $field){
				if ( isset($this->$field) ) {
					$info->$field = $this->$field;
				} else {
					$info->$field = null;
				}
			}

			//Other fields need to be renamed and/or transformed.
			$info->download_link = $this->download_url;
			$info->author = $this->getFormattedAuthor();
			$info->sections = array_merge(array('description' => ''), $this->sections);

			if ( !empty($this->banners) ) {
				//WP expects an array with two keys: "high" and "low". Both are optional.
				//Docs: https://wordpress.org/plugins/about/faq/#banners
				$info->banners = is_object($this->banners) ? get_object_vars($this->banners) : $this->banners;
				$info->banners = array_intersect_key($info->banners, array('high' => true, 'low' => true));
			}

			return $info;
		}

		protected function getFormattedAuthor() {
			if ( !empty($this->author_homepage) ){
				/** @noinspection HtmlUnknownTarget */
				return sprintf('<a href="%s">%s</a>', $this->author_homepage, $this->author);
			}
			return $this->author;
		}
	}

endif;
