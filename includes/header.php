<?php
if($LANG_TAG == 'en' || !file_exists($SERVER_ROOT.'/content/lang/header.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/header.en.php');
else include_once($SERVER_ROOT . '/content/lang/header.' . $LANG_TAG . '.php');
include_once($SERVER_ROOT . '/includes/head.php');

include_once($SERVER_ROOT . '/classes/ProfileManager.php');
$pHandler = new ProfileManager();
$isAccessiblePreferred = $pHandler->getAccessibilityPreference($SYMB_UID);
$SHOULD_USE_HARVESTPARAMS = $SHOULD_USE_HARVESTPARAMS ?? false;
$collectionSearchPage = $SHOULD_USE_HARVESTPARAMS ? '/collections/index.php' : '/collections/search/index.php';
?>
<div class="header-wrapper">
	<header>
		<div class="top-wrapper">
			<a class="screen-reader-only" href="#end-nav"><?= $LANG['SKIP_NAV'] ?></a>
			<nav class="top-login" aria-label="horizontal-nav">
				<?php
				if ($USER_DISPLAY_NAME) {
					?>
					<div class="welcome-text bottom-breathing-room-rel">
						<?= (isset($LANG['H_WELCOME'])?$LANG['H_WELCOME']:'Welcome') . ' ' . $USER_DISPLAY_NAME ?>!
					</div>
					<span style="white-space: nowrap; padding: 0.8rem;" class="button button-tertiary bottom-breathing-room-rel">
						<a style="font-size: 1.1em;" href="<?= $CLIENT_ROOT ?>/profile/viewprofile.php"><?= (isset($LANG['H_MY_PROFILE'])?$LANG['H_MY_PROFILE']:'My Profile') ?></a>
					</span>
					<span style="white-space: nowrap; padding: 0.8rem;" class="button button-secondary bottom-breathing-room-rel">
						<a style="font-size: 1.1em;" href="<?= $CLIENT_ROOT ?>/profile/index.php?submit=logout"><?= (isset($LANG['H_LOGOUT'])?$LANG['H_LOGOUT']:'Sign Out') ?></a>
					</span>
					<?php
				} else {
					?>
					<span class="button button-secondary">
						<a href="<?= $CLIENT_ROOT . "/profile/index.php?refurl=" . htmlspecialchars($_SERVER['SCRIPT_NAME'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "?" . htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES); ?>">
							<?= (isset($LANG['H_LOGIN'])?$LANG['H_LOGIN']:'Login') ?>
						</a>
					</span>
					<?php
				}
				?>
			</nav>
			<div class="top-brand">
				<a href="<?php echo $CLIENT_ROOT; ?>">
					<div class="image-container">
						<img src="<?php echo $CLIENT_ROOT; ?>/images/layout/biodiversidad_logo.png" alt="Biodiversidad.gt logo" width="100%">
					</div>
				</a>
				<div class="brand-name">
					<h1><?php echo $LANG['H_TITLE']; ?></h1><br>
					<h2><?php echo $LANG['H_SUBTITLE']; ?>
				</div>
			</div>
		</div>
		<div class="menu-wrapper">
			<!-- Hamburger icon -->
			<input class="side-menu" type="checkbox" id="side-menu" name="side-menu" />
			<label class="hamb hamb-line hamb-label" for="side-menu" tabindex="0">☰</label>
			<!-- Menu -->
			<nav class="top-menu" aria-label="hamburger-nav">
				<ul class="menu">
					<li>
						<a href="<?php echo $CLIENT_ROOT; ?>/index.php">
							<?php echo (isset($LANG['H_HOME'])?$LANG['H_HOME']:'Home'); ?>
						</a>
					</li>
					<li>
						<a href="#">
							<?php echo (isset($LANG['H_SEARCH'])?$LANG['H_SEARCH']:'Search'); ?>
						</a>
						<ul>
							<li>
								<a href="<?php echo $CLIENT_ROOT; ?>/collections/search/index.php">
									<?php echo (isset($LANG['H_COLLECTIONS'])?$LANG['H_COLLECTIONS']:'Collections'); ?>
								</a>
							</li>
							<li>
								<a href="<?php echo $CLIENT_ROOT; ?>/collections/map/index.php" target="_blank" rel="noopener noreferrer">
									<?php echo (isset($LANG['H_MAP_SEARCH'])?$LANG['H_MAP_SEARCH']:'Map Search'); ?>
								</a>
							</li>
						</ul>
					</li>
					<li>
						<a href="<?php echo $CLIENT_ROOT; ?>/projects/index.php">
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
					<li id="lang-select-li">
						<label for="language-selection"><?= $LANG['SELECT_LANGUAGE'] ?>: </label>
						<select oninput="setLanguage(this)" id="language-selection" name="language-selection">
							<option value="en">English</option>
							<option value="es" <?= ($LANG_TAG=='es'?'SELECTED':'') ?>>Espa&ntilde;ol</option>
							<option value="fr" <?= ($LANG_TAG=='fr'?'SELECTED':'') ?>>Français</option>
						</select>
					</li>
				</ul>
			</nav>
		</div>
		<div id="end-nav"></div>
	</header>
</div>
