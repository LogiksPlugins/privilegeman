<style>
#peditor table,#peditor table td {
	border:0px;
}
</style>
<?php function printBar() { ?>
<select id=siteselector class='siteselector ui-state-active ui-corner-all' style='width:200px;' onchange="reloadList()"></select>
<button onclick="reloadList()" title="Load User Table" ><div class="reloadicon">Reload</div></button>

<button onclick="showInfo()" title="Show Selected User Information" ><div class="infousericon">Info</div></button>
<button onclick="newUser()" title="Create New User" ><div class="addusericon">Create</div></button>
<button onclick="editUser()" title="Edit Selected User" ><div class="editusericon">Edit</div></button>
<button onclick="showPWDWindow()" title="Change Password" ><div class="pwdicon">Pwd</div></button>
<button onclick="deleteUser()" title="Delete Selected User" ><div class="deleteusericon">Delete</div></button>

<button onclick="exportUsers()" title="Export Selected Users" ><div class="exporticon">Export</div></button>
<?php } ?>
<?php function printContent() { ?>
	<style>
	.pwdicon1 {background:transparent url(media/images/forbidden.png) no-repeat center left; width:48px; height:48px;padding-left:50px;}
	 #changePwdDlg table,#changePwdDlg td {border:0px;}
	</style>
	<table id=datatable width=99% cellpadding=0 cellspacing=0 border=1 style='margin:5px;border:1px solid #aaa;'>
		<thead>
			<tr align=center class='clr_darkblue'>
				<th width=40px>*</th>
				<th width=100px>UserID</th>
				<th>User Name</th>
				<th>Email</th>
				<th>Mobile</th>
				<th width=100px>Privilege</th>
				<th width=100px>Access</th>
				<th width=100px>Expires On</th>
				<th width=100px>Created</th>
				<th width=100px>Edited</th>
				<th width=70px>Blocked</th>
			</tr>
		</thead>
		<tbody>
		</tbody>
	</table>
	<div style='display:none;'>
		<div id=changePwdDlg title='Change Password' width=300px style=''>
			<h3 class='pwdicon1' style='width:90%;font-size:2.3em;color:#3DBBFF;padding-top:10px;'>Change Your Password</h3>
			<p align=justify>
				A password is a secret word or string of characters that is used for authentication, to prove identity
				or gain access to your account. Use a unique password for all your important accounts. <br/>
				New Password Should Be <b>Atleast <?=getConfig("PWD_MIN_LENGTH");?> Characters Long</b>.
			</p><hr/>
			<div>
				<button onclick="genratePWD()" style='width:25px;height:25px; float:right;'>
					<span class='ui-icon ui-icon-gear' style='margin-left:-10px;margin-top:-2px;'></span>Generate
				</button>
				<span style='font-size:14px;'>You can also use generator to generate strong Password.</span>
			</div><br/><hr/>
			<table width=100% border=0>
				<input type=hidden id=uid value='' />
				<input type=hidden id=rid value='' />
				<input type=hidden id=s value='' />
				<?php if($_SESSION["SESS_PRIVILEGE_ID"]!="1") { ?>
				<tr>
					<th width=150px align=right>Current Password</th><td><input type=password id=oldpwd class='' style='font-size:14px;font-weight:bold;' /></td>
				</tr>
				<tr><td colspan=10><hr/></td></tr>
				<?php } else {
					echo "<input type=hidden id=oldpwd value='*' />";
					  }
				?>
				<tr>
					<th width=150px align=right>New Password</th><td><input type=password id=newpwd1 class='' style='font-size:14px;font-weight:bold;' /></td>
				</tr>
				<tr>
					<th width=150px align=right>Confirm Password</th><td><input type=password id=newpwd2 class='' style='font-size:14px;font-weight:bold;' /></td>
				</tr>
				<tr><td colspan=10><hr/></td></tr>
				<tr><td colspan=10 align=right>
					<button onclick='changePWD()'>Change Password</button>
					<button onclick="$('#changePwdDlg').dialog('close');">Cancel</button>
					<br/><br/>
				</td></tr>
				<tr><td class='ui-widget-content' id=pwdmsgs colspan=10 style='padding:4px;' align=center>
					<h5 style='margin:0px;'>Please fill the all the fields.</h5>
				</td></tr>
			</table>
		</div>
		<div id=createUserDlg title='Create User' width=450px style=''>

		</div>
	</div>
<?php } ?>
<script language=javascript>
function reloadList() {
	v=$("#siteselector").val();
	if(v==null) {
		$("#datatable tbody").html("<tr><td colspan=10><div class=ajaxerror>No Site Selected</div></td></tr>");
		return;
	}
	lnk=getServiceCMD("privilegeman")+"&type=u&format=table&action=userlist&s="+v;
	//lnk="services/?scmd=privilegeman&site=<?=SITENAME?>&type=u&format=table&action=userlist&s="+v;
	$("#loadingmsg").show();
	$("#datatable tbody").html("<tr><td colspan=10><div class=ajaxloading3></div></td></tr>");
	$("#datatable tbody").load(lnk,function() {
			$("#datatable tr").click(function() {
					$(this).parents("tbody").find("input[name=selector]").removeAttr("checked");
					$(this).find("input[name=selector]").attr("checked","checked");
				});
			$("#datatable input[type=checkbox]").click(function() {
					if($(this).attr("name")=="blocked") {
						changeBlocked(this,'u');
					}
				});
			$("#loadingmsg").hide();
		});
}
function newUser() {
	v=$("#siteselector").val();
	if(v!=null && v.toUpperCase()=="CORE") {
		v="*";
	}

	//osxPopupURL("services/?scmd=userinfo&site=<?=SITENAME?>&mode=create&s="+v,"User Editor :: Create",null,700);
	//q="services/?scmd=userinfo&site=<?=SITENAME?>&mode=create&s="+v;
	q=getServiceCMD("userinfo")+"&mode=create&s="+v;
	lgksPopup(q,null,{
			width:750,
			height:$(window).height()-50,
			resizable:false,
			closeOnEscape:true,
			close:function() {
				$("#user_editor").parents("form").detach();
				return true;
			}
		},"url","User Editor :: Create");
}
function editUser() {
	if($("#datatable tr input[name=selector][checked=checked]").length>0) {
		tr=$("#datatable tr input[name=selector][checked=checked]").parents("tr");
		uid=tr.attr("rel");
		rid=tr.find("input[name=selector]").attr("refid");
		ss=tr.find("input[name=selector]").attr("site");

		//q="services/?scmd=userinfo&site=<?=SITENAME?>&mode=edit&uid="+uid+"&rid="+rid+"&s="+s;
		q=getServiceCMD("userinfo")+"&mode=edit&uid="+uid+"&rid="+rid+"&s="+ss;
		//osxPopupURL(q,"User Editor :: Edit",null,700);
		lgksPopup(q,null,{
				width:750,
				height:$(window).height()-50,
				resizable:false,
				closeOnEscape:true,
				close:function() {
					$("#user_editor").parents("form").detach();
					return true;
				}
			},"url","User Editor :: Edit");
	}
}
function showInfo() {
	if($("#datatable tr input[name=selector][checked=checked]").length>0) {
		tr=$("#datatable tr input[name=selector][checked=checked]").parents("tr");
		uid=tr.attr("rel");
		rid=tr.find("input[name=selector]").attr("refid");
		ss=tr.find("input[name=selector]").attr("site");

		//q="services/?scmd=userinfo&site=<?=SITENAME?>&mode=view&uid="+uid+"&rid="+rid+"&s="+ss;
		q=getServiceCMD("userinfo")+"&mode=view&uid="+uid+"&rid="+rid+"&s="+ss;
		//osxPopupURL(q,"User Info",null,700);
		lgksPopup(q,null,{
				width:750,
				height:$(window).height()-50,
				resizable:false,
				closeOnEscape:true,
				close:function() {
					$("#user_editor").parents("form").detach();
					return true;
				}
			},"url","User Info");
	}
}
function changePWD() {
	if($("#changePwdDlg input#oldpwd").val().length<=0) {
		$("#pwdmsgs").html("<h3 style='color:maroon;margin:0px;'>Old Password Is Required For Changing Password.</h3>.");
		$("#changePwdDlg input#oldpwd").focus();
	} else if($("#changePwdDlg input#newpwd1").val().length<<?=getConfig("PWD_MIN_LENGTH");?>) {
		$("#pwdmsgs").html("<h3 style='color:maroon;margin:0px;'>New Password Should Be Atleast <?=getConfig("PWD_MIN_LENGTH");?> Long.</h3>.");
		$("#changePwdDlg input#newpwd1").val("");
		$("#changePwdDlg input#newpwd2").val("");
		$("#changePwdDlg input#newpwd1").focus();
	} else if($("#changePwdDlg input#newpwd1").val()==$("#changePwdDlg input#oldpwd").val()) {
		$("#pwdmsgs").html("<h3 style='color:blue;margin:0px;'>Old Password And New Password Can Not Be Same.</h3>.");
		$("#changePwdDlg input#newpwd1").focus();
	} else if($("#changePwdDlg input#newpwd1").val()!=$("#changePwdDlg input#newpwd2").val()) {
		$("#pwdmsgs").html("<h3 style='color:maroon;margin:0px;'>Password Mismatch. <b>New Password</b> must match <b>Current Password</b></h3>.");
		$("#changePwdDlg input#newpwd2").val("");
		$("#changePwdDlg input#newpwd1").focus();
	} else {
		ss=tr.find("input[name=selector]").attr("site");
		//l="services/?scmd=privilegeman&site=<?=SITENAME?>&type=u&action=userpwdchange";
		l=getServiceCMD("privilegeman")+"&type=u&action=userpwdchange";
		q="&uid="+uid+"&rid="+rid+"&s="+ss+"&old="+encodeURIComponent($("#changePwdDlg input#oldpwd").val())+"&new="+encodeURIComponent($("#changePwdDlg input#newpwd1").val());

		processAJAXPostQuery(l,q,function(txt) {
				if(txt=="ok") {
					$('#changePwdDlg').dialog('close');
				} else {
					lgksAlert(txt);
				}
			});
	}
}
function showPWDWindow() {
	if($("#datatable tr input[name=selector][checked=checked]").length>0) {
		tr=$("#datatable tr input[name=selector][checked=checked]").parents("tr");
		uid=tr.attr("rel");
		rid=tr.find("input[name=selector]").attr("refid");
		ss=tr.find("input[name=selector]").attr("site");

		$("#changePwdDlg input[type=password]").val("");

		$("#changePwdDlg #uid").val(uid);
		$("#changePwdDlg #rid").val(rid);
		$("#changePwdDlg #s").val(ss);

		osxPopupDiv("#changePwdDlg");
	}
}
function deleteUser() {
	if($("#datatable tr input[name=selector][checked=checked]").length>0) {
		tr=$("#datatable tr input[name=selector][checked=checked]").parents("tr");
		id=tr.attr("rel");
		rid=tr.find("input[name=selector]").attr("refid");
		ss=tr.find("input[name=selector]").attr("site");

		q=getServiceCMD("privilegeman")+"&type=u&action=delete&id="+id+"&rid="+rid+"&s="+ss;

		lgksConfirm("Are You Sure About Deleting User "+id,"Delete User",function() {
				$("#loadingmsg").show();
				processAJAXQuery(q,function(txt) {
						if(txt.length>0) lgksAlert(txt);
						reloadList();
					});
			});
	}
}
function saveUserForm(id) {
	allok=true;
	toCheck=$(id).find("tr td div.checkcol");
	if(toCheck.length>0) {
		if(toCheck.hasClass("info_icon")) {
			checkUniqueUserID();
			lgksAlert("Please Check UserID. It Must Be Unique");
			return;
		} else if(toCheck.hasClass("error_icon")) {
			toCheck.parents("tr").find("input,select").css("background","#D1ECFF");
			toCheck.parents("tr").find("input,select").focus();
			lgksAlert("Please Check UserID. It Must Be Unique");
			return;
		}
	}

	$(id).find("input.required,select.required").each(function() {
			if(!allok) return;
			name=$(this).attr("name");
			val=$(this).val();
			if(val==null || val.length<=0) {
				allok=false;
				$(this).focus();
				$(this).parents("tr").css("background","#FFDAD6");
				return;
			}
		});
	if(allok) {
		saveForm(id,'u','saveuser', function(txt) {
				if(txt.trim().indexOf("created")==0) {
					pwd=txt.replace("created#","");
					uid=$("#useridfield").val();
					msg="Successfully Created UserID ::<br/><p style=''>";
					msg+="<table width=80% border=0>";
					msg+="<tr><td width=100px><b>UserID</b></td><td width=10px align=center> :: </td><td>"+uid+"</td></tr>";
					msg+="<tr><td width=100px><b>Password</b></td><td width=10px align=center> :: </td><td>"+pwd+"</td></tr>";
					msg+="</table></p>";
					msg+="<p>Please note down the UserID and Password. If you happen to forget the password please use Password reset.</p>Thank You";
					msg="<tr class='nohover'><td class='allok_icon' align=center><h1><br/>User Created Successfully<br/><br/></h1></td></tr><tr class='nohover'><td colspan=10>"+msg+"<br/><br/><br/></td></tr>";
					$(id).html(msg);
				} else if(txt.trim().indexOf("updated")==0) {
					uid=$("#user_editor input[name=userid]").val();
					msg="Successfully Updated UserID :: <b>"+uid+"</b><br/>";
					msg+="<p>Please note down the UserID and Password. If you happen to forget the password please use Password reset.</p>Thank You";
					msg="<tr class='nohover'><td class='allok_icon' align=center><h1><br/>User Created Successfully<br/><br/></h1></td></tr><tr class='nohover'><td colspan=10>"+msg+"<br/><br/><br/></td></tr>";
					$(id).html(msg);
				} else {
					lgksAlert(txt);
				}
				reloadList();
			});
	} else {
		lgksAlert("Please Confirm That All Required Fields Are Filled.");
	}
}
function genratePWD() {
	//q="services/?scmd=pwd&site=<?=SITENAME?>&type=generate";
	q=getServiceCMD("pwd")+"&type=generate";
	processAJAXQuery(q,function(data) {
			msg="<p style='font-size:!4px;'>Use <b>"+data+"</b> as your password.</p>";
			lgksConfirm(msg,"Generated Password",function() {
					$("#changePwdDlg input#newpwd1").val(data);
					$("#changePwdDlg input#newpwd2").focus();
				});
		});
}
function exportUsers() {
	lgksConfirm("Please select the type of export !!!","Export Users !").dialog({
		buttons:{
			"Table":function(){
				exportData("full");
				$(this).dialog("close");
			},
			"Emails":function() {
				exportData("email");
				$(this).dialog("close");
			},
			"Mobile":function() {
				exportData("mobile");
				$(this).dialog("close");
			},
			"Userids":function() {
				exportData("userid");
				$(this).dialog("close");
			}
		}
	});
	//jqPopupData(html,"User List",null,true,800,500);
}
function exportData(type) {
	html="<style>textarea{border:0px;width:100%;height:100%;resizable:none;}</style>";
	html+="<textarea readonly=true>";
	$("#datatable tbody tr").each(function() {
		tr=$(this);
		if(type=="full") {
			html+=tr.find("td[name=userid]").attr("rel");
			html+=","+tr.find("td[name=name]").attr("rel");
			html+=","+tr.find("td[name=email]").attr("rel");
			html+=","+tr.find("td[name=mobile]").attr("rel");
			html+=","+tr.find("td[name=privilege]").text();
			html+=","+tr.find("td[name=doc]").attr("rel");
			html+="\n";
		} else if(type=="email") {
			html+=tr.find("td[name=email]").attr("rel");
			html+=","+tr.find("td[name=name]").attr("rel");
			html+="\n";
		} else if(type=="mobile") {
			html+=tr.find("td[name=mobile]").attr("rel");
			html+=","+tr.find("td[name=name]").attr("rel");
			html+="\n";
		} else if(type=="userid") {
			html+=tr.find("td[name=userid]").attr("rel");
			html+=","+tr.find("td[name=name]").attr("rel");
			html+="\n";
		}
	});
	html+="</textarea>";
	win=window.open("","Users");
	win.document.write(html);
}
</script>
