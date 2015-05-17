<?php
if(!defined('ROOT')) exit('No direct script access allowed');
checkServiceSession();
isAdminSite();

loadHelpers("pwdhash");

if(isset($_REQUEST["action"])) {
	$tbl="";
	if(isset($_REQUEST["type"])) {
		if(strtoupper($_REQUEST["type"])=="P") {$tbl=_dbtable("privileges",true);}
		elseif(strtoupper($_REQUEST["type"])=="U") {$tbl=_dbtable("users",true);}
		elseif(strtoupper($_REQUEST["type"])=="R") {$tbl=_dbtable("rolemodel",true);}
		else {
			printErr("TypeNotSupported","Requested Type Is Not Supported.");
			exit();
		}
	} else {
		printErr("TypeNotSupported","Requested Type Is Not Supported.");
		exit();
	}
	if(isset($_REQUEST['s']) && strtolower($_REQUEST['s'])=="core") $_REQUEST['s']="*";
	if(isset($_POST['s']) && strtolower($_POST['s'])=="core") $_POST['s']="*";
	if(isset($_GET['s']) && strtolower($_GET['s'])=="core") $_GET['s']="*";

	if(!isset($_REQUEST['s'])) {
		printErr("WrongFormat","Requested Format Ommits Required Fields.");
		exit();
	}

	if($_REQUEST["action"]=="privilegelist") {
		if($_REQUEST['s']=="**") {
			checkUserSiteAccess("*",true);
		} else {
			checkUserSiteAccess($_REQUEST['s'],true);
		}

		$sql="SELECT id,name,site,blocked,remarks FROM $tbl";
		if(isset($_REQUEST['s'])) {
			if($_REQUEST['s']=="*") {
				if($_SESSION['SESS_PRIVILEGE_ID']>3) {
					exit("<tr><td colspan=10><h3 align=center>Wrong Site Selected</h3></td></tr>");
				}
			} elseif($_REQUEST['s']=="**") {
				if($_SESSION['SESS_PRIVILEGE_ID']<=3) {
					$sql.=" WHERE site='*' ";
				} else {
					exit("<tr><td colspan=10><h3 align=center>Wrong Site Selected</h3></td></tr>");
				}
			} else {
				$sql.=" WHERE (site='{$_REQUEST['s']}' OR site='*') ";
			}
		} else {
			exit("<tr><td colspan=10><h3 align=center>Wrong Site Selected</h3></td></tr>");
		}
		$sql.=" order by id";

		$r=_dbQuery($sql,true);
		if($r) {
			$a=_db(true)->fetchAllData($r);
			$o=array();
			foreach($a as $x=>$b) {
				if(checkUserSiteAccess($b['site'],false)) {
					$idl=$b["id"];
					$o[$b["id"]]=$b;
					$o[$b["id"]]["id"]="<input name=selector rel=$idl type=radio />";
				} else {
					$idl=$b["id"];
					$b['blocked']="";
					$o[$b["id"]]=$b;
					$o[$b["id"]]["id"]="<input name=selector rel=$idl type=radio />";
				}
			}
			printFormattedArray($o);
			_db(true)->freeResult($r);
		}
	} elseif($_REQUEST["action"]=="saveprivilege") {
		checkUserSiteAccess($_POST["s"],true);

		$id=$_POST["id"];
		$name=$_POST["name"];
		$site=$_POST["s"];
		$remarks=$_POST["remarks"];
		$sql="";
		if($id=="" || $id=="0") {
			$sql="INSERT INTO $tbl (id,name,site,blocked,remarks) VALUES(0,'$name','$site','false','$remarks')";
		} else {
			$sql="UPDATE $tbl SET name='$name',site='$site',remarks='$remarks' WHERE id=$id";
		}
		_dbQuery($sql,true);
	}
	//Usermanager
	elseif($_REQUEST["action"]=="saveuser") {
		checkUserSiteAccess($_REQUEST['s'],true);

		//echo "mode  {$_REQUEST['modeCmd']}={$_SESSION[$_REQUEST['modeCmd']]}";
		if(!isset($_REQUEST['modeCmd']) || !isset($_SESSION[$_REQUEST['modeCmd']])) {
			printErr("AccessDenial","<b>The Form Is Out Of League.</b> <br/>Please recreate the form or reload the tab or reload the page to fix this problem.");
			exit();
		}
		//printArray($_POST);
		if($_SESSION[$_REQUEST['modeCmd']]=="edit" || $_SESSION[$_REQUEST['modeCmd']]=="totedit" || $_SESSION[$_REQUEST['modeCmd']]=="infoedit") {
			$cols="site,privilege,access,name,email,mobile,address,region,country,zipcode,blocked,expires,remarks,notes,privacy,q1,a1,doe";
			$sql="UPDATE $tbl SET %s WHERE id={$_POST["id"]} AND userid='{$_POST["userid"]}'";

			$_POST["site"]=$_POST["s"];
			$_POST["doe"]=date("Y-m-d");

			$colsArr=explode(",",$cols);
			$colsArr=array_flip($colsArr);

			foreach($colsArr as $a=>$b) {
				if(isset($_POST[$a])) {
					if($a=="pwd") $colsArr[$a]="pwd='{$_POST[$a]}'";
					elseif(is_numeric($_POST[$a])) $colsArr[$a]="$a={$_POST[$a]}";
					elseif(strlen($_POST[$a])>0) $colsArr[$a]="$a='{$_POST[$a]}'";
					else unset($colsArr[$a]);
				} else unset($colsArr[$a]);
			}
			$sql=sprintf($sql,implode(",",$colsArr));

			_dbQuery($sql,true);
			echo "updated";
		} elseif($_SESSION[$_REQUEST['modeCmd']]=="create") {
			if(checkUserID($_POST["userid"],$_POST["s"])) {
				printErr("NotAcceptable","UserID Already Exists In Database. Please try Some Other UserID.");
				exit();
			}

			$cols="id,userid,pwd,site,privilege,access,name,email,mobile,address,region,country,zipcode,blocked,expires,remarks,notes,privacy,q1,a1,doc,doe";
			$sql="INSERT INTO $tbl ($cols) VALUES (%s)";

			$pwd=ucwords(substr(md5(date("YmdHmis")),2,getConfig("PWD_MIN_LENGTH")));

			if(!isset($_POST["blocked"])) $_POST["blocked"]="false";
			if(!isset($_POST["notes"])) $_POST["notes"]="";
			if(!isset($_POST["q1"])) $_POST["q1"]="";
			if(!isset($_POST["a1"])) $_POST["a1"]="";
			if(!isset($_POST["pwd"])) $_POST["pwd"]=getPWDHash($pwd); else {
				$pwd=$_POST["pwd"];
				$_POST["pwd"]=getPWDHash($_POST["pwd"]);
			}

			$_POST["site"]=$_POST["s"];
			$_POST["doc"]=date("Y-m-d");
			$_POST["doe"]=date("Y-m-d");

			$colsArr=explode(",",$cols);
			$colsArr=array_flip($colsArr);
			foreach($colsArr as $a=>$b) {
				if(isset($_POST[$a])) {
					if($a=="pwd") $colsArr[$a]="'{$_POST[$a]}'";
					elseif(is_numeric($_POST[$a])) $colsArr[$a]="{$_POST[$a]}";
					else $colsArr[$a]="'{$_POST[$a]}'";
				} else {
					$colsArr[$a]="''";
				}
			}
			$sql=sprintf($sql,implode(",",$colsArr));

			_dbQuery($sql,true);
			/*if(checkUserID($_POST["userid"],$_POST["s"])) {
				echo "created#$pwd";
			} else {
				echo "failed";
			}*/
			echo "created#$pwd";
		} else {
			printErr("NotAcceptable","Selected For Mode Is Not Acceptable By Server.");
			exit();
		}
		unset($_SESSION[$_REQUEST['modeCmd']]);
	}
	elseif($_REQUEST["action"]=="userpwdchange") {
		checkUserSiteAccess($_REQUEST['s'],true);

		$sql="UPDATE $tbl SET pwd ='%s' WHERE id='{$_POST['rid']}' AND userid='{$_POST['uid']}' and site='{$_POST['s']}'";
		$sql1="SELECT pwd FROM $tbl WHERE id='{$_POST['rid']}' AND userid='{$_POST['uid']}' and site='{$_POST['s']}'";
		$sql2="SELECT count(*) as cnt FROM $tbl WHERE id='{$_POST['rid']}' AND userid='{$_POST['uid']}' and site='{$_POST['s']}'";
		$r=_dbQuery($sql1,true);
		$ra=_dbData($r);
		if(!isset($ra[0])) {
			exit("No UserID Found For Changing Password");
		}
		$ra=$ra[0];

		$_POST["old"]=getPWDHash($_POST["old"]);
		$_POST["new"]=getPWDHash($_POST["new"]);

		if($ra["pwd"]!=$_POST["old"]) {
			if($_SESSION["SESS_PRIVILEGE_ID"]!="1") {
				exit("Old Password Doesn't Match");
			}
		}

		$sql=sprintf($sql,$_POST["new"]);
		_dbQuery($sql,true);

		$sql2.=" and pwd='{$_POST["new"]}'";

		$r=_dbQuery($sql2,true);
		$ra=_dbData($r);
		if(!isset($ra[0])) {
			exit("Failed To Update Password.");
		}
		$ra=$ra[0];
		if($ra["cnt"]>0) {
			exit("ok");
		} else {
			exit("Failed To Update Password.");
		}
	}
	elseif($_REQUEST["action"]=="usersforprivilege") {
		checkUserSiteAccess($_REQUEST['s'],true);
		$tbl1=_dbtable("users",true);
		$tbl2=_dbtable("access",true);
		$sql="SELECT userid,name,blocked FROM $tbl1 WHERE privilege='{$_REQUEST['pid']}' AND ";
		$sql.="access IN (SELECT id FROM $tbl2 WHERE FIND_IN_SET('{$_REQUEST['s']}',sites))";
		//echo $sql;
		$r=_dbQuery($sql,true);
		if($r) {
			$a=_dbData($r);
			_dbFree($r);
			if(count($a)>0) {
				printFormattedArray($a);
			} else {
				echo "<tr><td colspan=10><h3>No Users Found</h3></td></tr>";
			}
		} else {
			echo "<tr><td colspan=10><h3>No Users Found</h3></td></tr>";
		}
	}
	elseif($_REQUEST["action"]=="userlist") {
		checkUserSiteAccess($_REQUEST['s'],true);

		$tbl1=_dbtable("users",true);
		$tbl2=_dbtable("privileges",true);
		$tbl3=_dbtable("access",true);
		$sql="SELECT $tbl1.id,$tbl1.userid,$tbl1.name as username,$tbl1.email,$tbl1.site,$tbl1.mobile,$tbl1.privilege,$tbl1.access,$tbl1.blocked,$tbl1.expires,$tbl1.doc,$tbl1.doe,$tbl2.name as privilegename,$tbl3.master as accessname ";
		$sql.="FROM $tbl1,$tbl2,$tbl3 WHERE ";
		$sql.="$tbl1.privilege=$tbl2.id and $tbl1.access=$tbl3.id";
		if(isset($_REQUEST['s']) && $_REQUEST['s']!="*") {
			//$sql.=" AND $tbl1.site='{$_REQUEST['s']}' ";
			$sql.=" AND (SELECT FIND_IN_SET('{$_REQUEST['s']}',lgks_access.sites))";
		} else {
			$sql.=" AND $tbl1.site is not null ";
		}
		$sql.=" order by $tbl1.id,$tbl1.site";
		//echo $sql;exit();
		$r=_dbQuery($sql,true);
		if($r) {
			$a=_db(true)->fetchAllData($r);
			$o=array();
			foreach($a as $x=>$b) {
				/*$idl=$b["id"];
				$o[$b["id"]]=$b;
				$o[$b["id"]]["id"]="<input name=selector rel=$idl type=radio />";*/
				$o[$b['userid']]["id"]="<input name=selector rel='{$b['userid']}' refid='{$b['id']}' site='{$b['site']}' type=radio />";
				$o[$b['userid']]["userid"]=$b['userid'];
				$o[$b['userid']]["name"]=$b['username'];
				if(strlen($b['email'])>0) {
					$o[$b['userid']]["email"]=array("name"=>"email","rel"=>$b['email'],
						"text"=>"<a onclick='parent.openMailPad(\"{$b['email']}\");' ><div title='{$b['email']}' class='emailicon'></div></a>",
						"title"=>$b['email'],"align"=>"center");
				} else {
					$o[$b['userid']]["email"]=array("name"=>"email","rel"=>$b['email'],
						"text"=>"<div title='No Email Found' class='nodataicon'></div>",
						"title"=>$b['email'],"align"=>"center");
				}
				$o[$b['userid']]["mobile"]=$b['mobile'];
				$o[$b['userid']]["privilege"]=array("name"=>"privilege","rel"=>$b["privilege"],"text"=>$b["privilegename"]);
				$o[$b['userid']]["access"]=array("name"=>"access","rel"=>$b["access"],"text"=>$b["accessname"]);
				$expires=_pDate($b['expires']);
				if($expires==_pDate("0000-00-00")) $expires="";
				$o[$b['userid']]["expires"]=$expires;
				$o[$b['userid']]["doc"]=_pDate($b['doc']);
				$o[$b['userid']]["doe"]=_pDate($b['doe']);
				$o[$b['userid']]["blocked"]=$b['blocked'];
			}
			printFormattedArray($o);
			_db(true)->freeResult($r);
		}
	}
	//RoleModel
	elseif($_REQUEST["action"]=="roleheader") {
		checkUserSiteAccess($_REQUEST["s"],true);

		$sql="SELECT id,name,site,blocked,remarks FROM "._dbtable("privileges",true);
		if(isset($_REQUEST['s']) && $_REQUEST['s']!="*") {
			$sql.=" WHERE (site='{$_REQUEST['s']}' OR site='*') AND id>=3";
		} else {
			//$sql.=" WHERE site='*' ";
		}
		$sql.=" order by id";
		$r=_dbQuery($sql,true);
		if($r) {
			$a=_db(true)->fetchAllData($r);
			$s="<tr>";
			$s.="<th width=400px style='min-width:200px;width:200px;'>Permission Activity</th>";
			//$s.="<th width=100px>Category</th>";
			$s.="<th width=100px>Module</th>";
			$s.="%s";
			$s.="</tr>";

			$roleCols="";
			foreach($a as $x) {
				$title=$x['name'];
				$title=str_replace("_"," ",$title);
				$title=ucwords($title);
				$roleCols.="<th class='roles' rolename='{$x['name']}' roleid='{$x['id']}' style='padding:0px;padding-left:5px;padding-right:5px;width:100px' title='{$title}'>{$title}</th>";
			}
			echo sprintf($s,$roleCols);
			_db(true)->freeResult($r);
		}
	} elseif($_REQUEST["action"]=="rolemodel") {
		checkUserSiteAccess($_REQUEST["s"],true);

		$reqRoles=explode(",",$_POST['roles']);
		$reqRoles=array_flip($reqRoles);
		unset($reqRoles[""]);
		$reqRoles=array_flip($reqRoles);

		$tbl1=_dbtable("privileges",true);
		$sql="SELECT $tbl.id, $tbl.category, $tbl.module, $tbl.activity, $tbl.privilegeid, $tbl.access, $tbl.role_type, $tbl.doc, $tbl.doe, $tbl1.blocked ";
		$sql.="FROM $tbl,$tbl1 WHERE $tbl.privilegeid=$tbl1.name";

		if(isset($_REQUEST['s']) && $_REQUEST['s']!="*") {
			$sql.=" AND $tbl.site='{$_REQUEST['s']}' ";
		} else {
			printErr("MethodNotAllowed","Requested Method Not Supported.");
			exit();
		}
		$sql.=" order by category, module, activity";
		$r=_dbQuery($sql,true);
		if($r) {
			$outArr=array();
			while($a=_db(true)->fetchData($r)) {
				if(!array_key_exists($a['category'],$outArr)) $outArr[$a['category']]=array();
				$md=$a["category"].$a["module"].$a["activity"];
				$md=md5($md);
				if(array_key_exists($md,$outArr[$a['category']])) {
					$outArr[$a['category']][$md]["privilege"][$a["privilegeid"]]=array($a["id"],$a["access"],$a["role_type"],$a["privilegeid"]);
				} else {
					$u=$a;
					unset($u["privilegeid"]);unset($u["blocked"]);
					unset($u["access"]);unset($u["id"]);
					unset($u["role_type"]);unset($u["doc"]);unset($u["doe"]);
					$u["privilege"]=array($a["privilegeid"]=>array($a["id"],$a["access"],$a["role_type"],$a["privilegeid"]));
					$outArr[$a['category']][$md]=$u;
				}
			}
			_db(true)->freeResult($r);

			//echo "<tr><td colspan=100>";
			//printArray($reqRoles);
			//printArray($outArr);
			//echo "</td></tr>";

			foreach($outArr as $a=>$b) {
				$tgrps="<tr class='subheader clr_darkblue'><td colspan=100>$a <div onclick=\"toggleRelatedRow($(this).parents('tbody'),'row_{$a}');\" class='right' style='width:20px;height:20px;'>^</div></td></tr>";
				echo $tgrps;
				//printArray($b);
				foreach($b as $x=>$y) {
					$tr="<tr ref='row_{$a}'>";
					$tr.="<td align=left>".ucwords($y['activity'])."</td>";
					$tr.="<td rel='{$y['module']}'>".ucwords($y['module'])."</td>";
					foreach($reqRoles as $r) {
						$rx="";
						$re="";
						if(isset($y['privilege'][$r])) {
							$re="roleid='{$y['privilege'][$r][0]}' role_type='{$y['privilege'][$r][2]}' privilegeid='{$y['privilege'][$r][3]}'";
							if($y['privilege'][$r][1]=="true")
								$rx="<input class='role_check' type=checkbox {$re} method='update' checked title='For {$y['privilege'][$r][3]}' />";
							else
								$rx="<input class='role_check' type=checkbox {$re} method='update' title='For {$y['privilege'][$r][3]}' />";
						} else {
							$re="roleid='0' role_type='auto' privilegeid='$r' category='$a' module='{$y['module']}' activity='{$y['activity']}' ";
							$rx="<input class='role_check' type=checkbox {$re} method='insert' title='For {$r}' />";
						}
						$tr.="<td class='{$r}' rolename='{$r}' align=center>{$rx}</td>";
					}
					$tr.="</tr>";
					echo $tr;
				}
			}
		}
	} elseif($_REQUEST["action"]=="roleaccess") {
		checkUserSiteAccess($_POST["s"],true);
		$date=date("Y-m-d");
		$user=$_SESSION["SESS_USER_ID"];
		if($_POST['rid']<=0) {
			$sql="INSERT INTO $tbl (id,site,category,module,activity,privilegeid,access,role_type,userid,doc,doe) VALUES ";
			$sql.="(0, '{$_POST['s']}', '{$_POST['category']}','{$_POST['module']}','{$_POST['activity']}','{$_POST['pid']}','{$_POST['access']}','auto','{$user}','{$date}','{$date}')";
			//echo $sql;exit();
			_dbQuery($sql);
			if(mysql_affected_rows()<=0) {
				echo "Error Updating Role";
			} else {
				echo _db()->insert_id();
			}
		} else {
			$sql="UPDATE $tbl SET access = '{$_POST['access']}', doe = '{$date}', userid='{$user}' WHERE id = {$_POST['rid']} AND site = '{$_POST['s']}' AND privilegeid = '{$_POST['pid']}'";
			//echo $sql;exit();
			_dbQuery($sql);
			if(mysql_affected_rows()<=0) {
				echo "Error Updating Role";
			}
		}
	}

	//Common Functions
	elseif($_REQUEST["action"]=="block") {
		if(isset($_REQUEST['id']) && isset($_REQUEST['block'])) {
			$acp=explode(",",ADMIN_USERIDS);
			if(in_array($_REQUEST['id'],$acp)) {
				printErr("AccessDenial","The userid <b>{$_REQUEST['id']}</b> is a System Privileged ID and Can't Be Blocked.");
				exit();
			}
			$sql="";
			if($_REQUEST["type"]=="p") $sql="UPDATE $tbl SET blocked='{$_REQUEST['block']}' WHERE id={$_REQUEST['id']} AND site='{$_REQUEST['s']}'";
			elseif($_REQUEST["type"]=="u") $sql="UPDATE $tbl SET blocked='{$_REQUEST['block']}' WHERE userid='{$_REQUEST['id']}' AND site='{$_REQUEST['s']}'";
			//echo $sql;
			_dbQuery($sql,true);
		}
	} elseif($_REQUEST["action"]=="delete") {
		if(isset($_REQUEST['id'])) {
			$acp=explode(",",ADMIN_USERIDS);
			if(in_array($_REQUEST['id'],$acp)) {
				printErr("AccessDenial","The userid <b>{$_REQUEST['id']}</b> is a System Privileged ID and Can't Be Deleted");
				exit();
			}
			checkUserSiteAccess($_REQUEST["s"],true);
			$sql="";
			if($_REQUEST["type"]=="p") $sql="DELETE FROM $tbl WHERE id={$_REQUEST['id']} AND site='{$_REQUEST['s']}'";
			elseif($_REQUEST["type"]=="u") $sql="DELETE FROM $tbl WHERE userid='{$_REQUEST['id']}' AND site='{$_REQUEST['s']}'";
			_dbQuery($sql,true);
		}
	}
}
exit();
?>
