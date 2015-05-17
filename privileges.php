<style>
#peditor table,#peditor table td {
	border:0px;
}
table#datatable,table#datatable td {
	border:0px;
	cursor:pointer;
}
</style>
<?php function printBar() { ?>
<select id=siteselector  class='siteselector ui-state-active ui-corner-all' style='width:200px;height:28px;' onchange='reloadList()'></select>
<button onclick="reloadList()" title="Load Privilege Table" ><div class="reloadicon">Reload</div></button>
<button onclick="newPrivilege()" title="Create New Privilege Group" ><div class="addicon">Create</div></button>
<button onclick="editPrivilege()" title="Edit Selected Privilege Group" ><div class="editicon">Edit</div></button>
<button onclick="deletePrivilege()" title="Delete Selected Privilege Group" ><div class="deleteicon">Delete</div></button>
<?php } ?>
<?php function printContent() { ?>
	<div id=peditor class="dialog ui-widget-content ui-corner-all" style="width:400px;height:200px;float:right;margin:10px;padding:5px;display:none;">
		<table width=100% cellpadding=3 cellspacing=0 border=0 class='nostyle input'>
			<input type=hidden name='id' value="0" />
			<tr align=left>
				<th width=150px>Privilege Title</th>
				<td>
					<input type=text name='name' />
				</td>
			</tr>
			<tr align=left>
				<th>For Site</th>
				<td>
					<input type=text name='s' id=forsite readonly />
				</td>
			</tr>
			<tr align=left>
				<th>Remarks</th>
				<td>
					<input type=text name='remarks' />
				</td>
			</tr>
			<tr>
				<td colspan=10><hr/></td>
			</tr>
			<tr>
				<td colspan=10 align=center>
					<button onclick="$('#peditor').hide();">Close</button>
					<button onclick="saveForm('#peditor','p','saveprivilege')">Save</button>
				</td>
			</tr>
		</table>
	</div>
	<table id=datatable class=datatable width=550px cellpadding=0 cellspacing=0 border=1 style='margin:5px;border:1px solid #aaa;width:550px;'>
		<thead>
			<tr align=center class='ui-widget-header'>
				<th width=40px>*</th>
				<th>Name</th>
				<th>For Site</th>
				<th width=70px>Blocked</th>
				<th>Remarks</th>
			</tr>
		</thead>
		<tbody>
		</tbody>
	</table>
	<br/>
	<table id=userstable width=550px cellpadding=0 cellspacing=0 border=1 style='margin:5px;border:1px solid #aaa;width:550px;'>
		<thead>
			<tr align=center class='ui-widget-header'>
				<th>UserID</th>
				<th>User Name</th>
				<th width=70px>Blocked</th>
			</tr>
		</thead>
		<tbody>
		</tbody>
	</table>
<?php } ?>
<script language=javascript>
$(function() {
	$("#datatable tbody").delegate("tr","click",function() {
			$(this).parents("tbody").find("tr.active").removeClass("active");
			r=$(this).find("input[name=selector][type=radio]");
			if(r.length>0) {
				$(this).addClass("active");
				r=r.get(0);
				r.checked=true;
			}
			loadUserListForPrivilege();
		});
});
function reloadList() {
	v=$(".siteselector").val();
	if(v==null || v.toUpperCase()=="CORE") {
		v="*";
	}
	lnk=getServiceCMD("privilegeman")+"&type=p&format=table&action=privilegelist&s="+v;
	$("#loadingmsg").show();
	$("#datatable tbody").html("<tr><td colspan=10><div class=ajaxloading3></div></td></tr>");
	$("#userstable tbody").html("<tr><td colspan=10><h3>No Users Found</h3></td></tr>");
	$("#datatable tbody").load(lnk,function() {
			$("#datatable input[type=checkbox]").click(function() {
					if($(this).attr("name")=="blocked") {
						changeBlocked(this,"p");
					}
				});
			$("#peditor").find("input").val("");
			$("#peditor").find("input[name=id]").val("0");
			$("#peditor").find("select").val("*");
			$("#peditor").hide();
			$("#loadingmsg").hide();
		});

}
function newPrivilege() {
	v=$(".siteselector").val();
	$("#peditor").find("input[name=name]").removeAttr("readonly");
	$("#peditor").find("input").val("");
	$("#peditor").find("input[name=id]").val("0");
	//$("#peditor").find("select").val("*");
	$("#peditor #forsite").val(v);
	$("#peditor").show();
}
function editPrivilege() {
	$("#peditor").hide();
	if($("#datatable tr.active").length>0) {
		tr=$("#datatable tr.active");
		id=tr.attr("rel");

		$("#peditor").find("input[name=id]").val(id);
		$("#peditor").find("input[name=name]").attr("readonly","readonly");
		$("#peditor").find("input[name=name]").val(tr.find("td[name=name]").attr("rel"));
		$("#peditor").find("select#forsite").val(tr.find("td[name=site]").attr("rel"));
		$("#peditor").find("input#forsite").val(tr.find("td[name=site]").attr("rel"));
		$("#peditor").find("input[name=remarks]").val(tr.find("td[name=remarks]").attr("rel"));

		$("#peditor").show();
	} else {
		lgksAlert("Please Select A Privilege To Edit.");
	}
}
function deletePrivilege() {
	if($("#datatable tr.active input[name=selector]").length>0) {
		tr=$("#datatable tr.active input[name=selector]").parents("tr");
		id=tr.attr("rel");
		ss=tr.find("td[name=site]").attr("rel");
		t=tr.find("td[name=name]").attr("rel");

		if($("#userstable tr").length>0) {
			lgksAlert("There are multiple user accounts associated.<br/>Can't Delete This Privilege.<br/>Consider Blocking This Privilege");
			return;
		}
		lgksConfirm("Do you really want to delete <b>"+t+"</b> ?<br/>This will not delete the users linked to the privilege though.",
				"Delete Privilege ?", function() {
					q=getServiceCMD("privilegeman")+"&type=p&action=delete";
					l="&id="+id+"&s="+ss;
					$("#loadingmsg").show();
					processAJAXPostQuery(q,l,function(txt) {
							if(txt.length>0) lgksAlert(txt);
							reloadList();
						});
				});
	} else {
		lgksAlert("Please Select A Privilege To Delete.");
	}
}
function loadUserListForPrivilege() {
	if($("#datatable tr.active input[name=selector]").length>0) {
		tr=$("#datatable tr.active input[name=selector]").parents("tr");
		id=tr.attr("rel");
		//ss=tr.find("td[name=site]").attr("rel");
		ss=$("#siteselector").val();
		t=tr.attr("rel");
		$("#userstable tbody").html("<tr><td colspan=10><div class=ajaxloading3></div></td></tr>");

		q=getServiceCMD("privilegeman")+"&action=usersforprivilege&type=p&format=table&s="+ss+"&pid="+t;
		$("#userstable tbody").load(q,function() {
				$("#userstable tbody input[type=checkbox]").attr ("disabled",true);
			});
	}
}
</script>
