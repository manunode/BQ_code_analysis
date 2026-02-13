<?php
	
	include_once("bq_indi_engine.php");
	if(isset($_GET)){
		if(empty($_POST['q']))$_SERVER['REQUEST_METHOD']='GET';
		if(!empty($_POST['q']))$_SERVER['REQUEST_METHOD']='POST';
		if(empty($_POST['q']) and !empty($_GET['action'])){
			$_SESSION['pickerformdata']=$_POST;
		}
		
	}
	// user selected the record we have to populate it
	// printr($_GET);
	// echo $_GET['pickercontrol'];
	// $pickCaption=$_SESSION['currentpage']['fields'][$_GET['control']]['caption'];

	if(isset($_GET['action']) and $_GET['action']=="pickfill"){
		unset($_GET['bqkey']);
		//$combosql=$_SESSION['currentpage']['fields'][$_GET['pickercontrol']]['combosql'];
		if($_GET['src']=='list_le')$combosql=$_SESSION['currentpage_le']['fields'][$_GET['pickercontrol']]['combosql'];
		if($_GET['src']=='list')$combosql=$_SESSION['currentpage']['fields'][$_GET['pickercontrol']]['combosql'];
		if (preg_match('/\bFROM\s+([a-zA-Z0-9_]+)/i', $combosql, $matches)) {
			$tablename = $matches[1];
		}
		$sql="selrec * from ".$tablename. " where id=?";
		// echo $sql;
		$sql = replaceDS2message("",$sql);
		$ds=getValueForPS($sql,"s",$_GET['id']);
		$tokens=explode(";",$combosql);
		$combosql=$tokens[0];
		$tofields=$tokens[1];
		$maxtodisplay=4;//$tokens[2];
		$lower=strtolower($combosql);
		$posSelect = strpos($lower, "select");
		
		$posFrom   = strpos($lower, "from");
		// Extract the part between SELECT and FROM
		$fromfields = substr($combosql, $posSelect + 6, $posFrom - ($posSelect + 6));
		//$fromfields=str_replace(" id,","",$fromfields);
		$fromfieldsarr=explode(",",$fromfields);
		if($_GET['id']=="none"){
			foreach($fromfieldsarr as $key => $value){
				$ds[trim($value)]="none";
			}
		} 
		if($_GET['id']=="empty"){
			foreach($fromfieldsarr as $key => $value){
				$ds[trim($value)]="";
			}
		}
		$tofieldsarr=explode(",",$tofields);
		$tocount = count($tofieldsarr);
		$fromcount = count($fromfieldsarr);
		$diffcount = $tocount-$fromcount;
		if($diffcount!=0) $fromfieldsarr = array_slice($fromfieldsarr, 0, $diffcount);
		echo "<div style='display:block'></div>";
		// get the picker fields and extract the tofields
		// from id of to fields get the control name prefix_last 6 chars of id
		echo "
		<script>";
		foreach($fromfieldsarr as $k=>$v){
			$encvalue=pw_enc($ds[trim($fromfieldsarr[$k])]);
			//$controltype=strtoupper($_SESSION['currentpage']['fields'][$tofieldsarr[$k]]['controltype']);
			//$controlid=$_SESSION['currentpage']['fields'][$tofieldsarr[$k]]['id'];
			if($_GET['src']=='list_le'){
				$controltype=strtoupper($_SESSION['currentpage_le']['fields'][$tofieldsarr[$k]]['controltype']);
				$controlid=$_SESSION['currentpage_le']['fields'][$tofieldsarr[$k]]['id'];
			}
			if($_GET['src']=='list'){
				$controltype=strtoupper($_SESSION['currentpage']['fields'][$tofieldsarr[$k]]['controltype']);
				$controlid=$_SESSION['currentpage']['fields'][$tofieldsarr[$k]]['id'];
			}
			$controlprefix=substr($controlid,11,100);
			$prefix="txt_";
			if($controltype=="EMAIL") $prefix="eml_";
			if($controltype=="THREAD") $prefix="thc_";
			if($controltype=="COMBO") $prefix="cmb_";
			if($controltype=="TEXT AREA") $prefix="txa_";
			if($controltype=="SQL PICKER") $prefix="spi_";
			if($controltype=="READ ONLY") $prefix="ron_";
			if($controltype=="SMS") $prefix="pho_";
			if($tofieldsarr[$k]=='linkedid' or $tofieldsarr[$k]=='linkedid2' or $tofieldsarr[$k]=='linkedto' or $tofieldsarr[$k]=='linkedto2'){
				$ds[trim($fromfieldsarr[$k])]=$encvalue;
				$encvalue=pw_enc($encvalue);
			}
			echo "
				o=document.getElementById('".$prefix.$controlprefix."');
				o.value='".$ds[trim($fromfieldsarr[$k])]."'
				if ('".$prefix."'== 'ron_' || '".$prefix."'== 'spi_') {
					o=document.getElementById('enc_".$controlprefix."')
					o.value='".$encvalue."'
				}
			";
		}
		echo "
			document.getElementById('bqPopToolbar').style.display='none'
			document.getElementById('allpops').style.display='none'
			document.getElementById('allpopsparent').style.display='none'
		</script>";
		exit;
	}
	echo "<div style='display:block'></div>";
	$_SESSION['pickerformdata']=$_SESSION['pickerformdata'];
	// displaying the list 
	//if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (isset($_POST['q']) ) {
		
		echo "<script>
		//alert('in post');
		document.getElementById('resultDivget').style.display='none';
		document.getElementById('nonget').style.display='none';
		document.getElementById('Clearget').style.display='none';
		//alert('resget');
		document.getElementById('initdiv').style.display='none';
		//alert('final');
		</script>";

		$q = isset($_POST['q']) ? $_POST['q'] : '';
	    //if ($q === '') { exit; }
	    
		// make combosql as picker sql
		$pickercontrol = $_GET['field'];//$_SESSION['currentpage']['meta']['pickercontrol'];
		//$combosql=$_SESSION['currentpage']['fields'][$pickercontrol]['combosql'];
		if($_GET['src']=='list_le'){
			$combosql=$_SESSION['currentpage_le']['fields'][$pickercontrol]['combosql'];
			$src="&src=list_le";
		}
		if($_GET['src']=='list'){
			$combosql=$_SESSION['currentpage']['fields'][$pickercontrol]['combosql'];
			$src="&src=list";
		}
		
		$combosql=str_replace("select ","selrec id,",$combosql);
		
		$tokens=explode(";",$combosql);
		$combosql=$tokens[0];
		if(isset($tokens[3])){
			//printr($_SESSION['pickerformdata']);
			$jcol=explode(",",$tokens[3]);
			foreach($jcol as $key=>$val){
				if($_GET['src']=='list_le'){
					$filedds=$_SESSION['currentpage_le']['fields'][$val];
					$filedcntr=strtoupper($_SESSION['currentpage_le']['fields'][$val]['controltype']);
				}if($_GET['src']=='list'){
					$filedds=$_SESSION['currentpage']['fields'][$val];
					$filedcntr=strtoupper($_SESSION['currentpage']['fields'][$val]['controltype']);
				}
				$right6=substr($filedds['id'],-6);
				$filed="txt_".$right6;
				if($filedcntr=="EMAIL") $filed="eml_".$right6;
				if($filedcntr=="THREAD") $filed="thc_".$right6;
				if($filedcntr=="COMBO") $filed="cmb_".$right6;
				if($filedcntr=="TEXT AREA") $filed="txa_".$right6;
				if($filedcntr=="SQL PICKER") $filed="spi_".$right6;
				if($filedcntr=="READ ONLY") $filed="ron_".$right6;
				if($filedcntr=="SMS") $filed="pho_".$right6;
				$filedValues=$_SESSION['pickerformdata'][$filed];
				if($val=='linkedid')$filedValues=pw_dec($filedValues);
				if($val=='linkedto')$filedValues=pw_dec($filedValues);
				if($val=='linkedid2')$filedValues=pw_dec($filedValues);
				if($val=='linkedto2')$filedValues=pw_dec($filedValues);
				$combosql=str_replace("j:[".$val."]",$filedValues,$combosql);
			}
		}
		if(isfoundin($combosql,"selrec id,")==0)$combosql=str_replace("selrec ","selrec id,",$combosql);
		/*$fields=$tokens[1];
		$temp=explode(",",$fields);*/
		$lower=strtolower($combosql);
		$posSelect = strpos($lower, "select");
		$posFrom   = strpos($lower, "from");
		// Extract the part between SELECT and FROM
		$fromfields = substr($combosql, $posSelect + 6, $posFrom - ($posSelect + 6));
		$temp=explode(",",$fromfields);
		$maxtodisplay=count($temp);
		$savedflds=$maxtodisplay;
		if($maxtodisplay>4)$maxtodisplay=4;//$tokens[2];
		$temp=str_replace("selrec ","",$combosql);
		$combofields=explode(" from ",$temp);
		//printr($combofields);
		$combofields=$combofields[0];
		$sqlflds = explode(",",$combofields);
		$sqlfldscount = count($sqlflds)-1;
		// if($sqlfldscount!=$savedflds) echo "<p>Field mismatch between source and destination.<br>Due to this discrepancy, the picker functionality is not working correctly.</p>";
		$combofields=str_replace(",","  like '%".$_POST['q']."%' or ",$combofields). "  like '%".$_POST['q']."%'";
		$combosql=str_replace( " where "," where (".$combofields.") and ",$combosql);
		if(!isFoundIn($combosql," limit "))$combosql.=" limit 0,20";
		$combosql = replaceDS2message("",$combosql);
		//echo $combosql; // check point
	    $rs = PW_sql2rsPS($combosql);
		if ($rs->num_rows == 0) {
			echo "<div class='p-1 m-2 alert alert-danger'>No data found</div>";
			exit;
		}
		if($_GET['src']=='list_le') $src="&src=list_le";
		if($_GET['src']=='list') $src="&src=list";
		$bqkeynone = pw_enc("pw=do_bq_sqlpicker.php&pickercontrol=".$pickercontrol."&action=pickfill&id=none".$src);
		$bqkeyempty = pw_enc("pw=do_bq_sqlpicker.php&pickercontrol=".$pickercontrol."&action=pickfill&id=empty".$src);
	    echo "<button type='button'  hx-get='do_bq.php?bqkey=".$bqkeyempty."' hx-target='#resultDiv' class='btn btn-primary mb-2 mt-1 me-2 btn-sm'><i class='bi bi-x-circle'></i>
		Clear Picked Data</button><button type='button' hx-get='do_bq.php?bqkey=".$bqkeynone."' hx-target='#resultDiv' class='btn btn-secondary btn-sm mt-1 mb-2'>None</button>";
	    echo "<div style='max-height:320px;overflow:auto'>
	    	<table class='table table-bordered table-hover table-sm'>";
	    $j=1;
		    while ($ds = PW_fetchArray($rs)) {
		       	$bqkey = pw_enc("pw=do_bq_sqlpicker.php&pickercontrol=".$pickercontrol."&action=pickfill&id=".$ds[0].$src);
		    	echo "<tr 
			    	hx-get='do_bq.php?bqkey=".$bqkey."'
					hx-target='#resultDiv'>
		    	<td>".$j."</td>";
		    	for($i=0;$i<($maxtodisplay);$i++){
		    		if(is_null($ds[$i])) $ds[$i] = "--";
		    		$ds[$i]=limitstringto($ds[$i],15);
		        	if($i>0)echo "<td style='cursor:pointer'>".htmlspecialchars($ds[$i], ENT_QUOTES, 'UTF-8')."</td>";
		    	}
		    	$j++;
		        echo "</tr>";
		    }
	    	echo "</table></div>";
	    // Show the dropdown container after HTMX swap (no listeners)
	    echo "<script>document.getElementById('resultDiv').style.display='block';</script>";
	    exit;
	}
	
	if($_GET['src']=='list_le'){
		$pickCaption=$_SESSION['currentpage_le']['fields'][$_GET['control']]['caption'];
		$src = "&src=list_le";
	}
	if($_GET['src']=='list'){
		
		$pickCaption=$_SESSION['currentpage']['fields'][$_GET['control']]['caption'];
		$src = "&src=list";
	}

	?>
	<!doctype html>
	<html>
	<head>
	<meta charset="utf-8">
	</head>
	<body>
	<div>
	<table border='1' width=100% cellpadding="6" style="background:#ffffff!important;border:1px solid #c6c6c6">
		<tr>
			<td class='bq-head bq-popup'>
		  		<b><?php echo "Pick ".$pickCaption;?></b>
			</td>
		</tr>
		<tr>
		  <td>
		  <input type="text" class="form control form-control-sm w-100"  style='border:1px solid #d9d9d9' id="myInput" autofocus name="q" placeholder="Type here..." autocomplete="off" hx-post="do_bq_sqlpicker.php?pgid=<?php echo $_GET['pgid'];?>&control=<?php echo $_GET['control'];?>&field=<?php echo $_GET['control'].$src;?>" hx-vals="<?php echo $src; ?>" hx-trigger="keyup changed delay:300ms"  hx-swap="innerHTML" hx-target="#resultDiv" hx-include="#myInput" hx-on:keyup="document.getElementById('resultDiv').style.display='block'">
		</td>
		</tr>
		<tr>
		  <td><!-- Absolutely positioned suggestions; hidden by default -->
		  <div id="resultDiv" class='p-0 m-0' style="padding:0px;max-height:400px ;overflow:auto;z-index:2000;"></div>
		  <div id="resultDivget" class='p-0 m-0' style="padding:0px;max-height:400px ;overflow:auto;z-index:2000;"></div>
		 </td>
		</tr>
	</table>
	</div>
	</body>
	</html>
<?php
	if ($_SERVER['REQUEST_METHOD'] === 'GET') {
		// make combosql as picker sql
		//$pickercontrol = $_GET['field'];//$_SESSION['currentpage']['meta']['pickercontrol'];
		if(isset($_GET['control']))$pickercontrol = $_GET['control'];//$_SESSION['currentpage']['meta']['pickercontrol'];
		if($_GET['src']=='list_le')$combosql=$_SESSION['currentpage_le']['fields'][$pickercontrol]['combosql'];
		if($_GET['src']=='list')$combosql=$_SESSION['currentpage']['fields'][$pickercontrol]['combosql'];
		//$combosql=$_SESSION['currentpage']['fields'][$pickercontrol]['combosql'];
		$combosql=str_replace("select ","selrec id,",$combosql);
		$tokens=explode(";",$combosql);
		$combosql=$tokens[0];
		if(isset($tokens[3])){
			$jcol=explode(",",$tokens[3]);
			foreach($jcol as $key=>$val){
				if($_GET['src']=='list_le'){
					$filedds=$_SESSION['currentpage_le']['fields'][$val];
					$filedcntr=strtoupper($_SESSION['currentpage_le']['fields'][$val]['controltype']);
				}if($_GET['src']=='list'){
					$filedds=$_SESSION['currentpage']['fields'][$val];
					$filedcntr=strtoupper($_SESSION['currentpage']['fields'][$val]['controltype']);
				}
				$right6=substr($filedds['id'],-6);
				$filed="txt_".$right6;
				if($filedcntr=="EMAIL") $filed="eml_".$right6;
				if($filedcntr=="THREAD") $filed="thc_".$right6;
				if($filedcntr=="COMBO") $filed="cmb_".$right6;
				if($filedcntr=="TEXT AREA") $filed="txa_".$right6;
				if($filedcntr=="SQL PICKER") $filed="spi_".$right6;
				if($filedcntr=="READ ONLY") $filed="ron_".$right6;
				if($filedcntr=="SMS") $filed="pho_".$right6;
				$filedValues=$_SESSION['pickerformdata'][$filed];
				if($val=='linkedid')$filedValues=pw_dec($filedValues);
				if($val=='linkedto')$filedValues=pw_dec($filedValues);
				if($val=='linkedid2')$filedValues=pw_dec($filedValues);
				if($val=='linkedto2')$filedValues=pw_dec($filedValues);
				$combosql=str_replace("j:[".$val."]",$filedValues,$combosql);
			}
		}
		if(isfoundin($combosql,"selrec id,")==0)$combosql=str_replace("selrec ","selrec id,",$combosql);
		/*
		$fields=$tokens[1];
		$temp=explode(",",$fields);
		*/
		$lower=strtolower($combosql);
		$posSelect = strpos($lower, "select");
		$posFrom   = strpos($lower, "from");
		// Extract the part between SELECT and FROM
		$fromfields = substr($combosql, $posSelect + 6, $posFrom - ($posSelect + 6));
		$temp=explode(",",$fromfields);
		$maxtodisplay=count($temp);
		$savedflds=$maxtodisplay;
		if($maxtodisplay>4)$maxtodisplay=4;//$tokens[2];
		$temp=str_replace("selrec ","",$combosql);
		$combofields=explode(" from ",$temp);
		
		$combofields=$combofields[0];
		$sqlflds = explode(",",$combofields);
		$sqlfldscount = count($sqlflds)-1;
		// if($sqlfldscount!=$savedflds) echo "<p>Field mismatch between source and destination.<br>Due to this discrepancy, the picker functionality is not working correctly.</p>";
		if(isset($_POST['q'])){
			$combofields=str_replace(",","  like '%".$_POST['q']."%' or ",$combofields). "  like '%".$_POST['q']."%'";
			$combosql=str_replace( " where "," where (".$combofields.") and ",$combosql);
		}else{
			$combofields="";
			$combosql=$combosql;
		}
		
		if(!isFoundIn($combosql," limit "))$combosql.=" limit 0,20";
		$combosql = replaceDS2message("",$combosql);
		//echo $combosql; // check point
	    $rs = PW_sql2rsPS($combosql);
		if ($rs->num_rows == 0) {
			echo "<div class='p-1 m-2 alert alert-danger'>No data found</div>";
			exit;
		}
		if($_GET['src']=='list_le') $src="&src=list_le";
		if($_GET['src']=='list') $src="&src=list";
		$bqkeynone = pw_enc("pw=do_bq_sqlpicker.php&pickercontrol=".$pickercontrol."&action=pickfill&id=none".$src);
		$bqkeyempty = pw_enc("pw=do_bq_sqlpicker.php&pickercontrol=".$pickercontrol."&action=pickfill&id=empty".$src);
	    echo "<button type='button' id='Clearget' hx-get='do_bq.php?bqkey=".$bqkeyempty."' hx-target='#resultDivget' class='btn btn-primary mt-1 mb-2 me-2 btn-sm'><i class='bi bi-x-circle'></i>
		Clear Picked Data</button><button id='nonget' type='button' hx-get='do_bq.php?bqkey=".$bqkeynone."' hx-target='#resultDivget' class='btn btn-secondary btn-sm mt-1 mb-2'>None</button>";
	    echo "<div style='max-height:320px;overflow:auto' id='initdiv'>
	    	<table class='table table-bordered table-hover table-sm'>";
	    $j=1;
		    while ($ds = PW_fetchArray($rs)) {
		       	$bqkey = pw_enc("pw=do_bq_sqlpicker.php&pickercontrol=".$pickercontrol."&action=pickfill&id=".$ds[0].$src);
		    	echo "<tr 
			    	hx-get='do_bq.php?bqkey=".$bqkey."'
					hx-target='#resultDivget'>
		    	<td>".$j."</td>";
		    	for($i=0;$i<($maxtodisplay);$i++){
		    		if(is_null($ds[$i])) $ds[$i] = "--";
		    		$ds[$i]=limitstringto($ds[$i],15);
		        	if($i>0 && !empty($ds[$i]))echo "<td style='cursor:pointer'>".htmlspecialchars($ds[$i], ENT_QUOTES, 'UTF-8')."</td>";
		    	}
		    	$j++;
		        echo "</tr>";
		    }
	    echo "</table></div>";

	    // Show the dropdown container after HTMX swap (no listeners)
	    echo "<script>document.getElementById('resultDivget').style.display='block';</script>";
	    exit;
	}
	
	
?>

