	// javascript standrd file for beeq
	function showEditTabs(o){
		name=o.id;
		
		var rows = document.getElementsByTagName("tr");
		for (var i = 0; i < rows.length; i++){
			var el = rows[i];
			if (!el.hasAttribute("tabname")) continue; // only affect rows that have tabname
			if (!name || name === "all" || name === "*"){
				el.style.display = ""; // show all tabbed rows
			} else {
				var tn = el.getAttribute("tabname"); // exact match; make both .toLowerCase() if you want case-insensitive
				el.style.display = (tn === name) ? "" : "none";
			}
		}
	}    
	function showEditTabsold(tab) {
        const tabName = tab.id;
        document.querySelectorAll('tr[tabname]').forEach(tr => {
            tr.style.display = (tr.getAttribute('tabname') === tabName) ? '' : 'none';
        });
    }
	function CopyFieldValue(fromField,toField){
		if(document.getElementById(toField) && document.getElementById(fromField)){
			document.getElementById(toField).value = document.getElementById(fromField).value;
		}
	}
	function closeEditPanel(){
		o=document.getElementById('editPanel');
		if(o) o.innerHTML='';
	}
	// function closeAllPops(){
	// 	o=document.getElementById('allpops');
	// 	if(o) o.style.display='none';
	// 	if(o) o.innerHTML='';
		
	// 	o=document.getElementById('actionPopDiv');
	// 	if(o) o.style.display='none';
	// 	o=document.getElementById('if1');
	// 	if(o) o.style.display='none';
	// 	o=document.getElementById('bqPopToolbar');
	// 	if(o) o.style.display='none';
	// 	o=document.getElementById('buttonlinks');
	// 	if(o) o.style.display='none';
	// 	o=document.getElementById('dragenddiv');
	// 	if(o) o.style.display='none';
	// 	o=document.getElementById('myProfileDiv');
	// 	if(o) o.style.display='none';
	// 	o=document.getElementById('profile');
	// 	if(o) o.style.display='none';
		
	// }
	function closeAllPops(){
    const ids = [
        'allpops',
        'actionPopDiv',
        'if1',
        'bqPopToolbar',
        'buttonlinks',
        'dragenddiv',
        'myProfileDiv',
        'actionPopDiv',
        'profile'
    ];

    ids.forEach(id=>{
        const o = document.getElementById(id);
        if(!o) return;

        o.style.display = 'none';
        if(id === 'allpops') o.innerHTML = '';
    });
}

	function setiframe(size){ // small // medium // large
		o=parent.document.getElementById('if1');
		o.style.display=''
		o.style.maxWidth = '';

		o.style.left='120px'
		o.style.top='140px'
		o.style.height='500px'
		if (size === "small") {
	        // o.style.width='300px';
	        o.style.maxWidth='345px';
	    }
		if(size=="medium") o.style.width='500px'
		if(size=="large") o.style.width='700px'
		if(size=="xlarge") o.style.width='1000px'
		
	}
	function setPopAll(x,y,w,h){
		o=document.getElementById("allpops");
		o.style.top=y+'px';
		o.style.left=x+'px';
		o.style.height=h+'px';
		o.style.width=w+'px';
	}
	function opengeo(name,v){ 	// used for geo location control
		o=document.getElementById('allpops');
		o=document.getElementById('if1');
		if(o) o.style.display=''
		o.style.left='200px'
		o.style.top='140px'
		o.src='do_bq_geo.php?key='+name+'&value='+v
	}
	function closeallpops(){
		o=document.getElelmentById('allpops');
		o.style.display='none';
		o1=document.getElementById('bqPopToolbar');
		if(o1)o1.style.display='none'
	}
	function setSideWidth(width){
      const side = document.getElementById('editPanel');
    	if(width==='0%'){
	        side.style.display='none';
	        document.documentElement.style.setProperty('--side-width','0%');
	        document.documentElement.style.setProperty('--main-width','100%');
    	}else{
	        side.style.display='';
	        document.documentElement.style.setProperty('--side-width',width);
	        document.documentElement.style.setProperty('--main-width',`calc(100% - ${width})`);
    	}
    }
	function getObject(oname){
		//alert(oname);
		//  getObject : Gets object name from current document or parent or grandparent
		o=document.getElementById(oname);
		if(o) return(o);
	} 
	function showObject(oname){
		o=getObject(oname);
		if(o)o.style.display='block';
	}	
	function isdecimal(x,n=2){
		// usage onkeypress="return isdecimal(event,n)
		n = n>=0 ? n : Infinity;
		const s = typeof x==='string' ? x : ((e)=>{const t=e.target,v=t.value,a=t.selectionStart??v.length,b=t.selectionEnd??v.length;return e.key.length===1?v.slice(0,a)+e.key+v.slice(b):v;})(x);
		const re = n===Infinity ? /^\d*(?:\.\d*)?$/ : new RegExp(`^\\d*(?:\\.\\d{0,${n}})?$`);
		return re.test(s) && /\d/.test(s);
	}
	function AllowNumeric(objEvent, obj) {
		var valu = obj.value;
		var iKeyCode;
		if (window.event) {
			iKeyCode = objEvent.keyCode;
		} else if (objEvent.which) {
			iKeyCode = objEvent.which;
		}
	    if (
	        (iKeyCode <= 1 && iKeyCode >= 7) ||
	        (iKeyCode >= 9 && iKeyCode <= 45) ||
	        (iKeyCode >= 58 && iKeyCode <= 255) ||
	        iKeyCode == 47
	    ) {
	        if (iKeyCode != 13) {
	            var o = getObject('editerrordiv');
	            if (o) {
	                //o.style.display = "block";
	                o.innerHTML = `
	                    <div class="alert alert-warning d-flex align-items-start gap-2 shadow-sm p-3 rounded-3 w-100 m-0"
	                         role="alert"
	                         onclick="this.remove()"
	                         style="cursor:pointer">
	
	                        <i class="bi bi-exclamation-triangle-fill fs-4 text-warning"></i>
	
	                        <div>
	                            <div class="fw-semibold">Invalid Input</div>
	                            <div class="small text-muted">
	                                This column allows <strong>numbers only</strong>
	                            </div>
	
	                            <div class="mt-2">
	                                <span class="badge bg-secondary-subtle text-dark border">
	                                    Click anywhere to close
	                                </span>
	                            </div>
	                        </div>
	                    </div>
	                `;
	                showObject('editerrordiv');
	            } else {
	                alert('Numbers Only');
	            }
	            return false;
	        }
	    }
	    return true;
	}
	
	function maskMobile(el){
		const mask = el.dataset.mask || '';
		const digits = (el.value || '').replace(/\D/g,''); // keep digits only
		let out = '', i = 0;
		
		for (let ch of mask){
			if (ch === '#'){
				if (i < digits.length) out += digits[i++];
			else break; // stop when no more digits
				} else {
				out += ch;  // static mask char
			}
		}
		el.value = out;
	}	
	
	function toggleDisplayChromeExtension(id) {
    var el = document.getElementById(id);
	    if (el.style.display === "none" || el.style.display === "") {
	        el.style.display = "block";   // show
	    } else {
	        el.style.display = "none";    // hide
	    }
	}

function toggleDisplay(id) {
    var el = document.getElementById(id);
    var current = window.getComputedStyle(el).display;
    if (current === "none") {
        el.style.display = "block";
    } else {
        el.style.display = "none";
    }
}

	// function toggleDisplay(id) {
	//     var el = document.getElementById(id);
	//     //if (el.style.display === "none" || el.style.display === "") {
	//     if (el.style.display === "none" || el.style.display === "") {
	//         el.style.display = "";
	//     } else {
	//         el.style.display = "none";
	//     }
	// }
	function toggleFramex(f){
		//  Toggles a given frame.
		o=parent.document.getElementById(f);
		if(o.style.display=='none'){
			o.style.display='';
		}else{
			o.style.display='none';
		}
	}	
	function validateAll(form){  // this is used in the main edit form for all checking
		var fields = form.querySelectorAll('input,select,textarea');
		var msgs = [];
		for (var i=0;i<fields.length;i++){
			var el = fields[i];
			if (el.disabled) continue;
			if (!el.checkValidity()){
				var label = (el.labels && el.labels[0]) ? el.labels[0].innerText.trim()
				: (el.placeholder || el.name || el.id || 'This field');
				msgs.push('Data: '+el.dataset.caption + ': ' + (el.validationMessage || 'Invalid value. In tab:')+'Tab Name:'+el.dataset.tabname);
			}
		}
		if (msgs.length){
		alert('Please fix the following:\n\n' + msgs.join('\n'));
		return false; // block submit
		}
		return true; // all good
	}	
	function showdiv(d){
	    var o = document.getElementById(d);
	    if (!o) return;
	    // only for allpops — show and attach handles
	    if (d === "allpops" && typeof window.bqDragAttach === "function") {
	    	//alert('111')
	        window.bqDragAttach(d);
	       // alert('222')
	        return;
	    }
	    // normal behavior for everything else
	    //o.style.display = '';
	    //alert(o.style.display);
	    if (o.style.display === "none" || o.style.display === "") {
	        o.style.display = "block";
	    } else {
	        o.style.display = "none";
	    }	    
	}	
	function hidediv(d){
		o=document.getElementById('bqPopToolbar');
		if(o)o.style.display='none'
		o1=document.getElementById(d);
		if(o1) o1.style.display='none';
	}
	function xxxprintDiv(divId) {
		const divContents = document.getElementById(divId).innerHTML;
		const printWindow = window.open('', '', 'height=600,width=800');
		printWindow.document.write('<html><head><title>Print</title>');
		printWindow.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">');
		printWindow.document.write('</head><body>');
		printWindow.document.write(divContents);
		printWindow.document.write('</body></html>');
		printWindow.document.close();
		printWindow.focus();
		printWindow.print();
		printWindow.close();
	}
	

function printDiv(divId) {
  const src = document.getElementById(divId);
  const iframe = document.getElementById('if1');
  if (!src || !iframe) return;

  const doc = iframe.contentWindow.document;
  doc.open();
  doc.write('<!doctype html><html><head>');
  doc.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">');
  doc.write('<style>.dc_copy_btn,[data-dc-hide-on-copy],.dc-hide-in-copy{display:none!important}</style>');
  doc.write('</head><body>');
  doc.write(src.outerHTML);
  doc.write('</body></html>');
  doc.close();

  iframe.contentWindow.focus();
  iframe.contentWindow.print();
}
function printDivwazuh(divId) {
    const divContents = document.getElementById(divId).innerHTML;

    let styles = '';
    for (const sheet of document.styleSheets) {
        try {
            for (const rule of sheet.cssRules) {
                styles += rule.cssText;
            }
        } catch (e) {
            // Ignore cross-origin CSS (CDN)
        }
    }

    const printWindow = window.open('', '', 'height=700,width=1000');
    printWindow.document.write(`
        <html>
        <head>
            <title>Print</title>
            <style>${styles}</style>
        </head>
        <body>
            ${divContents}
        </body>
        </html>
    `);

    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
    printWindow.close();
}


 //this function shows the toast due on the rock type top right corner of a leave which is specified
function showMsgInside(divId, offsetTop, offsetRight, message, visibleSec = 3, fadeSec = 1) {
	
    const target = document.getElementById(divId);
    if (!target) return;

    const msg = document.createElement('div');
    msg.className = 'ps-2 border-start border-2  border-warning bg-dark text-warning rounded p-2 shadow-sm';
	const icon = document.createElement('i');
	icon.className = 'ms-2 me-2 text-warning bi-exclamation-diamond fs-5';
	const text = document.createTextNode(' ' + message + ' ');

	msg.append(icon, text);
	offsetTop=2;
	Object.assign(msg.style, {
		position: 'absolute',
		top: offsetTop + 'px',
		right: offsetRight + 'px',
		opacity: '1', // show immediately
		border:'#ffffff solid 2px',
		transition: `opacity ${fadeSec}s ease`, // fade-out only
		zIndex: '10',
		pointerEvents: 'none' // optional: don't block clicks inside target
	});

    target.appendChild(msg);

    // Stay visible, then fade out smoothly
	setTimeout(() => {
		msg.style.opacity = '0';
		setTimeout(() => msg.remove(), fadeSec * 1000);
	}, visibleSec * 1000);
  }
  
	
// ==== Global ESC key handler ====
document.addEventListener('keydown', function(e) {
  // Check if ESC key pressed
  if (e.key === 'Escape' || e.keyCode === 27) {
    // Example action: close all popups
    if (typeof closeAllPops === 'function') {
      closeAllPops();
    }

    // Optional actions you can enable as needed:
    // location.reload();                  // refresh page
    // document.getElementById('if1').style.display = 'none'; // hide iframe
    // console.log('ESC pressed - action triggered');
  }
});



//****************** hot key management ***
// let activeHKListener = null;
var activeHKListener = window.activeHKListener || null;


function isVisible(el){
  if (!el) return false;
  const s = getComputedStyle(el);
  return s.display !== 'none' && s.visibility !== 'hidden' && el.offsetParent !== null;
}

function enablePanelHotkeys(hkpanel){
  const panel = document.getElementById(hkpanel);
  disablePanelHotkeys();
  if (!isVisible(panel)) return;

  activeHKListener = function(e){
    // keep accesskey (Alt+char) and system shortcuts
    if (e.altKey || e.ctrlKey || e.metaKey) return;

    const t = e.target;
    // ignore while editing
    if (t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' ||
              t.tagName === 'SELECT' || t.isContentEditable)) return;

    if (!e.key || e.key.length !== 1) return;

    const k = e.key.toLowerCase();
    const btn = panel.querySelector('#hk' + k);
    if (btn){
      btn.click();
      e.preventDefault();
    }
  };

  document.addEventListener('keydown', activeHKListener);
}

function disablePanelHotkeys(){
  if (activeHKListener){
    document.removeEventListener('keydown', activeHKListener);
    activeHKListener = null;
  }
}

function openHKPanel(hkpanel){
  const panel = document.getElementById(hkpanel);
  panel.style.display = 'block';
  //alert(hkpanel+' Opened')
  enablePanelHotkeys(hkpanel);
}

function closeHKPanel(hkpanel){
  document.getElementById(hkpanel).style.display = 'none';
  disablePanelHotkeys();
}
function shareDivTable(txt) {
	textcontent = extractTableTextFromDiv(txt);
	if (navigator.share) {
		navigator.share({
		text: textcontent
		// Don't add `url` if you only want to share text
	})
	.then(() => console.log('Shared successfully!'))
	.catch((error) => console.error('Error sharing:', error));
	} else {
		alert('Sharing not supported on this device/browser.');
	}
}
function extractTableTextFromDiv(divId) {
	const div = document.getElementById(divId);
	if (!div) return '';
	output=(div.innerText);
	return output;
}

function WBStoggleTR(parentChain){

    // normalize (remove trailing - if any)
    parentChain = parentChain.replace(/-+$/,'');

    document.querySelectorAll('tr[data-parentchain]').forEach(tr => {

        const chain = (tr.getAttribute('data-parentchain') || '').replace(/-+$/,'');

        // SKIP: do not collapse the exact same row
        if (chain === parentChain) return;

        // Collapse only descendants (chain starts with parentChain-)
        if (chain.startsWith(parentChain + '-')) {
            tr.style.display = (tr.style.display === 'none') ? '' : 'none';
        }
    });
}

// function openPageList(el){

//     var pageList = document.getElementById("pageList");

//     // Move dropdown next to clicked gear
//     el.parentNode.appendChild(pageList);

//     // Position below gear
//     pageList.style.top = el.offsetTop + el.offsetHeight + "px";
//     pageList.style.left = el.offsetLeft + "px";

//     pageList.style.display = "block";
// }

// function closePageList(){
//     document.getElementById("pageList").style.display = "none";
// }

// function openPageList(el) {

//     closeAllPops();

//     var wrapper = el.closest("div[style*='position:relative']");
//     var dropdown = wrapper.querySelector(".pageList");

//     // Copy parent content
//     var parentPageList = document.getElementById("pageList");
//     dropdown.innerHTML = parentPageList.innerHTML;

//     // Get exact gear position
//     var rect = el.getBoundingClientRect();

//     dropdown.style.position = "fixed";
//     dropdown.style.top = rect.bottom + "px";
//     dropdown.style.left = rect.left + "px";

//     dropdown.style.zIndex = "99999";  // very high
//     dropdown.style.display = "block";
// }




