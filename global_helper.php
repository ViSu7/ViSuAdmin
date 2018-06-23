<?php 
	/* 
		Message display 
		accept 2 parameter.
		1 - message text.
		2 - (optional) type of message success, error, warning. Bydefault error is selected.
	*/
	function set_message($message,$type="error") {

		/* Check empty */
		if(empty(trim($message))) {
			return false;
		}

		$CI =& get_instance();
		if($type=="error") {
			$add=$CI->session->userdata('error');
			$set_message=$add."<li>".$message."</li>";
			$CI->session->set_userdata('error',$set_message);
		}
		else if($type=="success") {
			$add=$CI->session->userdata('success');
			$set_message=$add."<li>".$message."</li>";
			$CI->session->set_userdata('success',$set_message);
		}
		else if($type=="warning") {
			$add=$CI->session->userdata('warning');
			$set_message=$add."<li>".$message."</li>";
			$CI->session->set_userdata('warning',$set_message);
		}  
	}	

	/* 
		Message Notification 
		accept 2 parameter.
		1 - message text.
		2 - (optional) type of message success, error, warning. Bydefault error is selected.
	*/
	function set_notify($toast_title=false,$toast_desc=false,$toast_type="error") {
		$CI =& get_instance();
		if($toast_title){
			$data['toast_title']=$toast_title;
			$data['toast_desc']=$toast_desc;
			$data['toast_type']=$toast_type;
			
			$CI->load->view('admin/show_toast',$data);
	    }
	}	

	/* 
		print array in format 
		accept 1 parameter as array.
	*/

	function dsm($var) {
		if(is_array($var) || is_object($var)) {
			echo "<pre>".print_r($var,true)."</pre>";
		}
		else {
			echo "<pre>".$var."</pre>";
		}
		$debug=debug_backtrace();
		echo "<pre>".$debug[0]['file'].", line :".$debug[0]['line']."</pre><br/>";  
	}

	/* print last execulated query */
	function print_last_query() {
		$CI =& get_instance();
		dsm($CI->db->last_query());
	}	

	/* 
		redirect back - redirect to request page.
	*/
	function redirect_back() {
		if(isset($_SERVER['HTTP_REFERER'])) {
			$url=$_SERVER['HTTP_REFERER'];  
		}
		else {
			$url=base_url();
		}
		redirect($url);
	}	

	function login_check($redirect_back=1) {
		$CI=&get_instance();
		$user_id=$CI->session->userdata('user_id');
		if(empty($user_id)) {
			$CI->load->model('admin_login/login_model');
			/* Check remember me token exist */
        	if($CI->login_model->check_remember()) {
        		return true;
        	}
			/* Set back to page for authentication fail */
			$back_to='';
			$current_uri=$CI->uri->uri_string();
			if(!empty($current_uri)) {
				$back_to='?back_to='.$current_uri;
			}
			$message="Your session has been expired, Please login again to continue.";
			return redirect_action($redirect_back, base_url().$back_to,$message);
		}
		return true;
	}

	/* 
		$permission_id to check in session 
			@type mixed array or integer
		$check_type
			@string
			AND - User must have all the permission from provided 
			OR - User must have any one permission from list
		$redirect_back
			@type Integer
			0 - Return true/ false 
			1 - Redirect to back page
			2 - echo html formatted error message and stop execution
			3 - Return json formatted error message and die

		@Return true on permission found and above action from 0,1,2 if permission not exist

	*/
	function permission_check($permission_id, $check_type='and', $redirect_back=1) {
		login_check($redirect_back);
		$CI=&get_instance();
		$role_id=$CI->session->userdata('role_id');

		$user_permission=$CI->session->userdata('permission_id');

		if(empty($user_permission)) {
			return redirect_action($redirect_back);
		}
		
		/* If user have system admin permission, Always return true */
		if(in_array('10', $user_permission)) {
			return true;
		}

		/* Check multiple permission */
		if(is_array($permission_id)) {
			$available_permission=false;
			$unavailable_permission=false;

			/* Checking permission */
			foreach ($permission_id as $permission) {
				if(in_array($permission, $user_permission)) {
					$available_permission=true;
				}
				else {
					$unavailable_permission=true;
				}
			}

			/* when single permission required */
			if(strtolower($check_type) == 'or') {
				if($available_permission) {
					return true;	
				}
				else {
					redirect_action($redirect_back);
				}
				
			}
			/* When all permission required */
			else {
				if($available_permission && $unavailable_permission===false) {
					return true;
				}
				else {
					redirect_action($redirect_back);
				}
			}
		}
		/* Check single permission */
		else {
			if(in_array($permission_id, $user_permission)) {
				return true;
			}
			else {
				redirect_action($redirect_back);
			}
		}
	}

	function redirect_action($redirect_back=1, $redirect_url=false, $error_msg="You don't have sufficient permission to access this resource.") {
		if($redirect_back==0) {
            return false;
		}
		elseif($redirect_back==1) {
			set_message($error_msg);
			if($redirect_url == false) {
				redirect_back();die;
			}
			else {
				redirect($redirect_url);
			}
		}
		elseif($redirect_back==2) {
			echo '<div class="alert alert-danger alert-dismissable">
					<button aria-hidden="true" data-dismiss="alert" class="close" type="button">x</button>
						'.$error_msg.'
				</div>';
			die;
		}
		elseif($redirect_back==3) {
			echo json_encode(array("status"=>0,'message'=>$error_msg));
			die;
		}
		else{
			return false;
		}
	}

	/* 
		CURL Execution
		accept 1 parameter.
		1 - url where page will send the request.
	*/
	function curl_send($url) {
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$curl_response = curl_exec($curl);
		curl_close($curl);
		return $curl_response;
	}	

	/* 
		Create combobox from array 
		accept 7 parameter.
		1 - name of select box or combobox.
		2 - array of values.
		3 - option value of select box or combobox.
		4 - text which will display in select box or combobox.
		5 - bydefault selected value in select box or combobox.
		6 - HTML css attributes.
		7 - 'SELECT' will be first option or not.
	*/
	function generate_combobox($name,$array,$key,$value,$selected=false,$other=false,$defaultoption=true) {
		if(empty($array)) {
			$output = "<select name=\"{$name}\" ".$other.">";
			if($defaultoption) {
				$output .= "<option value=\"\">SELECT</option>";    
			}
			$output .= "</select>";
		}
		else{  
			$output = "<select name=\"{$name}\" ".$other.">";
			if($defaultoption) {
				$output .= "<option value=\"\">SELECT</option>";    
			}
			$keys=array_column($array,$key);
			if(is_array($value)) {
				$args=array();
				$args[]="combine";
				foreach($value as $val) {
					$args[]=array_column($array,$val);
				}
				$vals=call_user_func_array('array_map',$args);
			}
			else {
				$vals=array_column($array,$value);
			}

			$new_array=array_combine($keys,$vals);

			foreach ($new_array as $key => $value) {
				if(is_array($selected)) {
					if (in_array($key,$selected)) {
						$output .= "<option value=\"{$key}\" selected>{$value}</option>";
					} 
					else {
						$output .= "<option value=\"{$key}\">{$value}</option>";
					}
				}
				else {
					if ($selected !== false && $selected == $key) {
						$output .= "<option value=\"{$key}\" selected>{$value}</option>";
					} 
					else {
						$output .= "<option value=\"{$key}\">{$value}</option>";
					}
				}
			}

			$output .= "</select>";
		}
		return $output;
	}	

	function combine() {
		$args=func_get_args();
		$return='';
		foreach($args as $arg) {
			$return.=$arg.' - ';
		}
		$return=rtrim($return,' - ');
		 return $return;
	}	

	/* 
		Create textbox from array 
		accept 4 parameters
		1 - name of textbox control.
		2 - value to set in placeholder attribute.
		3 - value to set bydefault in value attribute.
		4 - HTML css attributes.
	*/
	function generate_textbox($name,$placeholder=false,$default=false,$other=false) {
	  $output = "<input type=\"text\" name=\"{$name}\" value=\"{$default}\" placeholder=\"{$placeholder}\" ".$other.">";
	  return $output;
	}

	/* 
		Create number textbox from array 
		accept 4 parameters
		1 - name of numeric textbox control.
		2 - value to set in placeholder attribute.
		3 - value to set bydefault in value attribute.
		4 - HTML css attributes.		
	*/
	function generate_numberbox($name,$placeholder=false,$default=false,$other=false) {
	  $output = "<input type=\"number\" name=\"{$name}\" value=\"{$default}\" placeholder=\"{$placeholder}\" ".$other.">";
	  return $output;
	}

	/* 
		Create file from array
		accept 2 parameters
		1 - name of file control.
		2 - HTML css attributes.	
	*/
	function generate_filebox($name,$other=false) {
	  $output = "<input type=\"file\" name=\"{$name}\" ".$other.">";
	  return $output;
	}

	/* 
		Create textarea from array 
		accept 4 parameters
		1 - name of textarea control.
		2 - value to set in placeholder attribute.
		3 - value to set bydefault in value attribute.
		4 - HTML css attributes.		
	*/
	function generate_textarea($name,$placeholder=false,$default=false,$other=false) {
	  $output = "<textarea name=\"{$name}\" placeholder=\"{$placeholder}\" ".$other.">".$default."</textarea>";
	  return $output;
	}	

	function mysql_dateformat($str) {
		if(empty($str)) {
			return null;
		}
		$str=str_replace('/', '-', $str);
		$time=strtotime($str);
		if($time) {
			return date('Y-m-d',$time);
		}
		return '';
	}

	/* 
		Date formatting 
		accept 2 parameters
		1 - date which will be convert to format.
		2 - format in which given date will convert. bydefault false.	
	*/
	function dateformat($date,$format=false, $include_time=false) {
		/* Define default date format */
		if(!$format) {
			$format="d M Y";
			/* Include time */
			if($include_time) {
				$format="d M Y H:i a";
			}
		}

		$time=strtotime($date);
		if($time > 0) {
			return date($format,strtotime($date));	
		}
		return '';
	}

	

	/* 
		add minute in time 
		accept 1 parameter
		1 - integer value as minute to be added on current time.
	*/
	function add_time($min) {
		$now = time();
		$add_time = $now + ($min * 60);
		$end_time = date('Y-m-d H:i:s', $add_time);
		return $end_time;
	}

	/* 
		calculate date difference 
		accept 3 parameter
		1 - smaller date.
		2 - larger date.
		3 - type in which difference will return.
	*/
	function datedifference($date1,$date2,$type='dd') {
		$datetime1 = new DateTime($date1);
		$datetime2 = new DateTime($date2);
		$interval = $datetime2->diff($datetime1);
		if($type='dd') {
			return $interval->format('%a');
		}
		elseif($type='mm') {
			return $interval->format('%m');
		}
		elseif($type='yr') {
			return $interval->format('%y');
		}
		elseif($type='hr') {
			return $interval->format('%h');
		}
	}

	/* 
		invoice no. increment
		accept 1 parameter
		1 - last invoice number.		
	*/
	function increment_invoice_no($matches) {
	  //return ++$matches[1];
	  $val=$matches[1];
	  $len=strlen($val);
	  ++$val;
	  return str_pad($val, $len, "0", STR_PAD_LEFT);  
	}	

	/* 
		Checking string in array.
		accept 2 parameters
		1 - array
		2 - string which is to be search.
	*/
	
	function string_inarray($array, $searchingFor) {
	    $i=0;
	    foreach ($array as $key=>$element) {
	        if (strpos($element, $searchingFor) !== false) {
	            return array('index'=>$i,'value'=>$key);
	        }
	        $i++;
	    }
	    return false;
	}		

	/* 
		Replaces all spaces with hyphens. & Removes special chars. 
		accept 1 parameter
		1 - string need to be clean.		
	*/
	function clean_name($string) {
	   $string = str_replace('  ', ' ', $string); 
	   $string = str_replace('', '-', $string); 
	   return preg_replace('/[^A-Za-z0-9\-\.]/', '', $string);
	}

	/* to remove blank space */
	function clean_field_name($string) {
		$string = preg_replace('/\s+/', ' ',strtolower($string));
	  	$string = str_replace(' ', '_', $string); // Replaces all spaces with hyphens.
	  	return preg_replace('/[^A-Za-z0-9\_\.]/', '', $string);
	}

	/* checking user directory */
	function check_upload_dir($dir) {
		/* converting to lowercase */
		$dir=strtolower($dir); 

		/* seprateing the dir by / */
		$dir=explode('/', $dir);

		$str_dir='.';
		foreach ($dir as $folder_name) {
			if(empty($folder_name)) { continue; }
			$str_dir.='/'.$folder_name;
			if(!file_exists($str_dir) || !is_dir($str_dir)) {
				mkdir($str_dir);
			}
		}

		if(!file_exists($str_dir.'/150') || !is_dir($str_dir.'/150')) {
			mkdir($str_dir.'/150');
		}

		if(!file_exists($str_dir.'/300') || !is_dir($str_dir.'/300')) {
			mkdir($str_dir.'/300');
		}

		if(!file_exists($str_dir.'/500') || !is_dir($str_dir.'/500')) {
			mkdir($str_dir.'/500');
		}
	}

	/* 
		Checking image directory & thumb directory
		accept 2 parameters
		1 - name of directory.	
		2 - thumb folder name of directory. bydefault false.
	*/
	function check_dir($name,$thumb=false) {
	    /* main upload folder */
	    if(!file_exists(UPLOAD_DIR) || !is_dir(UPLOAD_DIR)) {
	        mkdir(UPLOAD_DIR);
	    }
	    
	    /* folder of project */
	    $document_path='./upload/'.$name;
	    if(!file_exists($document_path) || !is_dir($document_path)) {
	        mkdir($document_path);
	    }

	    if($thumb) {
	        /* folder of project */
	        $thumb_path='./upload/'.$name.'/'.$thumb;
	        if(!file_exists($thumb_path) || !is_dir($thumb_path)) {
	            mkdir($thumb_path);
	        }     
	    } 
	}

	/* 
		creating the image thumb 
		accept 5 parameters
		1 - source image path.	
		2 - thumb image width.		
		3 - thumb image height.
		4 - original file name.
		5 - thumb folder path where image thumb is to be create.
	*/
		
	/* 
		get thumb image 
		accept 2 parameters
		1 - image path.
		2 - thumb dimension. bydefault false.
	*/
	function get_thumb($image,$thumb=false) {
	    if($image=='') {
	        return "";
	    }
	    $url_array=explode('/',$image);
	    $path_array=pathinfo($image);
	    $last=count($url_array);
	    if($thumb!='') {
	        $url_array[$last]=$path_array['filename'].'_thumb.'.$path_array['extension'];
	        $url_array[$last-1]=$thumb;
	        $thumb_url=implode('/',$url_array);
	    }
	    else {
	        $thumb_url=implode('/',$url_array);
	    }
	    return $thumb_url;
	}				

	/* Generate otp */
    function generateOTP() {
		$password=random_string("numeric",4);
	    return $password;
	}

	/* Generate token */
	function token($length=8) {
		$md5=md5(uniqid(rand(), true));
		return substr($md5,2,$length);
	}		

	/* 
		Parent child array 
		accept 2 parameters
		1 - array.
		2 - parent column name.	
	*/
	function parent_child($array,$parent_col) {
	    $return = array();
	    $i=0;
	    foreach($array as $key=>$row) {
	    	if(isset($row[$parent_col]) && $row[$parent_col]!='') {
	    		$return[$row[$parent_col]][] =$row;
	    	}
	    	else {
	    		$return['no_parent'][] =$row;
	    	}
	    }
	    return $return;
	}

	/* 
		Parent child array 
		accept 2 parameters
		1 - array.
		2 - parent column name.	
	*/
	function parent_child_array($array,$parent_col, $child_key_col=false) {
		$return = array();
		foreach($array as $key=>$row) {
			if(isset($row[$parent_col]) && $row[$parent_col]!='') {
				if($child_key_col) {
					$return[$row[$parent_col]][$row[$child_key_col]] =$row;
				}
				else {
					$return[$row[$parent_col]][] =$row;
				}
			}
			else {
				if($child_key_col) {
					$return['no_parent'][$row[$child_key_col]]=$row;
				}
				else {
					$return['no_parent'][]=$row;
				}
			}
		}
		return $return;
	}

	/* 
		Replace the text from message 
		accept 2 parameters
		1 - message string.
		2 - array of string that need to be replace.	
	*/
	function replaces($string,$array) {
		foreach($array as $key=>$val) {
			$string=str_replace('|*'.$key.'*|',$val,$string);
		}
		return $string;
	}


	/* 
		Filter for db queries 
		accept 1 parameter
		1 - array of filter.		
	*/
	function apply_filter($filter) {
		$CI=& get_instance();
		if(is_array($filter)) {
			foreach($filter as $key => $val) {
				/* limit */
				if($key==='LIMIT') {
					if(is_array($val)) {
						$CI->db->limit($val[0],$val[1]);
					}
					else {
						$CI->db->limit($val);
					}
				}

				/* Where clause */
				else if($key==='WHERE') {
					if(is_array($val)) {
						foreach ($val as $where_val) {
							$CI->db->where($where_val,null,FALSE);
						}
					}
					else {
						if(!empty($val)) {
							$CI->db->where($val,null,FALSE);
						}
					}
				}
				else if($key==='WHERE_IN') {
					foreach($val as $column => $value) {
						$CI->db->where_in($column,$value);
					}
				}
				else if($key==='WHERE_NOT_IN') {
					foreach($val as $column => $value) {
						$CI->db->where_not_in($column,$value);
					}
				}
				else if($key==='HAVING') {
					if(is_array($val)) {
						foreach($val as $col=>$value) {
							$CI->db->having($col,$value);
						}
					}
					else {
						$CI->db->having($val,null,FALSE);
					}
				}

				/* order by */
				elseif($key=='ORDER_BY') {
					foreach($val as $col => $order) {
						$CI->db->order_by($col,$order,false);
					}
				}

				/* group by */
				elseif($key=='GROUP_BY') {
					$CI->db->group_by($val);
				}

				/* group by */
				elseif($key=="SELECT") {
					$CI->db->select($val);
				}

				/* LIKE */
				elseif($key=='LIKE') {
					foreach($val as $col => $value) {
						$CI->db->like($col,$value);
					}
				}

				/* simple key=>value where condtions */
				else {
					$CI->db->where($key,$val);  
				}
			}
		}
	}

	/* Check Unique value form database  
		$value - Column value to check
		$column - Table column name
		$table - Table name
		$primary - Primary key column of table to ignore check unique value for own row 
		$edit_id  - primary column value to ignore that row for unique check
	*/
	function check_unique($value, $column, $table, $primary_column=false ,$edit_id=false) {
		$CI=&get_instance();
		if(!empty($edit_id)) {
			$CI->db->where($primary_column." !=", $edit_id);
		}
		$CI->db->where($column,$value);
		$rs=$CI->db->get($table);
		//print_last_query();die;
		$result=$rs->result_array();
		if(isset($result[0])) {
			return false;
		}
		else {
			return true;
		}
	
	}	

	/* 
		CSV EXPORT 
		accept 2 parameters
		1 - array of record.
		2 - header option.
	*/
	function convertToCSV($data, $options, $name=false) {
		/* setting the csv header*/
		if (is_array($options) && isset($options['headers']) && is_array($options['headers'])) {
			$headers = $options['headers'];
		} 
		else {
			$filename=date('d-M').".csv";
			$headers = array(
				'Content-Type' => 'text/csv',
				'Content-Disposition' => 'attachment; filename="'.$filename.'"'
			);
		}

		$output = '';
		/* setting the first row of the csv if provided in options array */
		if (isset($options['firstRow']) && is_array($options['firstRow'])) {
			$output .= implode(',', $options['firstRow']);
			$output .= "\n"; /* new line after the first line */
		}

		/* setting the columns for the csv. if columns provided, then fetching the or else object keys */
		if (isset($options['columns']) && is_array($options['columns'])) {
			$columns = $options['columns'];
		}
		else {
			if(is_object($data[0])) {
				$objectKeys = get_object_vars($data[0]);
			}
			else {
				$objectKeys = $data[0];	
			}
			$columns = array_keys($objectKeys);
		}

		/* populating the main output string */
		foreach ($data as $row) {
			foreach ($columns as $column) {
				$output .= str_replace(',', ';', $row[$column]);
				$output .= ',';
			}
			$output .= "\n";
		}
		/* $file="./".date('d-m-y').".csv"; */
		$file_name=date('d-m-y').".csv";
		if($name) {
			$file_name=$name;
		}
		/* file_put_contents($file,$output); */
		force_download($file_name, $output);  
	}

	/* 
		Currency format 
		accept 1 parameter
		1 - integer to be converted into proper format.
	*/
	function moneyFormatIndia($num){
		$explrestunits = "" ;
		if(strlen($num)>3){
			$lastthree = substr($num, strlen($num)-3, strlen($num));
			$restunits = substr($num, 0, strlen($num)-3); /* extracts the last three digits */
			$restunits = (strlen($restunits)%2 == 1)?"0".$restunits:$restunits; /* explodes the remaining digits in 2's formats, adds a zero in the beginning to maintain the 2's grouping. */
			$expunit = str_split($restunits, 2);
			for($i=0; $i<sizeof($expunit); $i++){
				/* creates each of the 2's group and adds a comma to the end */
				if($i==0) {
					$explrestunits .= (int)$expunit[$i].","; /* if is first value , convert into integer */
				}
				else{
					$explrestunits .= $expunit[$i].",";
				}
			}
			$thecash = $explrestunits.$lastthree;
		} 
		else {
			$thecash = $num;
		}
		return $thecash; /* writes the final format where $currency is the currency symbol. */
	}	

	/* 
		Generate HTTP request
		accept 1 parameter
		1 - url of page.	
	*/
	function httpRequest($url) {
	    $pattern = "/http...([0-9a-zA-Z-.]*).([0-9]*).(.*)/";
	    preg_match($pattern,$url,$args);
	    $in = "";
	    $fp = fsockopen($args[1],80, $errno, $errstr, 30);
	    if (!$fp) {
	    	return("$errstr ($errno)");
	    } 
	    else {
	  		$args[3] = "C".$args[3];
	        $out = "GET /$args[3] HTTP/1.1\r\n";
	        $out .= "Host: $args[1]:$args[2]\r\n";
	        $out .= "User-agent: PARSHWA WEB SOLUTIONS\r\n";
	        $out .= "Accept: */*\r\n";
	        $out .= "Connection: Close\r\n\r\n";

	        fwrite($fp, $out);
	        while (!feof($fp)) {
	        	$in.=fgets($fp, 128);
	        }
	    }
	    fclose($fp);
	    return($in);
	}		

	/* 
		Convert Amount to word 
		accept 1 parameter
		1 - integer to be converted into words.		
	*/
	function number_to_words($number) {
		$hyphen      = '-';
		$conjunction = ' and ';
		$separator   = ', ';
		$negative    = 'negative ';
		$decimal     = ' point ';
		$dictionary  = array(
			0                   => 'zero',
			1                   => 'one',
			2                   => 'two',
			3                   => 'three',
			4                   => 'four',
			5                   => 'five',
			6                   => 'six',
			7                   => 'seven',
			8                   => 'eight',
			9                   => 'nine',
			10                  => 'ten',
			11                  => 'eleven',
			12                  => 'twelve',
			13                  => 'thirteen',
			14                  => 'fourteen',
			15                  => 'fifteen',
			16                  => 'sixteen',
			17                  => 'seventeen',
			18                  => 'eighteen',
			19                  => 'nineteen',
			20                  => 'twenty',
			30                  => 'thirty',
			40                  => 'fourty',
			50                  => 'fifty',
			60                  => 'sixty',
			70                  => 'seventy',
			80                  => 'eighty',
			90                  => 'ninety',
			100                 => 'hundred',
			1000                => 'thousand',
			1000000             => 'million',
			1000000000          => 'billion',
			1000000000000       => 'trillion',
			1000000000000000    => 'quadrillion',
			1000000000000000000 => 'quintillion'
		);

		if (!is_numeric($number)) {
			return false;
		}

		if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
			// overflow
			trigger_error(
				'number_to_words only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX,
				E_USER_WARNING
			);
			return false;
		}

		if ($number < 0) {
			return $negative . number_to_words(abs($number));
		}

		$string = $fraction = null;

		if (strpos($number, '.') !== false) {
			list($number, $fraction) = explode('.', $number);
		}

		switch (true) {
			case $number < 21:
				$string = $dictionary[$number];
				break;
			case $number < 100:
				$tens   = ((int) ($number / 10)) * 10;
				$units  = $number % 10;
				$string = $dictionary[$tens];
				if ($units) {
					$string .= $hyphen . $dictionary[$units];
				}
			break;
			case $number < 1000:
				$hundreds  = $number / 100;
				$remainder = $number % 100;
				$string = $dictionary[$hundreds] . ' ' . $dictionary[100];
				if ($remainder) {
					$string .= $conjunction . number_to_words($remainder);
				}
			break;
			default:
				$baseUnit = pow(1000, floor(log($number, 1000)));
				$numBaseUnits = (int) ($number / $baseUnit);
				$remainder = $number % $baseUnit;
				$string = number_to_words($numBaseUnits) . ' ' . $dictionary[$baseUnit];
				if ($remainder) {
					$string .= $remainder < 100 ? $conjunction : $separator;
					$string .= number_to_words($remainder);
				}
			break;
		}

		if (null !== $fraction && is_numeric($fraction)) {
			$string .= $decimal;
			$words = array();
			foreach (str_split((string) $fraction) as $number) {
				$words[] = $dictionary[$number];
			}
			$string .= implode(' ', $words);
		}

		return strtoupper($string);
	}	

	/* 
		Validation rules for controls.
		accept 1 parameter
		1 - value of control.
		2 - define rules for HTML control.
	*/
	function validate($value,$rule) {
		if($rule=="") {
			return true;
		}
		$return=false;
		$arr_value=explode("|", $rule);
		foreach($arr_value as $case_value) {	
			switch($case_value) {
				case "required": 
					if ($value=="") { 
						$return=false;
					} 
					else { 
						$return=true;
					};
				break;
				case "valid_email": 
					if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
						$return=true;
					}
					else {
						$return=false;
					}
				break;
				case "mobile": 
					if(preg_match('/^\d{10}$/',$value)) {
						$return=true;
					}
					else {
						$return=false;
					}
				break;			
				case "numeric":
					if (is_numeric($value)) {
						$return=true;
					} 
					else {
						$return=false;
					}
				break;	
				default: 
					$return=true;

			}
		}
		return $return;
	}	

	if (!function_exists('array_column')) {

	    /**
	     * Returns the values from a single column of the input array, identified by
	     * the $columnKey.
	     *
	     * Optionally, you may provide an $indexKey to index the values in the returned
	     * array by the values from the $indexKey column in the input array.
	     *
	     * @param array $input A multi-dimensional array (record set) from which to pull
	     *                     a column of values.
	     * @param mixed $columnKey The column of values to return. This value may be the
	     *                         integer key of the column you wish to retrieve, or it
	     *                         may be the string key name for an associative array.
	     * @param mixed $indexKey (Optional.) The column to use as the index/keys for
	     *                        the returned array. This value may be the integer key
	     *                        of the column, or it may be the string key name.
	     * @return array
	     */
	    function array_column($input = null, $columnKey = null, $indexKey = null) {
	        // Using func_get_args() in order to check for proper number of
	        // parameters and trigger errors exactly as the built-in array_column()
	        // does in PHP 5.5.
	        $argc = func_num_args();
	        $params = func_get_args();

	        if ($argc < 2) {
	            trigger_error("array_column() expects at least 2 parameters, {$argc} given", E_USER_WARNING);
	            return null;
	        }

	        if (!is_array($params[0])) {
	            trigger_error('array_column() expects parameter 1 to be array, ' . gettype($params[0]) . ' given', E_USER_WARNING);
	            return null;
	        }

	        if (!is_int($params[1])
	            && !is_float($params[1])
	            && !is_string($params[1])
	            && $params[1] !== null
	            && !(is_object($params[1]) && method_exists($params[1], '__toString'))
	        ) {
	            trigger_error('array_column(): The column key should be either a string or an integer', E_USER_WARNING);
	            return false;
	        }

	        if (isset($params[2])
	            && !is_int($params[2])
	            && !is_float($params[2])
	            && !is_string($params[2])
	            && !(is_object($params[2]) && method_exists($params[2], '__toString'))
	        ) {
	            trigger_error('array_column(): The index key should be either a string or an integer', E_USER_WARNING);
	            return false;
	        }

	        $paramsInput = $params[0];
	        $paramsColumnKey = ($params[1] !== null) ? (string) $params[1] : null;

	        $paramsIndexKey = null;
	        if (isset($params[2])) {
	            if (is_float($params[2]) || is_int($params[2])) {
	                $paramsIndexKey = (int) $params[2];
	            } else {
	                $paramsIndexKey = (string) $params[2];
	            }
	        }

	        $resultArray = array();

	        foreach ($paramsInput as $row) {

	            $key = $value = null;
	            $keySet = $valueSet = false;

	            if ($paramsIndexKey !== null && array_key_exists($paramsIndexKey, $row)) {
	                $keySet = true;
	                $key = (string) $row[$paramsIndexKey];
	            }

	            if ($paramsColumnKey === null) {
	                $valueSet = true;
	                $value = $row;
	            } elseif (is_array($row) && array_key_exists($paramsColumnKey, $row)) {
	                $valueSet = true;
	                $value = $row[$paramsColumnKey];
	            }

	            if ($valueSet) {
	                if ($keySet) {
	                    $resultArray[$key] = $value;
	                } else {
	                    $resultArray[] = $value;
	                }
	            }

	        }

	        return $resultArray;
	    }
	}

	/* 
		Return Financial Year of date
		$date - Date 
		$year_start - Financial Year start month
	*/
	function get_finacialyear($date=false, $year_start=4) {
	    if(!$date) {
	      $date=date('Y-m-d');
	    }
	    $time=strtotime($date);
	    $month=date('m',$time);
	    $current_year=date('Y',$time);
	    $current_yr=date('y',$time);
	    if($month >= $year_start) {
	      return $current_year.'-'.($current_yr+1);
	    }
	    else {
	      return ($current_year-1).'-'.$current_yr;
	    }
	}

	function get_pagination_config() {
	    $CI=&get_instance();
	    $CI->config->load('pagination', TRUE);
	    return $CI->config->config['pagination'];
	}

	function get_pagination_links($total_rows, $other_options=array(), $return_config=true) {
		$CI=&get_instance();
		$CI->load->library('pagination');
		$config=get_pagination_config();
		$config=array_merge($config, $other_options);
		$config['total_rows']=$total_rows;

		/* Set baseurl for pagination */
		if(!isset($config['base_url'])) {
			$config['base_url']=base_url().uri_string()."?";
		}

		$CI->pagination->initialize($config);	
		$links = $CI->pagination->create_links();

		/* Return links with config */
		if($return_config) {
			return array(
				'config'=>$config,
				'links'=>$links
			);
		}
		return $links;
	}


	/* Return bootstrap element label for enable and disable */
	function get_status_markup($status) {
		$status=(string)strtolower($status);
		if(in_array($status, array('0','disable'))) {
			return '<span class="label label-danger">Disable</span>';
		}
		elseif (in_array($status, array('1','enable'))) {
			return '<span class="label label-success">Enable</span>';	
		}
	}

	/*
		Encrypt database primary key while passing on url 
	*/
	function encrypt_id($id) {
		return $id;
	}

	/*
		Decrypt id which encypt using encrypt_id function
	*/
	function decrypt_id($id) {
		return $id;
	}

	/* 
		Load view with master view
		$view - View file name of html data
		$view_data - view variables
		$master_view - Master view name
		$html_data - 
			$view is html content - 1
			$view is view file - 0
	*/
	function view_with_master($view,$view_data=false, $master_view=FALSE, $html_data=false) {
		if(empty($master_view)) {
			$master_view=ADMIN_MASTER_VIEW;
		}

		$view_data['view_type']='file';
		if(!empty($html_data)) {
			$view_data['view_type']='html';
		}

		$view_data['content_view']=$view;

		$CI=&get_instance();
		$CI->load->view($master_view,$view_data);
	}

	/* block theme list */
	function block_theme() {
		$block_theme=array(
			'0'=>'SELECT',
			'1'=>'Left Side',
			'2'=>'Right Side',
			'3'=>'Top Side',
			'4'=>'Bottom Side',
		);

		return $block_theme;
	}

	/* creating the image thumb 
		$image_path - image path
		$width - Thumb width
		$height - Thumb Height
		$thumb_name - Thumb File name 
		$thumb_folder - Thumb folder path
	*/
	function image_thumb($image_path, $width, $height,$thumb_name,$thumb_folder) {
	    $CI =& get_instance();
	    $image_thumb = $thumb_folder . '/' . $thumb_name;
	    if (!file_exists($image_thumb)) {
	        $CI->load->library('image_lib');

	        /*CONFIGURE IMAGE LIBRARY*/
	        $config['image_library']    = 'gd2';
	        $config['source_image']     = $image_path;
	        $config['new_image']        = $image_thumb;
	        $config['create_thumb']     = TRUE;
	        $config['maintain_ratio']   = TRUE;
	        $config['width']            = $width;
	        $config['height']           = $height;
	        $CI->image_lib->initialize($config);
	        $CI->image_lib->resize();
	        $CI->image_lib->clear();
	    }
	}

	function generate_image_field($field_control,$name, $help_block='') {
		$div='<div class="row">
		<div class="col-md-6">'.$field_control.$help_block.'</p>
		</div>
			<div class="col-md-6">
				<input type="text" placeholder="File title" name="'.$name.'"class="form-control" value="">
				<p class="help-block">File title</p>
			</div>
		</div>';
	return $div;
	}

	function month() {
		$month=array(
			''=>'Month',
			'January'=>'January',
			'February'=>'February',
			'March'=>'March',
			'April'=>'April',
			'May'=>'May',
			'June'=>'June',
			'July'=>'July',
			'August'=>'August',
			'September'=>'September',
			'October'=>'October',
			'November'=>'November',
			'December'=>'December',
		);

		return $month;
	}

	function year() {
		$last_month='2010';

		$current_year=date('Y');

		$year=array();
		$year['']='Select';
		for ($i=$last_month; $i <=$current_year ; $i++) { 
			$year[$i]=$i;
		}
		return $year;
	}

	function get_youtube_id($url) {
		$matches=array();
		preg_match('/[\\?\\&]v=([^\\?\\&]+)/', $url, $matches);
		if(isset($matches[1])) {
			return $matches[1];
		}
		return false;
	}

	function validate_youtubeurl($url) {
		if(preg_match("/(youtube.com|youtu.be)\/(watch)?(\?v=)?(\S+)?/", $url, $match)){
			return $match[4];
		}else{
			return false;
		}
	}

	function get_youtube_title($id) {
		$content = file_get_contents("http://youtube.com/get_video_info?video_id=".$id);
		parse_str($content, $ytarr);
		if(empty($ytarr['title'])) {
			return '';
		}
		return $ytarr['title'];
	}


	/* 
		Checking string in array.
		accept 2 parameters
		1 - array
		2 - string which is to be search.
	*/
	
	function checkIfInArrayString($array, $searchingFor) {
	    $i=0;
	    foreach ($array as $key=>$element) {
	        if (strpos($element, $searchingFor) !== false) {
	            return array('index'=>$i,'value'=>$key);
	        }
	        $i++;
	    }
	    return false;
	}	

	function blood_group() {
		$blood_group=array(
			'A+'=>'A+',
			'O+'=>'O+',
			'B+'=>'B+',
			'AB+'=>'AB+',
			'A-'=>'A-',
			'O-'=>'O-',
			'B-'=>'B-',
			'AB-'=>'AB-',
		);
		return $blood_group;
	}

	function content_type_category() {
		$content_categories=array(
			'content'=>'Content',
			'webform'=>'Webform',
		);
		return $content_categories;	
	}


	function encrypt($value, $key) {
		$iv = '`hVyZWr.SCC5vR.?';
		return openssl_encrypt($value, 'AES-256-CBC', $key,0, $iv);
	}


	function decrypt($value, $key) {
		$iv = '`hVyZWr.SCC5vR.?';
		return openssl_decrypt($value, 'AES-256-CBC',$key);
	}
