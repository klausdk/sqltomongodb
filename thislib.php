<?


	### Handle where

function equation2mg($exp) {
	# split operator
	$exp = trim($exp);
	if (!$exp) { return(''); }


	### check for normal 
	if (preg_match ('/(\w+).*?([<>=!]+)(.*)/i',$exp,$matches)) {
		$operator = $matches[2];

	### check for is null and such
	} else if (preg_match ('/([^ ]+) +?(is +?null|is +?not +?null|like) *?(.*)/i',$exp,$matches)) {
		$operator = strtolower($matches[2]);
		#print_r ($matches);
		#print ($exp . " $operator <br/>");
	}

	$matches[3] = trim($matches[3]);
	if ($operator == '=') {
		$mg_equation = "{ " . $matches[1] . " : " . $matches[3] . " }";
	} elseif ($operator == '<') {
		$mg_equation = "{  " . $matches[1] . " : { '\$lt' : $matches[3] } }";
	} elseif ($operator == '>') {
		$mg_equation = "{  " . $matches[1] . " : { '\$gt' : $matches[3] } }";
	} elseif ($operator == '<=') {
		$mg_equation = "{  " . $matches[1] . " : { '\$lte' : $matches[3] } }";
	} elseif ($operator == '>=') {
		$mg_equation = "{  " . $matches[1] . " : { '\$gte' : $matches[3] } }";
	} elseif ($operator == 'is null') {
		$mg_equation = "{  " . $matches[1] . " : null }";
	} elseif ($operator == '!=') {
		$mg_equation = "{  " . $matches[1] . " : { '\$ne' : $matches[3] } }";
	} elseif ($operator == 'is not null') {
		$mg_equation = "{  " . $matches[1] . " : { '\$ne' : null } }";
	} elseif ($operator == 'like') {
		$a = $matches[1];
		$b = $matches[3];
		if (!preg_match('/[%_]/',$b)) {
			$mg_equation = "{ $a : $b }";
		} else {
			$b = trim($b);
			$b = preg_replace ("/(^['\"]|['\"]$)/","",$b); # 'text' -> text  or "text" -> text
			if (!preg_match ("/^%/",$b)) { $b = "^" . $b; }  # handles like 'text%' -> /^text/
			if (!preg_match ("/%$/",$b)) { $b .= "$"; }  # handles like '%text' -> /^text$/
			$b = preg_replace ("/%/",".*",$b);
			$b = preg_replace ("/_/",".",$b);
			$mg_equation = "{ $a : /$b/}";
		}
		#print ($exp . " $operator <br/>");
		#sqlerror ("unsupported");
		#$mg_equation = "{  " . $matches[1] . " : { '\$ne' : null } ";
	} else {
		sqlerror ("Unknown operator '$operator' :  $exp");	
	}

	return ($mg_equation);
}

function where2mg($str) {



	# Make infix stuff to polishstuff

	$arr = preg_split ('/ *?(\() *?| *?(\)) *?| +(and) +| +(or) +| +(not) +|(not)/i',$str,null,PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

	#print_r ($arr);
	$stack = array();
	$polish = array();

	foreach ($arr as $val) {
		
		
		$val = trim($val);
		#echo "'$val'\n<br/>";
		switch (strtolower($val)) {

			case 'not' : 
				#while ((sizeof($stack) > 0) && ($stack[sizeof($stack) - 1] != 'and' || $stack[sizeof($stack) - 1] != 'or') ) {
				#	$e = array_pop($stack);
				#	$polish[] = $e;
				#} 
				$stack[]= strtolower($val);
				break;
			case 'or' : 
				while ((sizeof($stack) > 0) && ( $stack[sizeof($stack) - 1] == 'and' ) ) {
					$e = array_pop($stack);
					$polish[] = $e;
				} 
				$stack[]= strtolower($val);
				break;
				
			case 'and' : 
				$stack[]= strtolower($val);
				break;

			case '(' : 
				$stack[]= $val;
				break;

			case ')' :
				while ((sizeof($stack) > 0) && ($stack[sizeof($stack) - 1] != '(') ) {
					$e = array_pop($stack);
					$polish[] = $e;
				} 
				$null .= array_pop($stack);
				
				break;
			default : 
				### handle not
				$polish[] = $val;
				if (1) {
				while ( (sizeof($stack) > 0) && $stack[sizeof($stack) - 1] == 'not' ) {
					#echo "... $val";
					$e = array_pop($stack);
					$polish[] = $e;
				}
				}

	
				break;

		}
	}

	# empty stack
	while ( sizeof($stack) > 0) {
		$e = array_pop($stack);
		$polish[] = $e;
	}

	
	#foreach ($polish as $key) { echo $key . " "; }
	#echo "<br/>";


	#### Polish stuff to mongo  
	$tmpval = array();
	$cnt=0;
	$nextoper = '';
	$popcount = 2;
	$tmparr=array();
	foreach ($polish as $val) {

		$cnt++;
		switch (strtolower($val))  {
			case 'and' :
			case 'not' :
			case 'or' :

				if ($val == 'or') { $mgoper = '$or'; }
				if ($val == 'and') { $mgoper = '$and'; }
				if ($val == 'not') { 
					#echo "not found";
					$mgoper = '$not'; 
					$popcount = 1;
				}

				if ($val == $polish[$cnt] && $popcount > 1 ) { 
					$popcount++;
					#echo "same oper $val\n"; 
					continue; 
				}

				$tmparr2 = array();
				for ($i = 1; $i<=$popcount; $i++) {
					$tmparr2[]=array_pop($tmparr);
				}
				$operstring = join(", ",array_reverse($tmparr2));	

				if ($popcount==1) {
					$e = $operstring;
					### Rewrite 'not' stuff
					if ($val == 'not') {
						# fx { b : { $ne : 5 } } = { b : 5 }
							$e = preg_replace ('/{ (\w+) : ([\w\'"]+) }/','{ $1 : { $ne : $2 } }',$e);	
						# fx:  { a : 5 }  = { a : { $ne : 5 } };
						### if no change
						if ($e == $operstring) {
							$e = preg_replace ('/{ (\w+) : { \$ne : ([\w\'"]+) } }/','{ $1 : $2 }',$e);
						}
						echo $e;
					} else {
						$e = " { $mgoper : $operstring } .."; 	
					}
					$tmparr[] = $e;
					$popcount=2;
					continue;

				} else {
					$e = " { $mgoper : [  $operstring ] } "; 	
				} 
				
				$popcount=2;

				if (isset($polish[$cnt])) { # if stuff is still left
					# push it back	
					$tmparr[] = $e;
				} else {
					# only called once
					$rs .= $e;
				}

			break;

			default :
				$tmparr[] = equation2mg($val);
				#$tmparr[] = ($val);
				break;
		}
	}
	foreach ($tmparr as $val) {
		$rs .= $val;
	}

	return ($rs);

}

function getrequest() {
  ### magicquotes check
  if (!get_magic_quotes_gpc()) {
    array_walk_recursive($_GET, 'addslashes_gpc');
    array_walk_recursive($_POST, 'addslashes_gpc');
    array_walk_recursive($_COOKIE, 'addslashes_gpc');
    array_walk_recursive($_REQUEST, 'addslashes_gpc');
  }
	import_request_variables("gp", "r_");
}

?>
