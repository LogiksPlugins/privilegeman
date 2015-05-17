<?php function printBar() { ?>
<select id=siteselector class='siteselectorplain ui-state-active ui-corner-all' style='width:200px;height:28px;' onchange="reloadList()"></select>
<button onclick="reloadRoleList()" title="Load Role Table" ><div class="reloadicon">Reload</div></button>
<span id=msgbox style='color:green;display:none;'></span>
<?php } ?>
<?php function printContent() { ?>
	<div style='width:100%;height:100%;overflow:auto;'>
	<table id=datatable width=99% cellpadding=3 cellspacing=0 border=0 style='margin:5px;border:0px solid #aaa;' class='nostyle'>
		<thead id=roleheader class='ui-widget-header' align=center>
		</thead>
		<tbody>
			<tr><th><h3>Please Press Reload for showing the Site's Permission Model...</h3></th></tr>
		</tbody>
	</table>
	</div>
<?php } ?>
<style>
#roleheader th {
}
#roleheader th.roles {
	cursor:pointer;
	width:100px;
}
#datatable tbody tr.subheader {
	opacity:0.6;
}
#datatable tbody tr.subheader td {
	cursor:pointer;
	padding-left:35px;
	height:23px;
	font-weight:bold;
	font-size:14px;
}
#datatable tbody td:first-child {
	padding-left:10px;
	font-size:13px;
}
#datatable tbody td input[type=checkbox].role_check {
	cursor:pointer;
}
#datatable tbody tr:not(.subheader):not(.nohover):hover td {
	background:#E2FFC9;
	cursor:pointer;
}
.hover { background-color: #eee; }
</style>
<script language=javascript>
function reloadList() {
	reloadRoleList();
}
function reloadRoleList() {
	$("#loadingmsg").show();
	$("#datatable tbody").html("<tr><td colspan=100><div class=ajaxloading3></div></td></tr>");
	$("#roleheader").load(getServiceCMD("privilegeman")+"&type=r&format=table&action=roleheader&s="+$("#siteselector").val(), function() {
			loadRoleTable();
		});
}
function loadRoleTable() {
	$("#loadingmsg").show();
	$("#datatable tbody").html("<tr><td colspan=100><div class=ajaxloading3></div></td></tr>");

	r="";
	$("#roleheader th.roles").each(function() {
			r+=$(this).attr("rolename")+",";
		});
	l=getServiceCMD("privilegeman")+"&type=r&format=table&action=rolemodel";
	q="&s="+$("#siteselector").val()+"&roles="+encodeURIComponent(r);
	processAJAXPostQuery(l,q,function(txt) {
			$("#datatable tbody").html("<tr class='nohover'><td colspan=100 style='height:5px;'></td></tr>"+txt);
			$("#datatable tbody td input[type=checkbox].role_check").click(function() {
					changeRoleAccess(this);
				});
			$("#loadingmsg").hide();
			$("#msgbox").html("Click On CheckBoxs To Permit Activity For That Privileged Users.");
			$("#msgbox").show();
		});
}
function changeRoleAccess(checkBox) {
	l=getServiceCMD("privilegeman")+"&type=r&format=table&action=roleaccess";
	q="&s="+$("#siteselector").val()+"&rid="+$(checkBox).attr("roleid")+"&pid="+$(checkBox).attr("privilegeid");
	q+="&category="+$(checkBox).attr("category")+"&module="+$(checkBox).attr("module")+"&activity="+$(checkBox).attr("activity");
	if($(checkBox).is(":checked")) q+="&access=true";
	else q+="&access=false";
	processAJAXPostQuery(l,q,function(txt) {
			if(!isNaN(txt)) {
				$(checkBox).attr("roleid",txt);
				txt="";
			}
			if(txt.length>0) {
				lgksAlert(txt);
				if($(checkBox).is(":checked")) $(checkBox).removeAttr("checked");
				else  $(checkBox).attr("checked","checked");
			}
		});
}
function toggleRelatedRow(srcTable,ref) {
	srcTable.find('tr[ref='+ref+']').toggle();
}
</script>
