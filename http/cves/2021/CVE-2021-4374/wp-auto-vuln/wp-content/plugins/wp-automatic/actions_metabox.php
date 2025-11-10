
<?php 

global $post;

?>  
<div class="spinner spinner_bulk"></div>
<p>
	<a class="bulk_action button" data-key="deleteAll" data-camp="<?php   echo $post->ID ?>" >Delete all posted posts</a>
</p>

<p>
<a class="bulk_action button" data-key="forgetExcluded" data-camp="<?php   echo $post->ID ?>" >Forget excluded posts</a>
</p>

<p>
<a class="bulk_action button" data-key="forgetPosted" data-camp="<?php   echo $post->ID ?>" >Forget posted URLs</a>
</p>

<p>
<a class="bulk_action button" data-key="reactivateAll" data-camp="<?php   echo $post->ID ?>" >Reactivate all keywords</a>
</p>
