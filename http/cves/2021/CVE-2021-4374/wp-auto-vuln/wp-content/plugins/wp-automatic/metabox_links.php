<?php

// Globals
global $post;
global $wpdb;
global $camp_general;
global $post_id;
global $camp_options;
global $post_types;

global $camp_post_category;

?>

<div class="TTWForm-container" dir="ltr">
	<div class="TTWForm">
		<div class="panes">

			<div id="field1zz-container" class="field f_100">
				<div class="option clearfix">
					<input data-controls="post_strip_external" name="camp_options[]" id="field2-1" value="OPT_STRIP" type="checkbox"> <span class="option-title"> Strip original links from the post (hyperlinks) </span> <br>


					<div id="post_strip_external" class="field f_100">

						<input name="camp_options[]"   value="OPT_STRIP_EXT" type="checkbox"> <span class="option-title"> Only strip internal links and leave external links </span>


					</div>



				</div>
			</div>

			<div class="field f_100">
				<div class="option clearfix">
					<input data-controls="permalink_external" name="camp_options[]" value="OPT_LINK_SOURSE" type="checkbox"> <span class="option-title"> Make permalinks link directly to the source (Posts will not load at your site) </span> <br>
				
					<div id="permalink_external" class="field f_100">

						<span style="padding-left:20px;"><strong>TIP:</strong></span> If you want to make external links open on a new tab, install <a href="https://wordpress.org/plugins/open-external-links-in-a-new-window/" target="_blank">this free plugin</a>


					</div>
				
				</div>
			</div>
			

			<div class="field f_100">
				<div class="option clearfix">
					<input data-controls="canonical_fb" name="camp_options[]" value="OPT_LINK_CANONICAL" type="checkbox"> <span class="option-title"> Add <a href="http://moz.com/learn/seo/canonicalization">Canonical Tag</a> with the original post link to the post for SEO
					 </span> <br>
										<div id="canonical_fb" class="field f_100">

						<span style="padding-left:20px;"><strong>Alert:</strong></span> When a post from your site get shared to Facebook, original site link will appear instead of your site</a>


					</div>

				</div>
			</div>



			<div class="field f_100">
				<div class="option clearfix">
					<input   name="camp_options[]" value="OPT_LINK_ONCE" type="checkbox"> <span class="option-title"> Never post the same link again <br>( by default if it was completely deleted it may get posted again)
					</span> <br>


				</div>
			</div>


			<div class="field f_100">
				<div class="option clearfix">
					<input name="camp_options[]" value="OPT_NO_COMMENT_LINK" type="checkbox"> <span class="option-title"> Don't add links for author at comments <br>(Applicable if campaign supports comments & posting comments is active)
					</span> <br>
				</div>
			</div>

			<div class="field f_100">
				<div class="option clearfix">
					<input name="camp_options[]" value="OPT_LNK_BLNK" type="checkbox"> <span class="option-title"> Open links copied from the source in new tab (target="_blank") </span> <br>
				</div>
			</div>
			
			<div class="field f_100">
				<div class="option clearfix">
					<input name="camp_options[]" value="OPT_LNK_NOFOLLOW" type="checkbox"> <span class="option-title"> Add nofollow attribute to links  (rel="nofollow") </span> <br>
				</div>
			</div>


			<div class="clear"></div>
		</div>
	</div>
</div>
