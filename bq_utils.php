<?php
	// program to call the link actions in the codesegment added on 05-01-2026 laxmikanth
	$rty = $_GET['rty'] ?? '';
	if ($rty === 'linkaction') {
		rty_linkAction();
	}
	function rty_linkAction(){
		// initialise variables
		$hpg = $_GET['hpg'] ?? '';
		$hid = $_GET['hid'] ?? '';
		$parentpgid = $_GET['parentpgid'] ?? '';
		// For link used as button
		if ($hpg === '' && $hid === '' && $parentpgid !== '') {
			$hpg = $parentpgid;
			$_GET['hpg'] = $hpg; // preserve original behavior
		}
		if ($hpg === '') {
			exit; // nothing to process
		}
		// Fetch page head record
		$dsPage = getValueForPS("selrec * from  _pb_pagehead where pgid = ?","s",$hpg);
		// Call code segment
		callCodesegmentLink($dsPage);
		exit;
	}
	

function xxxtxt2speech($text, $title="TTS"){
    $uid       = "tts_" . uniqid();
    $btnId     = $uid . "_btn";
    $contentId = $uid . "_content";

    return '
<div id="'.$uid.'" class="mt-4 position-relative border border-2 rounded-3 p-4 bg-white mx-auto" style="max-width: 600px;">
    <span class="position-absolute top-0 start-0 translate-middle-y bg-white px-2 ms-3 fw-bold text-secondary">'.$title.'</span>

    <button id="'.$btnId.'" type="button" class="position-absolute top-0 end-0 translate-middle-y btn btn-sm btn-primary me-3">
        <i class="bi bi-volume-up-fill"></i>
    </button>

    <div id="'.$contentId.'" class="pt-2">'.nl2br($text).'</div>
</div>

<style>
    #'.$uid.' .word { padding: 0 1px; }
    #'.$uid.' .word.hl { background: yellow; }
</style>

<script>
(function(){
    const btn = document.getElementById("'.$btnId.'");
    const content = document.getElementById("'.$contentId.'");

    let utterance = null;
    let originalHTML = "";
    let highlightInterval = null;
    let allWords = null;
    let wordIndex = 0;

    function clearHighlightOnly(){
        if (highlightInterval) clearInterval(highlightInterval);
        highlightInterval = null;

        if (allWords && allWords.length) {
            allWords.forEach(w => w.classList.remove("hl"));
        }

        allWords = null;
        wordIndex = 0;
    }

    function restoreHTML() {
        if (originalHTML !== "") content.innerHTML = originalHTML;
    }

    function stopAll() {
        speechSynthesis.cancel();
        clearHighlightOnly();
        restoreHTML();
        btn.innerHTML = \'<i class="bi bi-volume-up-fill"></i>\';
    }

    // Wrap only TEXT nodes into span.word, preserving <br>, <p>, etc.
    function wrapTextNodes(node) {
        if (node.nodeType === Node.TEXT_NODE) {
            const t = node.nodeValue;
            if (!t || !t.trim()) return;

            const parts = t.split(/(\s+)/); // keep spaces
            const frag = document.createDocumentFragment();

            parts.forEach(part => {
                if (part.trim() === "") {
                    frag.appendChild(document.createTextNode(part));
                } else {
                    const span = document.createElement("span");
                    span.className = "word";
                    span.textContent = part;
                    frag.appendChild(span);
                }
            });

            node.parentNode.replaceChild(frag, node);
        } else if (node.nodeType === Node.ELEMENT_NODE) {
            const tag = node.tagName;
            if (tag === "SCRIPT" || tag === "STYLE") return;
            Array.from(node.childNodes).forEach(wrapTextNodes);
        }
    }

    btn.addEventListener("click", () => {
        // If already speaking/pending, stop
        if (speechSynthesis.speaking || speechSynthesis.pending) {
            stopAll();
            return;
        }

        // Start
        clearHighlightOnly();          // IMPORTANT: clear BEFORE we collect words
        originalHTML = content.innerHTML;

        wrapTextNodes(content);

        allWords = content.querySelectorAll(".word");
        if (!allWords.length) {
            restoreHTML();
            return;
        }

        const plainText = content.innerText.replace(/\s+/g, " ").trim();
        if (!plainText) {
            restoreHTML();
            return;
        }

        const wordsPerSecond = 2.5;
        highlightInterval = setInterval(() => {
            if (!allWords || !allWords.length) return;

            if (wordIndex < allWords.length) {
                if (wordIndex > 0) allWords[wordIndex - 1].classList.remove("hl");
                allWords[wordIndex].classList.add("hl");
                wordIndex++;
            } else {
                // finished highlight cycle (speech may continue a bit)
                if (allWords.length) allWords[allWords.length - 1].classList.remove("hl");
                clearHighlightOnly();
            }
        }, 1000 / wordsPerSecond);

        utterance = new SpeechSynthesisUtterance(plainText);
        utterance.rate = 1.0;

        utterance.onend = () => {
            stopAll();
        };

        utterance.onerror = () => {
            stopAll();
        };

        btn.innerHTML = \'<i class="bi bi-stop-fill"></i>\';
        speechSynthesis.speak(utterance);
    });
})();
</script>
';
}


function txt2speech($text, $title="TTS"){
    $uid = "tts_" . uniqid();
    $btnId = $uid . "_btn";
    $contentId = $uid . "_content";

    return '
	<div id="'.$uid.'" class="mt-4 position-relative border border-2 rounded-3 p-4 bg-white mx-auto" style="max-width: 600px;">
	    <span class="position-absolute top-0 start-0 translate-middle-y bg-white px-2 ms-3 fw-bold text-secondary">'.$title.'</span>
	
	    <button id="'.$btnId.'" type="button" class="position-absolute top-0 end-0 translate-middle-y btn btn-sm btn-primary me-3">
	        <i class="bi bi-volume-up-fill"></i>
	    </button>
	
	    <div id="'.$contentId.'" class="pt-2">'.nl2br($text).'</div>
	</div>

	<style>
	    #'.$uid.' .word{
	        padding: 0 .08rem;
	        border-radius: .35rem;
	        transition: background-color .12s ease, box-shadow .12s ease;
	        display: inline-block;
	    }
	    #'.$uid.' .word.hl{
	        background: #ffe58f;
	        box-shadow: 0 0 0 .12rem rgba(255, 193, 7, .35);
	        font-weight: 600;
	    }
	</style>

	<script>
		(function(){
	    const btn = document.getElementById("'.$btnId.'");
	    const content = document.getElementById("'.$contentId.'");
	
	    let utterance = null;
	    let originalHTML = "";
	    let highlightInterval = null;
	    let allWords = null;
	    let wordIndex = 0;
	
	    function clearHighlightOnly(){
	        if (highlightInterval) clearInterval(highlightInterval);
	        highlightInterval = null;
	
	        if (allWords && allWords.length) {
	            allWords.forEach(w => w.classList.remove("hl"));
	        }
	
	        allWords = null;
	        wordIndex = 0;
	    }
	
	    function restoreHTML() {
	        if (originalHTML !== "") content.innerHTML = originalHTML;
	    }

	    function stopAll() {
	        speechSynthesis.cancel();
	        clearHighlightOnly();
	        restoreHTML();
	        btn.innerHTML = \'<i class="bi bi-volume-up-fill"></i>\';
	    }

	    // Wrap only TEXT nodes into span.word, preserving <br>, <p>, etc.
	    function wrapTextNodes(node) {
	        if (node.nodeType === Node.TEXT_NODE) {
	            const t = node.nodeValue;
	            if (!t || !t.trim()) return;
	
	            const parts = t.split(/(\\s+)/); // keep spaces
	            const frag = document.createDocumentFragment();
	
	            parts.forEach(part => {
	                if (part.trim() === "") {
	                    frag.appendChild(document.createTextNode(part));
	                } else {
	                    const span = document.createElement("span");
	                    span.className = "word";
	                    span.textContent = part;
	                    frag.appendChild(span);
	                }
	            });
	
	            node.parentNode.replaceChild(frag, node);
	        } 
	        else if (node.nodeType === Node.ELEMENT_NODE) {
	            const tag = node.tagName;
	            if (tag === "SCRIPT" || tag === "STYLE") return;
	            Array.from(node.childNodes).forEach(wrapTextNodes);
	        }
	    }
	
	    btn.addEventListener("click", () => {
	
	        // If already speaking → stop
	        if (speechSynthesis.speaking || speechSynthesis.pending) {
	            stopAll();
	            return;
	        }
	
	        // Start speaking
	        clearHighlightOnly();                // important: clear first
	        originalHTML = content.innerHTML;   // save original layout
	
	        wrapTextNodes(content);              // wrap words, keep layout
	
	        allWords = content.querySelectorAll(".word");
	        if (!allWords.length) {
	            restoreHTML();
	            return;
	        }

	        const plainText = content.innerText.replace(/\\s+/g, " ").trim();
	        if (!plainText) {
	            restoreHTML();
	            return;
	        }
	
	        const wordsPerSecond = 2.5;
	
	        highlightInterval = setInterval(() => {
	            if (!allWords || !allWords.length) return;
	
	            if (wordIndex < allWords.length) {
	                if (wordIndex > 0) allWords[wordIndex - 1].classList.remove("hl");
	                allWords[wordIndex].classList.add("hl");
	                wordIndex++;
	            } else {
	                clearHighlightOnly();
	            }
	        }, 1000 / wordsPerSecond);
	
	        utterance = new SpeechSynthesisUtterance(plainText);
	        utterance.rate = 1.0;
	
	        utterance.onend = () => {
	            stopAll();
	        };
	
	        utterance.onerror = () => {
	            stopAll();
	        };

	        btn.innerHTML = \'<i class="bi bi-stop-fill"></i>\';
	        speechSynthesis.speak(utterance);
	    });
	})();
	</script>
';
}


?>



