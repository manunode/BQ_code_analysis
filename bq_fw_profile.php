<?php
// --- Define your PHP variables ---
$bqkey_globalSearch = pw_enc("pw=bq_pagesetup_search.php");
$bqcapturelink=pw_enc("pw=bq_utils_screencapture.php&action=capture");	

if (isset($_GET['action']) && $_GET['action'] === "designmode") {

    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Toggle design mode
    $_SESSION['designmode'] = (!empty($_SESSION['designmode']) && $_SESSION['designmode'] === "on") ? "off" : "on";

    // Show toast
    echo toast("The design mode is: " . $_SESSION['designmode']);

    // JS: Update #prof immediately AND after any HTMX swap
    $dm = ($_SESSION['designmode'] ?? 'off') === 'on' ? 'on' : 'off';

echo "<script>
function updateProf(){
  const prof = document.getElementById('prof');
  if(!prof) return;

  const dm = " . json_encode($dm) . ";

  if(dm === 'on'){
    prof.style.color = '#ff0000';
    prof.title = 'Design mode on.';
  }else{
    prof.style.color = '#ffffff';
    prof.style.textShadow = 'none';
    prof.title = 'Design mode off.';
  }
}
updateProf();
document.body.addEventListener('htmx:afterSwap', updateProf);
</script>";

    exit;
}

// --- Output the HTML content ---
/*<div style='background:#5c5c5c;color:#fff;' class='rounded btn btn-white position-relative btn-sm my-account-btn border border-1 mt-2 d-flex align-items-center gap-1 px-2 py-2' 
				hx-get='do_bq.php?bqkey=".pw_enc("pw=bq_ai_tools_aibot.php&action=aibot")."' hx-target='#allpops' hx-swap='innerHTML' onclick='showdiv(\"allpops\");setPopAll(100,80,1000,500);hidediv(\"profile\");'
				title='BQ AI Help'><i class='bi bi-camera-fill text-white' ></i>? AIxxz
			</div>*/
echo "<div id='hkutils'>
		<div class='bg-white' style='width:300px;padding:0px;margin:0px;' id='myProfileDiv'>
        <div class='p-1'>
	        <div class='d-flex justify-content-between align-items-center' style='background:#c9daec!important;'>
			    <div class='dropdown-header bg-body-tertiary text-body-secondary fs-7 fw-semibold p-2' style='background:#c9daec!important;'>
			        My Profile
			    </div>
				<div class='pe-2' onclick='hideDiv(\"profile\");' style='cursor:pointer'>
				    <i class='bi bi-x-lg'></i>
				</div>
			</div>
			
					
	        <div class='d-flex gap-2 mb-1 py-1'>
		          <button style='background:#5c5c5c;color:#fff;' class='btn btn-sm my-account-btn'>
				    <i class='bi bi-person-circle text-white'></i> My Account
				  </button>
				  <button style='background:#5c5c5c;color:#fff;' class='btn btn-sm my-account-btn'>
				    <i class='bi bi-list-check text-white'></i> My Tasks
				  </button>
			</div> 
		  
			<div class='d-flex gap-2 mb-1 py-1 ' >
			     <button id='design' title='Design (alt+z)' style='background:#5c5c5c;color:#fff;' class='btn btn-sm my-account-btn border border-1  gap-1 px-2 py-2 position-relative' 
			    	hx-get='do_bq.php?bqkey=".pw_enc("pw=bq_fw_profile.php&action=designmode")."' hx-target='#editPanel' onclick='hideDiv(\"profile\");'>
			    		<span class='hotkey-badge-sm text-dark'>
							<span  style='position:absolute;top:-22px;right:3px'>Z</span>
					</span>
			    		<i accesskey='z' onclick='openHKPanel('design')' class='bi bi-sliders text-white'></i> Design: ".$_SESSION['designmode']."
					</button>
					
					<div style='background:#5c5c5c;color:#fff;' class='btn position-relative my-account-btn' hx-get='do_bq.php?bqkey=".$bqcapturelink."' hx-target='#allpops' onclick='showdiv(\"allpops\");setPopAll(100,100,450,450);hidediv(\"profile\");'
							title='Screen shot for bug reporting'>
						 <i class='bi bi-camera-fill text-white' onclick='hideDiv();'></i> Tickets
					</div>
			</div> 
			<div class='d-flex gap-2 mb-1 py-1 ' >
			     <button style='background:#5c5c5c;color:#fff;' class='btn btn-sm my-account-btn position-relative' 
			    	hx-get='do_bq.php?bqkey=".pw_enc("pw=bq_changepswd.php")."' hx-target='#allpops' title='Change Password' onclick='showdiv(\"allpops\");setPopAll(100,80,500,565);hidediv(\"profile\");'>
			    		<i class='bi bi-key text-white'></i> Change Password
					</button>
				<button style='background:#5c5c5c;color:#fff;' class='btn my-account-btn' onclick='closeAllPops();tourToggle();' accesskey='t' >
				<i class='bi bi-signpost'></i> Product Tour</button>	
			</div> 
         </div>

	     <div class='ms-1 dropdown-header bg-body-tertiary text-body-secondary fw-semibold p-2' style='background:#c9daec!important;cursor:pointer' onclick='showdiv(\"colabtools\");'><i class='bi bi-caret-down-fill text-secondary'></i> Collaboration Tools</div>
	     
	     <div id='colabtools' style='display:none'>
		    <div class='p-1' style='xmax-width:400px;xxborder:3px solid #000'>
		    
		 	  <div class='d-flex flex-wrap gap-2 mb-1'>
		        <button class='btn btn-white  btn-sm my-account-btn border border-1 d-flex align-items-center gap-1 px-2 py-2' onclick=\"closeAllPops();window.open('".ETHERCALC."','_blank')\"> <i class='bi bi-table text-secondary'></i>EtherCalc</button>
    		    
    		    <button class='btn btn-white  btn-sm  my-account-btn border border-1 d-flex align-items-center gap-1 px-2 py-2' onclick=\"closeAllPops();window.open('".ETHERPAD."','_blank')\"><i class='bi bi-pencil-square text-secondary'></i> Etherpad</button>
    		    
    		    <button class='btn btn-white  btn-sm my-account-btn border border-1 d-flex align-items-center gap-1 px-2 py-2' onclick=\"closeAllPops();window.open('https://excalidraw.com','_blank')\">
    		    	<i class='bi bi-brush text-secondary'></i> Excalidraw</button>
    		    
    		    <button class='btn btn-white btn-sm my-account-btn border border-1 d-flex align-items-center gap-1 px-2 py-2' onclick=\"closeAllPops();window.open('".DRAWIO."','_blank')\">
    		    	<i class='bi bi-diagram-3 text-secondary'></i> Draw.io</button>
    		    
    		    <button class='btn btn-white btn-sm my-account-btn border border-1 d-flex align-items-center gap-1 px-2 py-2' onclick=\"closeAllPops();window.open('".MINDMAP."','_blank')\">
    		    	<i class='bi bi-diagram-3-fill text-secondary'></i> Mind Map</button>
    		    
    		    <button class='btn btn-white btn-sm my-account-btn border border-1 d-flex align-items-center gap-1 px-2 py-2' onclick=\"closeAllPops();window.open('https://cryptpad.fr/','_blank')\">
    		    	<i class='bi bi-shield-lock text-secondary'></i> CryptPad</button>
    		    
    		    <button class='btn btn-white btn-sm my-account-btn border border-1 d-flex align-items-center gap-1 px-2 py-2' onclick=\"closeAllPops();window.open('https://webwhiteboard.com/','_blank')\">
    		    	<i class='bi bi-easel text-secondary'></i> Whiteboard</button>
    		    	
    		  </div>
    		 
    		  <div class='d-flex flex-wrap gap-2 mt-1 mb-1'>
    		    <button class='btn btn-white btn-sm my-account-btn border border-1 d-flex align-items-center gap-1 px-2 py-2  ' onclick=\"window.open('https://docs.google.com/spreadsheets/','_blank')\"><i class='bi bi-table text-secondary'></i>Google Sheet</button>
    		    
    		    <button class='btn btn-white btn-sm my-account-btn border border-1 d-flex align-items-center gap-1 px-2 py-2  ' onclick=\"window.open('https://docs.google.com/document/','_blank')\"><i class='bi bi-file-earmark-text text-secondary'></i>Google Doc</button>
    		    
    		    <button class='btn btn-white btn-sm my-account-btn border border-1 d-flex align-items-center gap-1 px-2 py-2  ' onclick=\"window.open('https://docs.google.com/presentation/','_blank')\">
    		    <i class='bi bi-file-earmark-slides-fill text-secondary'></i>Google Slide</button>
    		  </div>
    		  
    	</div>
    	</div>";
    	$Search=pw_enc('pw=bq_pagesetup_search.php&action=search');
    		  echo "<form id='searchbox' onsubmit='hideDiv();' hx-post='do_bq.php?bqkey=".$Search."' hx-target='#editPanel' hx-swap='innerHTML'  class='d-flex align-items-center p-2' style='max-width:450px;'>
					<b>Search</b> &nbsp; <input type='text' name='q' class='form-control form-control-sm me-2' autofocus placeholder='Search...'  required>
					<button type='submit' class='btn btn-sm btn-primary me-1'>Go</button>
					</form>
				</div>";
		     echo "</div>
    		</div>";
      
      
    		 /* <div class='d-flex gap-2 mb-1 py-1'>
    		  <button id='hks' style='background:#5c5c5c;color:#fff;' class='btn btn-white btn-sm my-account-btn border border-1 d-flex align-items-center gap-1 px-2 py-2 position-relative' onclick='closeAllPops();setSideWidth(\"30%\");' hx-get='do_bq.php?bqkey=".$bqkey_globalSearch."' hx-target='#editPanel' hx-swap='innerHTML'>
    				<span class='hotkey-badge-sm text-dark'>
							<span style='position:absolute;top:-22px;right:3px'>S</span>
					</span>
    		    <i class='bi bi-search text-white'  onclick='hideDiv();'></i>Search...</button>
    		  </div>*/

		echo "<script>
				function hideDiv() {
				const profile = document.getElementById('myProfileDiv');
				if (!profile) {
				    alert('Profile section not found!');
				    return;
				}
				profile.style.setProperty('display', 'none', 'important');
				}
				
				openHKPanel('hkutils')
		</script>";
?>
