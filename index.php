<?

	include ("generel.php");
	include ("thislib.php");

	getrequest();


	# queries to convert :
	# 
	# http://stackoverflow.com/questions/7122905/how-to-convert-such-sql-query-to-mongodb-query
	# http://www.mongodb.org/display/DOCS/SQL+to+Mongo+Mapping+Chart

	# other who automated
	# http://rickosborne.org/blog/2010/02/yes-virginia-thats-automated-sql-to-mongodb-mapreduce/


  $title = "Convert sql to mongodb online";
  $meta =<<<eof
<meta name="Description" content="Online sql to mongodb converter"/>
eof;

	
	$sql = array();

	if ($r_sql) {

		
		# make as oneline
		$r_sql = stripslashes(trim($r_sql));
		$oneline_sql = preg_replace ('/\r\n?/',' ', ($r_sql));
		# remove ending ;'s
		$oneline_sql = preg_replace ('/;+$/','', ($oneline_sql));
		


		preg_match('/^(\w+) /i',$oneline_sql,$querytype);
		$sql['querytype'] = strtolower($querytype[1]);

		### If select
		if ($sql['querytype'] == 'drop') {

			sqlerror("Drop not supported yet");
		} else if ($sql['querytype'] == "select") {
			$findcommand = "find";	
		
			preg_match('/select *?(.*?)from/i',$oneline_sql,$fields);
			preg_match('/select.*?from(.*?)($|where|group.*?by|order.*?by|limit|$)/i',$oneline_sql,$tables);
			preg_match('/select.*?from.*?where(.*?)(group.*?by|order.*?by|limit|$)/i',$oneline_sql,$where);
			preg_match('/select.*?from.*?(where|group.*?by|order.*?by|.*?).*?limit (.*?)$/i',$oneline_sql,$limit);
			preg_match('/select.*?from.*?order.*?by(.*?)(limit|$)/i',$oneline_sql,$orderby);


			$sql['fields'] = split(',',$fields[1]);
			$sql['tables'] = split(',',$tables[1]);
			if ($where[1]) { $sql['where'][] = $where[1]; }
			if ($limit[2]) { $sql['limit'] = $limit[2]; }
			if ($orderby[1]) { $sql['orderby'] = $orderby[1]; }

			#echo "<pre>" . print_r ($orderby,1) , "</pre>";
			### Handle fields
			# remove spaces
			
			foreach ($sql['fields'] as $key => $value) {
				
				if (preg_match ('/count\((.*?)\)/i',$value,$countmatch)) {
					# Special fields
					$mg_count .= ".count()";	
					$countfield = $countmatch[1];
					if ($countfield != "*") {
						$mg_where .= " { $countfield : { '\$exists' : true } } ";
					}
				} elseif (preg_match ('/distinct (.*?) *$/i',$value,$distinctmatch)) {
					$distinctfield = $distinctmatch[1];
					$mg_distinct .= ".distinct('$distinctfield') (not working)";
			
				} else {
					# normal fields
					$sql['fields'][$key] = trim ($value);
					$tmpfields[]= trim ($value);
				}
			}
			$sql['fields'] = $tmpfields;
			if (sizeof($sql['fields'] > 1) && $sql['fields'][0] != '*') {

				if (is_array($sql['fields']) && sizeof($sql['fields'] > 1)) {
					$mg_fields = ',{' . join (':1,',$sql['fields']) . ':1}';
				}
			}

			### Handle table

			if (sizeof($sql['tables']) > 1) {
				sqlerror ("only one table for now");	
			} else {
				$mg_collection = trim($sql['tables'][0]);
			}
			

			### Handle where
			if (is_array($sql['where'])) {
				foreach ($sql['where'] as $key) {
					# split operator
					$mg_where .= where2mg($key);
				}
			}

			### Handle order by

			if ($sql['orderby']) {
				$orderby = trim($sql['orderby']);
				$arr = preg_split("/ +/",$orderby);
				$orderfield = $arr[0];
				$ordersort = strtolower($arr[1]);
				$mg_sort = '.sort( { ' . $orderfield . ' : ';	

				if ($ordersort == 'asc') {
					$mg_sort .= "1";
				} elseif ($ordersort == 'desc') {
					$mg_sort .= "-1";
				} else {
					sqlerror("desc or asc missing $mg_sort $orderby");
				}
				$mg_sort .= " } )";
			}


			### Handle limit

			if ($sql['limit']) {
				$limits = split (',', $sql['limit']);
				$limitcnt=0;
				$rowstofind = 0;
				foreach ($limits as $value) {
					$value = trim($value);
					$limitcnt++;
					if ($limitcnt == 2) {
						$mg_skip = ".skip($value)";
						$mg_limit = ".limit($skipvalue)";;
						$rowstofind = $skipvalue;
					} else {
						$mg_limit = ".limit($value)";
						$skipvalue = $value;
						$rowstofind = $skipvalue;
					}
				}
				if ($rowstofind==1) {
					$findcommand = "findOne";	
				} else {
					$findcommand = "find";	
				}
			}

			
			$mongo = "db.$mg_collection.$findcommand( $mg_where$mg_fields )$mg_distinct$mg_count$mg_skip$mg_sort$mg_limit";

			### for testing
			infolog ("sqltomong", "sql:\n\n $r_sql\n\n mongo:\n\n $mongo\n\n");

		} else {
			sqlerror ("unsupported querytype for the time being: " . $sql['querytype']);
		}
		echo "<pre>";
		#print_r($sql);
		echo "</pre>";
	}


	if (!$r_sql) {
		$r_sql = "select a,b from table where c<=4 and (a=1 or (b=2 and c='something'))";
	}
	
function sqlerror($msg) {
	echo "<br/><b>$msg</b><br/>\n";
}

$head = <<<eof
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-121449-4']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>

<!-- google -->

<script type="text/javascript">
  (function() {
    var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
    po.src = 'https://apis.google.com/js/plusone.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
  })();
</script>

<style>
	label { display:block; }
	li { list-style: none; }
  #socialbuttons { margin-top:20px; }
</style>

eof;

?>

<? include ("header.php"); ?>

	<h1>Convert sql to mongodb</h1>
	<h2>This converter is far from done and makes a lots of mistakes!</h2>
	<p>Online convert sql to mongodb</p>



	<form method="post">

		<label>Sql to convert:</label>
		<textarea cols="80" rows="10" name="sql"><?=htmlentities($r_sql)?></textarea>
		<div>
			<input type="submit" name="submit" value="Convert"/>
		</div>
	</form>

	<div>
		<? if ($mongo) { ?>
			<label>mongosyntax:</label>
			<textarea cols="80" rows="10"><?=$mongo?></textarea>
		<? } ?>
	</div>


<div id="socialbuttons">
<ul>
<li>
<!-- twitter -->
<a href="https://twitter.com/KlausDK" class="twitter-follow-button" data-show-count="false">Follow @KlausDK</a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>

<!-- google -->
</li>
<li>
<g:plusone size="small" annotation="inline"></g:plusone>
</li>
</ul>

</div>

<p>Another sql to mongo converter. Works nicely with group. <a href="http://www.querymongo.com/" target="_blank">http://www.querymongo.com</a></p>

<p>
  Bitcoin donation: 1K1ausqBJeF6n6pftnrHLcYWecCbSzJnKg
</p>


<h3>Changelog:</h3>

<ul>
	<li>2012-01-26: make b!=5 into { b : { $ne : 5 } }.</li>
	<li>2012-01-24: "not" is really working badly.</li>
	<li>2012-01-18: not improved : makes not b=5 into { b : { $ne : 5 } }</li>
	<li>2012-01-17: 'not' seems to be working..  (ie. where not a=3 and b=4). (no.. not working anyway. Gives error in mongodb)</li>
	<li>2012-01-16: Handle like .</li>
	<li>2012-01-16: Handle is null and is not null.</li>
	<li>2012-01-16: Use findOne if limit is 1.</li>
	<li>2012-01-16: Handles count(field).</li>
	<li>2012-01-13: improved where parsing. Now handles 'and' 'or' and parantheses.</li>
	<li>2012-01-12: Very simple sql can be converted</li>
	<li></li>
	<li></li>
</ul>

<h3>To do: </h3>
<ul>
	<li> Make != work</li>
	<li> select distinct</li>
	<li> select a in (x,x,x)</li>
	<li> explain</li>
	<li> update</li>
	<li> group by (argh!)</li>
	<li> delete from </li>
	<li> drop table </li>
	<li> joins (argh!) </li>
	<li> sub selects (argh!) </li>
	<li> insert </li>
	<li> create index </li>
</ul>

<? include ("footer.php"); ?>

