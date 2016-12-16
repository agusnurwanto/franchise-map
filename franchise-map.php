<?php
/**
 * Plugin Name: Franchise Map
 * Plugin URI: https://github.com/agusnurwanto
 * Description: Show franchise locations to potential customers.
 * Version: 2.6.4
 * Author: Agus Nurwanto
 * Author URI: https://github.com/agusnurwanto
 * Requires at least: 4.7
 * Tested up to: 4.7
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
add_action( 'wp_enqueue_scripts', 'franchise_enqueue' );
function franchise_enqueue(){
	wp_localize_script( 'jquery', 'franchise_config', array( 'ajax_url' => admin_url( 'admin-ajax.php' )) ); 
}

add_action( 'admin_menu', 'franchise_menu' );

function franchise_menu() {
	add_menu_page( 'Franchise Map', 'Franchise Map', 'manage_options', 'franchise-map', 'my_plugin_options' );
}

function my_plugin_options() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	if(
		!empty($_POST) 
		&& !empty($_POST['franchise_nonce_field']) 
		&& wp_verify_nonce( $_POST['franchise_nonce_field'], 'franchise_action' )
		&& !empty($_FILES)
	){
		foreach ($_FILES as $key => $value) {
			if(
				!empty($_FILES[$key]['tmp_name'])
				&& (
					$key == 'location-franchise' 
					|| $key == 'zip_code-franchise'
				)
			){
				$errors= array();
			    $file_name = $_FILES[$key]['name'];
			    $file_size =$_FILES[$key]['size'];
			    $file_tmp =$_FILES[$key]['tmp_name'];
			    $file_type=$_FILES[$key]['type'];
			    $x = explode('.',$file_name);
			    $file_ext=strtolower(end($x));
				$expensions= array("csv");
			    if(in_array($file_ext,$expensions)=== false){
			        $errors[]="extension not allowed, please choose a JPEG or PNG file.";
			    }
			    if($file_size > 2097152){
			        $errors[]='File size must be excately 2 MB';
			    }
				if(empty($errors)==true){
					if ( ! function_exists( 'wp_handle_upload' ) ) {
					    require_once( ABSPATH . 'wp-admin/includes/file.php' );
					}
					$upload_overrides = array( 'test_form' => false );
					$movefile = wp_handle_upload( $_FILES[$key], $upload_overrides );
					if ( $movefile && ! isset( $movefile['error'] ) ) {
			        	$csv = file_get_contents($movefile['url']);
			        	$lines = explode(PHP_EOL, $csv);
						$array = array();
						foreach ($lines as $line) {
						    $array[] = str_getcsv($line);
						}
			        	$data = array();
			        	$count = count($array[0]);
			        	if($key == 'location-franchise'){
			        		if($count == 6){
			        			foreach ($array as $k => $v) {
			        				if($k==0 || empty($v[0]))
			        					continue;
			        				$regions = explode(',', str_replace(';', '', $v[5]));
			        				foreach ($regions as $val) {
				        				foreach ($v as $key2 => $value) {
				        					$data[$val][$v[1]][$array[0][$key2]] = $value;
				        				}
				        			}
			        			}
			        		}else{
			        			$errors[] = "This is not location csv format.";
			        		}
			        	}else{
			        		if($count == 5){
			        			foreach ($array as $k => $v) {
			        				if($k==0 || empty($v[0]))
			        					continue;
			        				foreach ($v as $key2 => $value) {
			        					$data[$v[0]][$array[0][$key2]] = $value;
			        				}
			        			}
			        		}else{
			        			$errors[] = "This is not zip code csv format.";
			        		}
			        	}
						if(empty($errors)==true){
				        	$save_file = $movefile['file'].'.save';
				        	$save_url = $movefile['url'].'.save';
			        		update_option( $key, $save_url );
			        		file_put_contents($save_file, serialize($data));
				        	echo "<b>Success upload ".$key.".csv to ".$save_file."</b><br>";
					    }else{
					        echo "<pre>".print_r($errors, 1)."</pre>";
					    }
			        	unlink($movefile['file']);
					} else {
			        	echo "<pre>".print_r($movefile, 1)."</pre>";
					}
			    }else{
			        echo "<pre>".print_r($errors, 1)."</pre>";
			    }
			}
		}
	}
?>
    <link href="<?php echo plugins_url('css/bootstrap.min.css', __FILE__); ?>" rel="stylesheet">
    <link href="<?php echo plugins_url('css/jquery.dataTables.min.css', __FILE__); ?>" rel="stylesheet">
    <script src="<?php echo plugins_url('js/promise.js', __FILE__); ?>"></script>
    <script src="<?php echo plugins_url('js/bootstrap.min.js', __FILE__); ?>"></script>
    <script src="<?php echo plugins_url('js/jquery.dataTables.min.js', __FILE__); ?>"></script>
    <div class="row">
    	<div class="col-md-12">
    		<h2>Franchise Map</h2>
			<form enctype="multipart/form-data" method="POST">
				<div class="form-group">
		    		<label for="location">Upload Location CSV</label>
		    		<input type="file" id="location" name="location-franchise">
				</div>
				<div class="form-group">
		    		<label for="zip_code">Uload Zip Code CSV</label>
		    		<input type="file" id="zip_code" name="zip_code-franchise">
				</div>
				<?php wp_nonce_field( 'franchise_action', 'franchise_nonce_field' ); ?>
		  		<button type="submit" class="btn btn-primary">Submit</button>
			</form>
		</div>
	</div>
<?php
	$file_location = get_option('location-franchise', true);
	$file_zip_code = get_option('zip_code-franchise', true);
	if(!empty($file_location)){
		$data = unserialize(file_get_contents($file_location));
		$body = "";
		$newData = array();
		foreach ($data as $k => $v) {
			foreach ($v as $key => $value) {
				$newData[$key] = $value;
			}
		}
		foreach ($newData as $k => $v) {
			$body .= "<tr>";
			foreach ($v as $key => $value) {
				$body .= "<td>".$value."</td>";
			}
			$body .= "</tr>";
		}
?>	
	<div class="row">
		<div class="col-md-12">
			<h3>Location</h3>
			<table id="table-locations" class="table table-striped table-bordered">
				<thead>
					<tr>
						<th>ID</th>
						<th>Franchise Name</th>
						<th>Phone</th>
						<th>Website</th>
						<th>Email</th>
						<th>County codes</th>
					</tr>
				</thead>
				<tbody>
					<?php echo $body; ?>
				</tbody>
			</table>
			<script>
				jQuery('#table-locations').DataTable();
			</script>
		</div>
	</div>
<?php
	}
	if(!empty($file_zip_code)){
		$data = unserialize(file_get_contents($file_zip_code));
		$body = "";
		foreach ($data as $k => $v) {
			$body .= "<tr>";
			foreach ($v as $key => $value) {
				$body .= "<td>".$value."</td>";
			}
			$body .= "</tr>";
		}
?>	
	<div class="row">
		<div class="col-md-12">
			<h3>Zip Codes</h3>
			<table id="table-zip-code" class="table table-striped table-bordered">
				<thead>
					<tr>
						<th>Zip</th>
						<th>County codes</th>
						<th>City</th>
						<th>ST</th>
						<th>County</th>
					</tr>
				</thead>
				<tbody>
					<?php echo $body; ?>
				</tbody>
			</table>
			<script>
				jQuery('#table-zip-code').DataTable();
			</script>
		</div>
	</div>
<?php
	}
}

add_action('wpv_after_top_header', 'set_franchise_finder');

function set_franchise_finder(){
?>
	<style>
		.close {
			float: right;
			font-size: 21px;
			font-weight: bold;
			line-height: 1;
			color: #000000;
			text-shadow: 0 1px 0 #ffffff;
			opacity: 0.2;
			filter: alpha(opacity=20);
		}
		.close:hover,
		.close:focus {
			color: #000000;
			text-decoration: none;
			cursor: pointer;
			opacity: 0.5;
			filter: alpha(opacity=50);
		}
		button.close {
			padding: 0;
			cursor: pointer;
			background: transparent;
			border: 0;
			-webkit-appearance: none;
		}
		.modal-open {
			overflow: hidden;
		}
		.modal {
			display: none;
			overflow: hidden;
			position: fixed;
			top: 0;
			right: 0;
			bottom: 0;
			left: 0;
			z-index: 1050;
			-webkit-overflow-scrolling: touch;
			outline: 0;
		}
		.modal.fade .modal-dialog {
			-webkit-transform: translate(0, -25%);
			-ms-transform: translate(0, -25%);
			-o-transform: translate(0, -25%);
			transform: translate(0, -25%);
			-webkit-transition: -webkit-transform 0.3s ease-out;
			-o-transition: -o-transform 0.3s ease-out;
			transition: transform 0.3s ease-out;
		}
		.modal.in .modal-dialog {
			-webkit-transform: translate(0, 0);
			-ms-transform: translate(0, 0);
			-o-transform: translate(0, 0);
			transform: translate(0, 0);
		}
		.modal-open .modal {
			overflow-x: hidden;
			overflow-y: auto;
		}
		.modal-dialog {
			position: relative;
			width: auto;
			margin: 10px;
		}
		.modal-content {
			position: relative;
			background-color: #ffffff;
			border: 1px solid #999999;
			border: 1px solid rgba(0, 0, 0, 0.2);
			border-radius: 6px;
			-webkit-box-shadow: 0 3px 9px rgba(0, 0, 0, 0.5);
			box-shadow: 0 3px 9px rgba(0, 0, 0, 0.5);
			-webkit-background-clip: padding-box;
			background-clip: padding-box;
			outline: 0;
		}
		.modal-backdrop {
			position: fixed;
			top: 0;
			right: 0;
			bottom: 0;
			left: 0;
			z-index: 1040;
			background-color: #000000;
		}
		.modal-backdrop.fade {
			opacity: 0;
			filter: alpha(opacity=0);
		}
		.modal-backdrop.in {
			opacity: 0.5;
			filter: alpha(opacity=50);
		}
		.modal-header {
			padding: 15px;
			border-bottom: 1px solid #e5e5e5;
			min-height: 16.42857143px;
		}
		.modal-header .close {
			margin-top: -2px;
		}
		.modal-title {
			margin: 0;
			line-height: 1.42857143;
		}
		.modal-body {
			position: relative;
			padding: 15px;
		}
		.modal-footer {
			padding: 15px;
			text-align: right;
			border-top: 1px solid #e5e5e5;
		}
		.modal-footer .btn + .btn {
			margin-left: 5px;
			margin-bottom: 0;
		}
		.modal-footer .btn-group .btn + .btn {
			margin-left: -1px;
		}
		.modal-footer .btn-block + .btn-block {
			margin-left: 0;
		}
		.modal-scrollbar-measure {
			position: absolute;
			top: -9999px;
			width: 50px;
			height: 50px;
			overflow: scroll;
		}
		.clickable {
			cursor:pointer;
		}
		@media (min-width: 768px) {
			.modal-dialog {
				width: 90%;
				margin: 30px auto;
		  	}
			.modal-content {
				-webkit-box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
				box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
			}
			.modal-sm {
				width: 300px;
			}
		}
		@media (min-width: 992px) {
		  	.modal-lg {
				width: 900px;
		  	}
		}
		.clearfix:before,
		.clearfix:after,
		.modal-footer:before,
		.modal-footer:after {
			content: " ";
			display: table;
		}
		.clearfix:after,
		.modal-footer:after {
			clear: both;
		}
		.center-block {
			display: block;
			margin-left: auto;
			margin-right: auto;
		}
		.pull-right {
			float: right !important;
		}
		.pull-left {
			float: left !important;
		}
		.hide {
			display: none !important;
		}
		.show {
			display: block !important;
		}
		.invisible {
			visibility: hidden;
		}
		.text-hide {
			font: 0/0 a;
			color: transparent;
			text-shadow: none;
			background-color: transparent;
			border: 0;
		}
		.hidden {
			display: none !important;
		}
		.affix {
			position: fixed;
		}
		.btn {
			display: inline-block;
			margin-bottom: 0;
			font-weight: normal;
			text-align: center;
			vertical-align: middle;
			-ms-touch-action: manipulation;
			touch-action: manipulation;
			cursor: pointer;
			background-image: none;
			border: 1px solid transparent;
			white-space: nowrap;
			padding: 6px 12px;
			font-size: 14px;
			line-height: 1.42857143;
			border-radius: 4px;
			-webkit-user-select: none;
			-moz-user-select: none;
			-ms-user-select: none;
			user-select: none;
		}
		.btn:focus,
		.btn:active:focus,
		.btn.active:focus,
		.btn.focus,
		.btn:active.focus,
		.btn.active.focus {
			outline: thin dotted;
			outline: 5px auto -webkit-focus-ring-color;
			outline-offset: -2px;
		}
		.btn:hover,
		.btn:focus,
		.btn.focus {
			color: #333333;
			text-decoration: none;
		}
		.btn:active,
		.btn.active {
			outline: 0;
			background-image: none;
			-webkit-box-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125);
			box-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125);
		}
		.btn.disabled,
		.btn[disabled],
		fieldset[disabled] .btn {
			cursor: not-allowed;
			pointer-events: none;
			opacity: 0.65;
			filter: alpha(opacity=65);
			-webkit-box-shadow: none;
			box-shadow: none;
		}
		.btn-default {
			color: #333333;
			background-color: #ffffff;
			border-color: #cccccc;
		}
		.btn-default:hover,
		.btn-default:focus,
		.btn-default.focus,
		.btn-default:active,
		.btn-default.active,
		.open > .dropdown-toggle.btn-default {
			color: #333333;
			background-color: #e6e6e6;
			border-color: #adadad;
		}
		.btn-default:active,
		.btn-default.active,
		.open > .dropdown-toggle.btn-default {
			background-image: none;
		}
		.btn-default.disabled,
		.btn-default[disabled],
		fieldset[disabled] .btn-default,
		.btn-default.disabled:hover,
		.btn-default[disabled]:hover,
		fieldset[disabled] .btn-default:hover,
		.btn-default.disabled:focus,
		.btn-default[disabled]:focus,
		fieldset[disabled] .btn-default:focus,
		.btn-default.disabled.focus,
		.btn-default[disabled].focus,
		fieldset[disabled] .btn-default.focus,
		.btn-default.disabled:active,
		.btn-default[disabled]:active,
		fieldset[disabled] .btn-default:active,
		.btn-default.disabled.active,
		.btn-default[disabled].active,
		fieldset[disabled] .btn-default.active {
			background-color: #ffffff;
			border-color: #cccccc;
		}
		.btn-default .badge {
			color: #ffffff;
			background-color: #333333;
		}
		.wrap-franchise-finder {
			background: #fff;
			padding: 5px 0;
		}
		.franchise-finder {
			margin: 0 15px;
			text-align: center;
		}
		#franchise-finder-value {
		    width: 80%;
		    border: 1px solid #ddd;
		    margin: 0;
		}
		#search-franchise {
			width: 19%;
			padding: 19px;
			border: 0;
			font-size: 18px;
		}
		.dataTables_wrapper select,
		.dataTables_wrapper .dataTables_filter input{
		    padding: 5px;
		    font-size: 15px;
		    border: 1px solid #aaa;
		    width: 100px;
		}
		.dataTables_wrapper .dataTables_filter input{
			width: 200px;
		}
	</style>
    <link href="<?php echo plugins_url('css/jquery.dataTables.min.css', __FILE__); ?>" rel="stylesheet">
    <script src="<?php echo plugins_url('js/bootstrap.min.js', __FILE__); ?>"></script>
    <script src="<?php echo plugins_url('js/jquery.dataTables.min.js', __FILE__); ?>"></script>
	<div class="wrap-franchise-finder">
		<div class="franchise-finder">
			<input type="text" id="franchise-finder-value" placeholder="Type zip code here for search Franchise in your location, e.g. 00501">
			<?php wp_nonce_field( 'front_franchise_action', 'front_franchise_nonce_field' ); ?>
			<button class="button button-primary" id="search-franchise">Search</button>
		</div>
	</div>
	<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  		<div class="modal-dialog" role="document">
    		<div class="modal-content">
				<div class="modal-header">
        			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        			<h4 class="modal-title" id="myModalLabel">Franchise Map</h4>
      			</div>
		      	<div class="modal-body"></div>
      			<div class="modal-footer">
			        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      			</div>
    		</div>
  		</div>
	</div>
	<script>
		jQuery('#search-franchise').on('click', function(){
			jQuery.ajax({
				url: franchise_config.ajax_url,
				type: 'post',
				data: {
					action: 'search_franchise',
					key: jQuery('#franchise-finder-value').val(),
					wp_nounce: jQuery('#front_franchise_nonce_field').val()
				},
				success: function(data){
					try{
						var data = JSON.parse(data);
						if(!data.error){
							var modal = jQuery('#myModal');
							modal.find('.modal-body').html(data.table);
							modal.modal('show');
							jQuery('#table-zip-code').DataTable({"dom": 't'});
							jQuery('#table-locations').DataTable();
						}else{
							alert(data.error);
						}
					}catch(e){
						console.log(e);
						alert(e);
					}
				}
			})
		});
	</script>
<?php
}

add_action( 'wp_ajax_search_franchise', 'search_franchise' );

function search_franchise(){
	if(
		!empty($_POST['wp_nounce']) 
		&& wp_verify_nonce( $_POST['wp_nounce'], 'front_franchise_action' )
	){
		$key = $_POST['key'];
		$file_location = get_option('location-franchise', true);
		$file_zip_code = get_option('zip_code-franchise', true);
		$msg_error = "Zip code not defined!";
		$errors = false;
		if(!empty($file_zip_code)){
			$zip_code = unserialize(file_get_contents($file_zip_code));
		}else{
			$errors = true;
		}
		if(!empty($file_location)){
			$location = unserialize(file_get_contents($file_location));
		}else{
			$errors = true;
		}
		if(
			empty($error)
			&& !empty($zip_code)
			&& !empty($zip_code[$key])
		){
			$region = $zip_code[$key]['County_Code'];
			if(
				!empty($location)
				&& !empty($location[$region])
			){
				$data = $location[$region];
				ksort($data);
				
				$zip_body = "<tr>";
				foreach ($zip_code[$key] as $key => $value) {
					$zip_body .= "<td>".$value."</td>";
				}
				$zip_body .= "</tr>";
				
				$location_body = "";
				foreach ($data as $k => $v) {
					$location_body .= "<tr>";
					foreach ($v as $key => $value) {
						$location_body .= "<td>".$value."</td>";
					}
					$location_body .= "</tr>";
				}
				$table = '
				<h3>Zip Code</h3>
				<table id="table-zip-code" class="table table-striped table-bordered">
					<thead>
						<tr>
							<th>Zip</th>
							<th>County codes</th>
							<th>City</th>
							<th>ST</th>
							<th>County</th>
						</tr>
					</thead>
					<tbody>'.$zip_body.'</tbody>
				</table>
				<h3 style="margin-top: 50px;">Franchise Location</h3>
				<table id="table-locations" class="table table-striped table-bordered">
					<thead>
						<tr>
							<th>ID</th>
							<th>Franchise Name</th>
							<th>Phone</th>
							<th>Website</th>
							<th>Email</th>
							<th>County codes</th>
						</tr>
					</thead>
					<tbody>'.$location_body.'</tbody>
				</table>';
				$allData = array(
					'zip_detail'=>$zip_code[$key], 
					'location'=>$data,
					'table'=>$table
				);
				echo json_encode($allData);
			}else{
				echo json_encode(array('error'=>$msg_error));
			}
		}else{
			echo json_encode(array('error'=>$msg_error));
		}
	}
	wp_die();
}