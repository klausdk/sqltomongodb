#!/usr/local/bin/php
<?



	include ("thislib.php");

	$str = "(a=1 and b=2) or (a=1 and y=2)";

	$str = "(a=1 and b=2 and b=4 or c=55) or (a=1 and (y=2 or x<=3 or bx>=33)) or (a<3 and b=55)";
	$str = "a=(1+4) and b=2 and c=3";
	$str = "a=1 and (b=2 and c=3)";
	$str = "a=1";



	$arr = preg_split ('/(\()|(\))| +(and) +| +(or) +/',$str,null,PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

	#print_r ($arr); 

	$stack = array();

	$outarr = array();
	$out = '';

	foreach ($arr as $val) {
		
		switch ($val) {

			case 'or' : 
				while ((sizeof($stack) > 0) && $stack[sizeof($stack) - 1] == 'and') {
					$out .= " ";
					$e = array_pop($stack);
					$out .= $e;
					$outarr[] = $e;
				} 
				$out .= " ";
				$stack[]= $val;
				break;
				
			case 'and' : 
				$out .= " ";
				$stack[]= $val;
				break;

			case '(' : 
				$stack[]= $val;
				break;

			case ')' :
				while ((sizeof($stack) > 0) && ($stack[sizeof($stack) - 1] != '(') ) {
					$out .= " ";
					$e = array_pop($stack);
					$out .= $e;
					$outarr[] = $e;
				} 
				$null .= array_pop($stack);
				
				break;
			default : 
				$out .= $val;
				$outarr[] = $val;
				break;
				

		}

	}

	# empty
	while ( sizeof($stack) > 0) {
		$out .= " ";
		$e = array_pop($stack);
  	$out .= $e;
		$outarr[] = $e;
	}


	$tmpval = array();
	$cnt=0;
	$nextoper = '';
	$popcount = 2;
	$tmparr=array();
	foreach ($outarr as $val) {

		$cnt++;
		echo "$cnt:\n";
		switch (strtolower($val))  {
			case 'and' :
			case 'or' :

				
				if ($val == 'or') { $mgoper = '$or'; }
				if ($val == 'and') { $mgoper = '$and'; }

				
				if ($val == $outarr[$cnt] ) { $popcount++;echo "same oper $val\n"; continue; }

				$tmparr2 = array();
				for ($i = 1; $i<=$popcount; $i++) {
					$tmparr2[]=array_pop($tmparr);
				}
				$popcount=2;
				$operstring = join(', ',array_reverse($tmparr2));	

				$e = " { $mgoper : [ $operstring ] } "; 	
				

				if (isset($outarr[$cnt])) { # if stuff is still left
					# push it back	
					$tmparr[] = $e;
					echo "hm";
				} else {
					echo "e: $e\n";	
				}

			break;

			default :
				$tmparr[] = equation2mg($val);
				break;
		}
		
	}
	# empty tmparr
	foreach ($tmparr as $val) {
		echo "$val\n";
	}
	echo "$e\n";
	#print ($out . "\n");

	#print_r (($outarr));
	

	

?>
