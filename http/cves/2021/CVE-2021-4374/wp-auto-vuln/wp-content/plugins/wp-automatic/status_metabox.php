<?php 

global $post;
global  $wpdb;
$prefix=$wpdb->prefix;
$post_id=$post->ID;

$query="SELECT * FROM {$prefix}automatic_camps where camp_id=$post_id ";
$rows=$wpdb->get_results($query);
if (count($rows) == 0 ) {

	$arrow=plugins_url('images/down-arrow-icon.png',__FILE__);
	 
	  echo 'Update <strong>campaign options</strong> below then hit publish <img style="width:25px;height:20px" src="'.$arrow.'" />';
	
}else{

	//read last post
	$query="SELECT * FROM {$prefix}automatic_log where action='Posted:$post_id' order by id DESC";
	$rows=$wpdb->get_results($query);
	
	if (count($rows)== 0 ){
		$lastpost='None';
	}else{
		$row=$rows[0];
		$lastpost= $row->data;
	}
	
	//read last processed
	$query="SELECT * FROM {$prefix}automatic_log where action like '%Processing Campaign:$post_id' order by id DESC limit 1";
	$rows=$wpdb->get_results($query);
	
	if (count($rows)== 0 ){
		$lastprocess='None';
	}else{
		$row=$rows[0];
		$lastprocess= get_date_from_gmt($row->date);
		
		
		
	}
	
	?>
	
	<div dir="ltr" id="campaign_status" >
		<div class="campaign_detail">
			<strong>Run campaign</strong><br>
			
			<?php 
			
			$wp_automatic_secret = trim(get_option('wp_automatic_cron_secret'));
			if(trim($wp_automatic_secret) == '') $wp_automatic_secret = 'cron';
			
			$cronUrl = home_url('?wp_automatic='.$wp_automatic_secret.'&id='.$post_id);
			$cronUrl = str_replace('http:', '', $cronUrl);
			
			?>
			
			<a class='run' id="campaign_run" href="<?php   echo  $cronUrl  ?>"></a><br>
			
		</div>
		<div class="campaign_detail">
			<strong>Last Run</strong><br>
			<?php   echo '<div id="last_run_date">'. $lastprocess . '</div>' ?>
		</div>
		<div class="campaign_detail">
			<strong>Last Post</strong><br>
			<?php   echo '<div id="last_post">'. str_replace('New post posted:','',$lastpost) .'</div>' ?>
		</div>
		
		<div class="clear"></div>
	</div>
	
	 
	<?php 
	
}

?>