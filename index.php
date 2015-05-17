<?php
if(!defined('ROOT')) exit('No direct script access allowed');

session_check(true);
user_admin_check(true);

loadModule("page");

$params=array();

$params["toolbar"]="printBar";
$params["contentarea"]="printContent";

$webPath=getWebPath(__FILE__);

$typePage=$_REQUEST["type"];

include "{$typePage}.php";

printPageContent("apppage",$params);
?>
<link href='<?=$webPath?>style.css' rel='stylesheet' type='text/css' media='all' />
<script language=javascript>
$(function() {
	$(".siteselectorplain").load(getServiceCMD("qtools")+"&action=sitelist&format=select",function() {
			<?php
				if(isset($_REQUEST['forsite'])) {
					echo "$('.siteselectorplain').val('{$_REQUEST['forsite']}');";
				} else {
					echo "$('.siteselectorplain').val($('.siteselector option:first-child').val());";
				}
			?>
			if(typeof reloadList == "function") reloadList();
		});

	$(".siteselector").load(getServiceCMD("qtools")+"&action=applist&format=select",function() {
			if($(".siteselector option[value='*']").length>0) {
				$(".siteselector").val("*");
			} else {
				<?php
					if(isset($_REQUEST['forsite'])) {
						echo "$('.siteselector').val('{$_REQUEST['forsite']}');";
					} else {
						echo "$('.siteselector').val($('.siteselector option:first-child').val());";
					}
				?>
			}
			if(typeof reloadList == "function") reloadList();
		});
});
function changeBlocked(ele,type,callBack) {
	if(callBack==null) callBack=reloadList;

	tr=$(ele).parents("tr");
	l=getServiceCMD("privilegeman")+"&type="+type+"&action=block";
	q="&id="+tr.attr("rel")+"&s="+tr.find("input[name=selector]").attr("site");
	if($(ele).is(":checked")) q+="&block=true";
	else q+="&block=false";

	$("#loadingmsg").show();
	processAJAXPostQuery(l,q,function(txt) {
			if(txt.length>0) {
				lgksAlert(txt);
				callBack();
			}
			$("#loadingmsg").hide();
		});
}
function saveForm(id, type, cmd, callBack) {
	if(callBack==null) callBack=reloadList;
	//lnk="services/?scmd=privilegeman&type="+type+"&action="+cmd;
	lnk=getServiceCMD("privilegeman")+"&type="+type+"&action="+cmd;
	q="";
	$("#loadingmsg").show();
	$(id).find("input[name],select[name],textarea[name]").each(function() {
			name=$(this).attr("name");
			val=$(this).val();
			if(val==null) val="";
			if(name!=null && name.length>0) { q+="&"+name+"="+val; }
		});

	processAJAXPostQuery(lnk,q,function(txt) {
			$("#loadingmsg").hide();
			if(typeof callBack == "function") callBack(txt);
			else if(txt.length>0) lgksAlert(txt);
		});
}
</script>

