<?php
if ( !interface_exists('Puc_v4p9_Vcs_BaseChecker', false) ):

	interface Puc_v4p9_Vcs_BaseChecker {
		/**
		 * Set the repository branch to use for updates. Defaults to 'master'.
		 *
		 * @param string $branch
		 * @return $this
		 */
		public function setBranch($branch);

		/**
		 * Set authentication credentials.
		 *
		 * @param array|string $credentials
		 * @return $this
		 */
		public function setAuthentication($credentials);

		/**
		 * @return Puc_v4p9_Vcs_Api
		 */
		public function getVcsApi();
	}

endif;
