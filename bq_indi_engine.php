<?php
//=================== framework functions ============================
	ini_set('session.use_strict_mode', 1);        // reject uninitialized IDs
	ini_set('session.use_only_cookies', 1);       // never use URL IDs
	ini_set('session.use_trans_sid', 0);          // belt & suspenders
	ini_set('session.cookie_httponly', 1);        // JS can’t read cookie
	ini_set('session.cookie_secure', 1);          // send only over HTTPS
	ini_set('session.cookie_samesite', 'Lax');    // or 'Strict' for tighter CSRF control
	session_start();
	setErrors(); 
	// start of constants management
		include_once("bq_utils_constants.php");
		echo'<link rel="stylesheet" href="bq_css.css">';
		defineConstrants();
		
		if(!isset($_SESSION['designmode'])) $_SESSION['designmode']="Off";
		if($_SESSION['designmode']=="on"){
			$clientmeta =get_full_request_meta_json();
			prepend_to_file($clientmeta);
		}
		$_SESSION['conn'] = PW_connect();
		
		// count($_GET) means loading from browser or refresh else will not be loaded
		if(isset($_GET) and count($_GET)==0){
			// get all constants defined by in the table
			$_SESSION['PW_CONSTANTS']=[];
			$rs= PW_sql2rsPS("selrec lookcode,txt1 from _pb_lookups where looktype='Constants' ");
				while ($ds=PW_fetchAssoc($rs)){
					 define($ds['lookcode'],$ds['txt1']);
					 $_SESSION['PW_CONSTANTS'][$ds['lookcode']]=$ds['txt1'];
				} 
		}else{
			// push session back to constants as we use these constants
			if(isset($_SESSION['PW_CONSTANTS'])){
				foreach($_SESSION['PW_CONSTANTS'] as $k=>$v){
					define($k,$v);
				}
			}
		}
	// end of constants management
	
	if(isset($_POST['form_xref']) && !empty($_POST['form_xref'])){
		checkXref($_POST['form_xref'],$_POST['xref']);
	}
	if(isset($_GET['time']) and $_GET['time']!=""){
		$sentTime=$_GET['time'];
		$replyTime=time();
		$lapsed=$replyTime-$sentTime;
		if($lapsed>60){
			echo '<link href="../res/bqv1/css/bootstrap5.3.8.css" rel="stylesheet">';
			echo toast("Link lapsed send command again.","danger");
			exit;
		}
	}
	
	// sendmail("sastry@plumsoft.com","telebot1:","DDDDDDDDDD","sastry@plumsoft.com"); 
	
	// ---------------------------------------------------------
// (C) OPTIONAL: Referrer domain validation
// ---------------------------------------------------------

	$ref = $_SERVER['HTTP_REFERER'] ?? '';
	$host = $_SERVER['HTTP_HOST'] ?? '';
	getRequest(); //temporarily suspeneded used 
	// sendmail("sastry@plumsoft.com","telebot2:","eeeee","sastry@plumsoft.com"); 
	function setErrors(){
		// ini_set('display_startup_errors',1);
		// ini_set('display_errors', 1);
		// ini_set('log_errors', 1);
		// error_reporting(E_ALL);
		// ini_set('error_log', __DIR__ . '/php_errors.log');
		// // in production
		//  ini_set('display_errors', 1);
		//  ini_set('display_startup_errors', 1);
	}

	function xxxpw_enc($str,$formName="",$controlName=""){
		//  pw_enc 			: Encrypts the given string
		//  See configuration params $_SESSION["ENCRYPTION"]=ON/OFF
		//  OPENSSLPREFIX0 : The prefix for the encryption for additional randomness and safety
		//  OPENSSLPREFIX1 : The sufix for the encryption for additional randomness and safety
		//  OPENSSLMETHOD  : The encryption method such as "aes256" etc
		//  OPENSSLKEY     : The encryption key such as "jshj7&5%Jq" etc
		$session2_10Chars=substr(session_id(),2,10); // brutal attempt fixing may 2023 sastry
		$_SESSION["ENCRYPTION"]="ON";
		if($_SESSION["ENCRYPTION"]=="ON" and $str!=""){
			$_SESSION['encSessKey'] = rand(111111,999999);
			$str = "l".rand(2222,9999)."/**/".OPENSSLPREFIX0."/**/".$str."/**/".OPENSSLPREFIX1."/**/".$formName."/**/".$controlName."/**/".$_SESSION['encSessKey']."/**/".			$session2_10Chars; // brutal attempt fixing may 2023 sastry
			return openssl_encrypt ($str, OPENSSLMETHOD, OPENSSLKEY,0,"12345678abcdefgh");	 
		}else{
			return $str;  //  If $_SESSION["ENCRYPTION"]=="OFF" simply return the same string
		}
	} 
	function xxxpw_dec($str,$err=1,$formName="",$controlName=""){
		// if($str==0) return $str;
		//  pw_dec : Decrypts the given string
		//  See configuration params $_SESSION["ENCRYPTION"]=ON/OFF
		//  OPENSSLPREFIX0 : The prefix for the encryption for additional randomness and safety
		//  OPENSSLPREFIX1 : The sufix for the encryption for additional randomness and safety
		//  OPENSSLMETHOD  : The encryption method such as "aes256" etc
		//  OPENSSLKEY     : The encryption key such as "jshj7&5%Jq" etc
		//echo $str;exit; 
		if($str!=""){
			if(isset($_REQUEST['rty']) and $_REQUEST['rty']=='directlogin')return '';
			$decStr = openssl_decrypt ($str, OPENSSLMETHOD, OPENSSLKEY,0,"12345678abcdefgh");
			if($decStr==""){
				$str = str_replace(" ","+",$str);
				$str = str_replace("%20","+",$str);
				$str = str_replace("%2F","/",$str);
				$decStr = openssl_decrypt ($str, OPENSSLMETHOD, OPENSSLKEY,0,"12345678abcdefgh");				
			}
			if($decStr=="" and ($err==0 or $str=='0')) return $str;  
			if($decStr==""){
				include_once("bq_utils_security.php");
				$agentString = getFullAgentDetailsJSON();
				$agentArr = json_decode($agentString,true);
				echo "<div style='margin:10px;border:1px solid #777;padding:5px'>Error 9289</div>";
				exit;
					//  Decryption failed through error and exit
				echo"
				<script>
					//alert('ENDE Error: 8266\\nWill be reloading...'); // reloading browser
					location.reload();
				</script>
				";
				exit;
			} 
			list($randTemp,$prefix0,$strOriginal,$prefix1,$dec_formName,$dec_controlName,$ancKey,$session2_10Chars) = explode("/**/",$decStr);
			// brutal attempt fixing may 2023 sastry
			$check2_10Chars=substr(session_id(),2,10); // brutal attempt fixing may 2023 sastry
			// decrypt does not have session 2-10 chars means session jacking.

			// cheking the original form name and dec form name must be same  
			if($dec_formName!='' && $formName!='' && $dec_formName!=$formName){
				fw_output("<script> alert('[".$formName."] = [".$dec_formName."] Data Error - FormName : 1886');</script>","script");
				exit;
			}
			// cheking the original control  name and dec control  name must be same  
			if($dec_controlName!='' && $controlName!='' && $dec_controlName!=$controlName){
				sendSecurityMail("Error: 3887, Data Error - ControlName .... ".$dec_controlName. " changed to ".$controlName. "<hr>");
				fw_output("<script> alert('Data Error - ControlName : 3887');</script>","script");
				exit;
			}
			//  Even after decrypt success, let us check the prefix and postfix data elements to ensure proper decryption
			if($prefix0==OPENSSLPREFIX0 && $prefix1==OPENSSLPREFIX1){
				return $strOriginal; //  everythinok return string
			}else {
				watchDog("Data Error : 5354","Post data error detected str='".$str."'","Error",2,__FILE__,__LINE__,__CLASS__,__METHOD__);
			}
		}else{
			return $str;  //  If $_SESSION["ENCRYPTION"]=="OFF" simply return the same string
		}
	}
	
	

/*
    pw_enc / pw_dec (BeeqU compatible, upgraded)

    Goals:
    1) Keep your CURRENT legacy format working (so old tokens still decrypt).
    2) NEW tokens become secure using AES-256-GCM (random IV + integrity tag).
    3) Bind tokens to (formName, controlName) via AAD so they can't be reused in another field.

    How to use (simple):
    - Replace your pw_enc() with pw_enc() below.
    - Replace your pw_dec() with pw_dec() below.
    - Done. Old encrypted strings (legacy openssl_encrypt with fixed IV) still work.
    - New strings returned by pw_enc() will start with "v1.".

    IMPORTANT:
    - Set APP_AES256GCM_KEY_B64 in your server environment to a base64-encoded 32-byte key.
      Example: 32 random bytes base64. (Do NOT use a short password.)

    Example usage:
      $bqkey = pw_enc("pgid=my.php&action=list", "myform", "bqkey");
      $plain = pw_dec($bqkey, 1, "myform", "bqkey");

    formName/controlName:
    - Pass them consistently. If you pass different names on decrypt, decrypt will fail for v1 tokens.
*/

// -----------------------
// base64url helpers
// -----------------------
	function b64url_enc(string $bin): string {
	    return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
	}
	function b64url_dec(string $txt): string {
	    $txt = strtr($txt, '-_', '+/');
	    $pad = strlen($txt) % 4;
	    if ($pad) $txt .= str_repeat('=', 4 - $pad);
	    $bin = base64_decode($txt, true);
	    return ($bin === false) ? '' : $bin;
	}
	
	// -----------------------
	// Get 32-byte key for GCM
	// Priority: ENV base64 key -> hash OPENSSLKEY to 32 bytes (fallback)
	// -----------------------
	function pw_get_gcm_key32(): string {
	    $k = getenv('APP_AES256GCM_KEY_B64');
	    if ($k !== false && $k !== '') {
	        $bin = base64_decode($k, true);
	        if ($bin !== false && strlen($bin) === 32) return $bin;
	    }
	    // Fallback: derive from OPENSSLKEY (not ideal but works)
	    $fallback = defined('OPENSSLKEY') ? (string)OPENSSLKEY : '';
	    if ($fallback === '') return '';
	    return hash('sha256', $fallback, true); // 32 bytes
	}
	
	// -----------------------
	// Legacy encrypt (your old format) - kept for backward compatibility
	// -----------------------
	function pw_enc_legacy(string $str, string $formName = "", string $controlName = ""): string {
	    $session2_10Chars = substr(session_id(), 2, 10);
	    if (!isset($_SESSION["ENCRYPTION"])) $_SESSION["ENCRYPTION"] = "ON";
	
	    if ($_SESSION["ENCRYPTION"] === "ON" && $str !== "") {
	        $_SESSION['encSessKey'] = random_int(111111, 999999);
	        $randTemp = "l" . random_int(2222, 9999);
	
	        $payload =
	            $randTemp . "/**/" .
	            OPENSSLPREFIX0 . "/**/" .
	            $str . "/**/" .
	            OPENSSLPREFIX1 . "/**/" .
	            $formName . "/**/" .
	            $controlName . "/**/" .
	            $_SESSION['encSessKey'] . "/**/" .
	            $session2_10Chars;
	
	        return openssl_encrypt($payload, OPENSSLMETHOD, OPENSSLKEY, 0, "12345678abcdefgh");
	    }
	    return $str;
	}
	
	function pw_dec_legacy(string $str, int $err = 1, string $formName = "", string $controlName = ""): string {
	    if ($str === '') return $str;
	
	    if (isset($_REQUEST['rty']) && $_REQUEST['rty'] === 'directlogin') return '';
	
	    $decStr = openssl_decrypt($str, OPENSSLMETHOD, OPENSSLKEY, 0, "12345678abcdefgh");
	
	    if ($decStr === "") {
	        $tmp = str_replace(" ", "+", $str);
	        $tmp = str_replace("%20", "+", $tmp);
	        $tmp = str_replace("%2F", "/", $tmp);
	        $decStr = openssl_decrypt($tmp, OPENSSLMETHOD, OPENSSLKEY, 0, "12345678abcdefgh");
	    }
	
	    if ($decStr === "" && ($err === 0 || $str === '0')) return $str;
	
	    if ($decStr === "") {
	        // Keep your existing failure behavior
	        echo "<div style='margin:10px;border:1px solid #777;padding:5px'>Error 9289</div>";
	        exit;
	    }
	
	    $parts = explode("/**/", $decStr);
	    if (count($parts) < 8) {
	        if ($err === 0) return $str;
	        echo "<div style='margin:10px;border:1px solid #777;padding:5px'>Error 9290</div>";
	        exit;
	    }
	
	    list($randTemp, $prefix0, $strOriginal, $prefix1, $dec_formName, $dec_controlName, $ancKey, $session2_10Chars) = $parts;
	
	    if ($dec_formName !== '' && $formName !== '' && $dec_formName !== $formName) {
	        echo "<script>alert('[".$formName."] = [".$dec_formName."] Data Error - FormName');</script>";
	        exit;
	    }
	    if ($dec_controlName !== '' && $controlName !== '' && $dec_controlName !== $controlName) {
	        echo "<script>alert('Data Error - ControlName');</script>";
	        exit;
	    }
	
	    if ($prefix0 === OPENSSLPREFIX0 && $prefix1 === OPENSSLPREFIX1) {
	        return $strOriginal;
	    }
	
	    // Prefix mismatch => tamper or wrong key/method
	    if ($err === 0) return $str;
	    echo "<div style='margin:10px;border:1px solid #777;padding:5px'>Error 9291</div>";
	    exit;
	}
	
	// -----------------------
	// Secure v1 encrypt/decrypt (AES-256-GCM)
	// Envelope: v1.<iv>.<tag>.<ct> (base64url)
	// AAD = formName|controlName  (bind token to its intended context)
	// -----------------------
	function pw_enc_v1(string $plain, string $formName = "", string $controlName = ""): string {
	    if ($plain === '') return $plain;
	
	    $key = pw_get_gcm_key32();
	    if ($key === '') {
	        // If key missing, fall back to legacy so system continues to work
	        return pw_enc_legacy($plain, $formName, $controlName);
	    }
	
	    $iv  = random_bytes(12); // GCM recommended
	    $aad = $formName . '|' . $controlName;
	
	    $tag = '';
	    $ct = openssl_encrypt(
	        $plain,
	        'aes-256-gcm',
	        $key,
	        OPENSSL_RAW_DATA,
	        $iv,
	        $tag,
	        $aad,
	        16
	    );
	
	    if ($ct === false || $tag === '') {
	        // Fail back to legacy
	        return pw_enc_legacy($plain, $formName, $controlName);
	    }
	
	    return 'v1.' . b64url_enc($iv) . '.' . b64url_enc($tag) . '.' . b64url_enc($ct);
	}
	
	function pw_dec_v1(string $token, int $err = 1, string $formName = "", string $controlName = ""): string {
	    $key = pw_get_gcm_key32();
	    if ($key === '') return ($err === 0) ? $token : '';
	
	    $parts = explode('.', $token, 4);
	    if (count($parts) !== 4) return ($err === 0) ? $token : '';
	
	    $iv  = b64url_dec($parts[1]);
	    $tag = b64url_dec($parts[2]);
	    $ct  = b64url_dec($parts[3]);
	
	    if ($iv === '' || $tag === '' || $ct === '') return ($err === 0) ? $token : '';
	    if (strlen($iv) !== 12 || strlen($tag) !== 16) return ($err === 0) ? $token : '';
	
	    $aad = $formName . '|' . $controlName;
	
	    $pt = openssl_decrypt(
	        $ct,
	        'aes-256-gcm',
	        $key,
	        OPENSSL_RAW_DATA,
	        $iv,
	        $tag,
	        $aad
	    );
	
	    if ($pt === false) {
	        if ($err === 0) return $token;
	        echo "<div style='margin:10px;border:1px solid #777;padding:5px'>Error 9292</div>";
	        exit;
	    }
	
	    return $pt;
	}

	// -----------------------
	// Public functions you call everywhere
	// - pw_enc now creates secure v1 tokens by default
	// - pw_dec auto-detects v1 vs legacy and decrypts accordingly
	// -----------------------
	function pw_enc(?string $str, string $formName = "", string $controlName = ""): string {
		//echo toast($str);
		if($str=="") return $str."";
	    // If encryption "OFF", behave like old logic
	    if (!isset($_SESSION["ENCRYPTION"])) $_SESSION["ENCRYPTION"] = "ON";
	    if ($_SESSION["ENCRYPTION"] !== "ON" || $str === '') return $str;
	
	    return pw_enc_v1($str, $formName, $controlName);
	}
	
	function pw_dec(string $str, int $err = 1, string $formName = "", string $controlName = ""): string {
	    if ($str === '') return $str;
	
	    // v1 secure token
	    if (strncmp($str, 'v1.', 3) === 0) {
	        return pw_dec_v1($str, $err, $formName, $controlName);
	    }
	
	    // legacy token
	    return pw_dec_legacy($str, $err, $formName, $controlName);
	}
	
	/*
	    Deployment steps:
	    1) Put these functions in your bq_engine.php (or included security file).
	    2) Set ENV var APP_AES256GCM_KEY_B64 on server:
	         - 32 random bytes, base64 encoded.
	       Example generation (Linux):
	         php -r 'echo base64_encode(random_bytes(32)), PHP_EOL;'
	       Put result into environment securely (nginx/apache/systemd/docker secret).
	    3) Keep OPENSSLMETHOD/OPENSSLKEY/OPENSSLPREFIX* unchanged (legacy still needs them).
	    4) Start using pw_enc/pw_dec as usual. Old tokens continue to work.
	
	    Notes:
	    - For URLs: v1 tokens are URL-safe already (base64url). Legacy tokens may still need urlencode if used in querystring.
	    - For best security, always pass correct $formName/$controlName on both enc & dec.
	*/




	setCSS();
	
	//setCSS();
	function setCSS(){
		echo '<!-- Bootstrap CSS & Icons -->
		        <meta name="viewport" content="width=device-width, initial-scale=1">
		        <!-- HTMX (optional demo endpoints at bottom) -->
		        <script src="../res/bqv1/js/htmx1.9.12.js" defer></script>
		        <link href="../res/bqv1/css/bootstrap5.3.8.css" rel="stylesheet">';

	}
	
	function checkInclude($file){
		$_GET['head'] = $_GET['head'] ?? '';
		$_GET['rty'] = $_GET['rty'] ?? '';
		if(isFoundIn($file,".php")){
			if(file_exists($file)){
				$_SESSION['include']="Engine".date("H:i:s");
				if($_GET['rty'] != 'download' and $_GET['head']!='nocssjs')echo debugtip();
				include_once($file);
			}else{
				echo displayError("File not found!! - 7266","File \"".$file. "\" not found (Err 8399) !!");
				exit;
			}
		}
		return;
	}
	function getRequest(){
			
		if(isset($_GET) and isset($_GET['bqkey']) and $_GET['bqkey']!=""){
			
			// security so changed to new
			// $bqparam=pw_dec($_GET['bqkey']);
			// $arr=explode("&",$bqparam);
			// foreach($arr as $a){
			// 	$temp=explode("=",$a);
			// 	$_GET[$temp[0]]=$temp[1];
			// }
			
// new security change - gpt
	$bqparam = pw_dec($_GET['bqkey']);
	
	$out = [];
	parse_str($bqparam, $out);
	
	if (!is_array($out) || !$out) {
	    bq_security_fail("Invalid bqkey payload");
	    exit;
	}
	
	
	// commented for gpt advise
	// foreach ($out as $k => $v) {
	
	//     // reject arrays like a[]=1 or nested params
	//     if (is_array($v)) continue;
	
	//     // allow only simple keys (prevents weird keys like a[b], or injection tricks)
	//     if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]{0,63}$/', (string)$k)) continue;
	
	//     // limit value size
	//     $v = (string)$v;
	//     if (strlen($v) > 4000) continue;
	
	//     // reject null bytes
	//     if (strpos($v, "\0") !== false) continue;
	
	//     $_GET[$k] = $v;
	// }
	
	//gpt advise
	foreach ($out as $k => $v) {
	
	    // NEVER allow decoded payload to override control keys
	    if ($k === 'bqkey') continue;
	    if ($k === 'direct') continue;
	
	    // reject arrays like a[]=1 or nested params
	    if (is_array($v)) continue;
	
	    // allow only simple keys (prevents weird keys like a[b], or injection tricks)
	    if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]{0,63}$/', (string)$k)) continue;
	
	    // limit value size
	    $v = (string)$v;
	    if (strlen($v) > 4000) continue;
	
	    // reject null bytes
	    if (strpos($v, "\0") !== false) continue;
	
	    $_GET[$k] = $v;
	}
	//gpt advise end

/// gpt advise done
			
			// security check against direct url hit from browser
			// for direct landing set direct=yes in request
			if(!isset($_GET['direct'])) $_GET['direct']="";
			if(strtoupper($_GET['direct'])!="YES"){
				if(isset($_GET['bqkey'])){
					$ref = $_SERVER['HTTP_REFERER'] ?? '';
					$host = $_SERVER['HTTP_HOST'] ?? '';
					if ($ref) {
					    // Example: allow only same-domain or subdomain navigation
					    // gpt advise
					    // if (strpos($ref, $host) === false) {
					    //     bq_security_fail("Invalid sourcereferrer: $ref");
					    //     exit;
					    // }
					    
					    //gpt advise
						$refHost  = strtolower(parse_url($ref, PHP_URL_HOST) ?? '');
						$selfHost = strtolower(parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST) ?? '');
						
						if ($refHost === '' || $selfHost === '' || $refHost !== $selfHost) {
						    bq_security_fail("Invalid sourcereferrer: $ref");
						    exit;
						}
						// gpt advise end

					} else {
					    // No referrer → block only direct top-level hits
					    $is_htmx = !empty($_SERVER['HTTP_HX_REQUEST']);
					    if (!$is_htmx) {
					        bq_security_fail("Direct navigation.. prohibited");
					        // bq_security_fail
					        exit;
					    }
					}
				} // end // security check against direct url hit from browser
			}
			
			if (!isset($_GET['pw']) || $_GET['pw'] === '') {
				bq_security_fail("Missing pw");
				exit;
			}
			
			if (!preg_match('/^[a-zA-Z0-9_\-]+\.php$/', $_GET['pw'])) {
				bq_security_fail("Invalid pw");
				exit;
			}
			checkInclude($_GET['pw']);
		}
		
		
	}
	function checkXref($k,$v){
		if($k!=pw_dec($v)){
			echo displayError("Error","Data Tampered BQK 7070");
			exit;
		}
		else{
		}
		
	}	
 	function dovarError(){
		// dovarError : Error traping for any direct call attempt
		// Displays the error, Page etc ***  Later to be removed or mailed or written to log file
		fw_output(displayError("Direct call attempt denied..", pw_dec($_SERVER['QUERY_STRING'])));
		exit;
	}
	function PW_connect(){ //$engine="",$server="",$uid="",$pwd="",$db=''
		$engine="mysql";
		$server="10.1.2.6";
		$uid="UID";
		$pwd="XXXXX";
		$db="DB";
		$_SESSION['engine'] = strtolower($engine);
		$conn = mysqli_connect($server,$uid,$pwd,$db);
		return $conn;
	} 
	function xxxmysqlOverwrite(&$sql){
		$sql = trim($sql) ;
		if($sql=='') return "";
	    $command=strtoupper(strtok(trim($sql), " "));
		if(isFoundIn("xx/SHOW COLUMNS/UNLOCK/LOCK/SELECT/UPDATE/INSERT/DROP/DELETE/TRUNCATE/SHOW/ALTER/CREATE/OPTIMIZE/EXPLAIN/DESCRIBE/RENAME/KILL/","/".$command."/"))  {
		    displayError("SQL Overwrite","Direct command used...".$command,1);
		}else{
		    if(!isFoundIn("xx/SHOCOL/UNLREC/LOCREC/(SELREC/SELREC/UPDREC/INSREC/DROREC/DELREC/TRUREC/SHOREC/ALTTAB/CRETAB/CREIND/DROTAB/OPTTAB/EXPTAB/DESTAB/SHOTAB/SHOCRT/RENTAB/SHODAB/KILPBQ/","/".$command."/"))displayError($sql."Prohibited Command 5644","Only limited commands are permitted. Found : ".$command,1);
		    if($command=="SHOCOL") $sql="SHOW COLUMNS ".	substr($sql,strlen($command));
		    if($command=="SELREC") $sql="SELECT ".	substr($sql,strlen($command));
		    if($command=="(SELREC")$sql="(SELECT ".	substr($sql,strlen($command));
		    if($command=="DELREC") $sql="DELETE ".	substr($sql,strlen($command));
		    if($command=="UPDREC") $sql="UPDATE ".	substr($sql,strlen($command));
		    if($command=="INSREC") $sql="INSERT ".	substr($sql,strlen($command));
 		    if($command=="TRUREC") $sql="TRUNCATE ".substr($sql,strlen($command));
		    if($command=="SHOREC") $sql="SHOW ".	substr($sql,strlen($command));
		    if($command=="SHOTAB") $sql="SHOW TABLES".substr($sql,strlen($command));
		    if($command=="SHODAB") $sql="SHOW DATABASES".substr($sql,strlen($command));
		    if($command=="SHOCRT") $sql="SHOW CREATE TABLE ".substr($sql,strlen($command));
		    if($command=="ALTTAB") $sql="ALTER ".	substr($sql,strlen($command));
		    if($command=="CRETAB") $sql="CREATE ".	substr($sql,strlen($command));
		    if($command=="CREIND") $sql="CREATE ".	substr($sql,strlen($command));
		    if($command=="DROTAB") $sql="DROP ".	substr($sql,strlen($command));
		    if($command=="OPTTAB") $sql="OPTIMIZE ".substr($sql,strlen($command));
		    if($command=="DESTAB") $sql="DESCRIBE ".substr($sql,strlen($command));
		    if($command=="EXPTAB") $sql="EXPLAIN ".	substr($sql,strlen($command));
		    if($command=="RENTAB") $sql="RENAME ".	substr($sql,strlen($command));
		    if($command=="LOCREC") $sql="LOCK ".	substr($sql,strlen($command));
		    if($command=="UNLREC") $sql="UNLOCK ".	substr($sql,strlen($command));
		    if($command=="ANATAB") $sql="ANALYZE TABLE ".substr($sql,strlen($command));
		    if($command=="KILPBQ") $sql="KILL ".	substr($sql,strlen($command));
		}
		return;
	}
function mysqlOverwrite(&$sql) {
    // Step 1: Basic validation
    $sql = trim($sql);
    if ($sql === '') {
        return "";
    }
    
    // Step 2: Extract the command (handles spaces, tabs, newlines, parenthesis)
    if (!preg_match('/^(\()?([A-Z]+)/i', $sql, $matches)) {
        logSQLWarning("Invalid SQL format", $sql);
        return; // Log but allow to proceed
    }
    
    $hasParenthesis = !empty($matches[1]);
    $command = strtoupper($matches[2]);
    
    // Step 3: Define what's allowed (whitelist approach)
    $allowedCommands = [
        // Read operations
        'SELREC' => 'SELECT',
        'SHOREC' => 'SHOW',
        'SHOCOL' => 'SHOW COLUMNS',
        'SHOTAB' => 'SHOW TABLES',
        'SHODAB' => 'SHOW DATABASES',
        'SHOCRT' => 'SHOW CREATE TABLE',
        'DESTAB' => 'DESCRIBE',
        'EXPTAB' => 'EXPLAIN',
        
        // Write operations
        'INSREC' => 'INSERT',
        'UPDREC' => 'UPDATE',
        'DELREC' => 'DELETE',
        'TRUREC' => 'TRUNCATE',
        
        // Table operations
        'ALTTAB' => 'ALTER TABLE',
        'CRETAB' => 'CREATE TABLE',
        'CREIND' => 'CREATE INDEX',
        'DROTAB' => 'DROP TABLE',
        'OPTTAB' => 'OPTIMIZE TABLE',
        'ANATAB' => 'ANALYZE TABLE',
        'RENTAB' => 'RENAME TABLE',
        
        // Lock operations
        'LOCREC' => 'LOCK TABLES',
        'UNLREC' => 'UNLOCK TABLES',
        
        // Process operations
        'KILPBQ' => 'KILL'
    ];
    
    // Step 4: Block direct SQL commands (force custom commands)
    $blockedDirectCommands = [
        'SELECT', 'INSERT', 'UPDATE', 'DELETE', 'DROP', 'TRUNCATE',
        'ALTER', 'CREATE', 'SHOW', 'DESCRIBE', 'EXPLAIN', 'RENAME',
        'LOCK', 'UNLOCK', 'KILL', 'OPTIMIZE', 'ANALYZE', 'GRANT',
        'REVOKE', 'UNION', 'EXEC', 'EXECUTE', 'REPLACE'
    ];
    
    if (in_array($command, $blockedDirectCommands, true)) {
        logSQLWarning("Direct SQL command not allowed: " . $command, $sql);
        return; // Log and stop processing
    }
    
    // Step 5: Check if cogustom command is allowed
    if (!isset($allowedCommands[$command])) {
        logSQLWarning("Command not permitted: " . $command, $sql);
        return; // Log and stop processing
    }
    
    // Step 6: Block dangerous SQL keywords ANYWHERE in the entire query
    $dangerousPatterns = [
        '/\bDROP\s+(TABLE|DATABASE|INDEX)/i',
        '/\bTRUNCATE\b/i',
        '/\bALTER\s+TABLE\b/i',
        '/\bCREATE\s+(TABLE|DATABASE|INDEX)/i',
        '/\bRENAME\s+TABLE\b/i',
        '/\bUNION\s+(ALL\s+)?SELECT\b/i',
        '/\bINTO\s+(OUT|DUMP)FILE\b/i',
        '/\bLOAD\s+DATA\b/i',
        '/\bLOAD_FILE\s*\(/i',
        '/\bEXEC\b/i',
        '/\bEXECUTE\b/i',
        '/\bSLEEP\s*\(/i',
        '/\bBENCHMARK\s*\(/i',
        '/--[^\n]*$/m',           // SQL line comments
        '/\/\*.*?\*\//s',         // SQL block comments
        '/;\s*\w+/i'              // Semicolon followed by any word (multi-statement)
    ];
    
    // Check patterns BEFORE command substitution to catch custom commands in dangerous positions
    foreach ($dangerousPatterns as $pattern) {
        if (preg_match($pattern, $sql)) {
            logSQLWarning("Dangerous SQL pattern detected", $sql, "Pattern: " . $pattern);
            return; // Log and stop processing
        }
    }

    // Step 7: Replace custom command with real SQL command
    $realCommand = $allowedCommands[$command];
    $commandLength = strlen($command);
    $startPosition = $hasParenthesis ? $commandLength + 1 : $commandLength;
    $remainingSQL = substr($sql, $startPosition);
    
    // Reconstruct the SQL
    $sql = ($hasParenthesis ? '(' : '') . $realCommand . $remainingSQL;
    
    // Step 8: Validate reconstructed SQL doesn't contain dangerous patterns
    // This catches cases where dangerous keywords might be in table/column names
    $postSubstitutionPatterns = [
        '/\bUNION\s+(ALL\s+)?SELECT\b/i',
        '/\bINTO\s+(OUT|DUMP)FILE\b/i',
        '/\bLOAD_FILE\s*\(/i',
        '/\bSLEEP\s*\(/i',
        '/\bBENCHMARK\s*\(/i'
    ];
    
    foreach ($postSubstitutionPatterns as $pattern) {
        if (preg_match($pattern, $sql)) {
            logSQLWarning("Dangerous pattern in reconstructed SQL", $sql, "Pattern: " . $pattern);
            // Log but allow execution (might be legitimate column/table names)
        }
    }
    
    // Step 9: Warn if not using prepared statements (but allow execution)
    $placeholderCount = substr_count($sql, '?');
    if ($placeholderCount === 0 && preg_match('/WHERE|SET/i', $sql)) {
        logSQLWarning("Query not using prepared statement placeholders", $sql, "Missing ? in WHERE/SET clause");
        // Log warning but continue execution
    }
    
    return;
}

// Centralized SQL warning logger
function logSQLWarning($warningType, $sql, $additionalInfo = '') {
    // Get backtrace to find calling file
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
    $callingFile = 'Unknown';
    $callingLine = 'Unknown';
    $callingFunction = 'Unknown';
    
    // Find the first file that's not this utilities file
    foreach ($backtrace as $trace) {
        if (isset($trace['file']) && 
            basename($trace['file']) !== basename(__FILE__) &&
            !in_array($trace['function'] ?? '', ['mysqlOverwrite', 'logSQLWarning'])) {
            $callingFile = $trace['file'];
            $callingLine = $trace['line'] ?? 'Unknown';
            $callingFunction = $trace['function'] ?? 'Unknown';
            break;
        }
    }
    
    // Prepare log entry
    $logEntry = str_repeat('=', 100) . "\n";
    $logEntry .= "SQL VALIDATION WARNING\n";
    $logEntry .= str_repeat('=', 100) . "\n";
    $logEntry .= "Timestamp        : " . date('Y-m-d H:i:s') . "\n";
    $logEntry .= "Warning Type     : " . $warningType . "\n";
    $logEntry .= "PHP File         : " . $callingFile . "\n";
    $logEntry .= "Line Number      : " . $callingLine . "\n";
    $logEntry .= "SQL              : " . $sql."\n";
    $logEntry .= "Function/Method  : " . $callingFunction . "\n";
    $logEntry .= "User ID          : " . ($_SESSION['userid'] ?? 'Guest') . "\n";
    $logEntry .= "User Name        : " . ($_SESSION['username'] ?? 'Guest') . "\n";
    $logEntry .= "IP Address       : " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "\n";
    $logEntry .= "Request URI      : " . ($_SERVER['REQUEST_URI'] ?? 'Unknown') . "\n";
    $logEntry .= "Request Method   : " . ($_SERVER['REQUEST_METHOD'] ?? 'Unknown') . "\n";
    
    if ($additionalInfo) {
        $logEntry .= "Additional Info  : " . $additionalInfo . "\n";
    }
    
    $logEntry .= str_repeat('-', 100) . "\n";
    $logEntry .= "SQL Query:\n" . $sql . "\n";
    $logEntry .= str_repeat('=', 100) . "\n\n";
    
    // Define log directory and file
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    // Daily rotation - separate file per day
    $logFile = $logDir . '/sql_warnings_' . date('Y-m-d') . '.log';
    
    // Write to log file (append mode with file locking)
    @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    // Optional: Keep only last 30 days of logs
    cleanOldLogs($logDir, 30);
}

// Clean old log files
function cleanOldLogs($logDir, $daysToKeep = 30) {
    static $lastCleanup = 0;
    
    // Only run cleanup once per day
    if (time() - $lastCleanup < 86400) {
        return;
    }
    
    $lastCleanup = time();
    $cutoffTime = time() - ($daysToKeep * 86400);
    
    if (is_dir($logDir)) {
        $files = glob($logDir . '/sql_warnings_*.log');
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                @unlink($file);
            }
        }
    }
}
// Logging function for non-prepared statement warnings
function logNonPreparedStatement($sql) {
    // Get backtrace to find calling file
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
    $callingFile = 'Unknown';
    $callingLine = 'Unknown';
    $callingFunction = 'Unknown';
    
    // Find the first file that's not this file
    foreach ($backtrace as $trace) {
        if (isset($trace['file']) && basename($trace['file']) !== basename(__FILE__)) {
            $callingFile = $trace['file'];
            $callingLine = $trace['line'] ?? 'Unknown';
            $callingFunction = $trace['function'] ?? 'Unknown';
            break;
        }
    }
    
    // Prepare log entry
    $logEntry = str_repeat('=', 100) . "\n";
    $logEntry .= "NON-PREPARED STATEMENT WARNING\n";
    $logEntry .= str_repeat('=', 100) . "\n";
    $logEntry .= "Timestamp        : " . date('Y-m-d H:i:s') . "\n";
    $logEntry .= "PHP File         : " . $callingFile . "\n";
    $logEntry .= "Line Number      : " . $callingLine . "\n";
    $logEntry .= "Function/Method  : " . $callingFunction . "\n";
    $logEntry .= "User ID          : " . ($_SESSION['userid'] ?? 'Unknown') . "\n";
    $logEntry .= "IP Address       : " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "\n";
    $logEntry .= "Request URI      : " . ($_SERVER['REQUEST_URI'] ?? 'Unknown') . "\n";
    $logEntry .= str_repeat('-', 100) . "\n";
    $logEntry .= "SQL Query (Missing Prepared Statement Placeholders):\n";
    $logEntry .= $sql . "\n";
    $logEntry .= str_repeat('=', 100) . "\n\n";
    
    // Define log file path (daily rotation)
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/non_prepared_statements_' . date('Y-m-d') . '.log';
    
    // Write to log file (append mode)
    @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}
/*
====================================================================================================
NON-PREPARED STATEMENT WARNING
====================================================================================================
Timestamp        : 2026-02-13 15:45:30
PHP File         : /var/www/html/admin/users.php
Line Number      : 234
Function/Method  : getUsersList
User ID          : admin
IP Address       : 192.168.1.50
Request URI      : /admin/users.php?action=list
----------------------------------------------------------------------------------------------------
SQL Query (Missing Prepared Statement Placeholders):
SELECT * FROM users WHERE status=1 AND role='admin'
====================================================================================================
*/
	function PW_sql2rsPS($sql,$formats="",$val1="",$val2="",$val3="",$val4="",$val5=""){
		
	    mysqlOverwrite($sql);  
	    
	    // echo toast($sql);
		$con  = $_SESSION['conn'];
		$stmt = $con->prepare( $sql );
		$BindError = 0;
		if($stmt){
	    // Store values in an array for debugging
	    	$params = [];			
			if(strlen($formats)==1){
				$stmt->bind_param($formats, $val1);
		        $params = [$val1];
			}elseif(strlen($formats)==2){
				$stmt->bind_param($formats, $val1,$val2);
				$params = [$val1, $val2];
			}elseif(strlen($formats)==3){
				$stmt->bind_param($formats, $val1,$val2,$val3);
				$params = [$val1, $val2, $val3];
			}elseif(strlen($formats)==4){
				$stmt->bind_param($formats, $val1,$val2,$val3,$val4);
				$params = [$val1, $val2, $val3, $val4];
			}elseif(strlen($formats)==5){
				$stmt->bind_param($formats, $val1,$val2,$val3,$val4,$val5);
				$params = [$val1, $val2, $val3, $val4, $val5];
			}
		}else{
			$BindError = 1;
			$err = $con->error;
		}
		$errsql="";
		if(isset($_SESSION['designmode']) && $_SESSION['designmode']=="on") $errsql="<hr>(design mode only) SQL:".$sql."<hr>";  // show sql in design mode only
		if($BindError==1 and $_SESSION['designmode']=="on") showInvalidOperation(2432,"S2RS:Invalid Operation<br>",$err.$sql);
		$stmt->execute(); 
		if($stmt->error){
			$err = $stmt->error;
		    $errsql="";
		    if($_SESSION['designmode']=="on")$errsql="<hr>(design mode only) SQL:".$sql."<hr>";  
			showInvalidOperation(244,"S2RS:Invalid Operation",$err.$errsql);
		}	
		$rs = $stmt->get_result();
		return $rs;
	}
	function getValueForPS($sql,$formats="",$val1="",$val2="",$val3="",$val4="",$val5=""){
		//  getValueForPS : Returns the value output of a SQL :: single row
		//  In case SQL has more than one field, an array is returned
		//  Use List command for cutting this array into variables.
		//  Currently it can handle 5 variables as ? points
		//  Example $getvalue=getValueForPS("select 1",$formats="")
		//-----------------------
		
		mysqlOverwrite($sql); //  SQL Command are overwritten checking
		//echo toast($sql);exit;
		$connPS = $_SESSION['conn'];
		// echo "---".$sql."====";  // check point
		$stmt   = $connPS->prepare($sql);
		$BindError = 0;
		if($stmt){
			if(strlen($formats)==1){
				$stmt->bind_param($formats, $val1);
			}elseif(strlen($formats)==2){
				$stmt->bind_param($formats, $val1,$val2);
			}elseif(strlen($formats)==3){
				$stmt->bind_param($formats, $val1,$val2,$val3);
			}elseif(strlen($formats)==4){
				$stmt->bind_param($formats, $val1,$val2,$val3,$val4);
			}elseif(strlen($formats)==5){
				$stmt->bind_param($formats, $val1,$val2,$val3,$val4,$val5);
			}
		}else{
			$BindError = 1;
			$err = $connPS->error;
		}
		$errsql="";
		$_SESSION['designmode']=$_SESSION['designmode'] ?? "";
		if($_SESSION['designmode']=="on")$errsql="<hr>(design mode only) SQL:".$sql."<hr>";  // show sql in design mode only
		if($BindError==1) showInvalidOperation(2439,"GVPSP:Invalid Operation<br>".$errsql,$err);
		$stmt->execute();
		if($stmt->error){
			$err = $stmt->error;
			showInvalidOperation(244,"GVPS:Invalid Operation",$err);
		}	
		//$stmt->store_result(); commented for php 8.2
/*		$Result = get_resultPS($stmt);
		$temp   = @$Result[$stmt->num_rows-1];
		$recs	= @count($temp);
		if($recs==1){
			$k = key($temp);
			return $temp[$k];
		}*/
		$Result = get_resultPS($stmt);
		$temp   = $Result[$stmt->num_rows-1] ?? [];
		$recs   = count($temp);
		if ($recs == 1) {
		    $k = key($temp);
		    return $temp[$k];
		}		
		return $temp;
	}
	function PW_fetchArray($rs){
		$ds = mysqli_fetch_array($rs);
		return $ds;
	}
	function PW_fetchAssoc($rs){
 		$ds = mysqli_fetch_assoc($rs);
		return $ds;
	}
	function PW_fetch_field ( $rs,$i){
		//  PW_fetch_field : Returns an array of attributes of the field offset
		//  $rs : Result set
		$arr=mysqli_fetch_field_direct ($rs,$i);
		return $arr;
	}
	function PW_field_name($rs,$i){
		//  Return field name from input result set , and field  using 'mysqli_fetch_field_direct'
		$fldname = mysqli_fetch_field_direct($rs,$i);
		return $fldname->name;	
	}
	function PW_field_length($rs,$i){
		// Return field length from input result set , and field  using 'mysqli_fetch_field_direct'
		$fldname = mysqli_fetch_field_direct($rs,$i);
		if($fldname->type!=253)return $fldname->length;
		$length = getValueForPS("selrec CHARACTER_MAXIMUM_LENGTH from information_schema.COLUMNS WHERE TABLE_SCHEMA =?  AND TABLE_NAME = ? AND COLUMN_NAME =?","sss",DB_NAME,$fldname->table,$fldname->name);
		//return $fldname->length;
		return $length;
	}
	function PW_free_result($rs){
		//  PW_free_result : Frees the result set
		mysqli_free_result($rs);
	}
    function PW_fieldTypeName($typeNo) {
        static $map = [
            0   => 'DECIMAL',
            1   => 'TINYINT',
            2   => 'SMALLINT',
            3   => 'INT',
            4   => 'FLOAT',
            5   => 'DOUBLE',
            6   => 'NULL',
            7   => 'TIMESTAMP',
            8   => 'BIGINT',
            9   => 'MEDIUMINT',
            10  => 'DATE',
            11  => 'TIME',
            12  => 'DATETIME',
            13  => 'YEAR',
            16  => 'BIT',
            245 => 'JSON',
            246 => 'DECIMAL',
            247 => 'ENUM',
            248 => 'SET',
            249 => 'TINYBLOB',
            250 => 'MEDIUMBLOB',
            251 => 'LONGBLOB',
            252 => 'BLOB/TEXT',
            253 => 'VARCHAR',
            254 => 'CHAR',
            255 => 'GEOMETRY',
        ];
        return $map[$typeNo] ?? "UNKNOWN";
    }

	 function checkCriticalTableValidation($table_name){ // Added on (01-09-2018)
		// Not allowing the user to create pageforms for the below framework tables...
		// Only the users having Admin and Sadmin role will create forms for this framework tables.
		return true ;// suspended	
	 	if(!isFoundIn("~".strtoupper($_SESSION['user_role'])."~","~ADMIN~") and !isFoundIn("~".strtoupper($_SESSION['user_role'])."~","~SADMIN~") and !isFoundIn("~".strtoupper($_SESSION['user_role'])."~","~LOCALADMN~") and !isFoundIn("~".strtoupper($_SESSION['user_role'])."~","~EXCEL UPLOAD~")){
	 		$criticalTables = str_replace(",","~",$_SESSION['PW_CONSTANTS']['Critical Framework Tables']);
	 		// checking the critical tables with the table name passed to validate the critical validation
			if(isFoundIn("~".$criticalTables."~","~".$table_name."~")){
				fw_output(displayError("Action Denied! ".$table_name." 8988<br> Please Contact Administrator!"));
	 			exit;
	 		}
	 	}
		
	 }

	function PW_fetch_field_align ($fieldtype){
		//  PW_fetch_field : Returns an array of attributes of the field offset
		//  $rs : Result set
		$arr=mysqli_fetch_field_direct ($rs,$i);
		return $arr;
	}
	function PW_field_type($rs,$i){
		// PW_field_type : Returns the type of the specified field in a result
		$fldname = mysqli_fetch_field_direct($rs,$i);
		//$fldtyp  = PW_field_type_number($fldname->type);
		$fldtyp  = PW_fieldTypeName($fldname->type);
		return $fldtyp;
	}
	function insertRecord($array,$table_name,$audit=1){
		checkCriticalTableValidation($table_name);
		// Assigning the generate auto ID 
		$array['id'] = getAutoId($table_name);

		//  If the context have multiple companies and porting data with companycode and companyname. (Eg: arvind context have 2 companies ), So added following conditions.
		$array['companycode'] = $array['companycode'] ?? "";
		if(isset($array['companycode']) && $array['companycode']=='')	@$array['companycode']   = $_SESSION['companycode']??'';
		
		$array['company'] = $array['company'] ?? "";
		if(isset($array['company']) and $array['company']=='')		@$array['company']   = $_SESSION['company'];
		
		$array['createdby'] = $array['createdby'] ?? "";
		$array['updatedby'] = $array['updatedby'] ?? "";
		if($array['createdby']=="") 	$array['createdby'] = $_SESSION['userid']?? "";
		if($array['updatedby']=="") 	$array['updatedby'] = $_SESSION['userid']?? "";
		$array['createdat'] 			= date('Y-m-d H:i:s');
		$array['updatedat'] 			= date('Y-m-d H:i:s');
		$array['tenent'] 				= TENENT;
		// if($_SESSION['TESTPOINT']==1)$array['updatedby'] = "Test Data"; 
		//  Random key used for Is a Mail Form --> To restrict from the resubmission
		$_REQUEST['rkey'] = $_REQUEST['rkey'] ?? "";
		if($_REQUEST['rkey']=='')$rkey = getRandomStr(8);//rand('10000000','99999999');//For random key
		else $rkey = $_REQUEST['rkey'];
		$array['rkey'] = $rkey;
		$mysqli = $_SESSION['conn'];
		$placeholders = array_fill(0, count($array), '?');
		$keys   = array(); 
		$values = array();
		foreach($array as $k => $v) {
			$keys[] = $k;
			$values[] = !empty($v) ? trim($v."") : null;
		}
		$query = "insert into $table_name ".
				'('.implode(', ', $keys).') values '.
				'('.implode(', ', $placeholders).'); '; 
		$stmt = $mysqli->prepare($query);
        if($stmt){  //  valid statement
			$params = array();
			foreach ($array as &$value){ 
			  $params[] = &$value;
			}
			$types  = array(str_repeat('s', count($params))); 
			$values = array_merge($types, $params); 
			call_user_func_array(array($stmt, 'bind_param'), $values); 
			if($stmt->execute()){
				$_SESSION['lastinsertedid'] = $array['id'];
				$_SESSION['lastinsertedtable'] = $table_name;
				$_SESSION['lastinserted'][$table_name]=$array['id'];
				$recordDS = getValueForPS("selrec * from ".$table_name." where id=?","s",$array['id']);
			
			}else{ 
				$errmsg = $stmt->error;
				// display error message
				show_formErrors(str_replace("'","",$errmsg));
				fw_output("<script>showObject('aeform');</script>","script");
				exit;
			}
		}else{  //  statement error
			$errmsg = $mysqli->error;
			// display error message
			show_formErrors(str_replace("'","",$errmsg));
			fw_output("<script>showObject('aeform');</script>","script");
			exit;
		}
	}
	function insertID($array,$table_name,&$id){ 
		// Descrption : insert : inserts the data into the provided table of a given array with key and value pair using prepare statement
		// $array : array : This will be the pair of key and value the key is table field name and value is a field value
		// $table_name : string : The table name is for inserting data of given array  

		//checkCriticalTableValidation
		// Not allowing the user to create pageforms for the below framework tables...
		// Only the users having Admin and Sadmin role will create forms for this framework tables.
		checkCriticalTableValidation($table_name);
		// generate auto ID
		$id = getAutoId($table_name);
 
		$array['id'] = $id;
		//  If the context have multiple companies and porting data with companycode and companyname. (Eg: arvind context have 2 companies ), So added following conditions.
		$array['companycode'] = $array['companycode'] ?? "";
		if($array['companycode']=='')	$array['companycode']   = $_SESSION['companycode'];
		
		$array['company'] = $array['company'] ?? "";
		if(isset($array['company']) and $array['company']=='')		@$array['company']   = $_SESSION['company'];
		$array['createdby'] = $array['createdby'] ?? "";
		$array['updatedby'] = $array['updatedby'] ?? "";
		$array['createdby'] = $_SESSION['userid'];
		$array['updatedby'] = $_SESSION['userid'];
		$array['createdat'] = date('Y-m-d H:i:s');
		$array['updatedat'] = date('Y-m-d H:i:s');
		$array['tenent'] 	= TENENT;

		$mysqli = $_SESSION['conn'];
		$placeholders = array_fill(0, count($array), '?');
		$keys   = array(); 
		$values = array();
		foreach($array as $k => $v) {
			$keys[] = $k;
			$values[] = !empty($v) ? trim($v) : null;
		}
		$query = "insert into $table_name ".
				'('.implode(', ', $keys).') values '.
				'('.implode(', ', $placeholders).'); '; 
		$stmt = $mysqli->prepare($query);

		if($stmt){  //  valid statement
			$params = array(); 
			foreach ($array as &$value) { 
			  $params[] = &$value;
			}
			$types  = array(str_repeat('s', count($params))); 
			$values = array_merge($types, $params); 
			call_user_func_array(array($stmt, 'bind_param'), $values); 
			if($stmt->execute()){
				$_SESSION['lastinsertedid'] = $array['id'];
				$_SESSION['lastinsertedtable'] = $table_name;
			}else{ 
				$errmsg=$stmt->error;
				if($_SESSION['designmode']=="on"){
					fw_output("Ps-In: 7888/1 (".$mysqli->error."<br>".$query);
				}
				fw_output(displayError("Error : Insert","Duplicate record",1)); 
			} 
		}else{  //  statement error
				if($_SESSION['designmode']=="on"){
					fw_output("Ps-In: 7888/1 (".$mysqli->error."<br>".$query);
				}else{
					fw_output("Ps-In: 7888/2 ");

				}
		}
	}
	function updateRecord($array,$table_name,$audit=1){ 
		
 		// Descrption : update : updates the data into the provided table of a given array with key and value pair using prepare statement
		// $array : array : This will be the pair of key and value the key is table field name and value is a field value
		// $table_name : string : The table name is for updating data of given array  
		// $audit : string : Default '1' for data inserting in audit,  '0' for not inserting

		//checkCriticalTableValidation
		// Not allowing the user to create pageforms for the below framework tables...
		// Only the users having Admin and Sadmin role will create forms for this framework tables.
		if(count($array)<=1){
			return;// if there is no set of data except id then return there is no action need to taken // 28-11-2022
		}
		// checkCriticalTableValidation($table_name);
		$roldDS = getValueForPS("selrec * from ".$table_name."  where id=? and tenent=?","ss",$array['id'],TENENT);
		$array['updatedby'] = $_SESSION['userid']??null;
		$array['updatedat'] = date('Y-m-d H:i:s');

		$mysqli = $_SESSION['conn'];
		$keys   = array(); 
		$values = array();
		$placeholders="";
		foreach($array as $k => $v) {
			if($k!="id"){
				$keys[] = $k;
				$values[] = !empty($v) ? trim($v) : null;
				$placeholders .= $k."=?, "; 
				if($v!='' && ($roldDS[$k]=='')){
					$roldDS[$k] = ' ';
				}	
			}
		}
		$query = "update ".$table_name." set ".removeLastNchars($placeholders,2). " where id=?"; //  remove last coma.
		$stmt = $mysqli->prepare($query);
		if($stmt){  //  valid statement
			$params = array(); 
			foreach ($array as $k=>&$value) { 
				if($k!="id") $params[] = &$value;
			}
			$params[] = &$array['id'];
			$types  = array(str_repeat('s', count($params))); 
			$values = array_merge($types, $params); 
			call_user_func_array(array($stmt, 'bind_param'), $values); 
			if($stmt->execute()){ 
				$_SESSION['lastupdatedid'] = $array['id'];
				$_SESSION['lastupdatedtable'] = $table_name;
				$_SESSION['lastupdated'][$table_name]=$array['id'];
				$recordDS = getValueForPS("selrec * from ".$table_name." where id=?","s",$array['id']);

			}else{
				$errmsg = $stmt->error;
				show_formErrors(str_replace("'","","Error : Update<br>".$errmsg));
				fw_output("<script>showObject('aeform');</script>","script");
				exit;
			} 
		
		}else{  //  statement error
			$errmsg = $mysqli->error;
			// show_formErrors function is called to display any errors
			show_formErrors(str_replace("'","",$errmsg));
			fw_output("<script>showObject('aeform');</script>","script");
			exit;
		}
		$rnewDS = getValueForPS("selrec * from ".$table_name."  where id=?  and tenent=?","ss",$array['id'],TENENT);
		// Finding the difference between Old Data array & New Data Array
		$arr  = diffArray($roldDS,$rnewDS);
		// recordAudit function is called to record the changes in the audit table
		if($audit==1) recordAudit("Update",$arr,$table_name,$array['id']);
		$target="";

	}
	// before deleting a record it checks for child records in tasks, attachvments, child and entity child
	//  the data is checked in the BB child and entity child and tasks and attachments if any one of them is found it will be rejected and exited
	function checkDeleteChildren($table,$idx){
		$Cascadetables=getvalueforPS("selrec txt1 from _pb_lookups where looktype='Constants' and lookcode='Cascade Delete Tables'");
		$CascadeTables=explode(",",$Cascadetables);
		$HasError="";
		foreach($CascadeTables as $val=>$key){
			if(checkifdataexists($key,$table,$idx)){
				$HasError .="<li>".$key."</li>";
			}
		}
		if($HasError!=''){
			return "Data found in ".$HasError." hence can not be deleted !!";
			exit;
		}
	}
	
	// for a given table it checks if the parent id record exists... used in checkDeleteChildren
	function checkifdataexists($childtable,$parenttable,$id){
		$sql="selrec * from ".$childtable." where linkedid=? and linkedto=? limit 0,1";
		$ds=getValueForPS($sql,"ss",$id,$parenttable);
		if(isset($ds['id'])!=""){
			return true;
		}else{
			return false;
		}
	}
	
	function PW_execute($sql,$bypassError=0){  
		//  PW_execute :  executes an sql.  user for insert...update and delete only
		//  $sql: sql statement.
		//  Incase of error
		//Sudhakar added 14-05-2024 for deleted records data store in _pb_jsontbl
		// if(strtoupper(substr($sql,0,6))=='DELREC' or strtoupper(substr($sql,0,6))=='DELETE'){
		// 	RemoveData_Details($sql);
		// }
		mysqlOverwrite($sql); //  SQL Command are overwritten checking
		if($bypassError==1){
			return @mysqli_query($_SESSION['conn'],$sql);
		}else{
			//exit("in else");
		    $errsql="";
		    if($_SESSION['designmode']=="on")$errsql="<hr>(design mode only) SQL:".$sql."<hr>";  // show sql in d escin mode only
		    if(mysqli_error($_SESSION['conn'])) return mysqli_error($_SESSION['conn']);
			if($sql!='') {
				mysqli_query($_SESSION['conn'],$sql) or fw_output(displayError("Execute Err No 8944: ".$errsql,mysqli_error($_SESSION['conn']),1)); 
			}	
			
		}
		return PW_affectedrows();
	}
	function PW_affectedrows() {
		return mysqli_affected_rows($_SESSION['conn']);
	}
	function PW_escapeString($str=""){
		$str1=mysqli_real_escape_string($_SESSION['conn'],$str);
		return $str1;
	}
	function safeStr($str){
		if($str!='') return  htmlspecialchars($str, ENT_QUOTES, 'UTF-8') ;
	}
	function hsc($str){
		if($str!='') return  htmlspecialchars($str, ENT_QUOTES, 'UTF-8') ;
	}
	function fw_output($str,$script=''){
		$str = $str ?? "";
		if(isset($str)) $str=str_replace("`","'",$str);
		if(isFoundIn(strtoupper($str),"<SCRIPT") and strtoupper($script)!="SCRIPT") displayError("Scritp attempt","Direct scripting not allowed...<hr>".htmlspecialchars($str),1);
		//  User sent "Script" which meand it is sent by developer, then OK
		if(strtoupper($script)=='SCRIPT') echo $str;  //  Exempt from E..CHO
		else
		//  The string has script tag but the developer is unaware of this 
		if(!isFoundIn(strtoupper($str),"<SCRIPT")) echo $str;  //  Exempt from E..CHO
		else
		echo htmlspecialchars($str); //  Exempt from E..CHO
	}
	function bqecho($str){
		if($str) echo htmlspecialchars($str);
	}
	function printr($array){
		if(is_array($array)){
			if(@count($array)>0){
				if(isset($array['bqkey'])) $array['bqkey']=limitstringto($array['bqkey'],25);
				fw_output ("<pre style='margin:5px;border:1px solid #777'>");
				print_r($array);
				fw_output("</pre>");
			}else return "Array is empty";
		}else{
			echo $array;
		}
	}	
/*	function getRandomStr($length) {  
		// Description : Creates a randomized string with desired length
		// $length : length of the string
        $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $str = '';
        $maxIndex = strlen($characters) - 1; 
		for ($p = 0; $p < $length; $p++) {
			$temp = $characters[mt_rand(0, strlen($maxIndex))];
			if($temp=="") $temp="p";
			$str .= $temp;
		}
		return $str;
	}*/
	function getRandomStr($length) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$randomString = '';
		$maxIndex = strlen($characters) - 1;
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[random_int(0, $maxIndex)];
		}
		return $randomString;
	}	
	function isFoundIn($fullText,$findString,$glue=''){
		if(is_null($fullText)) return false;
		$fullText=strtoupper($fullText);
		$findString = $findString ?? '';
		$findString=strtoupper($findString);
		if($findString=="") return true;
		if(strpos("xx".$glue.$fullText.$glue,$glue.$findString.$glue)){
			return true;
		}else{
			return false;
		}
	}
	
	function displayDate($date,$separator = DATEFORMAT_DELIMITER){
		//  Description : The date given will be displayed in the given format
		//  $date : this is date variable must be in the format YYYY-MM-DD
		//  $separator : default DATEFORMAT_DELIMITER
		$returnDate=$date;
		if($date!=""){
			$tempArray =[];
			$tempArray = explode("-",$date);
			if(LOCALDATEFORMAT=='DMY'){
				$returnDate =  $tempArray[2].$separator.$tempArray[1].$separator. $tempArray[0];
			}elseif(LOCALDATEFORMAT=='MDY') $returnDate =  $tempArray[1].$separator.$tempArray[2].$separator. $tempArray[0];
			if($returnDate=='--')$returnDate='00-00-0000';
			if($returnDate=='//')$returnDate='00/00/0000';
		}
		return $returnDate;
	}
	function dbDate($date,$separator = DATEFORMAT_DELIMITER){
		//  Description : Date should display in required format
		//  $date : this is date variable with format YYYY-MM-DD
		//  $separator : default DATEFORMAT_DELIMITER
		$returnDate=$date;
		if($date!=''){
			$tempArray = explode($separator,$date);
			if(LOCALDATEFORMAT=='DMY'){
				$returnDate =  $tempArray[2]."-".$tempArray[1]."-". $tempArray[0];
			}elseif(LOCALDATEFORMAT=='MDY') $returnDate =  $tempArray[2]."-".$tempArray[0]."-". $tempArray[1];
			if($returnDate=='--') $returnDate='0000-00-00';
			if(strlen($tempArray[0])==4) $returnDate = $date;
		}
		return $returnDate;
	}
	function displayError($title,$message="",$exit=false){
		$test=cleanScript($title."-".$message); // clean for any script tag in title or message
		fw_output("<script>hideProgress();</script>","script");
		$str = "<div class='alert alert-danger m-1 p-1 ps-2' >
		<h6><i class='bi bi-exclamation-triangle-fill me-2 fs-2'></i>".($title)."</h6>
		".($message)."
		</div>";
		if ($exit) exit($str);
		return  $str;
	}
	function getAutoId($table_name){
		//  getAutoId : It takes last record id from the table,concat random string and returns Id. Used in insert function
		//  $table : String : Table name
		//	Return : string : lastno(new id) or error message 
		if($table_name){
			PW_execute("LOCREC TABLES _pb_sequencer WRITE");
			$lastno = getValueForPS("selrec lastno from _pb_sequencer where tablename=?","s",$table_name); 
			if($lastno=='' || empty($lastno)){ // If record not found with the given tablename,Inserting into sequencer
				$seq_id = getValueForPS("selrec max(id) from _pb_sequencer");
				$seq_id = substr($seq_id,0,10);
				// generate random string to concat with the ID
				$id = ($seq_id+1)."_".getRandomStr(6);
				PW_execute("UNLREC TABLES");
				$existing_id = getValueForPS("selrec max(id) from ".$table_name);
				if($existing_id!=''){
					$existing_id = substr($existing_id,0,10);
					$existing_id = ($existing_id+100)."_".getRandomStr(6);
				}else{
					$existing_id = '1000000001';
				}
				$insert_sql = "insrec into _pb_sequencer (id,tenent,tablename,lastno) values ('".$id."','".TENENT."','".$table_name."','1000000001')";
				PW_execute($insert_sql);
				PW_execute("LOCREC TABLES _pb_sequencer WRITE");
				$lastno = $existing_id;
			}
			if($lastno<100000000) $lastno=1000000001;
			// generate random string to concat with the ID
			$autoId = $lastno."_".getRandomStr(6);
			$lastno = $lastno+1;
			PW_execute("updrec _pb_sequencer set lastno='".$lastno."' where tablename='".$table_name."'");
			PW_execute("UNLREC TABLES");
			return $autoId;
			// display error if table name is empty
		}return displayError("Error","Table name is empty ");
		
	}

	function cleanScript($str=""){
		//  Description : The given string is checked for any script tag and is terminated for 
		//  $str 		: The string to be cleaned and any <script tag then the job is terminated.
		if(isFoundIn(strtoupper($str),"<SCRIPT")){
			exit("Script tag for display!...  Terminated Error 7344");
		}
		return $str;
	}
	function getDict($word,$lang=""){
    	//  Description : Gets the dictionary work for given language.
    	//  If english, the same word is returned.
    	if(!isset($_SESSION['user_language'])) $_SESSION['user_language']="en";
    	$word=trim($word);
    	if($lang=="") $lang=$_SESSION['user_language'];
     	if($lang=="en" or $lang=="") return $word;
     	if(	$lang!="en"){
     		$otherword=getValueForPS("selrec wordenglish,wordtranslated from _pb_dictionary where wordenglish=? and languageid=?","ss",$word,$lang);
     		if($otherword['wordtranslated']!="") return $otherword['wordtranslated'];
     		if($otherword['wordtranslated']=="") return $word;
     	}
     	
    }
	function getSequence($seq){
		$getSequence= getValueForPS("selrec id,txt1,txt2 from _pb_lookups where looktype='Sequencers' and lookcode='".$seq."' and tenent='".TENENT."' limit 0,1");
		$newSeqNo=intval($getSequence['txt1'])+1;
		$getSequence['txt1']=$newSeqNo;
		updateRecord($getSequence,"_pb_lookups");
		return $getSequence['txt2'].$newSeqNo;
	}    
	function get_resultPS($Statement) {
		//  get_resultPS : Returns the result set for a prepared sql statement
		$RESULT = array();
		$Statement->store_result();
		for ( $i = 0; $i < $Statement->num_rows; $i++ ) {
			$Metadata = $Statement->result_metadata();
			$PARAMS = array();
			while ($Field = $Metadata->fetch_field() ) {
				$PARAMS[] = &$RESULT[ $i ][ $Field->name ];
			}
			call_user_func_array( array( $Statement, 'bind_result' ), $PARAMS );
			$Statement->fetch();
		}
		return $RESULT;
	}	
	function showInvalidOperation($eno="",$message="",$error=""){ 
		if(SHOWSQLERRORS=="ON") $message.="; Error:".$error."; ";
		watchDog($eno,$message,"Error","");
		$errorNoTemp = explode("_",$_SESSION['pb_errorid']);
		$content = "No: ".$errorNoTemp[0];
		if($_SESSION['designmode']=="on")  $content .= "<br><br>Error:".$error."<br>";
 		$content .= "<br>Please contact Administrator."; 
		fw_output(displayError("Error",$content),"script");
		exit;
	}
	function watchDog($title,$message,$type="Error",$ds=array(),$variant=2,$file="",$line="",$class="",$method=""){
		//  watchDog : Keeps track of the execution of the program
		//  $message : message to be displayed
		//  $type : "Error", "Alert" or "Info"
		//  $ds : Any data array to be sent
		//  $file : __FILE__ the file being executed
		//  $line : __LINE__ the line being executed
		//  $class : __CLASS__ the class being executed
		//  $method : __METHOD__ the method being executed
		//  $variant : message and prog details
		//                     2 will display message and prog details with user details and time
		//  display the string in proper manner 
		//  If TESTMODE is ON then record the details in to the _pb_log table first
		echo WATCHDOG;
		if(WATCHDOG!="ON") return;  //  If WATCHDOG # ON then return
		//$array=explode(CONTEXTNAME,$file);
		$file				= str_replace(__DIR__,"",$file);
		if($message!="") 				$message.="; ( File:".$file.", Line:".$line.", Method:".$method.") Type:".$type."; ";
		$temp['id']			= getAutoId("_pb_log");		//  Push the test point values to dataset
		$temp['logtype']	= "WATCHDOG";
		$temp['logtitle']	= $title;
		$temp['logmessage']	= $message;
		$temp['logtime']	= date("Y-m-d H:i:s");
		$temp['userid']		= $_SESSION['userid'];
		$temp['username']	= $_SESSION['username'];
		$temp['logdata']	= json_encode($ds);			//  Save the data being tested into the _pb_log table
		$temp['phpfile']	= $file;
		$debug_backTrace = debug_backtrace();
		$caller = ($debug_backTrace);
		$temp['args']		= "REQ params pgid:".$_REQUEST['pgid'].", hpg:".$_REQUEST['hpg'].", hid:".$_REQUEST['hid'].", id:".$_REQUEST['id'].", rty:".$_REQUEST['rty'];
		$temp['source']		= "Function : ".$method;
		$temp['lineno']		= $line;
		$temp['weightage']	= 0;
		//  Do not use insert function here.... it gets into recursion
		PW_execute("insrec into _pb_log (id,logtype,logtitle,logtime,userid,username,logmessage,logdata,phpfile,args,source,lineno,weightage) values ('".$temp['id']."','".PW_escapeString($temp['logtype'])."','".PW_escapeString($temp['logtitle'])."','".PW_escapeString($temp['logtime'])."','".PW_escapeString($temp['userid'])."','".PW_escapeString($temp['username'])."','".PW_escapeString($temp['logmessage'])."','".PW_escapeString($temp['logdata'])."','".PW_escapeString($temp['phpfile'])."','".PW_escapeString($temp['args'])."','".PW_escapeString($temp['source'])."','".PW_escapeString($temp['lineno'])."','".PW_escapeString($temp['weightage'])."')");
		$_SESSION['pb_errorid']=$temp['id'];
	}
	function PW_num_fields($rs){ 
		//  Return the number of fields in a result set
		return mysqli_num_fields($rs);
	}
	function PW_num_rows($rs){
		//  Return the number of rows in a result set
		if ($rs === false) {
	        return 0; // or handle error
	    }
		return mysqli_num_rows($rs);
	}
	function PW_num_cols($rs){
		//  Return the number of fields in a result set
		return mysqli_num_fields($rs);
	}
	
	// display utilities

	function mathSymbols(string $text): string {
	    // 1. Escape user input first to prevent XSS
	    $s = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	
	    // 2. Parenthesized exponents: e.g. x^(2x) → x<sup>2x</sup>
	    $s = preg_replace_callback('/\^\(([^)]+)\)/u',fn($m) => '<sup>' . $m[1] . '</sup>',$s);
	
	    // 3. Simple exponents: e.g. x^2, e^-y, sin^2(x)
	    $s = preg_replace(
	        '/\^(-?[A-Za-z0-9]+)/u',
	        '<sup>$1</sup>',
	        $s
	    );

    	return $s;
	}
	function replaceDS2message($ds,$str){ 
		if($str!='')$str=str_replace("`","'",$str);
	    if($str!='')$str=str_replace('[today]',date("Y-m-d"),$str); // session values replacement
	    if($str!='')$str=str_replace('[yesterday]',date("Y-m-d", strtotime("-1 day")),$str); // date values replacement
	    if($str!='')$str=str_replace('[tomorrow]',date("Y-m-d", strtotime("+1 day")),$str); // date values replacement
	    if($str!='')$str=str_replace('[now]',date("Y-m-d H:i:s"),$str); // session values replacement
	    //  For templates added the below code To get the today date in the format of December 28, 2016 
	    if(isFoundIn($str,'[date]:D2W')){
	    	list($month_name,$year) = explode(" ",month2Words(date("Y-m")));
	    	$str = str_replace('[date]:D2W',$month_name." ".date('d').", ".$year,$str);
	    }
	    // Added photos size small,medium,large for additional info in pagehead
	    //if($_SESSION['app_logo']=="" or $BR_EncFileName=="") $_SESSION['app_logo'] = (getValueForPS("selrec encfilename from _pb_setup where companycode=?","s",$_SESSION['user_company']));
	   //printr(APPFULLPATH);
		if($str!='')$str= str_replace('[logo]',"<img style='max-height:50px;padding-left:3px;' src='".APPFULLPATH."res/images/bq_logo.png'>",$str); // logo
		// $str= str_replace('[photo]',"<img src='".APPFULLPATH."do.php?".pw_enc("pw=Fw_imageview.php&rq1=".$ds['encfilename'])."'>",$str); // Photo
		// $str= str_replace('[photos]',"<img style='width:60px;' src='".APPFULLPATH."do.php?".pw_enc("pw=Fw_imageview.php&rq1=".$ds['encfilename'])."'>",$str); // Photo
		// $str= str_replace('[photom]',"<img style='width:120px;' src='".APPFULLPATH."do.php?".pw_enc("pw=Fw_imageview.php&rq1=".$ds['encfilename'])."'>",$str); // Photo
		// $str= str_replace('[photol]',"<img style='width:200px;' src='".APPFULLPATH."do.php?".pw_enc("pw=Fw_imageview.php&rq1=".$ds['encfilename'])."'>",$str); // Photo
		if($str!='')$str = str_replace('[APPFULLPATH]',APPFULLPATH,$str); // fullpath
		if(isFoundIn($str,'d:[date1]')) $str=str_replace('d:[date1]',displaydate($ds['date1']),$str);
	    if(isFoundIn($str,'d:[date2]')) $str=str_replace('d:[date2]',displaydate($ds['date2']),$str);
	    if(isFoundIn($str,'d:[date3]')) $str=str_replace('d:[date3]',displaydate($ds['date3']),$str);
	    if(isFoundIn($str,'d:[date4]')) $str=str_replace('d:[date4]',displaydate($ds['date3']),$str);
	    if(isFoundIn($str,'d:[date5]')) $str=str_replace('d:[date5]',displaydate($ds['date3']),$str);
	    if(isFoundIn($str,'d:[docdate]')) $str=str_replace('d:[docdate]',displaydate($ds['date3']),$str);
	    if(isFoundIn($str,'d:[fromtime]')) $str=str_replace('d:[fromtime]',date_format($ds['date3'],"m-d-Y H:i A"),$str);
	    if(isFoundIn($str,'d:[fromtime]')) $str=str_replace('d:[totime]',date_format($ds['date3'],"m-d-Y H:i A"),$str);
	    // print_r($_SESSION);
	    // echo "<br><br>";
		foreach ($_SESSION as $key => $value){
			$ktype = gettype($value);
			if($ktype!='object'){
				$temp = $value;
				if($ktype=='integer' or $ktype=='real' or is_numeric($temp)){
					if(isFoundIn($str,'s:['.$key.']:2'))	$str=str_replace('s:['.$key.']:2',myMoney($temp,2),$str);
					if(isFoundIn($str,'s:['.$key.']:3'))	$str=str_replace('s:['.$key.']:3',myMoney($temp,3),$str);
				}	
				if(isFoundIn($str,'s:['.$key.']')) $str=str_replace('s:['.$key.']',$temp,$str); // session values replacement
			}
		}
		foreach ($_SESSION['PW_CONSTANTS'] as $key => $value){
			if(isFoundIn($str,'s:c:['.$key.']')) $str=str_replace('s:c:['.$key.']',$value,$str); // session constants values replacement
		}
		
		if(!empty($ds)){
			 foreach ($ds as $key => $value){  //  Replace all normal dataset variables in same way
				if ($value == null) continue;
				$ktype = gettype($value);
				$temp = $value ?? '';
				if($ktype=='integer' or $ktype=='real' or is_numeric($temp)){
					if(!empty($str) && !empty($key) && !empty($temp)){
						$str=str_replace('['.$key.']:2',myMoney($temp,2),$str);
						$str=str_replace('['.$key.']:3',myMoney($temp,3),$str);
					}
				}
				if($temp!='0000-00-00' and isFoundIn($temp,':DMY')){
					$str=str_replace('['.$key.']:DMY',displayDate($temp,DATEFORMAT_DELIMITER),$str); //  date replacement
				}else{
					//modified for sales purchase prints date replacement
					if(isFoundIn($temp,':DMY')) $str=str_replace('['.$key.']:DMY',"",$str); 
				}
				if(isFoundIn($temp,':D2W')) $str=str_replace('['.$key.']:D2W',month2Words($value),$str);    //  Used for templates
				if(isFoundIn($temp,':DMT')) $str=str_replace('['.$key.']:DMT',date2DateTime($value),$str);    //  Used for Triggers 
				if(isFoundIn($temp,':NL2BR')) $str=str_replace('['.$key.']:NL2BR',nl2br($value),$str);    //  Used for Triggers 
				if(isFoundIn($temp,':MTS')) $str=str_replace('['.$key.']:MTS',$value/1000,$str);    //  Used for KGS TO MTS
				if(isFoundIn($temp,':KGS')) $str=str_replace('['.$key.']:KGS',$value*1000,$str);    //  Used for MTS TO KGS
				if($temp!="" and $str!="") $str=str_replace('['.$key.']',$temp,$str);
				if(isFoundIn($str,'{'.$key.'}')){
					$temp_barcode = "<img src='".getBarcode($temp)."' width='200'>";
					$str=str_replace('{'.$key.'}',$temp_barcode,$str);
				}
			}
		}
		return $str;
	}
	 
	function myMoney($amount,$decimals=0){
		// to change the currency format
		// $amount : It's the required amount
		// $decimals : number of decimal characters.
		//if($decimals=='') $decimals=0;
		if(CURRENCY_FORMAT=="Indian") {
			// define("DECIMAL",$decimals);
			$formatter = new NumberFormatter('en_IN', NumberFormatter::DECIMAL);
			$formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimals);
			if($amount){
				$formattedNumber = $formatter->format($amount);
				//return money_format('%!i', round($amount,$decimals));
	 			//return money_format('%!.'.$decimals.'n', $amount);
	 			//return numberFormat($amount, $decimals);
	 			return $formattedNumber;
			}
		}else{
			return number_format($amount,$decimals);
		}
	}
	
	function month2Words($yymm){
		//  month2Words : To get month name from the number (Used in Templates)
		//  $yymm  : year and month
		$month = substr($yymm, -2, 2);
 		$monthName = date('F', strtotime(date('Y-'. $month .'-d'))); 
		return $monthName." ".substr($yymm,0,4); //  Concating month name and year
	}
	
	function date2DateTime($value){
		// to convert date 2 datetime used in replaceDS2message()
		// example $str=str_replace('['.$key.']:DMT',date2DateTime($value),$str);    //  Used for Triggers 
		if(isFoundIn($value,"T"))$valueArr = explode("T",$value);
		else $valueArr = explode(" ",$value);
		$value  = displayDate($valueArr[0])." ".$valueArr[1];
		return $value;
	}
	
	function removeLastNchars($str,$n=0){
		//  Description : To remove last N characters of a string
		//  $str : String
		//  $n : No of characters to remove
		return substr($str,0,-$n);
	}

	function show_formErrors($message="",$hideTime=5,$width=""){
		// to display the form errors
		// $message : To show the message in the error div
		// $hideTime : Error div will auto hide time in seconds, default is 5 seconds
		$hideScript = "";
		if($width!="") $width="o.style.width=".$width;
		
		if($hideTime>0){
			$hidetimeInMilliSeconds = ($hideTime*1000);
			$hideScript = "\n
				setTimeout(hideErrorObject,".$hidetimeInMilliSeconds.");
				$(document).ready(function(){
				$('#ederror').delay('".$hidetimeInMilliSeconds."').fadeOut();
			});\n";
		}
		$str = "<script>
			o=getObject('ederror_msg');
			".$width."
			o.style.padding='5px'
			o.style.display='';
			o.innerHTML='<i class=\"fa-solid fa-xmark\"></i>&nbsp;".$message."<br><br><div style=\'float:right;width:130px;\' class=\'btn btn-primary btn-sm\'>Click me to close</div>';
			showObject('ederror');
			o.style.padding='5px'
			hideProgress();
			// auto hiding the 
 			".$hideScript."
		
			</script>";
			fw_output($str,"script");
	}
	
	function diffArray($old_data,$new_data){
		//It returns the difference between the old array and new array values
		//$old_data : old data array
		//$new_data : new data array
		$results = array_merge(array_diff($old_data, $new_data), array_diff($new_data, $old_data));
		$diff = array();
		if(is_array($results)){
			foreach($results as $k => $v){
				if($k!='content2')$diff[] = array('field' => $k,
								'old' => $old_data[$k],
								'new' => $new_data[$k]);
			}
		}
		return $diff;
	}	
	function recordAudit($action,$dataArray,$table="",$recid=""){  
		// Audit Function
		// $action : either update or inset or delete
		// $dataArray :data Array will convert in json and save in the DB field
		// $table : table name
		// $recid : record id
		// if the table is _pb_audit simply return back

		$timeUTC = gmdate('Y-m-d H:i:s');

		if ($table=="_pb_audit"){
            return;
        } 
        if ($recid==""){
            return;
        }
        if (count($dataArray)==1){
        	$fld = $dataArray[0]['field']; 
        	if($fld=='updatedat' or $fld=='updatedby' ){  // for these 2 fields no need to create a audit record
            	return;
        	}
        }
         if (count($dataArray)==2){
        	$fld = $dataArray[0]['field']; 
        	$fld1 = $dataArray[1]['field']; 
        	if(($fld=='updatedat' or $fld=='updatedby') and ($fld1=='updatedat' or $fld1=='updatedby')){
            	return;
        	}
        }
        
		$oldDs = getValueForPS("selrec * from _pb_audit where recid=? and sqltable=? and tenent=?","sss",$recid,$table,TENENT); 
		$dataCount = count($dataArray);
		$_SESSION['audit_pgid'] = $_SESSION['currentpage']['head']['pgid']??'';
		$_SESSION['audit_caption'] = $_SESSION['currentpage']['head']['caption']??'';
		$_SESSION['audit_pw'] = $_GET['pw']??'';
		$dataArray[$dataCount]['Action']  = $action;
		$dataArray[$dataCount]['ID'] = $recid;
		$dataArray[$dataCount]['User_Id'] = $_SESSION['userid']??null;
		$dataArray[$dataCount]['Stamp'] = $timeUTC;// date('Y-m-d H:i:s');
		$dataArray[$dataCount]['IP'] = get_client_ip();
		$dataArray[$dataCount]['pgid'] = $_SESSION['audit_pgid'] ?? "newauditpgid";
		$dataArray[$dataCount]['Page_Caption'] = $_SESSION['audit_caption']?? "newauditcaption";
		$dataArray[$dataCount]['Page_pw'] = $_SESSION['audit_pw']?? "newauditpw";
		//$_SESSION['Deligationuserid'] = $_SESSION['Deligationuserid']?? "newDeligationuserid";
		//if($_SESSION['Deligationuserid']!="")$dataArray[$dataCount]['Delegatedtouser'] = $_SESSION['Deligationuserid'];
		//if(isset($oldDs['id']) && $oldDs['id']==''){  // No prev audit reord is there so insert
		//if($oldDs['id']=='' || !isset($oldDs['id'])){  // No prev audit reord is there so insert
		if(empty($oldDs['id'])){  // No prev audit reord is there so insert
			$jsonData = json_encode(array("0"=>$dataArray));
			$auditInsertSql = "insrec into _pb_audit (id,createdby,updatedby,createdat,updatedat,companycode,company,tenent,sqlaction,sql1,recid,sqltable,userid,stamp,ip) values('".getAutoId("_pb_audit")."','".$_SESSION['userid']."','".$_SESSION['userid']."','".date('Y-m-d H:i:s')."','".date('Y-m-d H:i:s')."','".$_SESSION['user_companycode']."','".$_SESSION['user_company']."','".TENENT."','".$action."','".mysqli_real_escape_string($_SESSION['conn'],$jsonData)."','".$recid."','".$table."','".$_SESSION['userid']."','".date('Y-m-d H:i:s')."','".get_client_ip()."')";
			PW_execute($auditInsertSql);
		}
 		//if(isset($oldDs['id']) && $oldDs['id']!=''){ //  prev audit reord is there so update
 		if (isset($oldDs['id']) && $oldDs['id'] !== ''){ //  prev audit reord is there so update
			$jsonNewData = json_encode(array("0"=>$dataArray));
			$jsonOldData = json_decode($oldDs['sql1'],true); 
			if($jsonNewData!="" and $jsonOldData!="") $jsonData = json_encode(array_merge(json_decode($jsonNewData,true),$jsonOldData));
			if($jsonOldData=='')$jsonData = $jsonNewData;
			$_SESSION['userid'] = $_SESSION['userid']??null;
			$sql1=
			$aSQL = "updrec _pb_audit set updatedat='".date('Y-m-d H:i:s')."',updatedby='".$_SESSION['userid']."',sql1=concat('".mysqli_real_escape_string($_SESSION['conn'],$jsonData)."')
			where recid='".$recid."' and tenent='".TENENT."' ";
        	$Result = PW_execute($aSQL); //  Do not put die statement here
			$ext="";

		}

    }
    
	function get_client_ip() {  
		//  gets real client IP
		$ipaddress = '';
	    if (getenv('HTTP_CLIENT_IP'))
	        $ipaddress = getenv('HTTP_CLIENT_IP');
	    else if(getenv('HTTP_X_FORWARDED_FOR'))
	        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
	    else if(getenv('HTTP_X_FORWARDED'))
	        $ipaddress = getenv('HTTP_X_FORWARDED');
	    else if(getenv('HTTP_FORWARDED_FOR'))
	        $ipaddress = getenv('HTTP_FORWARDED_FOR');
	    else if(getenv('HTTP_FORWARDED'))
	       $ipaddress = getenv('HTTP_FORWARDED');
	    else if(getenv('REMOTE_ADDR'))
	        $ipaddress = getenv('REMOTE_ADDR');
	    else
	        $ipaddress = 'UNKNOWN';
	    return $ipaddress;
	}
    
	function setSession($userid){
		$_SESSION['userid'] = $userid;
		$userDs = getValueForPS("selrec * from _pb_entity where userid=? and status='Active' and entitytype<>'Pluros Users'","s",$userid);
		
		$_SESSION['userid'] = $userDs['userid'];
		// Set user session variables from _pb_entity with "user_" prefix
		foreach($userDs as $key=>$value){
			if((isFoundIn("~".$value."~","~Admin~") or isFoundIn("~".$value."~","~sAdmin~")) && isFoundIn($value,"All form readonly"))$value = str_replace("~All form readonly","",$value); 
			if($key=="bucodes"){
				$value = getValueForPS("selrec group_concat(bucode separator '~') from _pb_businessunit where status='Active' and bucode in (".str_replace("~","','","'".$value."'").")");
			}
			$_SESSION['user_'.$key] = $value;
			if($key=='role'){
				$_SESSION['user_'.$key.'_comma'] = str_replace("~","','","'".$value."'"); 
			}	
			if($key=='wfrole')	$_SESSION['user_'.$key.'_comma'] = str_replace("~","','","'".$value."'");
			if($key=='bucodes')	$_SESSION['user_'.$key.'_comma'] = str_replace("~","','","'".$value."'"); 
			if($key=='locationcodes')	$_SESSION['user_'.$key.'_comma'] = str_replace("~","','","'".$value."'"); 
		}
		
		$setupDs = getValueForPS("selrec * from _pb_setup where status='Active'  limit 0,1");
		$_SESSION['companycode'] = $setupDs['companycode'];	//  Set Company
		foreach($setupDs as $key=>$value){
			$_SESSION['company_'.$key] = $value; 
		}
		$constrs=PW_sql2rsPS("Selrec lookcode,lookname,txt1 from _pb_lookups where status=?  and looktype=?","ss",'Active','Constants');
		while($constds=PW_fetchAssoc($constrs)){
			$_SESSION['PW_CONSTANTS'][$constds['lookcode']]=$constds['txt1'] ?? '';
		}
	} 
	
	function openForm(){
		$name="form_".getRandomStr(6);
		return "<form method='post' name='".$name."' id='".$name."' novalidate  hx-encoding='multipart/form-data' onsubmit='return validateAll(this)'>
				<input type=hidden name='form_xref' id='form_xref' value='".$name."'>
				<input type=hidden name='xref' id='xref' value='".pw_enc($name)."'>";
	}
	
	function displayAlert($title,$message=""){
		$test=cleanScript($title."-".$message); // clean for any script tag in title or message
		$str = "<table class='alert alert-info'>
		<tr><th>".getDict($title)."</th></tr>
		<tr><td>".getDict($message)."</td></tr>
		</tsble>";
		return  $str;
	}
	
	function displayInfo($title,$message="",$displayBelow='No'){
		$test=cleanScript($title."-".$message); // clean for any script tag in title or message
		$div_display="";
		

		fw_output("<script>hideProgress();</script>","script");
		$str = "<div class='alert alert-primary m-1 p-1 ps-2' >
		<h6><i class='bi bi-exclamation-triangle-fill me-2 fs-2'></i>".($title)."</h6>
		".($message)."
		</div>";
		return  $str;
	}
	
	
	function isColumnExists($table,$col){
		$sql = "selrec COUNT(*) AS col_exists
		FROM information_schema.COLUMNS
		WHERE TABLE_SCHEMA = DATABASE()
		AND TABLE_NAME = '".$table."'
		AND COLUMN_NAME = '".trim($col)."'";
		
		$res = PW_sql2rsPS($sql);
		$ds = PW_fetchAssoc($res);
		
		if ($ds['col_exists'] > 0) {
			return true;
		}else{
			return false;
		}
	}
	function getUploadFilePath($file){
		// function is used to get the full upload path.
		// $file : file name
		if($file=='') return;
		$fileDir = substr($file,0,7);
		$filePath = $fileDir."/".$file;
		if(!file_exists(UPLOAD_DIR_PATH.$filePath)){
			if(file_exists(UPLOAD_DIR_PATH.UPLOAD_DIR_OLDFILES.$file)){ // changes done on 11-06-2021 by chowdary
				$filePath = UPLOAD_DIR_PATH.UPLOAD_DIR_OLDFILES."/".$file;
			}else{
				$filePath = $file;
			}
		}
		return $filePath;
	}
	function uploadtemp2dir($filename){
		if(file_exists("temp/".$filename)){
		$tmpfilename=TEMP_DIR . $filename;
		createUploadMonthFolder();
		$fileDir = date("YM");
		$ext = pathinfo(basename($filename), PATHINFO_EXTENSION);
		$encfilename = $fileDir.getRandomStr(10).".".$ext;
		copy($tmpfilename,UPLOAD_DIR_PATH."/".$fileDir."/".$encfilename);
		return $encfilename;
		}
	}
	function createUploadMonthFolder(){
		// function is used to create folder with the current year and month
		$fileDir = date("YM");
		$dir = UPLOAD_DIR_PATH;
		if(!is_dir($dir.$fileDir)){
			mkdir($dir.$fileDir,0770);
		}
	}
    function uload($userUploadedFile,$fileInputName="userfile",$id="",$table="",$dir=""){
		//	Description: Upload the file with validations of size,extentions 
		//	$userUploadedFile : array : array of $_FILES
		//	$fileInputName : sring : input file type file name 
		//	$id : string : record id it will use for if any previous file exists it will be remove
		//	$table : string : table name for the object getting the previous file record filename
		//	$dir : sring :   
		if($dir==""){ $dir=UPLOAD_DIR_PATH;}
		$file_tmp_name = $userUploadedFile[$fileInputName]["tmp_name"];
		$file_name = $userUploadedFile[$fileInputName]["name"];
		$file_size = $userUploadedFile[$fileInputName]["size"];
		$file_mime = "";
		if($file_mime=="PHP" or $file_mime=='ASCII'){
			$_SESSION['user_uploadedfile'] = $_SESSION['enc_user_uploadedfile'] = $_SESSION['user_uploadfilesize'] = "";
			return  "\n<script>alert('Invalid file type');hideprogress();</script>\n";
		}
		$err = "";
		$ext = pathinfo(basename($file_name), PATHINFO_EXTENSION);
		$errorUP  = "";
	    $fname = $file_name;
	    $dots    = substr_count($fname,".");
	    $isSlash  = strpos("XXX".$fname,"/");
	    $isRSlash  = strpos("XXX".$fname,"\\");
	    if($dots>1 or $isSlash>0 or $isRSlash>0){
	      $errorUP.= "File name : ".$file_name." has error 7888::  Double dots or slash marks found";
	    }
	    if($errorUP != "" ){
			show_formErrors($errorUP);
			exit;	
	    } 
		$upload_file_size_limit = DOCUMENT_UPLOAD_LIMIT;
		$allowedExtensions = array("jpg","jpeg","gif","png","bmp");
		$allowedExtensions1 = array("doc","docx","xls","xlsx","csv","ppt","pptx","pdf","txt","eml","msg","opus");
		$notAllowedExtensions = array("php","js","exe","bat");
		if (in_array(strtolower($ext), $allowedExtensions)){
			$upload_file_size_limit = IMAGE_UPLOAD_LIMIT;
		}elseif (in_array(strtolower($ext), $allowedExtensions1)){
			$upload_file_size_limit = DOCUMENT_UPLOAD_LIMIT;
		}
		$allowedTypes = [
			// Images
			'jpg'  => ['image/jpeg','image/jpeg'],
			'jpeg' => ['image/jpeg','image/jpeg'],
			'png'  => ['image/png'],
			'gif'  => ['image/gif'],
			'bmp'  => ['image/bmp'],
		
			// Documents
			'pdf'  => ['application/pdf'],
			'doc'  => ['application/msword'],
			'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
			'xls'  => ['application/vnd.ms-excel'],
			'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
			'csv'  => ['text/csv', 'text/plain'],
			'ppt'  => ['application/vnd.ms-powerpoint'],
			'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
			'txt'  => ['text/plain'],
		
			// Emails
			'eml'  => ['message/rfc822'],
			'msg'  => ['application/vnd.ms-outlook'],
		
			// Audio
			'opus' => ['audio/opus'],
		
			// Archives
			'zip'  => ['application/zip'],
		
			// CAD files
			'dwg'  => ['application/acad'],
			'dxf'  => ['image/vnd.dxf'],
			'sldprt' => ['application/sldworks'],
			'prt'  => ['application/octet-stream'],
			'stp'  => ['application/step', 'application/x-step'],
			'step' => ['application/step', 'application/x-step'],
			'igs'  => ['application/iges', 'model/iges'],
			'x_t'  => ['application/x-t'],
		];
		$allowedExtensions = array("jpg","jpeg","gif","png","bmp");
		$allowedExtensions1 = array("doc","docx","xls","xlsx","csv","ppt","pptx","pdf","txt","eml","msg","opus");
		$notAllowedExtensions = array("php","js","exe","bat");
		if (in_array(strtolower($ext), $allowedExtensions)){
			$upload_file_size_limit = IMAGE_UPLOAD_LIMIT;
		}elseif (in_array(strtolower($ext), $allowedExtensions1)){
			$upload_file_size_limit = DOCUMENT_UPLOAD_LIMIT;
		}
		$extLower = strtolower($ext);
		// Check extension
		//if($id=='' && $file_tmp_name){
		if ($id === '' && !empty($file_tmp_name) && file_exists($file_tmp_name)) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$file_tmp = $file_tmp_name;
			$realMime = finfo_file($finfo, $file_tmp); // $file_tmp = $_FILES['yourfile']['tmp_name']
			finfo_close($finfo);
		
			if (!array_key_exists($extLower, $allowedTypes)) {
				fw_output("<script>alert('".$extLower." files cannot be Uploaded.".$id.$file_tmp_name."');showObject('aeform');</script>\n","script");
				exit;
			}
		}
		
		// if($file_size> $upload_file_size_limit){
		if($file_size> 20000000){
			fw_output("<script>alert('Upload file size limited to .....".$upload_file_size_limit." bytes...".$file_name." exceeds the file xxxxlimit and is hhh');
			hideprogress();
			</script>\n","script");
			exit;
		}
		$err="";
		
		$chkExtentionsArray = array('PDF','XLS','XLSX','DOC','CSV','DOCX','PPT','PPTX','TXT','ZIP','PNG','GIF','JPG','JPEG','BMP','EML','DWG','DXF','SLDPRT','PRT','STP','IGS','MSG','OPUS','STEP','X_T'); // STEP','X_T' igs are cad files
		if (!in_array(strtoupper($ext), $chkExtentionsArray) && $ext<>'') {
			fw_output("<script>alert('".$ext." files cannot be Uploaded.');hideprogress();</script>\n","script");
 			 exit;
		}
		
		if (in_array(strtolower($ext), $notAllowedExtensions)) {
			$err = "PHP/JS files cannot be Uploaded.....";
			return "<script>alert('PHP/JS files cannot be Uploaded.....');hideprogress();</script>\n";
			// exit($err);
		}
		
		if($file_name){
			if(strlen($file_name)>45) $file_name = substr($file_name,0,45).".".$ext;
			$_SESSION['user_uploadedfile'] 		= $file_name;
			$_SESSION['user_uploadfilesize'] 	= $file_size; // file size added on (07-10-2022)
			$fileDir = date("YM");
			// $_REQUEST['pgid'] = $_REQUEST['pgid'] ?? null;
			
			createUploadMonthFolder();
			$_SESSION['enc_user_uploadedfile']  = $fileDir.getRandomStr(10).".".$ext;
			//-----------------
			set_time_limit(240);
				
			if (move_uploaded_file($file_tmp_name, $dir.$fileDir."/".$_SESSION['enc_user_uploadedfile'])) {
				/* 	// suspended
					if (in_array(strtolower($ext), $allowedExtensions)) {
						createThumbnail($_SESSION['enc_user_uploadedfile'],$dir.$fileDir."/");
					}
				*/
	        }else{
				$_SESSION['user_uploadedfile'] 		= "";
				$_SESSION['enc_user_uploadedfile']  = ""; 
				$_SESSION['PW_ExternalDriveUploadID'] = "";
				$_SESSION['user_uploadfilesize'] = "";
			}
			

		}
    }
    

	function sendmail($to="",$subject="",$content="",$from="",$cc="",$Bcc="",$AttachFileArray=array(),$fromName="",$Footer=""){
		sendMailWithAttachmentsSendGrid($to,$subject,$content,$from,$cc,$Bcc,$AttachFileArray,$fromName,$Footer);
		return;
	}
	
	// send mail example
	// $AttachFileArray['filename'] =$pdffile;
	// $AttachFileArray['display_filename'] = $pdffile;
		// $AttachFileArray['path'] = "temp/";
    function sendMailWithAttachmentsSendGrid($to="",$subject="",$content="",$from="",$cc="",$Bcc="",$AttachFileArray=array(),$fromName="",$Footer=""){
		$cc = strtolower($cc); 
		$cc = trim($cc);
		$Bcc = strtolower($Bcc);
		$content=nl2br($content);

		
		$UserName ='ADMIN';
		$ComanyName = 'COMPANYNAME';
		if($from=='') $from = DEFAULTFROMMAIL;

		$contentBody = "<body style=''>
		<table align='center' style='width:100%;background:#FFFFFF;border:1px'>";
		$contentBody .= "<tr><td style='border:0px solid #f00'>".$content."</td></tr>";
		$footerBody = "<tr><td style='border:0px solid #00f'>".$UserName."<br>".$ComanyName."<br>".date('d-m-Y H:i:s')."</td></tr>";
		if($Footer!=""){
			$footerBody ="<tr><td style=''>".$Footer."<br>".date('d-m-Y H:i:s')."</td></tr>";
		}
		$contentBody .= $footerBody;
		$contentBody .="</table></body>";
		// Get cURL resource
		if(isset($AttachFileArray['filename'])!="")$filename  = $AttachFileArray['filename'];
		if(isset($filename)){
			$path = $AttachFileArray['path'];
			$file = $path.$filename;
			// echo toast("XX".$file,"danger");

			$file_size = filesize($file);
			$handle = fopen($file, "r");
			$content = fread($handle, $file_size);
			fclose($handle);
			$file_content = base64_encode($content); 
			if(isset($AttachFileArray['display_filename']) and $AttachFileArray['display_filename']){
				$ext = ".".pathinfo(basename($filename), PATHINFO_EXTENSION);
				$ext1 = pathinfo(basename($AttachFileArray['display_filename']), PATHINFO_EXTENSION);
				if($ext1!='')  $ext='';
				$filename = $AttachFileArray['display_filename'].$ext;
			} 
		}
		$multiFileNames = $multiFileContents = "";
		$AttachFileArray['multifiles'] = $AttachFileArray['multifiles'] ?? null;
		if(count($AttachFileArray['multifiles'] ?? []) > 0){
			foreach($AttachFileArray['multifiles'] as $filesArray){	
				$m_file  = $filesArray['filename'];
				if($m_file){
					$path = $filesArray['path'];
					$file = $path.$m_file;
					$file_size = filesize($file);
					$handle = fopen($file, "r");
					$content = fread($handle, $file_size);
					fclose($handle);
					$file_content = base64_encode($content); 
					if(isset($filesArray['display_filename']) and $filesArray['display_filename']){
						$ext = pathinfo(basename($m_file), PATHINFO_EXTENSION);
						$m_file = $filesArray['display_filename'].".".$ext;
					} 
					$fileUpArrayName[] = $m_file;
					$fileUpArrayContent[] = $file_content;
				}				
			}
			$multiFileNames = serialize($fileUpArrayName);
			$multiFileContents = serialize($fileUpArrayContent);
		}
		$contentBody = str_replace("<a rel='nofollow' href","<a rel='nofollow' href",$contentBody);
		$contentBody = str_replace("<a rel='nofollow' href","<a rel='nofollow' href",$contentBody);
		$contentBody = str_replace("<a rel='nofollow' href","<a rel='nofollow' href",$contentBody);
		$contentBody = str_replace("<a rel='nofollow' href","<a rel='nofollow' href",$contentBody);
		$curl = curl_init();
		// Set some options - we are passing in a useragent too here
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => SMTPURL,
			CURLOPT_USERAGENT => 'cURL Request',
			CURLOPT_POST => 1,
			CURLOPT_POSTFIELDS => array(
				'onmail' => 'sgmail',
				'frommail' => $from,
				'tomail' => $to,
				'ccmail' => $cc,
				'bccmail' => $Bcc,
				'subject' => $subject,
				'message' => $contentBody,
				'filename' => $filename ?? null,
				'filecontent' => $file_content??null,
				'multi_filename' => $multiFileNames,
				'multi_filecontent' => $multiFileContents
			)
		));
		// Send the request & save response to $resp
		$respJson = curl_exec($curl);
		// Close request to clear up some resources
		curl_close($curl);
		$respJsonDecode = json_decode($respJson);
		if($respJsonDecode->_status_code=='202'){
			$msg = toast("Mail sent successfully: @ ".date("H:i:s"));
		}else{
			$msg = "Error Code: ".$respJsonDecode->_status_code.". Mail failed To ".str_replace(",","<br>",$to);
		}
		return $msg;
	}
	
	// the posted data validation at server
	function validatePost($k,$v,$activeSession){
		if($k=="xref" or $k=="form_xref") return"";
		$caption=$activeSession['fields'][$k]['caption'];
		$tabname=$activeSession['fields'][$k]['tabname'];
		if($k!='xref' && $k!="form_xref"){	// leavinf xref checking only fields
			$tags = isset($tags) ? $tags : "";
			$tags="xxx".$activeSession['fields'][$k]['tags']."xxx";
			$controltype=strtoupper($activeSession['fields'][$k]['controltype']);
			if($controltype=="EMAIL" && $v!="") {
				if(filter_var($v, FILTER_VALIDATE_EMAIL)=="") return "<li>". $caption.": ".$v."<br>invalid email format..</li>";
			}
			if($controltype=="SMS") { 
    			if (preg_match('/^(\d{10})?$/', (string)$v) !== 1) {
    				return "<li>".$caption.": ".$v."<br>Invalid mobile: 10 digits, </li>";
    			}
			}
			if(isFoundIn(strtoupper($tags),"NUMBERS")){
				if (preg_match('/^\d+(\.\d+)?$/', (string)$v) != 1)	 return "<li>".$caption.": ".$v."<br>numbers only permitted !!";
			}
			if(($controltype =='DATE NORMAL' or $controltype=='DATE' or $controltype=='DATE TIME') and !isFoundIn(strtoupper($tags),"TODAY-FUTURE") and !isFoundIn(strtoupper($tags),"TODAY-PAST")){
				$year = date('Y', strtotime($v));
				if($year <1900 and $year!='0000'){
					 return "<li>".$caption." : ".$v."<br>Invalid Date format !!";
				}
				if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', date($v)) and $v!='' ) {
			        return "<li>".$caption." : ".$v."<br>Invalid Date format !!";
			    }
			}
			if (isFoundIn(strtoupper($tags),"TODAY-FUTURE")) { 
				$fldValue2 = dbDate($v);
				if (date('Y-m-d') > $fldValue2) {
					return "<li>".$caption." : ".$v."<br>Only today or future dates are allowed !!";
				}
			}
			if(isFoundIn(strtoupper($tags),"TODAY-PAST")) { 
				$fldValue2 = dbDate($v);
				if (date('Y-m-d') < $fldValue2) {
					return "<li>".$caption." : ".$v."<br>Only past dates or today's date are allowed !!";
				}
			}			
			// if(isFoundIn(strtoupper($tags),"NUMERIC 0 DECIMAL")){
			// 	if(preg_match('/^\d+$/', (string)$v) != 1) return "<li>".$caption.": ".$v."<br>Integer only permitted, no decimals !!";
			// }
			 
			if(isFoundIn(strtoupper($tags),"REQUIRED")){
				//echo "<script>alert('".$k."');alert('".$v."');alert('".$caption."');</script>";
				if(trim($v)=="") {
					return "<li>--".$tabname."--".$caption.": ".$v."<br>Data is mandatory";
				}
			}
			
		}
		//exit;
	}
	
	function fieldjsvalidations($k, $v,$activeSession) {
		$fieldInfo = $activeSession['fields'][$k] ?? [];
		$validations = $fieldInfo['validations'] ?? '';
		$caption = $fieldInfo['caption'] ?? $k;
		if (trim($validations) == '') return "";
		$vError = "";
		$lines = explode(PHP_EOL, trim($validations));
		foreach ($lines as $line) {
			if (trim($line) == '') continue;
			$parts = explode("::", $line);
			$rule = trim($parts[0] ?? '');
			$param = strtoupper(trim($rule));
			$value = trim($parts[1] ?? '');
			$errMsg = trim($parts[2] ?? '');
			
			if ($errMsg == '') $errMsg = "{$value} validation failed";
			// Handle MINV / MAXV
			if (($param === 'MINV' || $param === 'MAXV') && (!isFoundIn($fieldInfo['tags'],"No Edit") or $_GET['action']=='new')) {
				if (!is_numeric($v)) {
					$vError .= "<li><i class='bi bi-exclamation-triangle-fill'></i> {$caption} should be numeric</li>";
					continue;
				}
				$limit = floatval($value); // min / max limit in jscript 2 param
				$val = floatval($v); // actual value
				if ($param === 'MINV' && $val < $limit) {
					$vError .= "<li><i class='bi bi-exclamation-triangle-fill'></i> {$caption}: {$errMsg} (Min: {$limit})</li>";
				}
				if ($param === 'MAXV' && $val > $limit) {
					$vError .= "<li><i class='bi bi-exclamation-triangle-fill'></i> {$caption}: {$errMsg} (Max: {$limit})</li>";
				}
				continue;
			}
			if ($param === 'REGX') {
			    // Trim value fully
			    $pattern = trim($value);
			    if (!preg_match($pattern, $v) && (!isFoundIn($fieldInfo['tags'], "No Edit") || $_GET['action'] == 'new')) {
			        $vError .= "<li>{$caption}: {$errMsg}</li>";
			    }
			    continue; // skip eval
			}
			// Replace [field] placeholders with posted values
			$rule = str_replace("[TODAY]",date('Y-m-d'),$rule);
			$rule = str_replace("[EQ]","=",$rule);
			$rule = str_replace("[EE]","==",$rule);
			$rule = str_replace("[GT]",">",$rule);
			$rule = str_replace("[LT]","<",$rule);
			$rule = str_replace("[LE]","<=",$rule);
			$rule = str_replace("[GE]",">=",$rule);
			$rule = str_replace("[NE]","!=",$rule);
			$rule = str_replace("null","",$rule);
			$rule = str_replace("NULL","",$rule);
		
			$expr = $rule;// for > < = >= <=
			foreach ($_POST as $pk => $pv) {
				//$expr = str_replace("[$pk]", var_export($pv, true), $expr);
				$expr = str_replace("[$pk]", $pv, $expr);
			}
			try {
				$result = eval("return ($expr);");
			} catch (Throwable $e) {
				$vError .= "<li><i class='bi bi-exclamation-triangle-fill'></i> ".$rule.$value."hhhh".$v."Error in validation rule for {$caption}</li>";
				continue;
			}
			// Validation failed (expression false)
			if (!$result) {
				$vError .= "<li><i class='bi bi-exclamation-triangle-fill'></i> {$caption}: {$errMsg}</li>";
			}
		}
		// Just return errors to display — don't stop save
		return $vError;
	}
	
	function formvalidations($activeSession) {
	    $errors = '';
	    $validations = $activeSession['head']['validations'] ?? '';
	    if (trim($validations) === '') return '';
	    $lines = explode(PHP_EOL, trim($validations));
	    foreach ($lines as $line) {
	        $parts = explode('::', $line, 2);
	        if (count($parts) < 2) continue;
	        $rulePart = trim($parts[0]);
	        $errMsg   = trim($parts[1]);
	        preg_match("/'\[(.*?)\]'\s*\[(.*?)\]\s*'(.*?)'/", $rulePart, $matches);
	        if (count($matches) !== 4) continue;
	        $field  = $matches[1];
	        $op     = $matches[2];
	        $expect = $matches[3];
	        if (!isset($_POST[$field])) continue;
	        $actual = trim($_POST[$field]);
	        $expectedField = trim($expect, "[]");
	        if (isset($_POST[$expectedField])) {
	            $expectedValue = trim($_POST[$expectedField]);
	        } else {
	            $expectedValue = trim($expect);
	        }
	        /* ---- DATE / NUMBER / STRING NORMALIZATION ---- */
	        $isDate =
	            preg_match('/^\d{4}-\d{2}-\d{2}$/', $actual) &&
	            preg_match('/^\d{4}-\d{2}-\d{2}$/', $expectedValue);
	
	        if ($isDate) {
	            $actualVal   = strtotime($actual);
	            $expectedVal = strtotime($expectedValue);
	        } else {
	            $actualVal   = is_numeric($actual) ? (float)$actual : $actual;
	            $expectedVal = is_numeric($expectedValue) ? (float)$expectedValue : $expectedValue;
	        }
	        /* ---- OPERATOR CHECK ---- */
	        switch ($op) {
	            case 'EE':
	                $match = ($actualVal == $expectedVal);
	                break;
	            case 'NE':
	                $match = ($actualVal != $expectedVal);
	                break;
	            case 'GT':
	                $match = ($actualVal > $expectedVal);
	                break;
	            case 'LT':
	                $match = ($actualVal < $expectedVal);
	                break;
	            case 'GE':
	                $match = ($actualVal >= $expectedVal);
	                break;
	            case 'LE':
	                $match = ($actualVal <= $expectedVal);
	                break;
	            default: continue 2;
	        }
	        /* ---- COLLECT ERROR (DO NOT RETURN) ---- */
	        if ($match) {
	            $errors .= "<li><i class='bi bi-exclamation-triangle-fill'></i>".$errMsg ?: "{$field} validation failed </li>";
	        }
	    }
	    return trim($errors);
	}
	function is_table_exist($table) {
	    $sql = "selrec table_name, table_rows from  information_schema.tables where table_schema = DATABASE() and (table_name = '".$table."' or table_name = 'park_".$table."' )";
    	$ds = getValueForPS($sql);
    	// printr($ds);
	    if (isset($ds['TABLE_NAME']) and $ds['TABLE_NAME']!=""){
	    	return true;
	    }else{
	    	return false;
	    }
	}	
	
	function limitStringTo($str,$limit){
		//  limitStringTo : Limits the input string to the max length and puts ... characters
		// $str : string
		// $limit : limit value
		if($str!=''){
			if(strlen($str)>$limit) $str=nl2br(substr(htmlspecialchars_decode($str),0,$limit)."..");
			return $str;
		}
	}
	function deleteTempFiles(){
		$interval = strtotime('-10 minutes');//files older than 1hours
		// getting all files from the temp directory.
		foreach (glob(TEMP_DIR."*") as $file) {
			//delete if older
			if(strpos($file,".php")>0 or strpos($file,"transparent")>0){ // php and transparent files are not deleting. Transparent we are using for signature control.
			}else{
				if (filemtime($file) <= $interval )  @unlink($file); // deleting the file.
			}
		}
		
		// For multi login we are creating the sess_<session_id>.txt files.
		// We are removing the fiels from context folder if the created date is greater than 2 days.
		$fileList = glob('sess_*.txt');
		foreach($fileList as $filename){
	   		$today = date('Y-m-d');
	   		$ctearedDate = date ("Y-m-d.", filemtime($filename));
	   		$diff = lapsedDays($ctearedDate,$today);
	   		if($diff>2) @unlink($filename); // deleting the session file.
	   	}
 	}
	function img($name,$title="",$tags=""){
		//  Description : Returns the image link
		//  $img 	: The name of the image file name with extension
		//  $title 	: the mouse over title to be displayed
		//  $tags 	: The style tags if any which are required inside the image tag
		if(file_exists("res/images/".$name)) {
			return "<img src='res/images/".$name."' border=0 title='".getDict($title)."' ".$tags.">";//xxdata-toggle='tooltip' xxdata-placement='bottom' 
		}
	}
	function toast($message,$color="primary"){
		return "<div class='border-5 alert alert-".$color." border-0 border-start border-".$color." m-1 p-2'>".nl2br($message)."</div>";
		
	}
	
	function toastTrans($message,$color="primary"){
	return "<div class='text-white border-5 alert alert-".$color." border-0 border-start border-".$color." m-1 p-2' style='background-color: transparent; border-color: rgba(0, 0, 0, 0.5);'>".nl2br($message)."</div>";


	}
	function anc($params,$caption,$target="",$styleTags="",$progress="",$legend="",$licence="",$jscript=""){
		//  anc : anchor tag function
		//  $params  : the actual url php page with parameters
		//  $caption : The image or the caption
		//  $target  : Target Frame
		//  $styleTags  : This string is as it is embedded
		//  $progress  : The message to be shown in the progress bar
		//  $legent : Used in case if the user does not want anchor images and wants as a legend. (Full or first char
		//printr($params);//exit;
		$params.="&encseed=".substr(session_id(),2,10);

		if($licence!=''){
			if(!getLicences($licence))return ;
		}
		if($progress=="") $progress="Please wait while fetching data";
		if(isFoundIn($params,"?")) return " '?' found in params; try with & ";  //  Help user if by mistake he sends ? in params which is generally must be & sign as do.php is already catenated....
		if($target!="") $target = " target='".$target."' ";							//  If target is given Set tagrget for the anchor tag
		if($target=="") $target = " target='' ";							//  If target is given Set tagrget for the anchor tag
		$tags = $tags ?? "";
		if($tags=='') $tags=" target='' style='text-decoration:none;'"; 					//  If styleTags is given Set styleTags for the anchor tag
		$href="?".pw_enc($params);
		//  The link is created as a onclick event which also activates the progress bar
		// define("ANCHORTYPE","LEGEND");	
		$ancType="LEGEND";
		$url = "<pbanc xxdata-toggle='tooltip' xxdata-placement='bottom' ".$target." xref='".$href."' ".$styleTags." onClick='".$jscript." anchorClick(this.getAttribute(\"target\"),this.getAttribute(\"xref\"));showProgress(\"".$progress."\");'>".$caption."</pbanc>";  	//  Build final anchor string
		if(($ancType!="NORMAL" or $ancType!="") and $legend!=""){
			$clas = "anclegend";
			$xsty = "margin: 2px;border-radius: 3px;font-size: 11px;border: 1px #666 solid;padding-right: 6px;padding-left: 6px;background-color: #ddd;color: #555;";
			if($ancType=="FIRST")$legend=substr($legend,0,1);
			if($legend=="Legend")$legend=$caption;
			if($legend=="Legend Green"){
				$legend=$caption;
				$clas = "btn butgreen";
				$sty = "";
			}
			if($legend=="Legend Red"){
				$legend=$caption;
				$clas = "btn butred";
				$sty = "";
			}
			if($legend=="Legend Blue"){
				$legend=$caption;
				$clas = "btn butblue";
				$sty = "";
			}
			if($legend=="Char")$legend=substr($caption,0,1); 
			$url = "<pbanc ".$target." xref='".$href."' ".$styleTags." onClick='anchorClick(this.getAttribute(\"target\"),this.getAttribute(\"xref\"));showProgress(\"".$progress."\");'><span class='".$clas."' style='".$sty."' title='".getDict($caption)."'>".$legend."</span></pbanc>";  	//  Build final anchor stringbtn butgreen
		}
		// if($_SESSION['designmode'] and isFoundIn($params,"CORE")) $mycore="<div style='height:12px;font-size:8px;padding:2px;cursor:hand;pointer:hand;display:inline;background-color:#f00;color:#fff;border-radius: 4px;' title='Reference to direct version page has .. DD - error'>DD</div>&nbsp;";
		$mycore = $mycore ?? "";
		return $mycore.$url;  // return the anchor string
	}
	
	// from google vision - image	
	function extractTextFromImage($imagePath){
	    // Check if the file exists

	    if (!file_exists($imagePath)) {
	        return "Error: Image file not found.";
	    }
	
	    // Read the image file and encode it to base64
	    $imageData = file_get_contents($imagePath);
	    $base64Image = base64_encode($imageData);
	
	    // Google Vision API endpoint

	    $url = "https://vision.googleapis.com/v1/images:annotate?key=AIzaSyCsUZtMLnNesunxlQhkkJu1cpSwQzbyTno";
	
	    // Request payload (corrected format)
	    $payload = json_encode([
	        "requests" => [
	            [
	                "image" => ["content" => $base64Image],
	                "features" => [["type" => "TEXT_DETECTION", "maxResults" => 1]]
	            ]
	        ]
	    ]);
	
	    // Initialize cURL
	    $ch = curl_init($url);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_POST, true);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
	
	    // Execute request and capture response
	    $response = curl_exec($ch);
	    curl_close($ch);
	    $result = json_decode($response, true);
	    if (isset($result["error"])) {
	        return "Error: " . $result["error"]["message"];
	    }
	
	    // Extract and return text if available
	    if (isset($result["responses"][0]["textAnnotations"][0]["description"])) {
	        return $result["responses"][0]["textAnnotations"][0]["description"];
	    } else {
	        return "No text found in image.";
	    }
	}
	function getGPTresponse($prompt,$question){
	//	printr($_SESSION);
		//$api_key = $_SESSION['PW_CONSTANTS']['GPT_API_KEY'];  //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
		//$model = $_SESSION['PW_CONSTANTS']['GPT_API_MODEL']; //gpt-3.5-turbo
		$api_key = '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';
		$model = 'gpt-3.5-turbo';
		$temperature = 0.7;
		$maxTokens = 1000;
		$topP = 1;
		$presencePenalty = 0;
		$data = array(
		    'model' => $model,
		    'messages' => array(
		        array('role' => 'system', 'content' =>$prompt.":\n\n".$question),
		    )
	    );
		//$response = curl_exec($ch);
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/chat/completions");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Authorization: Bearer " . $api_key));
		
		$response = curl_exec($ch);
		$jsonResponse = json_decode($response, true);
		$choices=($jsonResponse['choices'][0]['message']['content']);
		$choicesArr=json_decode($choices,true);
		printr($choicesArr);
		return $choicesArr;
		exit;
	}	

	function getFastCombo($name,$values,$displays,$default="",$tags=""){
		//  getFastCombo : Creates a combo for given name, values and display with defined javascript
		//  $name : string : name and id attribute value for select tag
		//  $values : string :  Option values given as a comma separated, 
		//  $displays : string : Option Display values given as a comma separated
		//  $default : sring :Default value for the combo
		//	$tags : string : attributes or javascript evente of the select tag
		//  Returns : string : a combo
		
		$name = str_replace(" ","_",$name);
		$str= "<select  name='".$name."' class='form-select' id='".$name."' ".$tags."  secu=1>";
        $valuesArray   = explode(",",$values);
        $displaysArray = explode(",",$displays);
		$kount = count($valuesArray);
        for ($i=0;$i<$kount;$i++){
			$selected = "";
			if($default!='' and $default==$valuesArray[$i])$selected = " selected ";
			$value = ""; //  To check mandatory
			if($valuesArray[$i])$value = $valuesArray[$i]."::".pw_enc($valuesArray[$i]);
            $str .= "<option value='".$value."' ".$selected.">".$displaysArray[$i]."</option>";
        }
        $str .= "</select>";
		if (isFoundIn(strtoupper(str_replace(" ","",$tags)),"ISREQUIRED"))$str.= "&nbsp;".img("mandatory2.png","Mandatory");	
        return $str;
    }
    
    function sql2Combo($sql,$name,$default,$tags=""){
		//  sql2Combo : Creates a combo for sql with defined javascript
		//  getFastCombo : Creates a combo for given name, values and display with defined javascript
		//  $name : string : name and id attribute value for select tag
		//  $default : string :Default value for the combo
		//	$tags : string : attributes or javascript evente of the select tag
		//  Returns : string : a combo
		$name = str_replace(" ","_",$name);
		$Array = explode(";",$sql);
        $sql   = $Array[0];
        $Array[1] = $Array[1] ?? "";
		$str   = "<select id='".$name."' name='".$name."' class='form-select' ".$tags."  secu=1><option value=''>Select ...</option>"; 
		if($default==$Array[1]) $selected=' selected ';
		if($Array[1]) $str .="<option value='".trim($Array[1])."::".pw_enc(trim($Array[1]))."' ".$selected.">".trim($Array[1])."</option>";
		$rs  = PW_sql2rsPS($sql);
		while($ds = PW_fetchArray($rs)){
			$selected="";
			$_SESSION['clientenddata'][$name] = $_SESSION['clientenddata'][$name] ?? '';
			$_SESSION['clientenddata'][$name].=$ds[PW_field_name($rs,0)]."//";  //  for browser debug safety feature
			if ($default==$ds[PW_field_name($rs,0)]) $selected=' selected ';
			$value = "";   //  To check mandatory
			if($ds[PW_field_name($rs,0)])$value = $ds[PW_field_name($rs,0)]."::".pw_enc($ds[PW_field_name($rs,0)]);
			$str.="<option value='".$value."' ".$selected.">".$ds[PW_field_name($rs,1)]."</option>";
		}
		$str.="</select>";
		if (isFoundIn(strtoupper(str_replace(" ","",$tags)),"ISREQUIRED"))$str.= "&nbsp;".img("mandatory2.png","Mandatory");		
		return($str);
	}
	
	function fw_input($name="",$tags="",$value="",$typ=""){
		// for input tags
		// $name : input tag name and id
		// $tags :  input tags eg: readonly or onblur or javascript functions
		// $value :input value
		// $typ : if typ is null the mandatory icon will display after the textbox otherwise it will display before the textbox
		$name = str_replace(" ","_",$name);
		if (isFoundIn($tags," value=") and $_SESSION['designmode']=="on") fw_output( displayError("Error","PW: ".$_REQUEST['pw']."<br>rty: ".$_REQUEST['rty']."<br>Control: ".$name."<br>Value tag in fw_input Err:1099 ",1));  
		if (isFoundIn($tags,"readonly")>0 or isFoundIn($tags,"hidden")>0){
			$encname = $name;
			if(isFoundIn($encname,"txt_")) $encname = str_replace("txt_","",$encname);
			$_SESSION['formTagOpen'] = $_SESSION['formTagOpen'] ?? "";
			if($value)$encvalue = pw_enc($value,$_SESSION['formTagOpen'],"enc_".$encname); //  encrypting the value in enc tag
			$encvalue = $encvalue ?? '';
			$enc="<input type='hidden' name='enc_".$encname."' id='enc_".$encname."' secu=1 value='".$encvalue."'/>"; //  Exempt from code check
		}
		//	Appending mandatory icon to the text box if the input tagged as required
		$mand = "";
		if (isFoundIn(strtoupper(str_replace(" ","",$tags)),"ISREQUIRED")){ 
			$mand = "&nbsp;".img("mandatory2.png","Mandatory");
		}
		$idStr = " id='".$name."' ";
		if(isFoundIn($tags," id=")) $idStr = "";
		if( (isFoundIn($name,"100") and isFoundIn($name,"_")) or (isFoundIn($value,"100") and isFoundIn($value,"_")) or (isFoundIn($value,"_pb_")) ){
			$_REQUEST['rty'] = $_REQUEST['rty'] ?? "";
			/*if($_SESSION['designmode']=="on" and $_REQUEST['rty']!="editdata") fw_output(img("red.png","Direct ID / Obj Name sent to client::  Name:".$name.", Value:".$value,"style='width:99%;height:3px;margin:1px;'"));
			if($_REQUEST['rty']!="editdata" and substr($name,0,4)<>'box1') watchDog("Direct ID Used : 1599","Record--- Ids directly without encryption","Error","",2,__FILE__,__LINE__,__CLASS__,__METHOD__);*/
			if($_SESSION['designmode']=="on" and $_GET['rty']!="fulledit") fw_output(img("red.png","Direct ID / Obj Name sent to client::  Name:".$name.", Value:".$value,"style='width:99%;height:3px;margin:1px;'"));
			if($_GET['rty']!="fulledit" and substr($name,0,4)<>'box1') watchDog("Direct ID Used : 1599","Record--- Ids directly without encryption","Error","",2,__FILE__,__LINE__,__CLASS__,__METHOD__);
		}	
		$enc = $enc ?? "";
		if($typ=='')return "<input spellcheck='true' tg='yy".$name."' ".$tags." name='".$name."' secu=1 ".$idStr ." value='".$value."'/>".$mand.$enc; //  Exempt from code check
		if($typ!='')return $mand."&nbsp;<input tg='xx".$name."' spellcheck='true' ".$tags." secu=1 name='".$name."' ".$idStr ." value='".$value."'/>".$enc; //  Exempt
	}
	
	function PW_addField($table="",$field="",$bypassError=0){
		//  PW_addFields - Created some standard fields to each table
		//  $table : Table name
		//  $field : the field name includes the type definition say code varchar(30);
		PW_execute("ALTTAB TABLE ".$table." ADD COLUMN ".$field,$bypassError);
		//frameworkChanges("Table Changes",$field,"",$field,"","New Field Added",$table);
	}
	
	function replaceSplChar(&$valArray){
		foreach($valArray as $k => $val){
			$aa = str_split($val,1);
			$x = 0;
			$temp = array();
			foreach($aa as $v){
				$asci = ord($v);
				// asci 63 means "?" if not found the charecter the csv file is treated as a "?" so we are removing the "?"
				if(($asci>=32 and $asci<=126 and $asci!=63)){
					$temp[] = $v;
					$x++;
				}
			}
			if($x>0){
				$valArray[$k] = implode("",$temp);	
			}
		}
	}	
	function download2Temp($encfile,$newFile){
		$ext  = ".".strtolower(pathinfo(basename($encfile), PATHINFO_EXTENSION));
		if($newFile==''){
			$ext = "";
			$newFile = $encfile;
		} 
		$encfile = getUploadFilePath($encfile);
		// echo "---xx".UPLOAD_DIR_PATH.$encfile."xx---";
		if(file_exists(UPLOAD_DIR_PATH.$encfile)){
			copy(UPLOAD_DIR_PATH.$encfile,TEMP_DIR.$newFile.$ext);
		}
		return TEMP_DIR.$newFile.$ext;
	}
	
	function setcurrentpageSession($pgid){
		$newCurrentPage=array();
		$newCurrentPage['head']=getValueforPS("selrec * from _pb_pagehead where pgid=? limit 0,1","s",$pgid);
		// may be the form is deleted and stoll there innthe top links or menus
		if(!isset($newCurrentPage['head']['pgid'])){
			echo toast("Form (".$pgid.") not found.. May be deleted pelase check","danger");
			exit;
		};
		// push fields tto session
		$sql="selrec * from _pb_pagefields where pgid=? and status=? order by slno"; 
		$rs  = PW_sql2rsPS($sql,"ss",$newCurrentPage['head']['pgid'],'Active');
		$newCurrentPage['meta']['searchfields']=array();
		while ($ds = PW_fetchAssoc($rs)) {
			$newCurrentPage['fields'][$ds['fieldname']]=$ds;
			$tags=strtoupper($newCurrentPage['fields'][$ds['fieldname']]['tags']??'');
			if(isFoundIn($tags,"IS SEARCHABLE")){
				$newCurrentPage['meta']['searchfields'][]=$ds['fieldname'];
			} 
		}
		
		// push links to session
		$sql="selrec * from _pb_pagelinks where pgid=? and status=? order by slno";
		$rs  = PW_sql2rsPS($sql,"ss",$newCurrentPage['head']['pgid'],'Active');
		while ($ds = PW_fetchAssoc($rs)) {
			$newCurrentPage['links'][$ds['id']]=$ds;
		}
		if (isset($newCurrentPage['listfields'])) {
			$newCurrentPage['listfields']=str_replace(" ","",$newCurrentPage['listfields']);
		}
		$newCurrentPage['meta']['sortfield']=$_SESSION['currentpage']['head']['sortorder']??null;
		if(isset($newCurrentPage['head']['listfields']) && !empty($newCurrentPage['head']['listfields']))$newCurrentPage['meta']['colids']=explode(",",$newCurrentPage['head']['listfields']);
		return $newCurrentPage;
	}
	
	function setcurrentpageSession1($pgid){
		$newCurrentPage=array();
		$newCurrentPage['head']=getValueforPS("selrec * from _pb_pagehead where pgid=? limit 0,1","s",$pgid);
		// push fields to session
		$sql="selrec * from _pb_pagefields where pgid=? and status=? order by slno"; 
		$rs  = PW_sql2rsPS($sql,"ss",$newCurrentPage['head']['pgid'],'Active');
		$newCurrentPage['meta']['searchfields']=array();
		while ($ds = PW_fetchAssoc($rs)) {
			$newCurrentPage['fields'][$ds['fieldname']]=$ds;
			$tags=strtoupper($newCurrentPage['fields'][$ds['fieldname']]['tags']??'');
			if(isFoundIn($tags,"IS SEARCHABLE")){
				$newCurrentPage['meta']['searchfields'][]=$ds['fieldname'];
			} 
		}
		
		// push links to session
		$sql="selrec * from _pb_pagelinks where pgid=? and status=? order by slno";
		$rs  = PW_sql2rsPS($sql,"ss",$newCurrentPage['head']['pgid'],'Active');
		while ($ds = PW_fetchAssoc($rs)) {
			$newCurrentPage['links'][$ds['id']]=$ds;
		}
		if (isset($newCurrentPage['listfields'])) {
			$newCurrentPage['listfields']=str_replace(" ","",$newCurrentPage['listfields']);
		}
		$newCurrentPage['meta']['sortfield']=$_SESSION['currentpage']['head']['sortorder']??null;
		if(isset($newCurrentPage['head']['listfields']) && !empty($newCurrentPage['head']['listfields']))$newCurrentPage['meta']['colids']=explode(",",$newCurrentPage['head']['listfields']);
		return $newCurrentPage;
	}	
	function toastfade($msg, $div = "mainContent", $delay = 0, $duration = 2) {
		echo "
		<script>
			setTimeout(() => showMsgInside('".$div."', 5, 5, '".$msg."', 3, ".$duration."), ".($delay * 1000).");
		</script>
		";
	}
	
	function json2table($arr){
    if (empty($arr) || !is_array($arr)) {
        echo "<div class='text-muted'>No data</div>";
        return;
    }

    echo "<table class='table table-bordered table-sm table-striped'>";

    // Table Head
    echo "<thead><tr>";
    foreach(array_keys($arr[0]) as $col){
        echo "<th>" . htmlspecialchars($col) . "</th>";
    }
    echo "</tr></thead>";

    // Table Body
    echo "<tbody>";
    foreach($arr as $row){
        echo "<tr>";
        foreach($row as $val){
            echo "<td>" . htmlspecialchars($val) . "</td>";
        }
        echo "</tr>";
    }
    echo "</tbody>";

    echo "</table>";
} 




	function json_to_bulleted_text($json, array $opt = []): string{
	    $opt = array_merge([
	        "bullet" => "•",          // bullet for normal lines
	        "sub_bullet" => "◦",      // bullet for deeper lines (optional)
	        "indent" => "  ",         // indentation unit
	        "key_value_sep" => ": ",  // separator between key and value
	        "list_label" => "-",      // optional label for list items (used when key is numeric)
	        "sort_keys" => false,     // sort associative keys alphabetically
	        "max_depth" => 50,        // stop runaway recursion
	        "max_items" => 20000,     // stop runaway output on huge data
	        "show_types" => false,    // append type info for scalars
	    ], $opt);
	
	    // Decode if a JSON string is passed
	    if (is_string($json)) {
	        $trim = trim($json);
	        if ($trim !== "" && ($trim[0] === "{" || $trim[0] === "[")) {
	            $decoded = json_decode($json, true);
	            if (json_last_error() !== JSON_ERROR_NONE) {
	                return $opt["bullet"] . " Invalid JSON: " . json_last_error_msg();
	            }
	            $data = $decoded;
	        } else {
	            // Treat as plain string
	            $data = $json;
	        }
	    } else {
	        // Array/object already
	        $data = $json;
	        if (is_object($data)) $data = json_decode(json_encode($data), true);
	    }
	
	    $lines = [];
	    $counter = 0;
	
	    $walk = function ($node, int $depth, $key = null) use (&$walk, &$lines, &$counter, $opt) {
	        if ($depth > $opt["max_depth"]) {
	            $lines[] = str_repeat($opt["indent"], $depth) . $opt["bullet"] . " [max_depth reached]";
	            return;
	        }
	        if ($counter >= $opt["max_items"]) {
	            $lines[] = str_repeat($opt["indent"], $depth) . $opt["bullet"] . " [max_items reached]";
	            return;
	        }
	
	        $bullet = ($depth <= 1) ? $opt["bullet"] : $opt["sub_bullet"];
	        $pad = str_repeat($opt["indent"], $depth);
	
	        // Helper to format scalar
	        $fmt = function ($v) use ($opt) {
	            if (is_null($v)) return "null";
	            if (is_bool($v)) return $v ? "true" : "false";
	            if (is_string($v)) return $v === "" ? '""' : $v;
	            if (is_int($v) || is_float($v)) return (string)$v;
	            return "[unknown]";
	        };
	
	        // Determine node kind
	        if (is_array($node)) {
	            $isList = array_keys($node) === range(0, count($node) - 1);
	
	            // Print heading line for key (if any)
	            if ($key !== null) {
	                $label = is_int($key) ? $opt["list_label"] : (string)$key;
	                $lines[] = $pad . $bullet . " " . $label;
	                $counter++;
	            } else if ($depth === 0) {
	                // root array/object header is optional; omit for clean output
	            }
	
	            // Optionally sort associative keys
	            if (!$isList && $opt["sort_keys"]) {
	                $keys = array_keys($node);
	                sort($keys, SORT_NATURAL | SORT_FLAG_CASE);
	                $sorted = [];
	                foreach ($keys as $k) $sorted[$k] = $node[$k];
	                $node = $sorted;
	            }
	
	            // Recurse children
	            foreach ($node as $k => $v) {
	                if ($counter >= $opt["max_items"]) break;
	
	                if (is_array($v)) {
	                    $walk($v, $key === null ? $depth : $depth + 1, $k);
	                } else {
	                    $label = is_int($k) ? $opt["list_label"] : (string)$k;
	                    $value = $fmt($v);
	
	                    $typeSuffix = "";
	                    if ($opt["show_types"]) {
	                        $typeSuffix = " (" . gettype($v) . ")";
	                    }
	
	                    // If key is numeric list item, print bullet + value only
	                    if (is_int($k)) {
	                        $lines[] = str_repeat($opt["indent"], ($key === null ? $depth : $depth + 1))
	                            . $bullet . " " . $value . $typeSuffix;
	                    } else {
	                        $lines[] = str_repeat($opt["indent"], ($key === null ? $depth : $depth + 1))
	                            . $bullet . " " . $label . $opt["key_value_sep"] . $value . $typeSuffix;
	                    }
	                    $counter++;
	                }
	            }
	        } else {
	            // Scalar root or scalar under numeric key
	            $value = $fmt($node);
	            $typeSuffix = $opt["show_types"] ? " (" . gettype($node) . ")" : "";
	            if ($key === null) {
	                $lines[] = $pad . $bullet . " " . $value . $typeSuffix;
	            } else {
	                $label = is_int($key) ? $opt["list_label"] : (string)$key;
	                $lines[] = $pad . $bullet . " " . $label . $opt["key_value_sep"] . $value . $typeSuffix;
	            }
	            $counter++;
	        }
	    };
	
	    $walk($data, 0, null);
	
	    // Clean up: remove any empty lines
	    $lines = array_values(array_filter($lines, fn($l) => trim($l) !== ""));
	    return implode("\n", $lines);
	}

	function replaceDefaults_x($str){
		return $str;
	}
	
	function debugtip(){
		if($_SESSION['designmode']=="on"){
		$tip=$_GET;
		$tip['bqkey']="";
		$temp=json_encode($tip);
		$temp=str_replace(",","\n",$temp);
	    return "<div style='z-index:9000;position:absolute;top:0px;right:0px;height:10px;width:100%' title='".$temp."'></div>";
		}
	}
	
	function insertChildLink($parentds=array(),$new=""){
		//  insertChildLink : Line link inserted for the given pageform	
		//  Building dataset array to insert the link.
		//  $_REQDATA : Request Data
		$pageDs = getValueForPS("selrec id,tags,caption from _pb_pagehead where pgid=?","s",$_GET['pgid']);
		$ds = array();
		$ds['slno'] 	= "10";
		$ds['pgid'] 	= $_GET['hpg']; // parent form
		$ds['caption'] 	= str_replace("Standard ","",$pageDs['caption']);
		$ds['url'] 		= $_GET['pgid'];
		$ds['linktype'] = "List Edit Link";
		if($new=="New") {
			$ds['url'] 		= $_GET['hpg']."_".$_GET['pgid'];
			$ds['caption'] 	= str_replace("Standard ","",$pageDs['caption'])."_".$_GET['pgid'];
		}	
		$ds['tags']		= "Is a Button";
		$ds['status'] 	= "Active";
		$ds['role']		= "Admin";
		$ds['linkedto'] = "_pb_pagehead";
		$ds['linkedid'] = $parentds['id'];
		$ds['licence']  = "Temporary";
		insertRecord($ds,"_pb_pagelinks");
		$tags = $pageDs['tags'];
		if(isFoundIn($tags,"Direct Landing")){
			$tags = str_replace("Direct Landing","",$tags);
			$tags = str_replace("~~","~",$tags);
		}
		if($new=="New")insertNewChildForm();  // if user wants a new form let us create that from standard form
		hsc(toastfade("Link Inserted Successfully","editPanel"));
	}
	
	function insertNewChildForm(){
		$oldFormDS = array();
		$headtablename = "";
		$headtablename = getValueForPS("selrec tablename from _pb_pagehead where pgid=?","s",$_GET['hpg']);
		$oldFormDS = getValueForPS("selrec * from _pb_pagehead where pgid=?","s",$_GET['pgid']);
		$oldFormDS['pgid']= $_GET['hpg']."_".$_GET['pgid'];
		$tags = $oldFormDS['tags'];
		if(isFoundIn($tags,"Direct Landing")){
			$tags = str_replace("Direct Landing","",$tags);
			$tags = str_replace("~~","~",$tags);
		}
		if (isFoundIn($tags, "Is child form")) {
		    $tags = str_replace(["~Is child form", "Is child form"], "", $tags);
		    $tags = trim($tags, "~");          
		    $tags = preg_replace('/~+/', '~', $tags);  
		}
		$oldFormDS['tags'] = $tags;
		$oldFormDS['deletelinks'] = 'Nill';
		//printr("Pagehead");
	//	printr($oldFormDS['tags']);exit;
		insertRecord($oldFormDS,"_pb_pagehead");
	    $oldId = $_SESSION['lastinsertedid'];
	    $fldsSql= ""; 
	    $hasLinkedId = false; $parentcode = false;
		$hasLinkedTo = false; $parentname = false;
		$fldsSql	= "selrec * from _pb_pagefields where pgid=?";
		$fldsRes     = PW_sql2rsPS($fldsSql,"s",$_GET['pgid']);
		while($fldDs = PW_fetchAssoc($fldsRes)){
			if ($fldDs['fieldname'] === 'linkedid')  $hasLinkedId = true;
    		if ($fldDs['fieldname'] === 'linkedto')  $hasLinkedTo = true;
    		if ($fldDs['fieldname'] === 'parentcode') $parentcode = true;
    		if ($fldDs['fieldname'] === 'parentname') $parentname = true;
    		
			$fldDs['pgid']      = $oldFormDS['pgid'];
			if ($fldDs['fieldname'] === 'linkedid') {
			    //$fldDs['pbdefault'] = $oldId;
			    $fldDs['pbdefault'] = 'h:id';
			}
			if ($fldDs['fieldname'] === 'linkedto') {
			    //$fldDs['pbdefault'] = $headtablename;
			    $fldDs['pbdefault'] = 'hpg';
			}
			if($headtablename === '_pb_entity'){
				if ($fldDs['fieldname'] === 'parentcode') {
				    $fldDs['pbdefault'] = 'h:entitycode';
				}
				if ($fldDs['fieldname'] === 'parentname') {
				    $fldDs['pbdefault'] = 'h:entityname';
				}
			}
			if($headtablename === 'bq_project'){
				if ($fldDs['fieldname'] === 'parentcode') {
				    $fldDs['pbdefault'] = 'h:projectcode';
				}
				if ($fldDs['fieldname'] === 'parentname') {
				    $fldDs['pbdefault'] = 'h:projectname';
				}
			}
			if($headtablename === 'bq_assets'){
				if ($fldDs['fieldname'] === 'parentcode') {
				    $fldDs['pbdefault'] = 'h:assetcode';
				}
				if ($fldDs['fieldname'] === 'parentname') {
				    $fldDs['pbdefault'] = 'h:assetname';
				}
			}
			//printr("Pagefields");
			//printr($fldDs);
			insertRecord($fldDs,"_pb_pagefields");
		}
		if (!$hasLinkedId) {
			$flds = [];
			$flds['pgid']        = $oldFormDS['pgid'];
			$flds['fieldname']   = 'linkedid';
			$flds['caption']     = 'Linked ID';
			$flds['controltype'] = 'Read Only';
			$flds['pbdefault']   = 'h:id';
			$flds['tabname']     = 'XXX';
			$flds['status']      = 'Active';
			
			insertRecord($flds, "_pb_pagefields");
		}
		
		if (!$hasLinkedTo) {
			$flds = [];
			$flds['pgid']        = $oldFormDS['pgid'];
			$flds['fieldname']   = 'linkedto';
			$flds['caption']     = 'Linked To';
			$flds['controltype'] = 'Read Only';
			$flds['pbdefault']   = 'hpg';
			$flds['tabname']     = 'XXX';
			$flds['status']      = 'Active';
			
			insertRecord($flds, "_pb_pagefields");
		}
	}
	
	function delChildLink(){
		$count2 = getValueForPS("selrec count(id) from _pb_pagelinks where pgid=? and url=?","ss",$_GET['hpg'],$_GET['childlink']);
		//printr("selrec count(id) from _pb_pagelinks where pgid='".$_GET['hpg']."' and url='".$_GET['childlink']."'");exit;
		//echo $count2."count2<br>selrec count(id) from _pb_pagelinks where pgid='".$_GET['hpg']."' and url='".$_GET['childlink']."'";
		if($count2>0){
			PW_execute("delrec from _pb_pagelinks where pgid='".$_GET['hpg']."' and url='".$_GET['childlink']."'");
			PW_execute("delrec from _pb_pagefields where pgid='".$_GET['childlink']."'");
			PW_execute("delrec from _pb_pagehead where pgid='".$_GET['childlink']."'");
		}	
	}
	
	function Fw_Curl($url,$postValues='',$decode=1){ 
		// Description : Function used to get the data in JSON format from a given URL
		// $url : URL Request
		// $postValues : Post Values
		// $decode : default decode value is  1
		// Get cURL resource
		$curl = curl_init();
		// Set some options - we are passing in a useragent too here
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $url,
			CURLOPT_USERAGENT => 'cURL Request',
			CURLOPT_POST => 1,
			CURLOPT_POSTFIELDS => $postValues
		));
		// Send the request & save response to $resp
		$respJson = curl_exec($curl) or die("Curl Error".curl_error($curl)." - Err Curl-9255");
		
		if (curl_error($curl)) {
    		$error_msg = curl_error($curl);
    		printr($error_msg);
    		exit;
		}
		// Close request to clear up some resources
		curl_close($curl);
		if($decode==1){ $respJsonDecode = json_decode($respJson);
		}else  $respJsonDecode = $respJson;
		return $respJsonDecode;
	}
	function checkDesignMode(){
		if($_SESSION['designmode']=="on") return true;
		echo toast("Availble in design mode only.","danger");
		exit;
	}
	
	function prepend_to_file( $text) {
		$file=MESSAGELOG;
	    if (!is_file($file)) {
	        // If file doesn't exist, just create it
	        file_put_contents($file, $text);
	        return true;
	    }
	
	    // Read existing content
	    $current = file_get_contents($file);
	
	    // Prepend new text + newline
	    $new = $text . $current;
	
	    // Write back
	    return file_put_contents($file, $new) !== false;
	}
	
	function bq_ip_geo_lookup($ip) {
	    $info = [
	        "geo_city"    => "",
	        "geo_region"  => "",
	        "geo_country" => "",
	        "geo_lat"     => "",
	        "geo_lon"     => "",
	        "geo_org"     => "",
	        "geo_isp"     => ""
	    ];
	
	    if (!$ip) {
	        return $info;
	    }
	
	    // Simple external lookup – you can change provider if needed
	    $url = "http://ip-api.com/json/" . urlencode($ip) . "?fields=status,message,country,regionName,city,lat,lon,org,isp,query";
	    $ctx = stream_context_create([
	        "http" => [
	            "timeout" => 1.5
	        ]
	    ]);
	
	    $json = @file_get_contents($url, false, $ctx);
	    if ($json === false) {
	        return $info;
	    }
	
	    $data = json_decode($json, true);
	    if (!is_array($data) || ($data["status"] ?? "") !== "success") {
	        return $info;
	    }
	
	    $info["geo_city"]    = $data["city"]        ?? "";
	    $info["geo_region"]  = $data["regionName"]  ?? "";
	    $info["geo_country"] = $data["country"]     ?? "";
	    $info["geo_lat"]     = $data["lat"]         ?? "";
	    $info["geo_lon"]     = $data["lon"]         ?? "";
	    $info["geo_org"]     = $data["org"]         ?? "";
	    $info["geo_isp"]     = $data["isp"]         ?? "";
	
	    return $info;
	}
	
	function bq_domain_from_ip($ip) {
	
	    // Validate IP first
	    if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP)) {
	        return [
	            "domain_name" => "",
	            "domain_type" => "invalid_ip"
	        ];
	    }
	
	    // Reverse DNS lookup
	    $host = @gethostbyaddr($ip);
	
	    // If no PTR record exists → gethostbyaddr() returns the IP itself
	    if (!$host || $host === $ip) {
	        return [
	            "domain_name" => "",
	            "domain_type" => "no_domain"
	        ];
	    }
	
	    // A valid domain exists
	    return [
	        "domain_name" => $host,
	        "domain_type" => "full_domain"
	    ];
	}

	function getBarcode($name,$height='80',$width='320',$bottomName=1){
		// To generate barcode url
		// $name : It's the required string to create barcode
		// $height : height of the barcode
		// $width : width of the barcode
		return BARCODEURL.$name."&text=".$bottomName;
	}

	
	function get_full_request_meta_json() {
	
	    // --- derive IP ---
	    $remoteAddr    = $_SERVER['REMOTE_ADDR']          ?? '';
	    $forwardedFor  = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
	
	    if ($forwardedFor) {
	        $parts        = explode(',', $forwardedFor);
	        $forwardedFor = trim($parts[0]);
	    }
	
	    $realIp     = $forwardedFor ?: $remoteAddr;
	
	    // --- IP scope ---
	    $ipScope = "external";
	    if ($realIp &&
	        (str_starts_with($realIp, "10.") ||
	         str_starts_with($realIp, "192.168.") ||
	         preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $realIp))) {
	        $ipScope = "internal";
	    }
	
	    // --- domain lookup ---
	    $dom = bq_domain_from_ip($realIp);  // returns domain_name + domain_type
	
	    // --- reverse DNS for clarity ---
	    $reverseDns = $dom["domain_name"] ?: "";  // same meaning
	
	    // --- detect source ---
	    $ua      = $_SERVER['HTTP_USER_AGENT'] ?? "";
	    $uri     = $_SERVER['REQUEST_URI']     ?? "";
	    $source  = "web";
	
	    if (!empty($_SERVER['HTTP_HX_REQUEST']) ||
	        !empty($_SERVER['HTTP_HX_TARGET'])  ||
	        !empty($_SERVER['HTTP_HX_TRIGGER'])) {
	        $source = "beeq_htmx";
	    }
	
	    if (stripos($uri, "webhook") !== false || stripos($uri, "hook") !== false) {
	        $source = "webhook";
	    }
	
	    if (preg_match('/curl|PostmanRuntime|python-requests|okhttp|insomnia/i', $ua)) {
	        $source = "api_client";
	    }
	
	    // --- Geo IP lookup ---
	    $geo = bq_ip_geo_lookup($realIp);
	
	    // --- metadata array ---
	    $meta = [
	        "timestamp"        => date("Y-m-d H:i:s"),
	
	        "request_source"   => $source,
	
	        "ip"               => $remoteAddr,
	        "forwarded_for"    => $forwardedFor,
	        "real_ip"          => $realIp,
	        "ip_scope"         => $ipScope,
	
	        "domain_name"      => $dom["domain_name"],
	        "domain_type"      => $dom["domain_type"],
	        "reverse_dns"      => $reverseDns,
	
	        "geo_city"         => $geo["geo_city"],
	        "geo_region"       => $geo["geo_region"],
	        "geo_country"      => $geo["geo_country"],
	        "geo_lat"          => $geo["geo_lat"],
	        "geo_lon"          => $geo["geo_lon"],
	        "geo_org"          => $geo["geo_org"],
	        "geo_isp"          => $geo["geo_isp"],
	
	        "user_agent"       => $ua,
	        "request_method"   => $_SERVER['REQUEST_METHOD']        ?? "",
	        "protocol"         => $_SERVER['SERVER_PROTOCOL']       ?? "",
	        "https"            => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
	
	        "server_name"      => $_SERVER['SERVER_NAME']           ?? "",
	        "server_port"      => $_SERVER['SERVER_PORT']           ?? "",
	        "request_uri"      => $uri,
	        "script_name"      => $_SERVER['SCRIPT_NAME']           ?? "",
	        
	        "referrer"         => $_SERVER['HTTP_REFERER']          ?? "",
			"query_string"     => $_SERVER['QUERY_STRING']          ?? "",
	
	        "accept_lang"      => $_SERVER['HTTP_ACCEPT_LANGUAGE']  ?? "",
	        "accept"           => $_SERVER['HTTP_ACCEPT']           ?? "",
	        "content_type"     => $_SERVER['CONTENT_TYPE']          ?? "",
	        "content_length"   => $_SERVER['CONTENT_LENGTH']        ?? "",
	
	        "session_user"     => $_SESSION['user']                 ?? "",
	        "session_id"       => session_id(),
	
	        "cookies"          => $_COOKIE                         ?? []
	    ];
	
	    // --- build log text ---
	    $str = "";
	    foreach ($meta as $k => $v) {
	        if (is_array($v)) {
	            $v = json_encode($v, JSON_UNESCAPED_SLASHES);
	        }
	        $str .= $k . " => " . $v . "\n";
	    }
	
	    $str .= "====================================================\n\n";
	    return $str;
	}
	
	function bq_security_fail($reason = "Security violation") {
		// printr($_SESSION);
	    // --- 1. Log the incident ------------------------------------------
	    $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
	    $ua  = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
	    $uri = $_SERVER['REQUEST_URI'] ?? '';
	
	    $log = "[".date('Y-m-d H:i:s')."] SECURITY BLOCKED
		IP: $ip
		UA: $ua
		URI: $uri
		Reason: $reason
	-----------------------------------------------------\n"; 
	
	    // --- 2. Destroy session immediately -------------------------------
	    // if (session_status() === PHP_SESSION_ACTIVE) {
	    //     $_SESSION = [];
	    //     if (ini_get("session.use_cookies")) {
	    //         $params = session_get_cookie_params();
	    //         setcookie(session_name(), '', time() - 42000,
	    //             $params["path"], $params["domain"],
	    //             $params["secure"], $params["httponly"]
	    //         );
	    //     }
	    //     include_once("bq_utils_security.php");
	    //     $agentString = getFullAgentDetailsJSON();
	    //     echo "XX--XX";
	    //     @session_start();
	    //     $_SESSION['conn'] = PW_connect();
		   // if(isset($_GET['action']) && $_GET['action']!="cron") sendSecurityAlert("Direct attempt tried 73299 \n".date("y-m-d H:i:s"),$reason."\n\n".json_to_bulleted_text($agentString));
		   // //exit;
	    // }
	}
	
	function syncToMobileCalendar(string $datetimeIST, string $title, string $notes, int $durationMinutes = 30): void
	{
	    // MUST be first output: no spaces/BOM/echo before this function is called
	    if (ob_get_length()) { @ob_end_clean(); }
	
	    $tzIST = new DateTimeZone("Asia/Kolkata");
	    $tzUTC = new DateTimeZone("UTC");
	
	    $startIST = DateTime::createFromFormat("Y-m-d H:i:s", $datetimeIST, $tzIST);
	    if (!$startIST) {
	        http_response_code(400);
	        echo "Invalid datetime";
	        exit;
	    }
	
	    $endIST = clone $startIST;
	    $endIST->modify("+" . (int)$durationMinutes . " minutes");
	
	    $startUTC = clone $startIST; $startUTC->setTimezone($tzUTC);
	    $endUTC   = clone $endIST;   $endUTC->setTimezone($tzUTC);
	
	    $escape = function (string $s): string {
	        return str_replace(
	            array('\\', "\r\n", "\n", "\r", ',', ';'),
	            array('\\\\', '\\n', '\\n', '\\n', '\\,', '\\;'),
	            $s
	        );
	    };
	
	    $fmt = function (DateTime $d): string {
	        return $d->format("Ymd\\THis\\Z");
	    };
	
	    $uid = bin2hex(random_bytes(10)) . "@beeq";
	    $now = new DateTime("now", $tzUTC);
	
	    // $ics =
	    //     "BEGIN:VCALENDAR\r\n" .
	    //     "VERSION:2.0\r\n" .
	    //     "PRODID:-//BeeQ//Mobile Calendar Sync//EN\r\n" .
	    //     "CALSCALE:GREGORIAN\r\n" .
	    //     "METHOD:PUBLISH\r\n" .
	    //     "BEGIN:VEVENT\r\n" .
	    //     "UID:" . $uid . "\r\n" .
	    //     "DTSTAMP:" . $fmt($now) . "\r\n" .
	    //     "DTSTART:" . $fmt($startUTC) . "\r\n" .
	    //     "DTEND:" . $fmt($endUTC) . "\r\n" .
	    //     "SUMMARY:" . $escape($title) . "\r\n" .
	    //     "DESCRIPTION:" . $escape($notes) . "\r\n" .
	    //     "END:VEVENT\r\n" .
	    //     "END:VCALENDAR\r\n";
	
	$ics =
	    "BEGIN:VCALENDAR\r\n" .
	    "VERSION:2.0\r\n" .
	    "PRODID:-//BeeQ//Mobile Calendar Sync//EN\r\n" .
	    "CALSCALE:GREGORIAN\r\n" .
	    "METHOD:PUBLISH\r\n" .
	    "BEGIN:VEVENT\r\n" .
	    "UID:" . $uid . "\r\n" .
	    "DTSTAMP:" . $fmt($now) . "\r\n" .
	    "DTSTART:" . $fmt($startUTC) . "\r\n" .
	    "DTEND:" . $fmt($endUTC) . "\r\n" .
	    "SUMMARY:" . $escape($title) . "\r\n" .
	    "DESCRIPTION:" . $escape($notes) . "\r\n" .
	
	// 5 minutes before
	    "BEGIN:VALARM\r\n" .
	    "TRIGGER:-PT5M\r\n" .
	    "ACTION:DISPLAY\r\n" .
	    "DESCRIPTION:" . $escape($title) . "\r\n" .
	    "END:VALARM\r\n" .
	    
	    // 10 minutes before
	    "BEGIN:VALARM\r\n" .
	    "TRIGGER:-PT10M\r\n" .
	    "ACTION:DISPLAY\r\n" .
	    "DESCRIPTION:" . $escape($title) . "\r\n" .
	    "END:VALARM\r\n" .
	
	    
	
	    "END:VEVENT\r\n" .
	    "END:VCALENDAR\r\n";
	
	
	
	    $filename = "event-" . $startIST->format("Ymd-His") . ".ics";
	
	    header("Content-Type: text/calendar; charset=utf-8");
	    header("Content-Disposition: attachment; filename=\"$filename\"");
	    header("Content-Length: " . strlen($ics));
	    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
	    header("Pragma: no-cache");
	    header("X-Content-Type-Options: nosniff");
	
	    echo $ics;
	    exit;
	}

	function getFileContents($file){
		if (!is_readable($file)) {
		    http_response_code(404);
		    exit('File not readable');
		}
		$content = file_get_contents($file);
		return $content;
	}
	function callCodesegmentLink($dsPage){
		//  calling plx actions in codesegment
		//  $dsPage : page dataset
		if (file_exists("segment/".$dsPage['pgid'].".php")){ 
			include_once "segment/".$dsPage['pgid'].".php";
			$_REQDATA = $_GET;
			$_POSTDATA = $_POST;
			plx_linkActions($dsPage,$_REQDATA,$_POSTDATA);
		}
	}
	function reconfirm(string $title,string $content,array $_REQDATA = []){
		if (strtoupper($_REQDATA['confirm'] ?? '') === "YES") {
			return true; 
		}
		$actionUrl=$_SERVER["REQUEST_URI"];
		echo '<style>
			.htmx-indicator {
			    display: none;
			}
			.htmx-request .htmx-indicator {
			    display: inline-block;
			}
			</style>
		<div id="reconfirmdiv" class="card shadow-sm bs-alert-box border rounded p-1">
			<h6 class="card-header text-center fw-bold bg-secondary-subtle">'.$title.'</h6>
			<div class="text-center mb-2">'.$content.'</div>
				<div class="text-center">
					<span class="me-2">Please confirm</span><br><br>
					<button class="btn btn-primary btn-sm me-2" hx-get="'.$actionUrl.'&confirm=YES"	hx-indicator="#yesIndicator" hx-target="#reconfirmdiv" hx-swap="innerHTML" hx-on="click:document.getElementById(\'reconfirmdiv\').display=\'none\';">
					Yes<span id="yesIndicator" class="htmx-indicator ms-2">
    						<span class="spinner-border spinner-border-sm"></span>
						</span>	</button>
					
					<button class="btn btn-secondary btn-sm" hx-indicator="#noIndicator" hx-on="click:this.closest(\'.bs-alert-box\').remove()">
					No<span id="noIndicator" class="htmx-indicator ms-1"> 
    						<span class="spinner-border spinner-border-sm"></span>
						</span></button>
			</div>
		</div>';
		exit;
	}	

	function evaluateCondition($condition, $data, &$debug = []){
	    // Replace [field] with values
		$condition = preg_replace_callback('/\[([a-zA-Z0-9_]+)\]/', function ($m) use ($data) {
		$val = $data[$m[1]] ?? '';
		return is_numeric($val) ? $val : "'".addslashes($val)."'";
		}, $condition);
		$debug[] = "After field replace: $condition";
		return evaluateExpression($condition, $debug);
	}
	function evaluateExpression($expr, &$debug){
	    // Resolve parentheses first
		while (preg_match('/\(([^()]+)\)/', $expr, $m)) {
			$inner = $m[1];
			$res   = evaluateExpression($inner, $debug) ? 'true' : 'false';
			$expr  = str_replace("($inner)", $res, $expr);
			$debug[] = "Resolved ($inner) → $res";
		}
	    // Split AND / OR
		$tokens = preg_split('/\s+(AND|OR)\s+/i', $expr, -1, PREG_SPLIT_DELIM_CAPTURE);
		$result = null;
		$logic  = null;
		foreach ($tokens as $token) {
			$token = trim($token);
			if (strcasecmp($token, 'AND') === 0 || strcasecmp($token, 'OR') === 0) {
				$logic = strtoupper($token);
				continue;
			}
			$current = evaluateSimpleCondition($token, $debug);
			if ($result === null) {
				$result = $current;
			} else {
				$result = ($logic === 'AND') ? ($result && $current) : ($result || $current);
			}
			$debug[] = "Eval [$token] => ".($current ? 'TRUE' : 'FALSE');
		}
		return (bool)$result;
	}
	function evaluateSimpleCondition($expr, &$debug){
		$expr = trim($expr);
		// true / false literals
		if ($expr === 'true') return true;
		if ($expr === 'false') return false;
		
		// NOT LIKE
		if (preg_match("/^'?(.*?)'?\s+NOT\s+LIKE\s+'%(.+?)%'/i", $expr, $m)) {
			$res = stripos($m[1], $m[2]) === false;
			$debug[] = "NOT LIKE check: {$m[1]} NOT LIKE %{$m[2]}% => ".($res?'TRUE':'FALSE');
			return $res;
		}
		// LIKE
		if (preg_match("/^'?(.*?)'?\s+LIKE\s+'%(.+?)%'/i", $expr, $m)) {
			$res = stripos($m[1], $m[2]) !== false;
			$debug[] = "LIKE check: {$m[1]} LIKE %{$m[2]}% => ".($res?'TRUE':'FALSE');
			return $res;
		}
		// IN (...)
		if (preg_match("/^'?(.*?)'?\s+IN\s+\((.+)\)/i", $expr, $m)) {
			$left = trim($m[1], " '\"");
			$list = array_map(function($v){
				return trim($v, " '\"");
			}, explode(',', $m[2]));
			$res = in_array($left, $list, true);
			$debug[] = "IN check: $left IN (".implode(',', $list).") => ".($res?'TRUE':'FALSE');
			return $res;
		}
		// Comparison operators
		if (preg_match('/(.+?)\s*(>=|<=|!=|==|>|<)\s*(.+)/', $expr, $m)) {
			$left  = trim($m[1], " '\"");
			$right = trim($m[3], " '\"");
			if (is_numeric($left) && is_numeric($right)) {
				$left = (float)$left;
				$right = (float)$right;
			}
			switch ($m[2]) {
			case '>':
				$res = ($left > $right);
				break;
			case '<':
				$res = ($left < $right);
				break;
			case '>=':
				$res = ($left >= $right);
				break;
			case '<=':
				$res = ($left <= $right);
				break;
			case '==':
				$res = ($left == $right);
				break;
			
			case '!=':
				$res = ($left != $right);
				break;
				default:
				$res = false;
			}
			$debug[] = "Compare: $left {$m[2]} $right => ".($res?'TRUE':'FALSE');
			return $res;
		}
		$debug[] = "FAILED to parse: $expr";
		return false;
	}
	
	/**
	 * Validate POST payload size (HTMX friendly)
	 *
	 * @param int   $defaultLimit  Default payload limit in bytes
	 * @param array $excludeKeys   POST keys to exclude from size check
	 * @return void
	*/
	function validate_post_payload(int $defaultLimit = 1500000,array $excludeKeys = ['base64']) {
		// 1. Resolve payload limit
		$payloadLimit = $defaultLimit;
		if (!empty($_SESSION['PW_CONSTANTS']['PAYLOAD'])) {
			$payloadLimit = (int)$_SESSION['PW_CONSTANTS']['PAYLOAD'];
		}
		// 2. Clone POST data
		$tpost = $_POST;
		// 3. Remove excluded keys
		foreach ($excludeKeys as $key) {
			unset($tpost[$key]);
		}
		// 4. Encode and calculate size (byte-safe)
		$jsonPayload = json_encode($tpost, JSON_UNESCAPED_UNICODE);
		$payloadSize = mb_strlen($jsonPayload, '8bit');
		// 5. Validate payload
		if ($payloadSize > $payloadLimit) {
			$msg = "Payload {$payloadSize} bytes exceeded limit {$payloadLimit} bytes";
			// 6. HTMX-aware response
			if (!empty($_SERVER['HTTP_HX_REQUEST'])) {
				header('HX-Retarget: #editPanel');
				header('HX-Reswap: innerHTML');
				http_response_code(422);
				echo "<div class='alert alert-danger'>{$msg}</div>";
			} else {
				echo ("<script>alert('{$msg}')</script>");
				echo toast($msg);
			}
			exit;
		}
	}
	function printWazuhreport($json){
	    // Decode safely
		$data = is_string($json) ? json_decode($json, true) : $json;
		if (!$data) {
			echo "<pre>Invalid JSON</pre>";
			return;
		}
	    $changed = $data['syscheck']['changed_attributes'] ?? [];
	    // Flatten JSON
	    function flattenGrouped($array, $prefix = '', &$out = []){
			foreach ($array as $key => $value) {
				$newKey = $prefix ? "$prefix.$key" : $key;
				if (is_array($value)) {
					flattenGrouped($value, $newKey, $out);
				} else {
					$out[$newKey] = $value;
				}
			}
			return $out;
	    }
		$flat = flattenGrouped($data);
		// Group sections
		$sections = [
			'Agent'    => [],
			'Rule'     => [],
			'Syscheck' => [],
			'Other'    => []
		];
		foreach ($flat as $key => $value) {
			if (str_starts_with($key, 'agent.')) {
				$sections['Agent'][$key] = $value;
			} elseif (str_starts_with($key, 'rule.')) {
				$sections['Rule'][$key] = $value;
			} elseif (str_starts_with($key, 'syscheck.')) {
				$sections['Syscheck'][$key] = $value;
			} else {
				$sections['Other'][$key] = $value;
			}
		}
    ?>
		<div>
		<style>
			.json-wrap { font-family: Arial, sans-serif; }
			.print-btn {
			margin-bottom: 12px;
			padding: 6px 14px;
			background: #2563eb;
			color: #fff;
			border: none;
			border-radius: 6px;
			cursor: pointer;
			}
			.section-title {
			margin-top: 20px;
			padding: 6px 10px;
			background: #020617;
			color: #e5e7eb;
			font-size: 14px;
			border-radius: 6px;
			}
			table.json-table {
			width: 100%;
			border-collapse: collapse;
			font-size: 13px;
			margin-top: 6px;
			}
			table.json-table th {
			background: #e5e7eb;
			text-align: left;
			padding: 6px;
			}
			table.json-table td {
			padding: 6px;
			border-top: 1px solid #cbd5f5;
			vertical-align: top;
			word-break: break-word;
			}
			
			/* Changed attributes highlight */
			tr.changed td {
			background: #fff1f2;
			color: #7f1d1d;
			font-weight: bold;
			}
			
			/* DIFF highlight */
			tr.diff-row td {
			background: #020617;
			color: #bbf7d0;
			font-family: Consolas, monospace;
			white-space: pre-wrap;
			border-left: 5px solid #22c55e;
			}
			.badge {
			background: #dc2626;
			color: #fff;
			padding: 2px 8px;
			border-radius: 6px;
			font-size: 11px;
			margin-left: 6px;
			}
			@media print {
			.print-btn { display: none; }
			tr.diff-row td {
			background: #fff;
			color: #000;
			border-left: 3px solid #000;
			}
		}
		</style>  
		</div>
	    <div class="json-wrap" id="details";>
			<button class="btn p-0" onclick="this.style.display='none';printDivwazuh('details');">
			    <i class="bi bi-printer fs-4 me-1"></i> Print
			</button>	    	
			<?php foreach ($sections as $section => $rows): ?>
				<?php if (!$rows) continue; ?>
					<div class="section-title">
					<?= htmlspecialchars($section) ?>
					<?php if ($section === 'Syscheck' && $changed): ?>
						<span class="badge">Integrity Change</span>
					<?php endif; ?>
					</div>
					<table class="json-table">
					<thead>
					<tr>
						<th style="width:30%">Field</th>
						<th>Value</th>
					</tr>
					</thead>
					<tbody>
					<?php foreach ($rows as $key => $value):
						// Detect changed attributes
						$isChanged = false;
						foreach ($changed as $c) {
							if (str_contains($key, $c . '_before') || str_contains($key, $c . '_after')) {
							$isChanged = true;
							break;
							}
						}
						// Detect diff
						$isDiff = ($key === 'syscheck.diff');
					?>
					<tr class="<?= $isDiff ? 'diff-row' : ($isChanged ? 'changed' : '') ?>">
						<td><?= htmlspecialchars($key) ?></td>
						<td><?= nl2br(htmlspecialchars((string)$value)) ?></td>
					</tr>
					<?php endforeach; ?>
					</tbody>
					</table>
		<?php endforeach; ?>
		</div>
		<?php
	}
	
	function getFullAgentDetailsJSON() {

    // ---------- Headers ----------
    $headers = [];
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
    } else {
        foreach ($_SERVER as $k => $v) {
            if (strpos($k, 'HTTP_') === 0) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($k, 5)))));
                $headers[$name] = $v;
            }
        }
    }

    // ---------- IP Detection (exhaustive) ----------
    $ipChain = [
        'REMOTE_ADDR'       => $_SERVER['REMOTE_ADDR'] ?? '',
        'HTTP_X_FORWARDED_FOR' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
        'HTTP_X_REAL_IP'    => $_SERVER['HTTP_X_REAL_IP'] ?? '',
        'HTTP_CLIENT_IP'    => $_SERVER['HTTP_CLIENT_IP'] ?? '',
        'HTTP_CF_CONNECTING_IP' => $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '',
        'HTTP_TRUE_CLIENT_IP'   => $_SERVER['HTTP_TRUE_CLIENT_IP'] ?? '',
    ];

    // ---------- User Agent ----------
    $ua = $headers['User-Agent']
       ?? $headers['user-agent']
       ?? ($_SERVER['HTTP_USER_AGENT'] ?? '');

    // ---------- Request Meta ----------
    $requestMeta = [
        'method'        => $_SERVER['REQUEST_METHOD'] ?? '',
        'uri'           => $_SERVER['REQUEST_URI'] ?? '',
        'query_string'  => $_SERVER['QUERY_STRING'] ?? '',
        'protocol'      => $_SERVER['SERVER_PROTOCOL'] ?? '',
        'host'          => $_SERVER['HTTP_HOST'] ?? '',
        'port'          => $_SERVER['SERVER_PORT'] ?? '',
        'https'         => $_SERVER['HTTPS'] ?? '',
        'content_type'  => $_SERVER['CONTENT_TYPE'] ?? '',
        'content_length'=> $_SERVER['CONTENT_LENGTH'] ?? '',
        'referrer'      => $_SERVER['HTTP_REFERER'] ?? '',
        'accept'        => $_SERVER['HTTP_ACCEPT'] ?? '',
        'accept_lang'   => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
        'accept_encoding'=> $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '',
    ];

    // ---------- Server Meta ----------
    $serverMeta = [
        'server_name'   => $_SERVER['SERVER_NAME'] ?? '',
        'server_addr'   => $_SERVER['SERVER_ADDR'] ?? '',
        'server_software'=> $_SERVER['SERVER_SOFTWARE'] ?? '',
        'gateway_interface'=> $_SERVER['GATEWAY_INTERFACE'] ?? '',
        'php_self'      => $_SERVER['PHP_SELF'] ?? '',
        'script_name'   => $_SERVER['SCRIPT_NAME'] ?? '',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? '',
    ];

    // ---------- Body Meta (safe, not full body) ----------
    $bodyRaw = file_get_contents('php://input');
    $bodyInfo = [
        'body_length' => strlen($bodyRaw),
        'body_hash_sha256' => hash('sha256', $bodyRaw),
        'is_json' => (json_decode($bodyRaw, true) !== null),
    ];

    // ---------- Final Structure ----------
    $data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user_agent'=> $ua,
        'ip_chain'  => $ipChain,
        'headers'   => $headers,
        'request'   => $requestMeta,
        'server'    => $serverMeta,
        'body_meta' => $bodyInfo
    ];

    return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}

	function getfieldSequencer($fld,$pg){
		$flds_sql = "selrec combosql,fieldname from _pb_pagefields where concat('~',tags,'~') like '%~Sequencer~%' and pgid=?";
		//echo "selrec combosql,fieldname from _pb_pagefields where concat('~',tags,'~') like '%~Sequencer~%' and pgid='".$pg."'";
		$fld_res = PW_sql2rsPS($flds_sql,"s",$pg);
		while($fld_ds=PW_fetchArray($fld_res)){
			$seqPrefix='';
			if(!isFoundIn($fld_ds['combosql'],"###")){
				$sequenceArr = getValueForPS("selrec txt1,txt2 from _pb_lookups where lookcode=? and looktype=? and recordtype=? limit 0,1","sss",$fld_ds['combosql'],"Sequencers","Lookup");
				if($sequenceArr['txt2']<>'') $seqPrefix = replaceDs2message("",$sequenceArr['txt2']);
				if($fld_ds['combosql']!='')$prefix = substr($fld_ds['combosql'],0,2);
				$seqPrefix = strtoupper($prefix);
				$newSeqNo = $seqPrefix.$sequenceArr['txt1'];
			}else {
				$sequenceArr = getValueForPS("selrec txt1,txt2 from _pb_lookups where lookcode=? and looktype=? and recordtype=? limit 0,1","sss",$pg."_".$fld_ds['fieldname'],"Sequencers","Lookup");
				$newSeqNo = $sequenceArr['txt1'];
			}
			
		}
		return($newSeqNo);
	}
	
	function updatefieldSequencer($pg){
		$flds_sql = "selrec combosql,fieldname from _pb_pagefields where concat('~',tags,'~') like '%~Sequencer~%' and pgid=?";
		$fld_res = PW_sql2rsPS($flds_sql,"s",$pg);
		while($fld_ds=PW_fetchArray($fld_res)){
			$seqPrefix='';
			if(!isFoundIn($fld_ds['combosql'],"###")){
				$sequenceArr = getValueForPS("selrec id,txt1,txt2 from _pb_lookups where lookcode=? and looktype=? and recordtype=? limit 0,1","sss",$fld_ds['combosql'],"Sequencers","Lookup");
				if($sequenceArr['id']<>'') {
					//PW_execute("LOCREC TABLES _pb_lookups WRITE");
					$seqDS = getValueForPS("selrec id,txt1,txt2 from _pb_lookups where lookcode='".$fld_ds['combosql']."' and looktype='Sequencers' and recordtype='Lookup' limit 0,1");
					$seqDS['txt1']=$seqDS['txt1']+1;
					updateRecord($seqDS,"_pb_lookups"); 
					//PW_execute("UNLREC TABLES");
				}
				
			}else {
				$sequenceArr = getValueForPS("selrec id,txt1,txt2 from _pb_lookups where lookcode=? and looktype=? and recordtype=? limit 0,1","sss",$pg."_".$fld_ds['fieldname'],"Sequencers","Lookup");
				if($sequenceArr['id']<>'') {
					//PW_execute("LOCREC TABLES _pb_lookups WRITE");
					$seqDS = getValueForPS("selrec id,txt1,txt2 from _pb_lookups where lookcode='".$pg."_".$fld_ds['fieldname']."' and looktype='Sequencers' and recordtype='Lookup' limit 0,1");
					$seqDS['txt1']=$seqDS['txt1']+$seqDS['txt2'];
					updateRecord($seqDS,"_pb_lookups"); 
					//PW_execute("UNLREC TABLES");
				}
				
			}
		}
	}
	
	function SequencerInsertion($id,$table){
		if($table=='_pb_pagefields'){
			$ds = getValueForPS("selrec * from ".$table." where id=? ","s",$id);
			//echo "selrec * from ".$table." where id='".$id."' <br>";//exit;
			$ds1 = array();
			if(isFoundIn($ds['tags'],"Sequencer") && $ds['combosql']!=''){
				if(!isFoundIn($ds['combosql'],"###")){
					$seq_recId = getValueForPS("selrec count(id) from _pb_lookups where lookcode=? and looktype='Sequencers'","s",$ds['combosql']);
					if($seq_recId==0){
						$ds1['lookcode']   = $ds['combosql'];
						$ds1['lookname']   = $ds['combosql'];
						$ds1['txt1'] 	   = '100001';
						$ds1['txt2'] 	   = strtoupper(substr($ds['combosql'],0,3))."-";
						$ds1['recordtype'] = 'Lookup';
						$ds1['looktype']   = 'Sequencers';
						insertRecord($ds1,"_pb_lookups");
					}
				} 
				else {
					$fieldname = getValueForPS("selrec fieldname from _pb_pagefields where id=?","s",$ds['id']); //  to get fieldname -- Fieldname has tag no edit 
					list($seqNo,$increment) = explode("::",str_replace("###","",$ds['combosql']));
					$seqName = $ds['pgid']."_".$fieldname;
					$seq_recId = getValueForPS("selrec count(id) from _pb_lookups where lookcode=? and looktype='Sequencers'","s",$seqName);
					if($seq_recId==0){
						$ds1['lookcode']   = $seqName;
						$ds1['lookname']   = $seqName;
						$ds1['txt1'] 	   = $seqNo;
						$ds1['txt2'] 	   = $increment;
						$ds1['recordtype'] = 'Lookup';
						$ds1['looktype']   = 'Sequencers';
						insertRecord($ds1,"_pb_lookups");
					}
				}
			}
		}
		exit;
	}

function days_from_today($date)
{
    if (empty($date)) return null;   // handle NULL / empty safely

    try {
        $today = new DateTime("today");
        $dt    = new DateTime($date);
        return (int)$today->diff($dt)->format("%r%a");
    } catch (Exception $e) {
        return null;   // invalid date format
    }
}


?>	