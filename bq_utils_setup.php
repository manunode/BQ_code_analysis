<?php
	
	include_once("bq_indi_engine.php");
	opcache_invalidate("bq_pagesetup_utils.php", true);
	$bqkey_db=pw_enc("pw=bq_fw_form_builder.php&action=dbsetup");
	$bqkey_removelookups=pw_enc("pw=bq_utils_list_removelookups.php");
	$bqkey_files=pw_enc("pw=bq_utils_files.php&action=dbsetup");
	$bqkey_files_search=pw_enc("pw=bq_utils_files_search.php&action=dbsetup");
	$bqkey_pagehead=pw_enc("pw=do_bqshell.php&pgid=pb_pagehead");
	//$bqkey_apexcharts=pw_enc("pw=bq_fw_apexcharts.php&action=apex");
	$bqkey_pivot=pw_enc("pw=do_reports_pivot.php");
	$bqkey_powerbi=pw_enc("pw=do_powerbiv2.php");
	$bqkey_allapis=pw_enc("pw=bq_utils_api_demo.php");
	$bqkey_geoattendance=pw_enc("pw=bq_utils_geoattendance.php");
    $bqkey_modules=pw_enc("pw=bq_utils_list_modules.php");
	$bqkey_geodistance=pw_enc("pw=bq_utlis_geodistance.php");
	$bqkey_excelupload=pw_enc("pw=bq_utils_excelupload.php&rty=excel");
	$bqkey_phpminiadmin=pw_enc("pw=bq_utils_phpminiadmin.php&rty=phpminiadmin");
	$bqkey_Logistics=pw_enc("pw=bq_utils_logistics.php&rty=logisticsdetails");
	$bqkey_vcard=pw_enc("pw=bq_utils_vcard.php&rty=vcard&direct=yes");
	$bqkey_vcard1=pw_enc("pw=bq_utils_vcard1.php&rty=vcard1&direct=yes");
	$bqkey_codecompare= pw_enc("pw=bq_utils_code_compare.php&action=codecompare");
	//$bqkey_tree = 	pw_enc("pw=bq_project_treeview.php");

	$bqkey_editor=pw_enc("pw=bq_list_edit.php&pgid=_pb_crm_leads&hid=1000259093_dqnfoK&action=edit");
	
	/* AI - Artificial intelligence */
	
	$bqkey_ai_help= pw_enc("pw=bq_utils_ai_help.php&action=aihelp");
	$bqkey_ai_txt2report = pw_enc("pw=bq_ai_tools_text2report.php&action=aireport");
	$bqkey_scraping=pw_enc("pw=bq_ai_tools_scraping.php");
	$bqkey_ai_tools_ppt=pw_enc("pw=bq_ai_tools_ppt.php&action=aippt");
	$bqkey_ai_tools_yt=pw_enc("pw=bq_ai_tools_youtube.php&action=yttrans");
	$bqkey_ai_tools_imganalyzer=pw_enc("pw=bq_ai_tools_imganalyzer.php&action=img");
	$bqkey_ai_tools_pdf=pw_enc("pw=bq_ai_tools_pdf.php&action=img");
	$bqkey_ai_tools_global_scraper=pw_enc("pw=do_ai_tools_globalscraper.php&action=img");
	$bqkey_ai_tools_maphtml = pw_enc("pw=bq_pagesetup_maphtml.php");
	
	$bqkey_pedia= pw_enc("pw=bq_ai_tools_pedia.php&action=codecompare");
	//$bqgoog_transulate= pw_enc("pw=bq_utils_googletranslate.php");
	//$bqkey_ai_tools_set_workflow= pw_enc("pw=bq_ai_tools_set_workflow.php&action=setworkflow");
	$bqkey_ai_formgen= pw_enc("pw=bq_utils_ai_formgen.php&action=formfields");
	$bqkey_phpeditor= pw_enc("pw=bq_utils_phpeditor.php&action=editor&file=park_bq_naresh.php");
	
	echo '<div class="bq-head"><b>Utilities</b></div>';
	
	echo "<div id='hkutils'>
	
		  <div class='bq-subhead mt-1 mb-1'><b>Framework</b></div>
			<div class='wrapper_insidemenu flex_insidemenu'>
			  <div class='overview_insidemenu grid_insidemenu'>
				<!-- Module -->
				<div class='grid-item_insidemenu flex_insidemenu'>
					<div class='col-12 align-middle' hx-get='do_bq/".$bqkey_modules."' hx-target='#mainContent' hx-swap='innerHTML' onclick='setSideWidth(\"0%\");'>
						<div class='menuinside_rad'> <i class='bi bi-puzzle grid-item_insidemenu_icon fs-4'></i></div>Modules
					</div>
				</div>
				
					<!-- Lookups Remove -->
				<div class='grid-item_insidemenu flex_insidemenu'>
					<div class='col-12 align-middle' hx-get='do_bq/".$bqkey_removelookups."' hx-target='#mainContent' hx-swap='innerHTML' onclick='setSideWidth(\"0%\");'>
					<div class='menuinside_rad'>
					<i class='bi bi-trash grid-item_insidemenu_icon fs-4'></i>
                    </div>
                    Remove Lookups
					</div>
				</div>
				
			    <!-- Database -->
				<div class='grid-item_insidemenu flex_insidemenu position-relative'>
					<span class='hotkey-badge text-dark'>
						<span style='position:absolute;top:-32px;right:7px'>D</span>
					</span>
					<div id='hkd' class='col-12 align-middle' hx-get='do_bq/".$bqkey_db."' hx-target='#mainContent' hx-swap='innerHTML'
					onclick=\"setSideWidth('50%');\">
						<div class='menuinside_rad'><i class='bi bi-database grid-item_insidemenu_icon fs-4'></i></div>Database</div>
				</div>
				
				<!-- Pagehead -->
				<div class='grid-item_insidemenu flex_insidemenu position-relative'>
					<span class='hotkey-badge text-dark'>
						<span style='position:absolute;top:-32px;right:7px'>P</span>
					</span>
					<div id='hkp' class='col-12 align-middle' hx-get='do_bq/".$bqkey_pagehead."' hx-target='#mainContent' hx-swap='innerHTML'>
						<div class='menuinside_rad'> <i class='bi bi-card-text grid-item_insidemenu_icon fs-4'></i></div>Page head</div>
				</div>
				
				<!-- File Managment -->
				<div class='grid-item_insidemenu flex_insidemenu'>
					<div class='col-12 align-middle' hx-get='do_bq/".$bqkey_files."' hx-target='#mainContent' hx-swap='innerHTML'>
						<div class='menuinside_rad'><i class='bi-file-text grid-item_insidemenu_icon fs-4'></i></div>File Search</div>
				</div>
				
				<!-- Search String -->
				<div  class='grid-item_insidemenu flex_insidemenu position-relative'>
					<span class='hotkey-badge text-dark'>
						<span style='position:absolute;top:-32px;right:7px'>S</span>
					</span>
					<div id='hks' class='col-12 align-middle' hx-get='do_bq/".$bqkey_files_search."' hx-target='#mainContent' 
						hx-swap='innerHTML'>
						<div class='menuinside_rad'><i class='bi bi-search grid-item_insidemenu_icon fs-4'></i></div>Search String</div>
				</div>
				
				<!-- Php Min Admin -->
				<div class='grid-item_insidemenu flex_insidemenu position-relative'>
					<span class='hotkey-badge text-dark'>
						<span style='position:absolute;top:-32px;right:7px'>H</span>
					</span>
					<div id='hkh' class='col-12 align-middle' hx-get='do_bq/".$bqkey_phpminiadmin."' hx-target='#allpops'  
					onclick='showdiv(\"allpops\");
					setPopAll(100,80,1000,515);' hx-swap='innerHTML' onclick='setSideWidth(\"0%\");'>
						<div class='menuinside_rad'><i class='bi bi-database grid-item_insidemenu_icon fs-4'></i></div>PHP Mini Admin</div>
				</div>
				
				<!-- Code Compare -->
				<div class='grid-item_insidemenu flex_insidemenu position-relative'>
					<span class='hotkey-badge text-dark'>
						<span style='position:absolute;top:-32px;right:7px'>C</span>
					</span>
					<div id='hkc' class='col-12 align-middle' 
					hx-get='do_bq/".$bqkey_codecompare."' 
					hx-target='#allpops'  
					hx-swap='innerHTML' 
					onclick=\"showdiv('allpops');setPopAll(65,56,1250,585)\" >
						<div class='menuinside_rad'><i class='bi bi-braces grid-item_insidemenu_icon fs-4'></i></div>Code Compare</div>
				</div>

				
				<!-- AI Reports -->
				<div class='grid-item_insidemenu flex_insidemenu position-relative'>
					<div class='col-12 align-middle' 
					hx-get='do_bq/".$bqkey_ai_txt2report."' 
					hx-target='#editPanel' 
					hx-swap='innerHTML' onclick='setSideWidth(\"60%\");'>
						<div class='menuinside_rad'><i class='bi bi-file-earmark-text grid-item_insidemenu_icon fs-4'></i></div>AI Report</div>
				</div>
				
				<div class='grid-item_insidemenu flex_insidemenu position-relative'>
				<span class='hotkey-badge text-dark'>
						<span style='position:absolute;top:-32px;right:7px'>M</span>
					</span>
					<div  id='hkm' class='col-12 align-middle' onclick='setSideWidth(\"0%\")';
					hx-get='do_bq/".pw_enc("pw=bq_fw_module_builder.php&action=makemodule")."' 
					hx-target='#mainContent' 
					hx-swap='innerHTML' onclick='setSideWidth(\"40%\");'>
						<div class='menuinside_rad'><i class='bi bi-file-earmark-text grid-item_insidemenu_icon fs-4'></i></div>AI Module</div>
				</div>
				
				<a style='text-decoration:none;' class='grid-item_insidemenu flex_insidemenu position-relative' href='do_bq_utils_lovable.php' target='_BLANK'>
				<i class='bi bi-diagram-3 grid-item_insidemenu_icon menuinside_rad fs-4'></i>
				 Beeq Hirarchy</a>
				
				 <a style='text-decoration:none;' class='grid-item_insidemenu flex_insidemenu position-relative' href='do_bq_fw_processchart.php' target='_BLANK'>
				<i class='bi bi-diagram-3 grid-item_insidemenu_icon menuinside_rad fs-4'></i>
				 Proc chart</a>
				 
				 
				
				<div class='grid-item_insidemenu flex_insidemenu position-relative'>
				<span class='hotkey-badge text-dark'>
						<span style='position:absolute;top:-32px;right:7px'>F</span>
					</span>
					<div id='hkf' class='col-12 align-middle' 
					hx-get='do_bq/".$bqkey_ai_formgen."' 
					hx-target='#mainContent' 
					hx-swap='innerHTML' onclick='setSideWidth(\"30%\");'>
						<div class='menuinside_rad'><i class='bi bi-file-earmark-text grid-item_insidemenu_icon fs-4'></i></div>AI Form</div>
				</div>
				
				<div class='grid-item_insidemenu flex_insidemenu position-relative'>
					<div class='col-12 align-middle' 
					hx-get='do_bq/".pw_enc("pw=bq_pagesetup_utils.php&action=showsession")."' 
					hx-target='#editPanel' 
					hx-swap='innerHTML' onclick='setSideWidth(\"40%\");'>
						<div class='menuinside_rad'><i class='bi bi-file-earmark-text grid-item_insidemenu_icon fs-4'></i></div>View session</div>
				</div>


				<div class='grid-item_insidemenu flex_insidemenu position-relative'>
					<div class='col-12 align-middle' 
					hx-get='do_bq/".pw_enc("pw=bq_utils_cssjs.php&action=cssjs")."' 
					hx-target='#editPanel' 
					hx-swap='innerHTML' onclick='setSideWidth(\"40%\");'>
						<div class='menuinside_rad'><i class='bi bi-file-earmark-text grid-item_insidemenu_icon fs-4'></i></div>Latest CSS JS</div>
				</div>

				
		</div>
			 
			 
		</div>
		
		<!-- AI - Artificial intelligence -->
		<div class='bq-subhead mt-1 mb-1'><b>AI - Artificial intelligence</b></div>
		
			<div class='wrapper_insidemenu flex_insidemenu'>
			 <div class='overview_insidemenu grid_insidemenu'>
				
				<!-- URLScraping-->
				<div class='grid-item_insidemenu flex_insidemenu'>
					<div class='col-12 align-middle' hx-get='do_bq/".$bqkey_scraping."' hx-target='#editPanel' hx-swap='innerHTML' onclick='setSideWidth(\"30%\");'>
						<div class='menuinside_rad'><i class='bi bi-globe grid-item_insidemenu_icon fs-4'></i></div>URL Scraping
					</div>
				</div>	
				
				<!-- AI API-->
				<div class='grid-item_insidemenu flex_insidemenu'>
					<div class='col-12 align-middle' hx-get='do_bq/".$bqkey_ai_tools_ppt."' hx-target='#editPanel' hx-swap='innerHTML' onclick='setSideWidth(\"30%\");'>
					<div class='menuinside_rad'><i class='bi bi-filetype-ppt grid-item_insidemenu_icon fs-4'></i></div>AI PPT</div>
				</div>	
				
				<!-- YT Transcript-->
				<div class='grid-item_insidemenu flex_insidemenu'>
					<div class='col-12 align-middle' hx-get='do_bq/".$bqkey_ai_tools_yt."' hx-target='#editPanel' hx-swap='innerHTML' onclick='setSideWidth(\"30%\");'>
						<div class='menuinside_rad'><i class='bi bi-play-btn grid-item_insidemenu_icon fs-4'></i></div>YT Transcript
					</div>
				</div>	
				
				<!-- Image Analyzer-->
				<div class='grid-item_insidemenu flex_insidemenu'>
					<div class='col-12 align-middle' hx-get='do_bq/".$bqkey_ai_tools_imganalyzer."' hx-target='#editPanel' hx-swap='innerHTML' onclick='setSideWidth(\"30%\");'>
						<div class='menuinside_rad'><i class='bi bi-image grid-item_insidemenu_icon fs-4'></i></div>Image Analyzer
					</div>
				</div>
				
				<!-- Global Scraper-->
				<div class='grid-item_insidemenu flex_insidemenu'>
					<div class='col-12 align-middle' hx-get='do_bq/".$bqkey_ai_tools_global_scraper."' hx-target='#editPanel' hx-swap='innerHTML' onclick='setSideWidth(\"30%\");'>
						<div class='menuinside_rad'><i class='bi bi-globe grid-item_insidemenu_icon fs-4'></i></div>Global Scraper
					</div>
				</div>
				
				<!-- AI PDF-->
				<div class='grid-item_insidemenu flex_insidemenu'>
					<div class='col-12 align-middle' hx-get='do_bq/".$bqkey_ai_tools_pdf."' hx-target='#editPanel' hx-swap='innerHTML' onclick='setSideWidth(\"30%\");'>
						<div class='menuinside_rad'><i class='bi bi-filetype-pdf grid-item_insidemenu_icon fs-4'></i></div>AI PDF
					</div>
				</div>
				
				<!-- Map HTML-->
				<div class='grid-item_insidemenu flex_insidemenu'>
					<div class='col-12 align-middle' hx-get='do_bq/".$bqkey_ai_tools_maphtml."' hx-target='#mainContent' hx-swap='innerHTML' onclick='setSideWidth(\"0%\");'>
						<div class='menuinside_rad'><i class='bi bi-map-fill grid-item_insidemenu_icon fs-4'></i></div>Map HTML
					</div>
				</div>	
				
				<!-- AI Pedia -->
				<div class='grid-item_insidemenu flex_insidemenu'>
					<div class='col-12 align-middle' hx-get='do_bq/".$bqkey_pedia."' hx-target='#editPanel' hx-swap='innerHTML' onclick='setSideWidth(\"30%\");'>
						<div class='menuinside_rad'><i class='bi bi-robot grid-item_insidemenu_icon fs-4'></i></div>AI Pedia
					</div>
				</div>
				
				
			 </div>
		</div>
		 	
		<!-- POC -->
		<div class='bq-subhead mt-1 mb-1'><b>POC</b></div>
		
		<div class='wrapper_insidemenu flex_insidemenu'>
			<div class='overview_insidemenu grid_insidemenu'>
				<!-- Pivot -->
				<div class='grid-item_insidemenu flex_insidemenu'>
					<div class='col-12 align-middle' hx-get='do_bq/".$bqkey_pivot."' hx-target='#mainContent' hx-swap='innerHTML'>
						<div class='menuinside_rad'><i class='bi bi-table grid-item_insidemenu_icon fs-4'></i></div>Pivot
					</div>
				</div>
				
				<!-- Power BI -->
				<div class='grid-item_insidemenu flex_insidemenu'>
					<div class='col-12 align-middle' hx-get='do_bq/".$bqkey_powerbi."' hx-target='#mainContent' hx-swap='innerHTML'  onclick='setSideWidth(\"0%\");'>
						<div class='menuinside_rad'><i class='bi bi-bar-chart-line-fill grid-item_insidemenu_icon fs-4'></i></div>Power BI
					</div>
				</div>
				
				<!-- All API's -->
				<div class='grid-item_insidemenu flex_insidemenu'>
					<div class='col-12 align-middle' hx-get='do_bq/".$bqkey_allapis."' hx-target='#editPanel' hx-swap='innerHTML' onclick='setSideWidth(\"30%\");'>
						<div class='menuinside_rad'><i class='bi bi-plug grid-item_insidemenu_icon fs-4'></i></div>All APIS
					</div>
				</div>
				
				<!-- Geo Attendance -->
				<div class='grid-item_insidemenu flex_insidemenu'>
					<div class='col-12 align-middle' hx-get='do_bq/".$bqkey_geoattendance."' hx-target='#editPanel' hx-swap='innerHTML' onclick='setSideWidth(\"30%\");'>
						<div class='menuinside_rad'><i class='bi bi-geo-alt grid-item_insidemenu_icon fs-4'></i></div>Geo Attendance
					</div>
				</div>
				
				<!-- Geo Distance -->
				<div class='grid-item_insidemenu flex_insidemenu'>  
					<div class='col-12 align-middle' hx-get='do_bq/".$bqkey_geodistance."' hx-target='#editPanel' hx-swap='innerHTML' onclick='setSideWidth(\"30%\");'>
						<div class='menuinside_rad'><i class='bi bi-sign-merge-left-fill grid-item_insidemenu_icon fs-4'></i></div>Geo Distance
					</div>
				</div>
				
				<!-- Excel Upload -->
				<div class='grid-item_insidemenu flex_insidemenu'>
					<div class='col-12 align-middle' hx-get='do_bq/".$bqkey_excelupload."' hx-target='#mainContent' hx-swap='innerHTML' onclick='setSideWidth(\"0%\");'>
						<div class='menuinside_rad'><i class='bi bi-file-earmark-excel grid-item_insidemenu_icon fs-4'></i></div>Excel Upload
					</div>
				</div>
				
				<!-- Visting Card -->
				<div class='grid-item_insidemenu flex_insidemenu'>
					<div class='col-12 align-middle' hx-get='do_bq/".$bqkey_vcard."' hx-target='#mainContent' hx-swap='innerHTML' onclick='setSideWidth(\"0%\");'>
						<div class='menuinside_rad'><i class='bi bi-person-vcard grid-item_insidemenu_icon fs-4'></i></div> Visiting Card
					</div>
				</div>
				
				<!-- Visting Card1 -->
				<div class='grid-item_insidemenu flex_insidemenu'>
					<div class='col-12 align-middle' hx-get='do_bq/".$bqkey_vcard1."' hx-target='#mainContent' hx-swap='innerHTML' onclick='setSideWidth(\"0%\");'>
						<div class='menuinside_rad'><i class='bi bi-person-vcard grid-item_insidemenu_icon fs-4'></i></div>Visiting Card (Compressed)
					</div>
				</div>				
				
			
				
				<!-- Logistics -->
				<div class='grid-item_insidemenu flex_insidemenu'>
					<div class='col-12 align-middle' hx-get='do_bq/".$bqkey_Logistics."' hx-target='#mainContent' hx-swap='innerHTML' onclick='setSideWidth(\"0%\");'>
						<div class='menuinside_rad'><i class='bi bi-truck grid-item_insidemenu_icon fs-4'></i></div>Logistics
				</div>
			</div>
		 </div>
		</div>
	  </div>
	  
	  <script>
		openHKPanel('hkutils')
	  </script>";
	  
	  
	  	/*<div class='grid-item_insidemenu flex_insidemenu'>
					<div class='col-12 align-middle' 
					hx-get='do_bq.php?bqkey=".pw_enc("pw=do_sathwik.php")."' 
					hx-target='#editPanel' 
					hx-swap='innerHTML' onclick='setSideWidth(\"40%\");'>
						<div class='menuinside_rad'><i class='bi bi-phone-fill grid-item_insidemenu_icon fs-4'></i></div>Pluros Mobile</div>
				</div>*/

?>