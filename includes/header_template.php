<?php
if($LANG_TAG == 'en' || !file_exists($SERVER_ROOT.'/content/lang/header.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/header.en.php');
else include_once($SERVER_ROOT.'/content/lang/header.'.$LANG_TAG.'.php');
?>
<div class="header-wrapper">
	<header>
		<div class="top-wrapper">
			<nav class="top-login">
				<?php
				if ($USER_DISPLAY_NAME) {
					?>
					<span style="">
						<?php echo (isset($LANG['H_WELCOME'])?$LANG['H_WELCOME']:'Welcome').' '.$USER_DISPLAY_NAME; ?>!
					</span>
					<span class="button button-tertiary">
						<a href="<?php echo $CLIENT_ROOT; ?>/profile/viewprofile.php"><?php echo (isset($LANG['H_MY_PROFILE'])?$LANG['H_MY_PROFILE']:'My Profile')?></a>
					</span>
					<span class="button button-secondary">
						<a href="<?php echo $CLIENT_ROOT; ?>/profile/index.php?submit=logout"><?php echo (isset($LANG['H_LOGOUT'])?$LANG['H_LOGOUT']:'Sign Out')?></a>
					</span>
					<?php
				} else {
					?>
					<span>
						<a href="#">
							Contact Us
						</a>
					</span>
					<span class="button button-secondary">
						<a href="<?php echo $CLIENT_ROOT . "/profile/index.php?refurl=" . $_SERVER['SCRIPT_NAME'] . "?" . htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES); ?>">
							<?php echo (isset($LANG['H_LOGIN'])?$LANG['H_LOGIN']:'Login')?>
						</a>
					</span>
					<?php
				}
				?>
			</nav>
			<div class="top-brand">
				<a href="https://symbiota.org">
					<img src="<?php echo $CLIENT_ROOT; ?>/images/layout/logo_symbiota.png" alt="Symbiota logo" width="100%">
				</a>
				<div class="brand-name">
					<h1>Symbiota Brand New Portal</h1>
					<h2>Redesigned by the Symbiota Support Hub</h2>
				</div>
			</div>
		</div>
		<div class="menu-wrapper">
			<!-- Hamburger icon -->
			<input class="side-menu" type="checkbox" id="side-menu" />
			<label class="hamb" for="side-menu"><span class="hamb-line"></span></label>
			<!-- Menu -->
			<nav class="top-menu">
				<ul class="menu">
					<li>
						<a href="<?php echo $CLIENT_ROOT; ?>/index.php">
							<?php echo (isset($LANG['H_HOME'])?$LANG['H_HOME']:'Home'); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo $CLIENT_ROOT; ?>/collections/index.php">
							<?php echo (isset($LANG['H_COLLECTIONS'])?$LANG['H_COLLECTIONS']:'Collections'); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo $CLIENT_ROOT; ?>/collections/map/index.php" target="_blank" rel="noopener noreferrer">
							<?php echo (isset($LANG['H_MAP_SEARCH'])?$LANG['H_MAP_SEARCH']:'Map Search'); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo $CLIENT_ROOT; ?>/checklists/index.php">
							<?php echo (isset($LANG['H_INVENTORIES'])?$LANG['H_INVENTORIES']:'Checklists'); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo $CLIENT_ROOT; ?>/imagelib/search.php">
							<?php echo (isset($LANG['H_IMAGES'])?$LANG['H_IMAGES']:'Images'); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo $CLIENT_ROOT; ?>/includes/usagepolicy.php">
							<?php echo (isset($LANG['H_DATA_USAGE'])?$LANG['H_DATA_USAGE']:'Data Use'); ?>
						</a>
					</li>
					<li>
						<a href="https://symbiota.org/docs" target="_blank" rel="noopener noreferrer">
							<?php echo (isset($LANG['H_HELP'])?$LANG['H_HELP']:'Help'); ?>
						</a>
					</li>
					<li>
						<a href='<?php echo $CLIENT_ROOT; ?>/sitemap.php'>
							<?php echo (isset($LANG['H_SITEMAP'])?$LANG['H_SITEMAP']:'Sitemap'); ?>
						</a>
					</li>
					<li>
						<select onchange="setLanguage(this)">
							<option value="en">English</option>
							<option value="es" <?php echo ($LANG_TAG=='es'?'SELECTED':''); ?>>Espa&ntilde;ol</option>
							<option value="fr" <?php echo ($LANG_TAG=='fr'?'SELECTED':''); ?>>Français</option>
						</select>
					</li>
				</ul>
			</nav>
		</div>
	</header>
</div>