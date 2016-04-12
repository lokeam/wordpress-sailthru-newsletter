var iframe_timer;
var ajaxRequest, ajaxRequestSailthruList;
var uploader;
var submitHandlerFlag = false;
var iframeReloadFlag = false;
jQuery(document).ready(function () {
	jQuery('#menu').tabify();
	jQuery('#menu2').tabify();

	jQuery(".confirm_screen a").click(function() {

		if(jQuery(this).html()=="Ok"){
			jQuery("#sailthru-main-form").submit(function () {
				return true;
			});
		}else{
			jQuery(".confirm_screen").css("display","none");
		}
	});

	jQuery("#sync-db").click(function() {
		jQuery(".confirm_screen").css("display","block");
	});

	populateKeysAndLists();
	populateFiles();

});


function refresh_timer_iframe_stop(){

//	console.log("frame_stopped");
	reload_iframe_for_latest_log();
	iframeReloadFlag = false;
	jQuery("#sailthru_loader").fadeOut("slow");
	/*				if(iframe_timer){
	 iframe_timer.stop();
	 }*/
}


function handleFormSubmit(){

	jQuery("#sailthru_loader").fadeIn("slow");

	iframeReloadFlag = true;
	reload_iframe_for_latest_log();
	jQuery(".confirm_screen").css("display","none");

	if(!submitHandlerFlag){
		submitHandlerFlag = true;
	}else{
		return false;
	}
}

function populateKeysAndLists(){

	jQuery("#sailthru_market_loader").css("display","block");

	jQuery.ajax({
		type: 'POST',
		url: 'admin-ajax.php',
		data: {action: 'sailthru_get_keys',sailthru_dir:jQuery('#sailthru_market').find('option:selected').val()},
		success: function(result){
			var resultJsonObj = jQuery.parseJSON(result);
			jQuery("#sailthru_api_key").val(resultJsonObj["sailthru_api_key"]);
			jQuery("#sailthru_secret_key").val(resultJsonObj["sailthru_secret_key"]);
			jQuery.ajax({
				type: 'POST',
				url: 'admin-ajax.php',
				data: {action: 'sailthru_get_list',sailthru_dir:jQuery('#sailthru_market').find('option:selected').val()},
				dataType:'json',
				success: function(result){

					var resultJsonObj = result;
					var html = "";
					resultJsonObj = resultJsonObj.sort(function(a,b){
					  var a_lower_case = a.name.toLowerCase(), b_lower_case = b.name.toLowerCase();
					  return a_lower_case > b_lower_case ? 1 : a_lower_case < b_lower_case ? -1 : a.name > b.name ? 1 : a.name < b.name ? -1 : 0;
					});

					var optionValue = jQuery('#sailthru_list_default').attr('data-option-value');
					for(e in resultJsonObj){
						html += "<option value='"+resultJsonObj[e].name+"'>"+resultJsonObj[e].name+"</option>";
					}
					jQuery('#sailthru_list_default').html(html).val(optionValue);
					jQuery("#sailthru_market_loader").css("display","none");
				}
			});
		}
	});

}

function populateFiles(){
	if(ajaxRequestSailthruList){
		ajaxRequestSailthruList.abort();
	}

	jQuery("#list_loader").css("display","block");

	ajaxRequestSailthruList = jQuery.ajax({
		type: 'POST',
		url: 'admin-ajax.php',
		data: {action: 'sailthru_get_list',sailthru_dir:jQuery('#sailthru_dir').find('option:selected').val()},
		success: function(result){

			var resultJsonObj = jQuery.parseJSON(result);
			var html = "";
			resultJsonObj = resultJsonObj.sort(function(a,b){
			  var alc = a.name.toLowerCase(), blc = b.name.toLowerCase();
			  return alc > blc ? 1 : alc < blc ? -1 : a.name > b.name ? 1 : a.name < b.name ? -1 : 0;
			});
			console.log('inside populateFiles, showing sorted list:');
			console.log(resultJsonObj);
			for(e in resultJsonObj){
				html += "<option value='"+resultJsonObj[e].name+"'>"+resultJsonObj[e].name+"</option>";
			}

			jQuery('#sailthru_files').html(html);
			jQuery("#list_loader").css("display","none");
		}
	});

}


function file_upload_done_update(){
	submitHandlerFlag = false;
	refresh_timer_iframe_stop();
}
