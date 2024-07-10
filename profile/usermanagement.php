<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/PermissionsManager.php');
include_once($SERVER_ROOT.'/classes/ProfileManager.php');
@include_once($SERVER_ROOT.'/content/lang/profile/usermanagement.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);

$loginAs = array_key_exists("loginas",$_REQUEST) ? htmlspecialchars($_REQUEST["loginas"], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : "";
$searchTerm = array_key_exists("searchterm",$_REQUEST) ? htmlspecialchars($_REQUEST["searchterm"], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : "";
$userId = array_key_exists("userid",$_REQUEST) ? htmlspecialchars($_REQUEST["userid"], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : "";
$delRole = array_key_exists("delrole",$_REQUEST) ? htmlspecialchars($_REQUEST["delrole"], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : "";
$tablePk = array_key_exists("tablepk",$_REQUEST) ? htmlspecialchars($_REQUEST["tablepk"], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : "";

$userManager = new PermissionsManager();
if($IS_ADMIN){
	if($loginAs){
		$pHandler = new ProfileManager();
		$pHandler->setUserName($loginAs);
		$pHandler->authenticate();
		header("Location: ../index.php");
	}
	elseif($delRole){
		$userManager->deletePermission($userId,$delRole,$tablePk);
	}
	elseif(array_key_exists("apsubmit",$_POST)){
		foreach($_POST["p"] as $pname){
			$role = $pname;
			$tablePk = '';
			if(strpos($pname,'-')){
				$tok = explode('-',$pname);
				$role = $tok[0];
				$tablePk = $tok[1];
			}
			$userManager->addPermission($userId, $role, $tablePk);
		}
	}
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE . ' ' . $LANG['USER_MNGMT']; ?></title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<style>
		th{ font-size: 90% }
		/* alert box from https://www.w3schools.com/howto/howto_js_alert.asp */

		/* The alert message box */
		.alert {
			max-width: 30%;
			padding: 20px;
			background-color: green; /* Red */
			color: white;
			margin-bottom: 15px;
		}

		/* The close button */
		.closebtn {
			margin-left: 15px;
			color: white;
			font-weight: bold;
			float: right;
			font-size: 22px;
			line-height: 20px;
			cursor: pointer;
			transition: 0.3s;
		}

		/* When moving the mouse over the close button */
		.closebtn:hover {
			color: black;
		}
	</style>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($profile_usermanagementMenu)?$profile_usermanagementMenu:"true");
	include($SERVER_ROOT.'/includes/header.php');
	if(isset($profile_usermanagementCrumbs)){
		echo '<div class="navpath">';
		echo '<a href="../index.php">Home</a> &gt; ';
		echo $profile_usermanagementCrumbs;
		echo ' <b>'.(isset($LANG['USER_MNGMT'])?$LANG['USER_MNGMT']:'User Management').'</b></div>';
	}
	?>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading"><?= $LANG['USER_MNGMT']; ?></h1>
		<div style="float:right;">
			<div style="margin:10px 0px 15px 0px;">
				<section class="fieldset-like" style="background-color:#FFFFCC;">
					<h2> <span> <?php echo (isset($LANG['SEARCH'])?$LANG['SEARCH']:'Search'); ?> </span> </h2>
					<form name='searchform1' action='usermanagement.php' method='post'>
					<label for="searchterm" > <?php echo (isset($LANG['LAST_OR_LOGIN'])?$LANG['LAST_OR_LOGIN']:'Last Name or Login Name'); ?>: </label> <br>
						<input type="text" id="searchterm" name="searchterm" title="<?php echo (isset($LANG['ENTER_LAST'])?$LANG['ENTER_LAST']:'Enter Last Name'); ?>" /><br/>
						<button name="submit" type="submit" value="Search"><?php echo (isset($LANG['SEARCH'])?$LANG['SEARCH']:'Search'); ?></button>
					</form>

					<span class="screen-reader-only">
						<a href = "#userlist"><?php echo (isset($LANG['SKIP_LINK'])?$LANG['SKIP_LINK']:'Skip to list of users'); ?></a>
					</span>

					<?php echo (isset($LANG['QUICK_SEARCH'])?$LANG['QUICK_SEARCH']:'Quick Search'); ?>:
					<div style='margin:2px 0px 0px 10px;'>
						<div><a href='usermanagement.php?searchterm=A'>A</a>|<a href='usermanagement.php?searchterm=B'>B</a>|<a href='usermanagement.php?searchterm=C'>C</a>|<a href='usermanagement.php?searchterm=D'>D</a>|<a href='usermanagement.php?searchterm=E'>E</a>|<a href='usermanagement.php?searchterm=F'>F</a>|<a href='usermanagement.php?searchterm=G'>G</a>|<a href='usermanagement.php?searchterm=H'>H</a></div>
						<div><a href='usermanagement.php?searchterm=I'>I</a>|<a href='usermanagement.php?searchterm=J'>J</a>|<a href='usermanagement.php?searchterm=K'>K</a>|<a href='usermanagement.php?searchterm=L'>L</a>|<a href='usermanagement.php?searchterm=M'>M</a>|<a href='usermanagement.php?searchterm=N'>N</a>|<a href='usermanagement.php?searchterm=O'>O</a>|<a href='usermanagement.php?searchterm=P'>P</a>|<a href='usermanagement.php?searchterm=Q'>Q</a></div>
						<div><a href='usermanagement.php?searchterm=R'>R</a>|<a href='usermanagement.php?searchterm=S'>S</a>|<a href='usermanagement.php?searchterm=T'>T</a>|<a href='usermanagement.php?searchterm=U'>U</a>|<a href='usermanagement.php?searchterm=V'>V</a>|<a href='usermanagement.php?searchterm=W'>W</a>|<a href='usermanagement.php?searchterm=X'>X</a>|<a href='usermanagement.php?searchterm=Y'>Y</a>|<a href='usermanagement.php?searchterm=Z'>Z</a></div>
					</div>
				</section>
			</div>
		</div>
		<?php
		if($IS_ADMIN){
			if($userId){
				$user = $userManager->getUser($userId);
				?>
				<h1>
					<?php
						echo $user["firstname"]." ".$user["lastname"]." (#".$user["uid"].") ";
						echo "<a href='viewprofile.php?emode=1&tabindex=2&&userid=" . htmlspecialchars($user["uid"], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "'><img src='../images/edit.png' style='border:0px;width:0.8em;' /></a>";
					?>
				</h1>
				<div style="margin-left:10px;">
					<div style="clear:left;">
						<div style="float:left;font-weight:bold;margin-right:8px;"><?php echo (isset($LANG['TITLE'])?$LANG['TITLE']:'Title'); ?>: </div>
						<div style="float:left;"><?php echo $user["title"];?></div>
					</div>
					<div style="clear:left;">
						<div style="float:left;font-weight:bold;margin-right:8px;"><?php echo (isset($LANG['INSTITUTION'])?$LANG['INSTITUTION']:'Institution'); ?>: </div>
						<div style="float:left;"><?php echo $user["institution"];?></div>
					</div>
					<div style="clear:left;">
						<div style="float:left;font-weight:bold;margin-right:8px;"><?php echo (isset($LANG['CITY'])?$LANG['CITY']:'City'); ?>: </div>
						<div style="float:left;"><?php echo $user["city"];?></div>
					</div>
					<div style="clear:left;">
						<div style="float:left;font-weight:bold;margin-right:8px;"><?php echo (isset($LANG['STATE'])?$LANG['STATE']:'State'); ?>: </div>
						<div style="float:left;"><?php echo $user["state"];?></div>
					</div>
					<div style="clear:left;">
						<div style="float:left;font-weight:bold;margin-right:8px;"><?php echo (isset($LANG['ZIP'])?$LANG['ZIP']:'Zip'); ?>: </div>
						<div style="float:left;"><?php echo $user["zip"];?></div>
					</div>
					<div style="clear:left;">
						<div style="float:left;font-weight:bold;margin-right:8px;"><?php echo (isset($LANG['COUNTRY'])?$LANG['COUNTRY']:'Country'); ?>: </div>
						<div style="float:left;"><?php echo $user["country"];?></div>
					</div>
					<div style="clear:left;">
						<div style="float:left;font-weight:bold;margin-right:8px;"><?php echo (isset($LANG['EMAIL'])?$LANG['EMAIL']:'Email'); ?>: </div>
						<div style="float:left;"><?php echo $user["email"];?></div>
					</div>
					<div style="clear:left;">
						<div style="float:left;font-weight:bold;margin-right:8px;"><?php echo (isset($LANG['URL'])?$LANG['URL']:'URL'); ?>: </div>
						<div style="float:left;">
							<a href='<?php echo $user["url"];?>'>
								<?php echo $user["url"];?>
							</a>
						</div>
					</div>
					<div style="clear:left;">
						<div style="float:left;font-weight:bold;margin-right:8px;"><?php echo (isset($LANG['USERNAME'])?$LANG['USERNAME']:'Username'); ?>: </div>
						<div style="float:left;margin-bottom:30px;"><?php echo ($user["username"]?$user["username"].' (last login: '.$user['lastlogindate'].')':(isset($LANG['NOT_REGISTERED'])?$LANG['NOT_REGISTERED']:'login not registered for this user')); ?></div>
					</div>
				</div>
				<?php
				if($user["username"]){
					?>
					<div style="clear:both;margin:0px 0px 20px 30px;">
						<a href="usermanagement.php?loginas=<?php echo htmlspecialchars($user["username"], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><?php echo htmlspecialchars((isset($LANG['LOG_IN'])?$LANG['LOG_IN']:'Log in'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> <?php echo htmlspecialchars($DEFAULT_TITLE, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . ' ' . htmlspecialchars((isset($LANG['AS_USER'])?$LANG['AS_USER']:'as this user'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
					</div>
					<?php
				}
				?>
				<fieldset style="clear:both;margin:10px;padding:15px;">
					<legend style="font-weight:bold;font-size:120%;"><?php echo (isset($LANG['PERMISSIONS'])?$LANG['PERMISSIONS']:'Current Permissions'); ?></legend>
					<?php
					$userPermissions = $userManager->getUserPermissions($userId);
					if($userPermissions){
						?>
						<div>
							<ul>
							<?php
							if(array_key_exists("SuperAdmin",$userPermissions)){
								?>
								<li>
									<b><?php
									echo '<span title="'.$userPermissions['SuperAdmin']['aby'].'">';
									echo str_replace('SuperAdmin',(isset($LANG['SUPERADMIN'])?$LANG['SUPERADMIN']:'Super Administrator'),$userPermissions['SuperAdmin']['role']);
									echo '</span>';
									?></b>
									<a href="usermanagement.php?delrole=SuperAdmin&userid=<?php echo htmlspecialchars($userId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
										<img src="../images/del.png" style="border:0px;width:1.2em;" title=<?php echo htmlspecialchars((isset($LANG['DEL_PERM'])?$LANG['DEL_PERM']:'Delete permission'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?> />
									</a>
								</li>
								<?php
							}
							if(array_key_exists("Taxonomy",$userPermissions)){
								?>
								<li>
									<b><?php
									echo '<span title="'.$userPermissions['Taxonomy']['aby'].'">';
									echo str_replace('Taxonomy',(isset($LANG['TAX_EDITOR'])?$LANG['TAX_EDITOR']:'Taxonomy Editor'),$userPermissions['Taxonomy']['role']);
									echo '</span>';
									?></b>
									<a href="usermanagement.php?delrole=Taxonomy&userid=<?php echo htmlspecialchars($userId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
										<img src="../images/del.png" style="border:0px;width:1.2em;" title=<?php echo htmlspecialchars((isset($LANG['DEL_PERM'])?$LANG['DEL_PERM']:'Delete permission'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?> />
									</a>
								</li>
								<?php
							}
							if(array_key_exists("TaxonProfile",$userPermissions)){
								?>
								<li>
									<b><?php
									echo '<span title="'.$userPermissions['TaxonProfile']['aby'].'">';
									echo str_replace('TaxonProfile',(isset($LANG['TAX_PROF_EDITOR'])?$LANG['TAX_PROF_EDITOR']:'Taxon Profile Editor'),$userPermissions['TaxonProfile']['role']);
									echo '</span>';
									?></b>
									<a href="usermanagement.php?delrole=TaxonProfile&userid=<?php echo htmlspecialchars($userId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
										<img src="../images/del.png" style="border:0px;width:1.2em;" title=<?php echo htmlspecialchars((isset($LANG['DEL_PERM'])?$LANG['DEL_PERM']:'Delete permission'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?> />
									</a>
								</li>
								<?php
							}
							if(array_key_exists('GlossaryEditor',$userPermissions)){
								?>
								<li>
									<b><?php
									echo '<span title="'.$userPermissions['GlossaryEditor']['aby'].'">';
									echo str_replace('GlossaryEditor',(isset($LANG['GLOSSARY_EDITOR'])?$LANG['GLOSSARY_EDITOR']:'Glossary Editor'),$userPermissions['GlossaryEditor']['role']);
									echo '</span>';
									?></b>
									<a href="usermanagement.php?delrole=GlossaryEditor&userid=<?php echo htmlspecialchars($userId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
										<img src="../images/del.png" style="border:0px;width:1.2em;" title=<?php echo htmlspecialchars((isset($LANG['DEL_PERM'])?$LANG['DEL_PERM']:'Delete permission'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?> />
									</a>
								</li>
								<?php
							}
							if(array_key_exists("KeyAdmin",$userPermissions)){
								?>
								<li>
									<b><?php
									echo '<span title="'.$userPermissions['KeyAdmin']['aby'].'">';
									echo str_replace('KeyAdmin',(isset($LANG['ID_KEY_ADMIN'])?$LANG['ID_KEY_ADMIN']:'Identification Keys Administrator'),$userPermissions['KeyAdmin']['role']);
									echo '</span>';
									?></b>
									<a href="usermanagement.php?delrole=KeyAdmin&userid=<?php echo htmlspecialchars($userId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
										<img src="../images/del.png" style="border:0px;width:1.2em;" title=<?php echo htmlspecialchars((isset($LANG['DEL_PERM'])?$LANG['DEL_PERM']:'Delete permission'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?> />
									</a>
								</li>
								<?php
							}
							if(array_key_exists("KeyEditor",$userPermissions)){
								?>
								<li>
									<b><?php
									echo '<span title="'.$userPermissions['KeyEditor']['aby'].'">';
									echo str_replace('KeyEditor',(isset($LANG['ID_KEY_EDITOR'])?$LANG['ID_KEY_EDITOR']:'Identification Keys Editor'),$userPermissions['KeyEditor']['role']);
									echo '</span>';
									?></b>
									<a href="usermanagement.php?delrole=KeyEditor&userid=<?php echo htmlspecialchars($userId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
										<img src="../images/del.png" style="border:0px;width:1.2em;" title=<?php echo htmlspecialchars((isset($LANG['DEL_PERM'])?$LANG['DEL_PERM']:'Delete permission'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?> />
									</a>
								</li>
								<?php
							}
							if(array_key_exists("ClCreate",$userPermissions)){
								?>
								<li>
									<b><?php
									echo '<span title="'.$userPermissions['ClCreate']['aby'].'">';
									echo str_replace('ClCreate',(isset($LANG['CL_CREATE'])?$LANG['CL_CREATE']:'Create a Checklist'),$userPermissions['ClCreate']['role']);
									echo '</span>';
									?></b>
									<a href="usermanagement.php?delrole=clCreate&userid=<?php echo htmlspecialchars($userId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
										<img src="../images/del.png" style="border:0px;width:1.2em;" title=<?php echo htmlspecialchars((isset($LANG['DEL_PERM'])?$LANG['DEL_PERM']:'Delete permission'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?> />
									</a>
								</li>
								<?php
							}
							if(array_key_exists("RareSppAdmin",$userPermissions)){
								?>
								<li>
									<b><?php
									echo '<span title="'.$userPermissions['RareSppAdmin']['aby'].'">';
									echo str_replace('RareSppAdmin',(isset($LANG['RARE_SP_ADMIN'])?$LANG['RARE_SP_ADMIN']:'Rare Species List Administrator'),$userPermissions['RareSppAdmin']['role']);
									echo '</span>';
									?></b>
									<a href="usermanagement.php?delrole=RareSppAdmin&userid=<?php echo htmlspecialchars($userId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
										<img src="../images/del.png" style="border:0px;width:1.2em;" title=<?php echo htmlspecialchars((isset($LANG['DEL_PERM'])?$LANG['DEL_PERM']:'Delete permission'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?> />
									</a>
								</li>
								<?php
							}
							if(array_key_exists("RareSppReadAll",$userPermissions)){
								?>
								<li>
									<b><?php
									echo '<span title="'.$userPermissions['RareSppReadAll']['aby'].'">';
									echo str_replace('RareSppReadAll',(isset($LANG['RARE_SP_VIEWER'])?$LANG['RARE_SP_VIEWER']:'View and Map Specimens of Rare Species from all Collections'),$userPermissions['RareSppReadAll']['role']);
									echo '</span> ';
									?></b>
									<a href="usermanagement.php?delrole=RareSppReadAll&userid=<?php echo htmlspecialchars($userId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
										<img src="../images/del.png" style="border:0px;width:1.2em;" title=<?php echo htmlspecialchars((isset($LANG['DEL_PERM'])?$LANG['DEL_PERM']:'Delete permission'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?> />
									</a>
								</li>
								<?php
							}
							//Collections Admin
							if(array_key_exists("CollAdmin",$userPermissions)){
								echo '<li><b>'.(isset($LANG['ADMIN_FOR'])?$LANG['ADMIN_FOR']:'Administrator for following collections').'</b></li>';
								$collList = $userPermissions["CollAdmin"];
								echo "<ul>";
								foreach($collList as $k => $v){
									if(!empty($v['name'])){
										echo '<li><span title="'.$v['aby'].'"><a href="../collections/misc/collprofiles.php?collid=' . htmlspecialchars($k, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">' . htmlspecialchars($v['name'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></span> ';
										echo "<a href='usermanagement.php?delrole=CollAdmin&tablepk=$k&userid=$userId'>";
										echo "<img src='../images/del.png' style='border:0px;width:1.2em;' title=".(isset($LANG['DEL_PERM'])?$LANG['DEL_PERM']:'Delete permission').'/>';
										echo "</a></li>";
									}
								}
								echo "</ul>";
							}
							//Collections Editor
							if(array_key_exists("CollEditor",$userPermissions)){
								echo "<li><b>Editor for following collections</b></li>";
								$collList = $userPermissions["CollEditor"];
								echo "<ul>";
								foreach($collList as $k => $v){
									echo '<li><span title="'.$v['aby'].'"><a href="../collections/misc/collprofiles.php?collid=' . htmlspecialchars($k, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">' . htmlspecialchars($v['name'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></span> ';
									echo "<a href='usermanagement.php?delrole=CollEditor&tablepk=$k&userid=$userId'>";
									echo "<img src='../images/del.png' style='border:0px;width:1.2em;' title=" . htmlspecialchars((isset($LANG['DEL_PERM'])?$LANG['DEL_PERM']:'Delete permission'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) .'/>';
									echo "</a></li>";
								}
								echo "</ul>";
							}
							if(array_key_exists("RareSppReader",$userPermissions)){
								?>
								<li>
									<b><?php echo (isset($LANG['RARE_SP_FOR'])?$LANG['RARE_SP_FOR']:'Sensitive Species Reader for following Collections'); ?></b>
									<ul>
									<?php
									$rsrArr = $userPermissions["RareSppReader"];
									foreach($rsrArr as $collId => $v){
										?>
										<li>
											<?php echo '<span title="'.$v['aby'].'">'.$v['name'].'</span>'; ?>
											<a href="usermanagement.php?delrole=RareSppReader&tablepk=<?php echo htmlspecialchars($collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE)?>&userid=<?php echo htmlspecialchars($userId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
												<img src="../images/del.png" style="border:0px;width:1.2em;" title=<?php echo htmlspecialchars((isset($LANG['DEL_PERM'])?$LANG['DEL_PERM']:'Delete permission'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?> />
											</a>
										</li>
										<?php
									}
									?>
									</ul>
								</li>
								<?php
							}
							//Personal Specimen Mangement
							if(array_key_exists('PersonalObsAdmin',$userPermissions)){
								echo '<li><b>'.(isset($LANG['PERS_OBS_ADMIN'])?$LANG['PERS_OBS_ADMIN']:'Personal Observation Administrator').'</b></li>';
								$collList = $userPermissions['PersonalObsAdmin'];
								echo "<ul>";
								foreach($collList as $k => $v){
									echo '<li><span title="'.$v['aby'].'"><a href="../collections/misc/collprofiles.php?collid=' . htmlspecialchars($k, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) .'" target="_blank">' . htmlspecialchars($v['name'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) .'</a></span> ';
									echo "<a href='usermanagement.php?delrole=CollAdmin&tablepk=$k&userid=$userId'>";
									echo "<img src='../images/del.png' style='border:0px;width:1.2em;' title=".(isset($LANG['DEL_PERM'])?$LANG['DEL_PERM']:'Delete permission').'/>';
									echo "</a></li>";
								}
								echo "</ul>";
							}
							if(array_key_exists('PersonalObsEditor',$userPermissions)){
								echo '<li><b>'.(isset($LANG['PERS_OBS_EDITOR'])?$LANG['PERS_OBS_EDITOR']:'Personal Observation Editor').'</b></li>';
								$collList = $userPermissions["PersonalObsEditor"];
								echo "<ul>";
								foreach($collList as $k => $v){
									echo '<li><span title="'.$v['aby'].'"><a href="../collections/misc/collprofiles.php?collid=' . htmlspecialchars($k, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">' . htmlspecialchars($v['name'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) .'</a></span> ';
									echo "<a href='usermanagement.php?delrole=CollEditor&tablepk=$k&userid=$userId'>";
									echo "<img src='../images/del.png' style='border:0px;width:1.2em;' title=" . htmlspecialchars((isset($LANG['DEL_PERM'])?$LANG['DEL_PERM']:'Delete permission'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) .'/>';
									echo "</a></li>";
								}
								echo "</ul>";
							}
							if(array_key_exists("PersonalObsReader",$userPermissions)){
								?>
								<li>
									<b><?php echo (isset($LANG['PERS_OBS_RARE_SP_READER'])?$LANG['PERS_OBS_RARE_SP_READER']:'Personal Observation Sensitive Species Reader'); ?></b>
									<ul>
									<?php
									$rsrArr = $userPermissions["PersonalObsReader"];
									foreach($rsrArr as $collId => $v){
										?>
										<li>
											<?php echo '<span title="'.$v['aby'].'">'.$v['name'].'</span>'; ?>
											<a href="usermanagement.php?delrole=RareSppReader&tablepk=<?php echo htmlspecialchars($collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE)?>&userid=<?php echo htmlspecialchars($userId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
												<img src="../images/del.png" style="border:0px;width:1.2em;" title=<?php echo htmlspecialchars((isset($LANG['DEL_PERM'])?$LANG['DEL_PERM']:'Delete permission'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?> />
											</a>
										</li>
										<?php
									}
									?>
									</ul>
								</li>
								<?php
							}
							//Inventory Projects
							if(array_key_exists("ProjAdmin",$userPermissions)){
								?>
								<li>
									<b><?php echo (isset($LANG['INVENTORY_ADMIN'])?$LANG['INVENTORY_ADMIN']:'Administrator for following inventory projects'); ?></b>
									<ul>
										<?php
										$projList = $userPermissions["ProjAdmin"];
										asort($projList);
										foreach($projList as $k => $v){
											echo '<li><a href="../projects/index.php?pid=' . htmlspecialchars($k, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank"><span title="' . htmlspecialchars($v['aby'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . htmlspecialchars($v['name'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</span></a>';
											echo "<a href='usermanagement.php?delrole=ProjAdmin&tablepk=$k&userid=$userId'>";
											echo "<img src='../images/del.png' style='border:0px;width:1.2em;' title=" . htmlspecialchars((isset($LANG['DEL_PERM'])?$LANG['DEL_PERM']:'Delete permission'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '/>';
											echo "</a></li>";
										}
										?>
									</ul>
								</li>
								<?php
							}
							//Checklists
							if(array_key_exists("ClAdmin",$userPermissions)){
								?>
								<li>
									<b><?php echo (isset($LANG['CHECKLIST_ADMIN'])?$LANG['CHECKLIST_ADMIN']:'Administrator for following checklists'); ?></b>
									<ul>
										<?php
										$clList = $userPermissions["ClAdmin"];
										asort($clList);
										foreach($clList as $k => $v){
											$name = '&lt;resource deleted&gt;';
											if(isset($v['name'])) $name = $v['name'];
											echo '<li>';
											echo '<a href="../checklists/checklist.php?clid=' . htmlspecialchars($k, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">';
											echo '<span title="' . htmlspecialchars($v['aby'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . htmlspecialchars($name, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</span>';
											echo '</a>';
											echo "<a href='usermanagement.php?delrole=ClAdmin&tablepk=$k&userid=$userId'>";
											echo "<img src='../images/del.png' style='border:0px;width:1.2em;' title=" . htmlspecialchars((isset($LANG['DEL_PERM'])?$LANG['DEL_PERM']:'Delete permission'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '/>';
											echo "</a></li>";
										}
										?>
									</ul>
								</li>
								<?php
							}
							?>
							</ul>
						</div>
						<?php
					}
					else{
						echo '<h3 style="margin:20px;">'.(isset($LANG['NO_PERMISSIONS'])?$LANG['NO_PERMISSIONS']:'No permissions have to been assigned to this user').'</h3>';
					}
					?>
				</fieldset>
				<form name="addpermissions" action="usermanagement.php" method="post">
					<?php
					$addPermButton = '<button type="submit" name="apsubmit" value="Add Permission">'.(isset($LANG['ADD_PERMISSION'])?$LANG['ADD_PERMISSION']:'Add Permission').'</button>';
					?>
					<fieldset style="margin:10px;-color:#FFFFCC;padding:15px;">
						<legend style="font-weight:bold;font-size:120%;"><?php echo (isset($LANG['ASSIGN_NEW'])?$LANG['ASSIGN_NEW']:'Assign New Permissions'); ?></legend>
						<div style="margin:5px;">
							<div style="float:right;margin:10px">
								<?php echo $addPermButton; ?>
								<input type="hidden" name="userid" value="<?php echo $userId;?>" />
							</div>
							<?php
							if(!array_key_exists("SuperAdmin",$userPermissions)){
								echo '<div><input type="checkbox" name="p[]" value="SuperAdmin" /> '.(isset($LANG['SUPERADMIN'])?$LANG['SUPERADMIN']:'Super Administrator').'</div>';
							}
							if(!array_key_exists("Taxonomy",$userPermissions)){
								echo '<div><input type="checkbox" name="p[]" value="Taxonomy" /> '.(isset($LANG['TAX_EDITOR'])?$LANG['TAX_EDITOR']:'Taxonomy Editor').'</div>';
							}
							if(!array_key_exists("TaxonProfile",$userPermissions)){
								echo "<div><input type='checkbox' name='p[]' value='TaxonProfile' /> ".(isset($LANG['TAX_PROF_EDITOR'])?$LANG['TAX_PROF_EDITOR']:'Taxon Profile Editor')."</div>";
							}
							if(!array_key_exists('GlossaryEditor',$userPermissions)){
								echo "<div><input type='checkbox' name='p[]' value='GlossaryEditor' /> ".(isset($LANG['GLOSSARY_EDITOR'])?$LANG['GLOSSARY_EDITOR']:'Glossary Editor')."</div>";
							}
							if(!array_key_exists("KeyAdmin",$userPermissions)){
								echo "<div><input type='checkbox' name='p[]' value='KeyAdmin' /> ".(isset($LANG['ID_KEY_ADMIN'])?$LANG['ID_KEY_ADMIN']:'Identification Keys Administrator')."</div>";
							}
							if(!array_key_exists("KeyEditor",$userPermissions)){
								echo "<div><input type='checkbox' name='p[]' value='KeyEditor' /> ".(isset($LANG['ID_KEY_EDITOR'])?$LANG['ID_KEY_EDITOR']:'Identification Keys Editor')."</div>";
							}
							if(!array_key_exists("ClCreate",$userPermissions)){
								echo "<div><input type='checkbox' name='p[]' value='ClCreate' /> ".(isset($LANG['CL_CREATE'])?$LANG['CL_CREATE']:'Create a Checklist')."</div>";
							}
							?>
						</div>
						<hr/>
						<h2 style="text-decoration:underline"><?php echo (isset($LANG['OCCURRENCE_PROTECT'])?$LANG['OCCURRENCE_PROTECT']:'Occurrence Protection'); ?></h2>
						<div>
							<?php
							$showRareSppOption = true;
							if(array_key_exists("RareSppAdmin",$userPermissions)){
								$showRareSppOption = false;
							}
							else{
								?>
								<div style="margin-left:5px;">
									<input type='checkbox' name='p[]' value='RareSppAdmin' />
									<?php echo (isset($LANG['RARE_SP_ADMIN_2'])?$LANG['RARE_SP_ADMIN_2']:'Rare Species Administrator (add/remove species from list)'); ?>
								</div>
								<?php
							}
							if(array_key_exists("RareSppReadAll",$userPermissions)){
								$showRareSppOption = false;
							}
							else{
								?>
								<div style="margin-left:5px;">
									<input type='checkbox' name='p[]' value='RareSppReadAll' />
									<?php echo (isset($LANG['CAN_READ'])?$LANG['CAN_READ']:'Can read Rare Species data for all collections'); ?>
								</div>
								<?php
							}
							?>
						</div>
						<?php
						//Collection projects
						$collArr = $userManager->getCollectionMetadata('Preserved Specimens');
						$obsArr = $userManager->getCollectionMetadata('Observations');
						$personalObsArr = $userManager->getCollectionMetadata('General Observations');
						if(array_key_exists("CollAdmin",$userPermissions)){
							$collArr = array_diff_key($collArr,$userPermissions["CollAdmin"]);
							$obsArr = array_diff_key($obsArr,$userPermissions["CollAdmin"]);
						}
						if(array_key_exists('PersonalObsAdmin',$userPermissions)){
							$personalObsArr = array_diff_key($personalObsArr,$userPermissions['PersonalObsAdmin']);
						}
						if($collArr){
							?>
							<div style="float:right;margin:10px;">
								<?php echo $addPermButton; ?>
							</div>
							<h2 style="text-decoration:underline"><?php echo (isset($LANG['SPEC_COLS'])?$LANG['SPEC_COLS']:'Specimen Collections'); ?></h2>
							<table>
								<tr>
									<th><?php echo (isset($LANG['ADMIN'])?$LANG['ADMIN']:'Admin'); ?></th>
									<th><?php echo (isset($LANG['EDITOR'])?$LANG['EDITOR']:'Editor'); ?></th>
									<?php if($showRareSppOption) echo '<th>'.(isset($LANG['RARE'])?$LANG['RARE']:'Rare').'</th>'; ?>
									<th>&nbsp;</th>
								</tr>
								<?php
								foreach($collArr as $collid => $cArr){
									?>
									<tr>
										<td align="center">
											<input type='checkbox' name='p[]' value='CollAdmin-<?php echo $collid;?>' title=<?php echo (isset($LANG['COL_ADMIN'])?$LANG['COL_ADMIN']:'Collection Administrator'); ?> />
										</td>
										<td align="center">
											<input type='checkbox' name='p[]' value='CollEditor-<?php echo $collid;?>' title=<?php echo (isset($LANG['ABLE_TO_ADD'])?$LANG['ABLE_TO_ADD']:'Able to add and edit specimen data'); ?> <?php if(isset($userPermissions["CollEditor"][$collid])) echo (isset($LANG['DISABLED'])?$LANG['DISABLED']:'DISABLED');?> />
										</td>
										<?php
										if($showRareSppOption){
											?>
											<td align="center">
												<input type='checkbox' name='p[]' value='RareSppReader-<?php echo $collid;?>' title=<?php echo (isset($LANG['ABLE_TO_READ'])?$LANG['ABLE_TO_READ']:'Able to read specimen details for rare species'); ?> <?php if(isset($userPermissions["RareSppReader"][$collid])) echo (isset($LANG['DISABLED'])?$LANG['DISABLED']:'DISABLED');?> />
											</td>
											<?php
										}
										?>
										<td>
											<?php
											echo '<a href="' . htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '/collections/misc/collprofiles.php?collid=' . htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&emode=1" target="_blank" >';
											echo $cArr['collectionname'];
											echo ' ('.$cArr['institutioncode'].($cArr['collectioncode']?'-'.$cArr['collectioncode']:'').')';
											echo '</a>';
											?>
										</td>
									</tr>
									<?php
								}
								?>
							</table>
							<?php
						}
						//Observation projects
						if($obsArr){
							?>
							<div style="float:right;margin:10px;">
								<?php echo $addPermButton; ?>
							</div>
							<h2 style="text-decoration:underline"><?php echo (isset($LANG['OBS_PROJECTS'])?$LANG['OBS_PROJECTS']:'Observation Projects'); ?></h2>
							<table>
								<tr>
									<th><?php echo (isset($LANG['ADMIN'])?$LANG['ADMIN']:'Admin'); ?></th>
									<th><?php echo (isset($LANG['EDITOR'])?$LANG['EDITOR']:'Editor'); ?></th>
									<?php if($showRareSppOption) echo '<th>'.(isset($LANG['RARE'])?$LANG['RARE']:'Rare').'</th>'; ?>
									<th>&nbsp;</th>
								</tr>
								<?php
								foreach($obsArr as $obsid => $oArr){
									?>
									<tr>
										<td align="center">
											<input type='checkbox' name='p[]' value='CollAdmin-<?php echo $obsid;?>' title=<?php echo (isset($LANG['COL_ADMIN'])?$LANG['COL_ADMIN']:'Collection Administrator'); ?> <?php if(isset($userPermissions["CollAdmin"][$obsid])) echo "DISABLED";?> />
										</td>
										<td align="center">
											<input type='checkbox' name='p[]' value='CollEditor-<?php echo $obsid;?>' title=<?php echo (isset($LANG['ABLE_TO_ADD'])?$LANG['ABLE_TO_ADD']:'Able to add and edit specimen data'); ?> <?php if(isset($userPermissions["CollEditor"][$obsid])) echo "DISABLED";?> />
										</td>
										<?php
										if($showRareSppOption){
											?>
											<td align="center">
												<input type='checkbox' name='p[]' value='RareSppReader-<?php echo $obsid;?>' title=<?php echo (isset($LANG['ABLE_TO_READ'])?$LANG['ABLE_TO_READ']:'Able to read specimen details for rare species'); ?> <?php if(isset($userPermissions["RareSppReader"][$obsid])) echo "DISABLED";?> />
											</td>
											<?php
										}
										?>
										<td>
											<?php
											echo '<a href="' . htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '/collections/misc/collprofiles.php?collid=' . htmlspecialchars($obsid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&emode=1" target="_blank" >';
											echo $oArr['collectionname'];
											echo ' ('.$oArr['institutioncode'].($oArr['collectioncode']?'-'.$oArr['collectioncode']:'').')';
											echo '</a>';
											?>
										</td>
									</tr>
									<?php
								}
								?>
							</table>
							<?php
						}
						//Personal Specimen Management projects (General Observations)
						if($personalObsArr){
							?>
							<div style="float:right;margin:10px;">
								<?php echo $addPermButton; ?>
							</div>
							<h2 style="text-decoration:underline"><?php echo (isset($LANG['PERS_SP_MGMNT'])?$LANG['PERS_SP_MGMNT']:'Personal Specimen Management'); ?></h2>
							<table style="margin-bottom:20px">
								<tr>
									<th><?php echo (isset($LANG['ADMIN'])?$LANG['ADMIN']:'Admin'); ?></th>
									<th><?php echo (isset($LANG['EDITOR'])?$LANG['EDITOR']:'Editor'); ?></th>
									<th>&nbsp;</th>
								</tr>
								<?php
								foreach($personalObsArr as $genObsID => $genObjArr){
									?>
									<tr>
										<td align="center">
											<input type='checkbox' name='p[]' value='CollAdmin-<?php echo $genObsID;?>' title=<?php echo (isset($LANG['COL_ADMIN'])?$LANG['COL_ADMIN']:'Collection Administrator'); ?> />
										</td>
										<td align="center">
											<input type='checkbox' name='p[]' value='CollEditor-<?php echo $genObsID;?>' title=<?php echo (isset($LANG['ABLE_TO_ADD'])?$LANG['ABLE_TO_ADD']:'Able to add and edit specimen data'); ?> <?php if(isset($userPermissions['PersonalObsEditor'][$genObsID])) echo "DISABLED";?> />
										</td>
										<td>
											<?php
											echo '<a href="' . htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '/collections/misc/collprofiles.php?collid=' . htmlspecialchars($genObsID, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&emode=1" target="_blank" >';
											echo $genObjArr['collectionname'];
											echo ' ('.$genObjArr['institutioncode'].($genObjArr['collectioncode']?'-'.$genObjArr['collectioncode']:'').')';
											echo '</a>';
											?>
										</td>
									</tr>
									<?php
								}
								?>
							</table>
							<?php
						}
						//Get checklists
						$pidArr = Array();
						if(array_key_exists("ProjAdmin",$userPermissions)){
							$pidArr = array_keys($userPermissions["ProjAdmin"]);
						}
						$projectArr = $userManager->getProjectArr($pidArr);
						if($projectArr){
							?>
							<div><hr/></div>
							<div style="float:right;margin:10px;">
								<?php echo $addPermButton; ?>
							</div>
							<h2 style="text-decoration:underline"><?php echo (isset($LANG['INV_MGMNT'])?$LANG['INV_MGMNT']:'Inventory Project Management'); ?></h2>
							<?php
							foreach($projectArr as $k=>$v){
								?>
								<div style='margin-left:15px;'>
									<?php
									echo '<input type="checkbox" name="p[]" value="ProjAdmin-'.$k.'" />';
									echo '<a href="../projects/index.php?pid=' . htmlspecialchars($k, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">' . htmlspecialchars($v, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>';
									?>
								</div>
								<?php
								}
							}
							//Get checklists
							$cidArr = Array();
							if(array_key_exists("ClAdmin",$userPermissions)){
								$cidArr = array_keys($userPermissions["ClAdmin"]);
							}
							$checklistArr = $userManager->getChecklistArr($cidArr);
							if($checklistArr){
							?>
							<div><hr/></div>
							<div style="float:right;margin:10px;">
								<?php echo $addPermButton; ?>
							</div>
							<h2 style="text-decoration:underline"><?php echo (isset($LANG['CHECKLIST_MGMNT'])?$LANG['CHECKLIST_MGMNT']:'Checklist Management'); ?></h2>
							<?php
							foreach($checklistArr as $k=>$v){
								?>
								<div style='margin-left:15px;'>
									<?php
									echo '<input type="checkbox" name="p[]" value="ClAdmin-'.$k.'" />';
									echo '<a href="../checklists/checklist.php?clid=' . htmlspecialchars($k, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">' . htmlspecialchars($v, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>';
									?>
								</div>
								<?php
							}
						}
						?>
					</fieldset>
				</form>
				<?php
			}
			else{
				if($IS_ADMIN){
					if(array_key_exists('adminRegisterSuccessfulUsername', $_SESSION)){
						?>
							<div class="alert">
								<span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span>
								<?php echo (isset($LANG['USER_CREATED_SUCCESSFULLY']) ? $LANG['USER_CREATED_SUCCESSFULLY'] : 'Successfully created user: ') . $_SESSION['adminRegisterSuccessfulUsername'] ?>
							</div>
							<div class="bottom-breathing-room-rel">
								<span class="success-alert"></span>
							</div>
						<?php
						unset($_SESSION['adminRegisterSuccessfulUsername']);
					}
					?>
					<form method="POST" action="<?php echo $CLIENT_ROOT ?>/profile/newprofile.php">
						<button id="adminRegister" name="adminRegister" class="button button-tertiary" type="submit" value="1">
							<?php echo isset($LANG['CREATE_NEW_USER']) ? $LANG['CREATE_NEW_USER'] : 'Create New User'; ?>
						</button>
					</form>
					<?php
				}
				?>
				<a id="userlist"></a>
				<?php
				$users = $userManager->getUsers($searchTerm);
				foreach($users as $id => $name){
					echo '<div><a href="usermanagement.php?userid=' . htmlspecialchars($id, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . htmlspecialchars($name, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></div>';
				}
			}
		}
		else{
			echo '<h3>'.(isset($LANG['MUST_LOGIN'])?$LANG['MUST_LOGIN']:'You must log in and have administrator permissions to manage users').'</h3>';
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>
