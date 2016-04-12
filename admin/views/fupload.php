<?php

require_once('../../../../../wp-load.php');

define('CHUNK_SIZE', 1024);
set_time_limit(0);                   // ignore php timeout
ignore_user_abort(true);             // keep on going even if user pulls the plug*

ini_set('max_input_time', 5500);
ini_set('max_execution_time', 5500);


//define( 'UPLOADS', trailingslashit( WP_CONTENT_FOLDERNAME ).'custom_uploads_name' );
////error_log("upload path:::".ABSPATH .UPLOADS.'sailthru-import-data');
//$import_data_dir = ABSPATH .UPLOADS.'sailthru-import-data';


/****************************************
Example of how to use this uploader class...
You can uncomment the following lines (minus the require) to use these as your defaults.

// list of valid extensions, ex. array("jpeg", "xml", "bmp")
$allowedExtensions = array();
// max file size in bytes
$sizeLimit = 10 * 1024 * 1024;

require('valums-file-uploader/server/php.php');
$uploader = new qqFileUploader($allowedExtensions, $sizeLimit);

// Call handleUpload() with the name of the folder, relative to PHP's getcwd()
$result = $uploader->handleUpload('uploads/');

// to pass data through iframe you will need to encode all html tags
echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);

/******************************************/

/**
 * Handle file uploads via XMLHttpRequest
 */
class qqUploadedFileXhr {


	/**
	 * Save the file to the specified path
	 * @return boolean TRUE on success
	 */
	function save($path,$listname) {


		return true;

	}


	function getName() {
		return $_GET['usersCSVFile'];
	}
	function getSize() {
		if (isset($_SERVER["CONTENT_LENGTH"])){
			return (int)$_SERVER["CONTENT_LENGTH"];
		} else {
			throw new Exception('Getting content length is not supported.');
		}
	}
}

/**
 * Handle file uploads via regular form post (uses the $_FILES array)
 */
class qqUploadedFileForm {

	private $sailthru_uploads;
	private $sailthru_import_data_dir;
	private $sailthru_blogId;
	private $sailthru_pwd;
	private $sailthru_filename;
	private $sailthru_tmpfilename;
	private $sailthru_listname;

	function __construct(){

		$this->sailthru_uploads = wp_upload_dir();
		$this->sailthru_import_data_dir=$this->sailthru_uploads['basedir'] . '/sailthru-import-data';
		//error_log("upload path:::".$this->sailthru_import_data_dir);

		if (! is_dir($this->sailthru_import_data_dir)){
			mkdir($this->sailthru_import_data_dir);
		}

		$this->sailthru_blogId = get_current_blog_id();
		$this->sailthru_pwd = wp_generate_password();
		$this->sailthru_filename = $this->sailthru_import_data_dir."/log_".$this->sailthru_blogId.".txt";
		$this->sailthru_tmpfilename = $this->sailthru_import_data_dir."/log_".$this->sailthru_blogId."_tmp.txt";

	}


	/**
	 * Save the file to the specified path
	 * @return boolean TRUE on success
	 */
	function save($path,$listname) {
		/* if(!move_uploaded_file($_FILES['usersCSVFile']['tmp_name'], $path)){
					return false;
			}*/

		//error_log("listname::::".$_REQUEST["sailthru_files"]);

		try{

			//error_log("start:::".memory_get_usage());

			$buffer = '';
			$cnt =0;
			$handle = fopen($_FILES['usersCSVFile']['tmp_name'], 'rb');
			if ($handle === false) {
				return false;
			}

			//error_log("after stream reading :::".memory_get_usage());

			$this->logUserInsertMessages("","clear");
			$memory_limit = ini_get('memory_limit');
			//error_log("Memory Limit::".$memory_limit);
			ini_set('memory_limit','1024M');
			//ini_set('memory_limit', '32M');
			//ini_set('max_execution_time', -1);
			//set_time_limit(0);
			$row_index = 0;
			////error_log("begin row::".$row);

			while (($parsed = fgetcsv($handle, 0, ",")) !== FALSE) {

				global $wpdb, $sailthru_blogId,$sailthru_pwd;
				$wpdb->flush();

//                              //error_log("before buffer reading :::".memory_get_usage());
//                              $buffer = fread($handle, CHUNK_SIZE);
//                              //error_log("after buffer reading :::".memory_get_usage());
//                              $parsed = str_getcsv(
//                                      $buffer,",");
				//error_log("after parsed csvconvert reading :::".memory_get_usage());

				//  foreach ($parsed as $value){
				//error_log("In for loop start:::".memory_get_usage());
//                                      $row_index++;
				////error_log("row::".$row_index);
				$key = $parsed[1];
				//error_log("key:::".$key);

				if(filter_var($key, FILTER_VALIDATE_EMAIL)){

					//$email_exist_check = email_exists($key);
					//$email_exist_check = get_user_by('email', $key);

					// $userId = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->users where user_email='".$key."';" ) );

					$sailthru_userId_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT($wpdb->users.user_email) FROM $wpdb->users WHERE $wpdb->users.user_email='$key'" ) );
					//error_log("sailthru_userId_count::::::".$sailthru_userId_count);
					if($sailthru_userId_count<=0){

						//$user_nicename = str_replace(array(" ", "."), "-",$key);
						//$user_nicename = str_replace("@","",$user_nicename);

						$user_nicename = $this->getUserNiceName($key);
						$wpdb->query( $wpdb->prepare(
								"INSERT INTO $wpdb->users
													( user_login, user_pass, user_nicename, user_email,user_registered, display_name)
													VALUES ( %s, %s, %s, %s, %s, %s )
												",
								array(
									$key,
									$this->sailthru_pwd,
									$user_nicename,
									$key,
									date( 'Y-m-d H:i:s' ),
									$key
								)
							) );

						//$blogId = get_current_blog_id();
						$userId = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->users where user_email='".$key."';" ) );

						$wpdb->query( $wpdb->prepare(
								"INSERT INTO $wpdb->usermeta
													( user_id, meta_key, meta_value )
													VALUES ( %s, %s, %s)
												",
								array(
									$userId,
									"nickname",
									$key
								)
							) );

						$wpdb->query( $wpdb->prepare(
								"INSERT INTO $wpdb->usermeta
													( user_id, meta_key, meta_value )
													VALUES ( %s, %s, %s)
												",
								array(
									$userId,
									"registration_method",
									newsletter
								)
							) );

						$wpdb->query( $wpdb->prepare(
								"INSERT INTO $wpdb->usermeta
													( user_id, meta_key, meta_value )
													VALUES ( %s, %s, %s)
												",
								array(
									$userId,
									"create_through_newletter",
									1
								)
							) );


						$wpdb->query( $wpdb->prepare(
								"INSERT INTO $wpdb->usermeta
													( user_id, meta_key, meta_value )
													VALUES ( %s, %s, %s)
												",
								array(
									$userId,
									'ns_' . $this->sailthru_blogId . '_' . urlencode($listname),
									"1"
								)
							) );

						////error_log("new userID::".$userId."::".$this->sailthru_blogId);
						echo "new userID::".$userId."::".$this->sailthru_blogId."<br/>";


						$this->logUserInsertMessages("Created user:".$key."<br/>","message");



						//update_user_meta($userId,"registration_method","newsletter");
						//update_user_meta($userId,'ns_' . $blogId . '_' . urlencode($listname), '1');

//											 $user_nicename = NULL;
//											 unset($user_nicename);

						$userId = null;
						unset($userId);



					}else{

						//$blogId = get_current_blog_id();
						$userId = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->users where user_email='".$key."';" ) );
						//error_log("existing userID::".$userId."::".$this->sailthru_blogId);
						echo "existing userID::".$userId."::".$this->sailthru_blogId."<br/>";
						//update_user_meta($userId,'ns_' . $blogId . '_' . urlencode($listname), '1');

						$meta_key = "ns_".$this->sailthru_blogId."_".urlencode($listname);
						$meta_key_check = $wpdb->query( "SELECT meta_value FROM $wpdb->usermeta where meta_key = \"$meta_key\" AND user_id='$userId';" );

						if(empty($meta_key_check))
						{

							$wpdb->query( $wpdb->prepare(
									"INSERT INTO $wpdb->usermeta
														( user_id, meta_key, meta_value )
														VALUES ( %s, %s, %s)
													",
									array(
										$userId,
										'ns_' . $this->sailthru_blogId . '_' . urlencode($listname),
										"1"
									)
								) );


						}else{

							$wpdb->query( $wpdb->prepare(
									"UPDATE $wpdb->usermeta SET
															meta_value = '1'
															WHERE user_id = %s AND meta_key = %s
														",
									array(
										$userId,
										'ns_' . $this->sailthru_blogId . '_' . urlencode($listname)
									)
								) );

						}

						$meta_key_check = NULL;
						unset($meta_key_check);

						$meta_key = NULL;
						unset($meta_key);


						$userId = NULL;
						unset($userId);

						$this->logUserInsertMessages("Existing user:".$key."<br/>","message");

					}

					//error_log(serialize($wpdb->users));


				}

				//error_log("before flush buffer reading :::".memory_get_usage());
				//ob_flush();
				//flush();
				//error_log("after flush buffer reading :::".memory_get_usage());
				$buffer = NULL;
				unset($buffer);
				$parsed = NULL;
				unset($parsed);
				//error_log("after unset buffer and parsed array reading :::".memory_get_usage());

			}

			fclose($handle);

			//error_log("file stream closing flush buffer reading :::".memory_get_usage());


		}catch(Exception $e){
			$this->logUserInsertMessages($e->getMessage(),"message");
//                      global $memory_limit;
//                      ini_set('memory_limit', $memory_limit);
		}

		//ini_set('memory_limit', $memory_limit);
		$this->logUserInsertMessages("","done");

		////error_log("listName::::::::::::::::".$listname);





		return true;
	}

	public function getUserNiceName($key){

		$user_nicename = str_replace(array(" ", "."), "-",$key);
		$user_nicename = str_replace("@","",$user_nicename);

		return $user_nicename;

	}

	public function logUserInsertMessages($msg,$command){

		global $sailthru_filename,$sailthru_tmpfilename;
		//error_log("Filename:::".$this->sailthru_filename);
		//error_log("Temporary Filename:::".$this->sailthru_tmpfilename);

		if($command == "clear"){
			//file_put_contents($file,"Start Import...\n");
			/////error_log("file_update Called:: command :clear");
			if ( ( $f = @fopen($this->sailthru_filename, 'w') ) !== FALSE ) {
				$bytes = fwrite($f, "<p style='color:red;'>File name: ".$this->getName()."<p/>...........START IMPORT...........<br/> ");
				fclose($f);
				$bytes = NULL;
				unset($bytes);
			}

		}else if($command == "done"){

			if (
				( $fp_source = fopen($this->sailthru_filename, 'r') ) !== FALSE &&
				( $fp_dest = fopen($this->sailthru_tmpfilename, 'w') ) !== FALSE
			) { // better to generate a real temp filename
				fwrite($fp_dest, "............IMPORT IS DONE..........<br/><p style='color:red;'>File name: ".$this->getName()."<p/>");
				while (!feof($fp_source)) {
					$contents = fread($fp_source, CHUNK_SIZE);
					fwrite($fp_dest, $contents);
					$contents = NULL;
					unset($contents);
				}
				fclose($fp_source);
				fclose($fp_dest);
				unlink($this->sailthru_filename);
				rename($this->sailthru_tmpfilename,$this->sailthru_filename);
			}

		}else{

			////error_log("file_update Called::command:msg::".$msg.":::".$filename);
			if (
				($fp_source = fopen($this->sailthru_filename, 'r')) !== FALSE &&
				($fp_dest = fopen($this->sailthru_tmpfilename, 'w')) !== FALSE
			) {
				fwrite($fp_dest, $msg);
				while (!feof($fp_source)) {
					$contents = fread($fp_source, 128);
					fwrite($fp_dest, $contents);
					$contents = NULL;
					unset($contents);
				}
				fclose($fp_source);
				fclose($fp_dest);
				unlink($this->sailthru_filename);
				rename($this->sailthru_tmpfilename,$this->sailthru_filename);
			}

		}


	}



	function getName() {
		return $_FILES['usersCSVFile']['name'];
	}
	function getSize() {
		return $_FILES['usersCSVFile']['size'];
	}
}




class qqFileUploader {
	private $allowedExtensions = array();
	private $sizeLimit = 10485760;
	private $file;
	private $listName;


	function __construct(array $allowedExtensions = array(), $sizeLimit = 10485760){
		$allowedExtensions = array_map("strtolower", $allowedExtensions);

		$this->allowedExtensions = $allowedExtensions;
		$this->sizeLimit = $sizeLimit;

		//  $this->checkServerSettings();

		if (isset($_GET['usersCSVFile'])) {
			$this->file = new qqUploadedFileXhr();
		} elseif (isset($_FILES['usersCSVFile'])) {
			$this->file = new qqUploadedFileForm();
		} else {
			$this->file = false;
		}

		//error_log("FileName::". serialize($this->file));

		$this->listName = $_REQUEST["sailthru_files"];
		////error_log("listName::::::::::::::::11111111".$_REQUEST["listname"]);

	}

	public function getName(){
		if ($this->file)
			return $this->file->getName();
	}

	private function checkServerSettings(){
		$postSize = $this->toBytes(ini_get('post_max_size'));
		$uploadSize = $this->toBytes(ini_get('upload_max_filesize'));

		if ($postSize < $this->sizeLimit || $uploadSize < $this->sizeLimit){
			$size = max(1, $this->sizeLimit / 1024 / 1024) . 'M';
			die("{'error':'increase post_max_size and upload_max_filesize to $size'}");
		}
	}

	private function toBytes($str){
		$val = trim($str);
		$last = strtolower($str[strlen($str)-1]);
		switch($last) {
			case 'g': $val *= 1024;
			case 'm': $val *= 1024;
			case 'k': $val *= 1024;
		}
		return $val;
	}

	/**
	 * Returns array('success'=>true) or array('error'=>'error message')
	 */
	function handleUpload($uploadDirectory, $replaceOldFile = FALSE){
		/*        if (!is_writable($uploadDirectory)){
								return array('error' => "Server error. Upload directory isn't writable.");
						}

						if (!$this->file){
								return array('error' => 'No files were uploaded.');
						}
						*/

		$size = $this->file->getSize();

		if ($size == 0) {
			return array('error' => 'File is empty');
		}

		if ($size > $this->sizeLimit) {
			return array('error' => 'File is too large');
		}

		$pathinfo = pathinfo($this->file->getName());

		$filename = $pathinfo['filename'];
		//$filename = md5(uniqid());
		$ext = @$pathinfo['extension'];         // hide notices if extension is empty

		if($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions)){
			$these = implode(', ', $this->allowedExtensions);
			return array('error' => 'File has an invalid extension, it should be one of '. $these . '.');
		}

		if(!$replaceOldFile){
			/// don't overwrite previous files that were uploaded
			while (file_exists($uploadDirectory . $filename . '.' . $ext)) {
				$filename .= rand(10, 99);
			}
		}

		if ($this->file->save($uploadDirectory . $filename . '.' . $ext,$this->listName)){
			return array('success'=>true);
		} else {
			return array('error'=> 'Could not save uploaded file.' .
				'The upload was cancelled, or server error encountered');
		}

	}
}


// list of valid extensions, ex. array("jpeg", "xml", "bmp")
$allowedExtensions = array("png", "txt","csv");
// max file size in bytes
$sizeLimit = 20 * 1024 * 1024;

$uploader = new qqFileUploader($allowedExtensions, $sizeLimit);

// Call handleUpload() with the name of the folder, relative to PHP's getcwd()
$result = $uploader->handleUpload('uploads/');

// to pass data through iframe you will need to encode all html tags
echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
