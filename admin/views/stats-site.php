<?php screen_icon('options-general'); ?>
<h2><?php _e('Sailthru Stats'); ?></h2>

<div id="details" class="content">
	<form method="POST" action="" enctype="multipart/form-data">
		<p>Get detailed information about Primary Lists in your Sailthru account</p>
		<h2>Date:</h2>
		<p>
			<input id="sailthru_date" type="input" name="sailthru_date" value="<?php echo isset($_POST['sailthru_date']) ? $_POST['sailthru_date'] : '';?>" />(leave blank for today)<br />
		</p>
		<p><input type="submit" id="stat_submit" style="font-size: 120%;"></p>
	</form>
	<div class="qq-upload-spinner" style="display: none;"></div>
	<?php
	$sailthru_date = isset($_POST['sailthru_date']) ? $_POST['sailthru_date'] : '';
	if(isset($_POST['sailthru_date'])) {
	?>
	<hr>
	<table style="width: 700px;">
		<tr>
			<td><b>List Name</b></td>
			<td><b>Valid Count</b></td>
		</tr>
		<?php
		$lists = My_Sailthru::get_lists();
		foreach ($lists as $key => $value){
			$list = My_Sailthru::get_list($value["name"]);
			$stats = My_Sailthru::get_stats($value["name"],$sailthru_date);
			$valid_count = ($stats['email_count'] - $stats['optout_count'] - $stats['hardbounce_count']);
			if ($list['primary']==1){
				echo "<tr>";
				echo "<td>" . $value["name"] . "</td>";
				echo "<td>" . $valid_count . "</td>";
				echo "</tr>";
				$valid_total += $valid_count;
			}
		}
		echo "<tr></tr>";
		echo "<tr>";
		echo "<td><b>Total</b></td>";
		echo "<td><b>" . $valid_total . "</b></td>";
		echo "</tr>";
		echo "</table>";
		}?>
</div>