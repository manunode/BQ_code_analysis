<?php 
	ini_set('display_startup_errors',1);
	ini_set('display_errors', 1);
	ini_set('log_errors', 1);
	error_reporting(E_ALL);
	ini_set('error_log', __DIR__ . '/php_errors_'.date("Y-m").'_log');
	$dovar="plumbee";
	include_once("bq_indi_engine.php"); 
	$_SESSION['notifications']="<b>●";
	echo '<script src="bq_js.js" defer></script>';
	echo '<script src="product_tour.js" defer></script>';

	if(@count($_GET)==0 and @count($_POST)==0){ // first loading from browser
		// only first time the popup div is loaded
		include_once("do_bq_fw_dragdivs.php");
		setSession("rksastry");
		$_SESSION['designmode']="off";  // first time 
	}


 //echo toast(count($_GET));
	if(count($_GET)>0 and (!isset( $_GET['bqkey']) or  $_GET['bqkey']=="")){
		include_once("bq_utils_security.php");
		$agentString = getFullAgentDetailsJSON();
		$agentArr = json_decode($agentString,true);
		sendSecurityAlert("No bqkey found 5388 \n".date("y-m-d H:i:s"),json_to_bulleted_text($agentString));
		exit;
	}
	if(isset($_GET['bqkey']) and $_GET['bqkey']!=""){
		$bqparam=pw_dec($_GET['bqkey']);
		$arr=explode("&",$bqparam);
		foreach($arr as $a){
			$temp=explode("=",$a);
			$_GET[$temp[0]]=$temp[1];
		}
		$_GET['bqkey']="";
		// toast($_GET['pw']);
		bq_safe_include($_GET['pw'] ?? '');
		
		
		// bq_safe_include($_POST['pw'] ?? '');
		exit;
	}
	
	if(isset($_POST['bqkey']) and $_POST['bqkey']!=""){
		// printr($_POST)	;
		$bqparam=pw_dec($_POST['bqkey']);
		$arr=explode("&",$bqparam);
		foreach($arr as $a){
			$temp=explode("=",$a);
			$_POST[$temp[0]]=$temp[1];
		}
		$_POST['bqkey']="";
		bq_safe_include($_POST['pw'] ?? '');
	}
	$_REQUEST['rty'] = $_REQUEST['rty'] ?? "";
	if($_REQUEST['rty']=='drptable' or $_REQUEST['rty']=='deltabledata' or $_REQUEST['rty']=='stu'){
		include_once($_REQUEST['pw']);
		exit;
	}
	$_POST['rty'] = $_POST['rty'] ?? "";
	if($_POST['rty']=='afl' or $_POST['rty']=='crf'){
		include_once("do_bq_fw_db_tables.php");
	}
	
	
	function xxxbq_safe_include(string $pw): void {
	    // 1) must be a plain php filename only (no /, \, .., querystrings)
	    if ($pw === '' ||
	        strpos($pw, '..') !== false ||
	        strpos($pw, '/') !== false ||
	        strpos($pw, '\\') !== false ||
	        !preg_match('/^[a-zA-Z0-9_\-\.]+\.php$/', $pw)
	    ) {
	        exit('Invalid include');
	    }
	
	    // 2) lock to ONE folder only
	    $baseDir = realpath(__DIR__);   // <<< CHANGE this folder name if needed
	    if ($baseDir === false) {
	        exit('Base folder missing');
	    }
	
	    $file = realpath($baseDir . DIRECTORY_SEPARATOR . $pw);
	    if ($file === false || strpos($file, $baseDir) !== 0) {
	        exit('Access denied');
	    }
	
	    include_once($file);
	    exit;
	}
	
	
	function bq_safe_include(string $pw): void {
	    if ($pw === '' ||
	        strpos($pw, '..') !== false ||
	        strpos($pw, '/') !== false ||
	        strpos($pw, '\\') !== false ||
	        !preg_match('/^[a-zA-Z0-9_\-\.]+\.php$/', $pw)
	    ) {
	        exit('Invalid include');
	    }
	
	    $file = __DIR__ . DIRECTORY_SEPARATOR . $pw;
	
	    if (!file_exists($file)) {
	        exit('File not found');
	    }
	
	    include_once($file);
	    exit;
	}
	
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<title>BeeqU</title>
		<script src="https://code.jquery.com/jquery-3.7.1.min.js" defer></script>

		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
		<!--<link rel="stylesheet" href="https://cdn.webdatarocks.com/latest/webdatarocks.min.css"> -->		
		<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
		<!--<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>-->
		<!--<link href="../res/bqv1/css/bootstrap5.3.8.css" rel="stylesheet">-->
		<!--<link rel="stylesheet" href="bq_css.css">-->
		
		
		<!--<script src="https://cdn.webdatarocks.com/latest/webdatarocks.js"></script>
		<script src="https://cdn.webdatarocks.com/latest/webdatarocks.toolbar.min.js"></script>-->	
		<script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
		<!--<script src="https://unpkg.com/htmx.org@1.9.2"></script>-->
		<!--// for copy and drag a div-->
		<script src="bq_dupldiv.js" defer></script>
		<link rel="stylesheet" href="bq_dupldiv.css">

		<style>
			:root{
				--h1:50px; --h3:25px; --w2:60px;
				--main-width:70%;
				--side-width:30%;
				/* Debug mode ON */
				--bor1: 2px solid #ff7f50;  /* Coral */
				--bor2: 2px solid #6a5acd;  /* SlateBlue */
				--bor3: 2px solid #20b2aa;  /* LightSeaGreen */
			}
	
			.top-bar{height:var(--h1)}
			.footer-bar{height:var(--h3)}
			.menu-bar{width:var(--w2);min-width:var(--w2)}
			.full-height{height:calc(100vh - var(--h1) - var(--h3))}
			.content-columns{display:flex}
			.main-content{
				width:var(--main-width);
				overflow:auto;
			}
			.side-content{
				width:var(--side-width);
				overflow:auto;
			}
			.bq-ellipse{
				padding:1px;
				padding-left: 10px;
				font-size:14px!important;
				max-width:140px;
				overflow:hidden;
				text-overflow:ellipsis;
				white-space:nowrap;
			   
			}
			.bq-ellipse2{
				padding:1px;
				padding-left: 10px;
				font-size:14px!important;
				max-width:140px;
				overflow:hidden;
				text-overflow:ellipsis;
				white-space:nowrap;
			}
			.tr-title {
				  background-color: #f1f3f5;  /* light gray background */
				  text-transform: uppercase;  /* all caps for section header */
				  color: #f00;
				  border-top: 2px solid #dee2e6;  /* top border for separation */
				  border-bottom: 2px solid #dee2e6;
			}
			.tr-title td {
			  padding: 6px 8px;
			}

			/* Draggable divider */
			.resizer{
				width:6px;
				cursor:col-resize;
				background:linear-gradient(90deg,#e9ecef,#dee2e6,#e9ecef);
				border-left:1px solid #dee2e6;
				border-right:1px solid #dee2e6;
				user-select:none;
			}
			.resizer:hover{background:#e2e6ea}
			.resizing .resizer{background:#d0d4d9}
			 
			@media (max-width:767.98px){
				.content-columns{flex-direction:column}
				.main-content,.side-content{width:100% !important}
				.resizer{display:none}
			}
			
		.ellipsis-30 {
		  display: inline-block;       /* or block */
		  max-width: 30ch;              /* ~30 characters */
		  white-space: nowrap;          /* prevent wrapping */
		  overflow: hidden;             /* hide overflow */
		  text-overflow: ellipsis;      /* add the "..." */
		  vertical-align: middle;       /* optional alignment */
		}
		 /* Floating button */
		  #bq-wapp-float {
		    position: fixed;
		    right: 100px;
		    bottom: 3px;
		    z-index: 99999;
		    xwidth: 56px;
		    xheight: 56px;
		    xborder-radius: 50%;
		    xbackground: #25D366;
		    display: flex;
		    align-items: center;
		    justify-content: center;
		    xcolor: #fff;
		    cursor: grab;
		    xbox-shadow: 0 10px 24px rgba(0,0,0,.25);
		    user-select: none;
		    touch-action: none;
		  }
		
		  /* Expanded panel */
		  #bq-wapp-panel {
		    position: fixed;
		    width: 260px;
		    border-radius: 14px;
		    background: #ffffff;
		    box-shadow: 0 14px 40px rgba(0,0,0,.28);
		    z-index: 99998;
		    display: none;
		    overflow: hidden;
		    font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial;
		  }
		
		  #bq-wapp-head {
		    background: #075E54;
		    color: #fff;
		    padding: 10px 12px;
		    font-weight: 600;
		    display: flex;
		    justify-content: space-between;
		    align-items: center;
		  }
		
		  #bq-wapp-body {
		    padding: 12px;
		    font-size: 14px;
		    color: #333;
		  }
		
		  #bq-wapp-body button {
		    width: 100%;
		    border: 0;
		    padding: 10px;
		    border-radius: 8px;
		    background: #25D366;
		    color: #fff;
		    font-weight: 600;
		    cursor: pointer;
		  }
		
		  #bq-wapp-close {
		    cursor: pointer;
		    font-size: 16px;
		  }		
		</style>
	</head>
	<script>
		function closeDivs(){
			document.getElementById('menudiv').style.display='none'
		}
		function waiter(obj,message){
			message = '<div class="m-2 p-2 rounded bg-success-subtle">' 
	        + message 
	        + '<span class="spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true"></span>'
	        + '</div>';		document.getElementById(obj).innerHTML=message;
		}
		function showindiv(divId,html){
			const el = document.getElementById(divId);
			el.innerHTML=html;
		}	
		function showTemp(divId, html) {
		    const el = document.getElementById(divId);
		    el.style.opacity = 1;
		    el.style.transition = 'opacity 2s ease';
		    el.innerHTML = html;
		
		    // Wait before starting fade
		    setTimeout(() => {
		        el.style.opacity = 0;
		    }, 2500);
		
		    // Clear after fade
		    setTimeout(() => {
		        el.innerHTML = '';
		        el.style.opacity = 1; // reset for next time
		    }, 3000);
		}
	</script>
<?php 
		//$bqcapturelink=pw_enc("pw=bq_utils_screencapture.php&action=capture");
		?>
		
<body class="m-0">
  <!-- Top Title Bar  -->
  <!-- xxstyle="background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);background:#2596be!important"-->
 	<div id='topbar' class="top-bar bg-primary text-white d-flex align-items-center justify-content-center gap-2" style='background:linear-gradient(135deg, #667eea 0%, #764ba2 100%)'>
		<img src="res/images/bq_logo.png"
    	style="width:59px;height:50px;
            background:#fff;
            display:inline-block;">
		<h1 class="m-0 fs-4 flex-grow-1 text-center" style='color:#ffffff!important'>BeeqU</h1>
		<div class="d-flex align-items-center gap-2 me-2 px-2 py-1" style='border:0px solid #000'>
			
			<div class="align-items-center" id="pins">..</div>
			<!--<button class="align-items-center btn btn-sm " id="screencap"
				hx-get='do_bq.php?bqkey="<?php //echo $bqcapturelink;?>"' 
				hx-target='#allpops' 
				onclick='showdiv("allpops");setPopAll(100,100,450,450);'
				title='Screen shot for bug reporting'>
				<i class="text-white fs-5 bi bi-camera"></i>
			</button>-->
			<div class='d-flex'
				data-tour-name="Beequ-Pans"
				data-tour-explanation="You can set the list grid and the edit pan in different layouts."
				data-tour-order="-2">
				<div accesskey='1' title="Left pan 30%" style="cursor: pointer;" onclick="setSideWidth('70%')">
					<i class="bi bi-caret-left-fill" style='color:#ffffff'></i>
				</div>
				<div accesskey='2' title="Left pan 70%"  style="cursor: pointer;" onclick="setSideWidth('30%')">
					<i class="bi bi-app" style='color:#ffffff'></i>
				</div>
				<div accesskey='3' title="Left pan 100%"  style="cursor: pointer;" onclick="setSideWidth('0%')">
					<i class="bi bi-caret-right-fill" style='color:#ffffff'></i>
				</div>
			</div>
			<?php 
			$profileicon='<i id="prof" class="fs-4 bi bi-person-square" style="color:#ffffff"></i>';
			if(!empty($_SESSION['designmode']) && $_SESSION['designmode']==="on"){
				$profileicon='<i id="prof" class="fs-4 bi bi-person-square text-danger"></i>';
			}
			$bqkey=pw_enc("pw=bq_fw_profile.php&action=profile");
			?>
			<div title="Profile (alt+p) Design - on" accesskey="p" tabindex="0"
		     style="cursor: pointer;" onclick="closeAllPops();hidediv('profile');openHKPanel('profile')"  hx-get="do_bq.php?bqkey=<?php echo $bqkey;?>"hx-swap="innerHTML" hx-target="#profile">
			<?php echo $profileicon;?>
			</div>
		</div>
	</div>

	  <!-- Body -->
	  <?php
		$bqmenuskey = pw_enc("pw=bq_menus.php&action=menus");
	   ?>
            
	<div class="d-flex full-height">
	    <!-- Left Menu -->
		<div id="f"  class="menu-bar bq-leftmenubar border-end d-flex flex-column align-items-center justify-content-start" onclick="hidediv('pageList');">
				<?php include_once("bq_menus_leftbar.php");?>
		</div>
	
	    <!-- Main + Side with resizer -->
	    <div class="flex-grow-1 content-columns">
			<div class="main-content bg-white p-0 border-bottom border-md-end" id="mainContent" style="position:relative;">
				<iframe class=" rounded shadow border border-secondary border-1 " name='bq_listf' id='bq_listf' style='height:650px;width:100%;z-index:4000;display:none;' src=''></iframe>
				<!--<h2>Main Content</h2>-->
				<!--<p>Main area content goes here...</p>-->
				<!--<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalOne">Open Modal One</button>-->
				<!--<button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#modalTwo">Open Modal Two</button>-->
			</div>
	
	      <!-- Draggable divider -->
	    	<div class="resizer" id="splitResizer" role="separator" aria-orientation="vertical" aria-label="Resize side panel" tabindex="0"></div>
	
	    	<div class="side-content p-1" id="editPanel" style="position:relative;">
	    		<iframe class=" rounded shadow border border-secondary border-1 " name='bq_editf' id='bq_editf' style='display:none;height:650px;width:100%;z-index:4000' src=''></iframe>
		        <!--<h2>Side Panel</h2>-->
		        <!--<p>Side area content here...</p>-->
	    	</div>
	    	</div>
		</div>
		<div id='profile' style='z-index:8000;display:none;position:absolute;top:50px;right:5px;' class='border border-secondary m-1 p-1 shadow rounded bg-white '>data here</div>
	
		<iframe class=" rounded shadow border border-secondary border-1 " name='if1' id='if1' style='display:none;height:500px;width:500px;position:absolute;top:40px;left:40px;z-index:9200' src=''></iframe>

		<div id="twak" name="twak" style="display:none;position:fixed;top:60px;right:10px;width:350px;height:550px;z-index:9999; box-shadow:0 0 10px rgba(0,0,0,.3);background:#fff;">
		
		    <!-- Close Button -->
		    <button id="twakClose" style="position:absolute;top:1px;right:0px;z-index:10000;border:none;background:#dc3545;color:#fff;width:25px;height:25px;border-radius:50%;cursor:pointer;font-size:18px;line-height:25px;">&times;
		    </button>
		    <iframe src="https://tawk.to/chat/6954ec73243c27197ed88f77/1jdprn9iv" style="width:100%; height:100%; border:none;"></iframe>
		</div>
		
		<!--<div name='twak' id='twak' style='display:none;position:absolute;top:60px;width:350px;height:550px;right:0px;'>
			<iframe  width=100% height=100% src='https://tawk.to/chat/6954ec73243c27197ed88f77/1jdprn9iv'></iframe>
		</div>-->
		
		
	  <?php $bqkeynotif = pw_enc("pw=bq_bottom_hub_server.php&action=notify");?>
	  <!-- Footer -->
	  <div class="footer-bar bg-dark text-white d-flex align-items-center px-3 ">
  	
    <p class="m-0" id='notifications'>Footer Bar</p>  
   <!--onclick = "allowAutoRefresh = false;document.querySelector('#bqHud').style.transform = 'translateX(0%)';fetchPost({ a: 1, b: 2 });"-->
	  
    <!-- Push this to the right -->
    
    <p id="notifications" title="Notifications" onclick="handleNotify();"  class="m-1 border border-0 ms-auto" style="font-size:16px;color:#7CFF7C;font-weight:700;cursor:pointer;text-shadow:0 0 4px  #00FF00, 0 0 8px  #00FF00,
           0 0 12px #00FF00, 0 0 20px #00FF66;">ooo</p>
<!--           <p id='twakchat' onclick="toggleDisplay('twak')" style='margin-top:15px;padding-left:5px'><i class="bi bi-chat-quote">Chat</i></p>
-->           
	<!-- Floating Panel  -->
	<div id="bq-wapp-panel">
	  <div id="bq-wapp-head">
	    BeeqU Support
	    <span id="bq-wapp-close">✕</span>
	  </div>
	  <div id="bq-wapp-body">
	    Chat with BeeqU on WhatsApp.<br><br>
	    <button id="bq-open-wapp">Open WhatsApp</button>
	  </div>
	</div>        

		<!-- Floating icon -->
	<!--	<div id="bq-wapp-float" title="BeeqU WhatsApp">
		  Whatsapps
		</div>    -->     
	</div>
	
	  <!-- Modal One -->
		<div class="modal fade" id="modalOne" tabindex="-1" aria-labelledby="modalOneLabel" aria-hidden="true">
			<div class="modal-dialog"><div class="modal-content">
				<div class="modal-header"><h5 class="modal-title" id="modalOneLabel">Modal One</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
				<div class="modal-body">This is the content of Modal One.</div>
				<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
				</div>
			</div>
		</div>
	
	  <!-- Modal Two -->
		<div class="modal fade" id="modalTwo" tabindex="-1" aria-labelledby="modalTwoLabel" aria-hidden="true">
	    	<div class="modal-dialog"><div class="modal-content">
	    		<div class="modal-header"><h5 class="modal-title" id="modalTwoLabel">Modal Two</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
	    		<div class="modal-body">This is the content of Modal Two.</div>
	    		<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
	    	</div>
	    </div>
	</div>
		<div id="menudiv" class='shadow bq_main_modules' style='display:none;'></div>
		
		
		<!--TImer Demo-->
		
<!--Beeq timer for demo-->



<!-- Single Capsule Timer (Dropdown + Time inside ONE capsule) -->
<!--<div class="bq-onecap" id="bqOneCap">-->
<!--  <select class="bq-onecap__sel" id="bqOneCapSel" aria-label="Timer minutes">-->
<!--    <option value="10">10m</option>-->
<!--    <option value="20">20m</option>-->
<!--    <option value="30">30m</option>-->
<!--    <option value="45">45m</option>-->
<!--    <option value="60">60m</option>-->
<!--  </select>-->

<!--  <span class="bq-onecap__sep">|</span>-->

<!--  <span class="bq-onecap__time" id="bqOneCapTxt">--:--</span>-->
<!--</div>-->

<style>
  .bq-onecap{
    position: fixed;
    top: 12px;
    left: 75px;
    z-index: 9999;

    display: inline-flex;
    align-items: center;
    gap: 6px;

    height: 24px;
    padding: 0 10px;

    border-radius: 999px;
    border: 3px solid #888888;
    background: #888888;

    box-shadow: 0 6px 18px rgba(0,0,0,.06);
    font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
    user-select: none;
  }

  .bq-onecap__sel{
    border: none;
    background: transparent;
    font-size: 12px;
    font-weight: 700;
    outline: none;
    padding: 0;
    cursor: pointer;
  }

  .bq-onecap__sep{
    opacity: .4;
    font-size: 12px;
  }

  .bq-onecap__time{
    font-size: 12px;
    font-weight: 800;
    letter-spacing: .2px;
    min-width: 42px;
    text-align: right;
  }

  /* Capsule color states */
  .bq-cap--white  { background:#ffffff; border-color:#e9e9e9; }
  .bq-cap--blue   { background:#e8f1ff; border-color:#ffff00; }
  .bq-cap--orange { background:#fff2e3; border-color:#e65907; }
  .bq-cap--red    { background:#ffe9e9; border-color:#e60716; }
</style>

<script>
(function(){
  const KEY_END   = "bqOneCapEndMs";
  const KEY_TOTAL = "bqOneCapTotalSec";
  const SHOW_SECONDS = true;

  const wrap = document.getElementById("bqOneCap");
  const sel  = document.getElementById("bqOneCapSel");
  const txt  = document.getElementById("bqOneCapTxt");

  let tick = null;

  function pad2(n){ return String(n).padStart(2,"0"); }

  function readState(){
    return {
      endMs: Number(localStorage.getItem(KEY_END) || "0"),
      totalSec: Number(localStorage.getItem(KEY_TOTAL) || "0")
    };
  }

  function writeState(endMs, totalSec){
    localStorage.setItem(KEY_END, String(endMs));
    localStorage.setItem(KEY_TOTAL, String(totalSec));
  }

  function setCapClass(cls){
    wrap.classList.remove("bq-cap--white","bq-cap--blue","bq-cap--orange","bq-cap--red");
    wrap.classList.add(cls);
  }

  function formatRemaining(sec){
    sec = Math.max(0, Math.floor(sec));
    const mm = Math.floor(sec / 60);
    const ss = sec % 60;
    return SHOW_SECONDS ? `${pad2(mm)}:${pad2(ss)}` : `${mm}m`;
  }

  function pickColor(remSec, totalSec){
    if (remSec <= 300) return "bq-cap--red";                 // last 5 min
    if (totalSec > 0 && remSec <= totalSec * 0.25) return "bq-cap--orange"; // last quarter
    if (totalSec > 0 && remSec <= totalSec * 0.50) return "bq-cap--blue";   // half time
    return "bq-cap--white";
  }

  function render(){
    const { endMs, totalSec } = readState();

    if (!endMs || !totalSec){
      txt.textContent = "--:--";
      setCapClass("bq-cap--white");
      return;
    }

    const now = Date.now();
    const rem = Math.max(0, (endMs - now) / 1000);

    txt.textContent = formatRemaining(rem);
    setCapClass(pickColor(rem, totalSec));

    if (rem <= 0){
      txt.textContent = SHOW_SECONDS ? "00:00" : "0m";
      setCapClass("bq-cap--red");
      stop();
    }
  }

  function stop(){
    if (tick){ clearInterval(tick); tick = null; }
  }

  function start(minutes){
    const totalSec = Math.max(1, Math.round(Number(minutes) * 60));
    const endMs = Date.now() + totalSec * 1000;
    writeState(endMs, totalSec);
    stop();
    tick = setInterval(render, 250);
    render();
  }

  sel.addEventListener("change", () => start(sel.value));

  (function init(){
    const { endMs } = readState();
    if (endMs && endMs > Date.now()){
      tick = setInterval(render, 250);
    }
    render();
  })();

  window.addEventListener("storage", (e) => {
    if (e.key === KEY_END || e.key === KEY_TOTAL) render();
  });
})();
</script>

    
		
		
	<script>
	

    const twakBox = document.getElementById('twak');
    const twakClose = document.getElementById('twakClose');

    // Close on button click
    twakClose.addEventListener('click', () => {
        twakBox.style.display = 'none';
    });

    // Close on ESC key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            twakBox.style.display = 'none';
        }
    });

    // Optional: function to open chat
    function openTwak() {
        twakBox.style.display = 'block';
    }

	
	

	
    // Drag logic
    (function(){
    	document.getElementById('notifications').innerHTML ='<?php //echo $_SESSION['notifications'];?>' // notification check 
		const resizer = document.getElementById('splitResizer');
		const side = document.getElementById('editPanel');
		const container = resizer.parentElement; // .content-columns
		let dragging = false;
		const clamp = (val,min,max)=>Math.min(Math.max(val,min),max);
			function onMouseDown(e){
			    if (window.matchMedia('(max-width: 767.98px)').matches) return;
			    dragging = true;
			    document.body.classList.add('resizing');
			    e.preventDefault();
			}
		function onMouseMove(e){
	        if(!dragging) return;
	        const rect = container.getBoundingClientRect();
	        const x = clamp(e.clientX - rect.left, 0, rect.width); // px from container left

        // compute side width as % of container
	        const sideWidthPx = rect.width - x; // because side is on the right
	        const percent = clamp((sideWidthPx / rect.width) * 100, 0, 100);

        // Snap-to-zero if very small
        	if(percent < 2){
	        	setSideWidth('0%');
	        	return;
        	}

        // Show panel if hidden and being dragged open
        	if(side.style.display==='none'){ side.style.display=''; }

	        const sidePct = clamp(percent, 10, 90); // keep reasonable bounds
	        document.documentElement.style.setProperty('--side-width', sidePct.toFixed(2) + '%');
	        document.documentElement.style.setProperty('--main-width', `calc(100% - ${sidePct.toFixed(2)}%)`);
    	}
    	function onMouseUp(){
        	if(!dragging) return;
        	dragging = false;
        	document.body.classList.remove('resizing');
    	}

	      resizer.addEventListener('mousedown', onMouseDown);
	      window.addEventListener('mousemove', onMouseMove);
	      window.addEventListener('mouseup', onMouseUp);

      // Keyboard support: left/right arrows adjust 5%
      resizer.addEventListener('keydown', (e)=>{
        const step = 5; // percent
        const cs = getComputedStyle(document.documentElement).getPropertyValue('--side-width').trim();
        let current = parseFloat(cs || '30') || 30;

        if(e.key==='ArrowLeft'){ current = clamp(current+step, 0, 90); }
        if(e.key==='ArrowRight'){ current = clamp(current-step, 0, 90); }

        if(current<=1){
          setSideWidth('0%');
        }else{
          setSideWidth(current + '%');
        }
      });
    })();
	function fetchPost( dataObj, callback = null) {
	    // Convert data object {a:1, b:2} → "a=1&b=2"
	    const body = new URLSearchParams(dataObj).toString();
	    fetch("do_bq.php?bqkey=<?php echo pw_enc("pw=bq_bottom_hub_server.php");?>", {
	        method: "POST",
	        headers: { "Content-Type": "application/x-www-form-urlencoded" },
	        body: body
	    })
	    .then(res => res.text())
	    .then(text => {
	        if (callback) callback(text);
	    })
	    .catch(err => console.error("Fetch error:", err));
	}  
	function openHudWithoutRefresh() {
		document.querySelector('#bqHud').style.transform = 'translateX(0%)';		
	    //hud.style.transform = "translateX(0%)";
	    //overlay.style.display = "block";
	}
function handleNotify() {
    // Open HUD first
    openHudWithoutRefresh();
    // Wait a tick so DOM updates
    requestAnimationFrame(() => {
        const target = document.getElementById('notifyTarget');
        if (!target) {
            console.error("notifyTarget div not found!");
            return;
        }
        // Show the div only during notification fetch
        target.style.display = "block";

        fetchPost({a:1,b:2}, (res) => {
            target.innerHTML = res;
        });
    });
}
  /* ===========================
     WhatsApp
     =========================== */
(function(){

  /* ===========================
     WhatsApp config (unchanged)
     =========================== */

  var WAPP_NUMBER = "919885431698";

  var userId  = window.BQ_USER_ID  || "";
  var pgid    = window.BQ_PGID     || "";
  var recid   = window.BQ_RECORDID || "";

  var msg = "Hi BeeqU";
  if(userId) msg += "%0AUser: " + encodeURIComponent(userId);
  if(pgid)   msg += "%0APage: " + encodeURIComponent(pgid);
  if(recid)  msg += "%0ARecord: " + encodeURIComponent(recid);

  var waUrl = "https://wa.me/" + WAPP_NUMBER + "?text=" + msg;

  var floatBtn = document.getElementById("bq-wapp-float");
  var panel    = document.getElementById("bq-wapp-panel");

  /* ===========================
     Toggle panel
     =========================== */

  floatBtn.onclick = function(){
    panel.style.display = (panel.style.display === "block") ? "none" : "block";
    syncPanel();
  };

  document.getElementById("bq-wapp-close").onclick = function(){
    panel.style.display = "none";
  };

  document.getElementById("bq-open-wapp").onclick = function(){
    window.open(waUrl, "_blank", "noopener,noreferrer");
  };

  /* ===========================
     DRAG LOGIC (added)
     =========================== */

  var isDragging = false, startX, startY, startRight, startBottom;

  function dragStart(e){
    isDragging = true;
    floatBtn.style.cursor = "grabbing";

    var p = e.touches ? e.touches[0] : e;
    startX = p.clientX;
    startY = p.clientY;

    startRight  = parseInt(getComputedStyle(floatBtn).right);
    startBottom = parseInt(getComputedStyle(floatBtn).bottom);

    document.addEventListener("mousemove", dragMove);
    document.addEventListener("mouseup", dragEnd);
    document.addEventListener("touchmove", dragMove, { passive:false });
    document.addEventListener("touchend", dragEnd);
  }

  function dragMove(e){
    if(!isDragging) return;
    e.preventDefault();

    var p = e.touches ? e.touches[0] : e;
    var dx = p.clientX - startX;
    var dy = p.clientY - startY;

    floatBtn.style.right  = (startRight - dx) + "px";
    floatBtn.style.bottom = (startBottom - dy) + "px";

    syncPanel();
  }

  function dragEnd(){
    isDragging = false;
    floatBtn.style.cursor = "grab";

    document.removeEventListener("mousemove", dragMove);
    document.removeEventListener("mouseup", dragEnd);
    document.removeEventListener("touchmove", dragMove);
    document.removeEventListener("touchend", dragEnd);
  }

  function syncPanel(){
    var r = parseInt(getComputedStyle(floatBtn).right);
    var b = parseInt(getComputedStyle(floatBtn).bottom);

    panel.style.right  = r + "px";
    panel.style.bottom = (b + 68) + "px";    
  }

  floatBtn.addEventListener("mousedown", dragStart);
  floatBtn.addEventListener("touchstart", dragStart);

})();
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  
<?php
include_once("bq_bottom_hub.php");
include_once("do_chatdots.php");
?>


</body>
</html>
