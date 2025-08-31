<?php	
	$mysqlnd=function_exists('mysqli_get_client_stats');
	define('SERVER',$db_server);
	define('USER', $db_user);
	define('PASS', $db_pass);
	$GLOBALS['sql']=new mysqli(SERVER,USER,PASS);
	$GLOBALS['sql']->set_charset("utf8");
	$GLOBALS['sql']->query("SET sql_mode = '' ");
	function sql($a){
		global $mysqlnd;
		$statement=isset($a['statement']) ? $a['statement'] : '';
		$types=isset($a['types']) ? $a['types'] : null;
		$parameters=isset($a['parameters']) ? $a['parameters'] : null;
		$alone=isset($a['only_first_row']) ? $a['only_first_row'] : false;
		$numeric=isset($a['numeric_array_results']) ? $a['numeric_array_results'] : false;
		if($mysqlnd){
			$query=$GLOBALS['sql']->prepare($statement);
			if($query===false){
				$db=debug_backtrace();
				$error="\nSqlError:\n\tError: ".$GLOBALS['sql']->error."\n\tFile: ".$db[0]['file']."\n\tLine: ".$db[0]['line']."\n\tQuery: ".$db[0]['args'][0]['statement']."\n";
				trigger_error($error,E_USER_ERROR);
			}
			$params=array();
			$params[]=&$types;
			for($i=0;$i<count($parameters);$i++){
				$params[]=&$parameters[$i];
			}
			if($parameters!=null) call_user_func_array(array($query,'bind_param'),$params);
			$query->execute();
			if($GLOBALS['sql']->error){
				$db=debug_backtrace();
				$error="\nSqlError:\n\tError: ".$GLOBALS['sql']->error."\n\tFile: ".$db[0]['file']."\n\tLine: ".$db[0]['line']."\n\tQuery: ".$db[0]['args'][0]['statement']."\n";
				trigger_error($error,E_USER_ERROR);
			}
			$result=$query->get_result();
			if(substr($statement,0,6)=="SELECT" || substr($statement,0,4)=="SHOW"){
				if($result->num_rows!=0){
					$method=$numeric==0 ? MYSQLI_ASSOC : MYSQLI_NUM;
					while($line=$result->fetch_array($method)){
						$return[]=$line;
					}
					return $alone==1 ? $return[0] : $return;
				}
			}else if(substr($statement,0,6)=="INSERT"){
				return $GLOBALS['sql']->insert_id;
			}
		}else{
			if(count($types)==0){
				$query=$GLOBALS['sql']->query($statement);
				if(substr($statement,0,6)=="SELECT" || substr($statement,0,4)=="SHOW"){
					if($query->num_rows!=0){
						$method=$numeric==0 ? MYSQLI_ASSOC : MYSQLI_NUM;
						while($line=$query->fetch_array($method)){
							$return[]=$line;
						}
						return $alone==1 ? $return[0] : $return;
					}
				}else if(substr($statement,0,6)=="INSERT"){
					return $GLOBALS['sql']->insert_id;
				}
			}else{
				$query=$GLOBALS['sql']->prepare($statement);
				if($query===false){
					$db=debug_backtrace();
					$error="\nSqlError:\n\tError: ".$GLOBALS['sql']->error."\n\tFile: ".$db[0]['file']."\n\tLine: ".$db[0]['line']."\n\tQuery: ".$db[0]['args'][0]['statement']."\n";
					trigger_error($error,E_USER_ERROR);
				}
				$params=array();
				$params[]=&$types;
				for($i=0;$i<count($parameters);$i++){
					$params[]=&$parameters[$i];
				}
				if($parameters!=null) call_user_func_array(array($query,'bind_param'),$params);
				$query->execute();
				if($GLOBALS['sql']->error){
					$db=debug_backtrace();
					$error="\nSqlError:\n\tError: ".$GLOBALS['sql']->error."\n\tFile: ".$db[0]['file']."\n\tLine: ".$db[0]['line']."\n\tQuery: ".$db[0]['args'][0]['statement']."\n";
					trigger_error($error,E_USER_ERROR);
				}
				if(substr($statement,0,6)=="SELECT" || substr($statement,0,4)=="SHOW"){
					$metadata=$query->result_metadata();
					$fields=$metadata->fetch_fields();
					foreach($fields as $field) {
						$result[$field->name]='';
						$resultArray[$field->name]=&$result[$field->name];
					}
					call_user_func_array(array($query,'bind_result'),$resultArray);
					$result=array();
					$j=0;
					while($query->fetch()){
						foreach($resultArray as $k=>$v){
							if($numeric==0){
								$result[$j][$k]=$v;
							}else{
								$result[$j][]=$v;
							}
						}
						$j++;
					}
					return $alone==1 ? $return[0] : $return;
				}else if(substr($statement,0,6)=="INSERT"){
					return $GLOBALS['sql']->insert_id;
				}
			}
		}	
	}
?>