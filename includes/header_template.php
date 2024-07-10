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
					<span style="white-space: nowrap;" class="button button-tertiary bottom-breathing-room-rel">
						<a href="<?= $CLIENT_ROOT ?>/profile/viewprofile.php"><?= (isset($LANG['H_MY_PROFILE'])?$LANG['H_MY_PROFILE']:'My Profile') ?></a>
					</span>
					<span style="white-space: nowrap;" class="button button-secondary bottom-breathing-room-rel">
						<a href="<?= $CLIENT_ROOT ?>/profile/index.php?submit=logout"><?= (isset($LANG['H_LOGOUT'])?$LANG['H_LOGOUT']:'Sign Out') ?></a>
					</span>
					<?php
				} else {
					?>
					<span class="button button-tertiary">
						<a onclick="window.location.href='#'">
							<?= $LANG['CONTACT_US'] ?>
						</a>
					</span>
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
				<a href="https://symbiota.org">
					<div class="image-container">
						<img src="<?= $CLIENT_ROOT ?>/images/layout/logo_symbiota.png" alt="Symbiota logo">
					</div>
				</a>
				<div class="brand-name">
					<h1>Symbiota Brand New Portal</h1>
					<h2>Redesigned by the Symbiota Support Hub</h2>
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
						<a href="<?= $CLIENT_ROOT ?>/index.php">
							<?= (isset($LANG['H_HOME'])?$LANG['H_HOME']:'Home') ?>
						</a>
					</li>
					<li>
						<a href="<?= $CLIENT_ROOT . $collectionSearchPage ?>">
							<?= (isset($LANG['H_SEARCH'])?$LANG['H_SEARCH']:'Search') ?>
						</a>
					</li>
					<li>
						<a href="<?= $CLIENT_ROOT ?>/collections/map/index.php" rel="noopener noreferrer">
							<?= (isset($LANG['H_MAP_SEARCH'])?$LANG['H_MAP_SEARCH']:'Map Search') ?>
						</a>
					</li>
					<li>
						<a href="<?= $CLIENT_ROOT ?>/checklists/index.php">
							<?= (isset($LANG['H_INVENTORIES'])?$LANG['H_INVENTORIES']:'Checklists') ?>
						</a>
					</li>
					<li>
						<a href="<?= $CLIENT_ROOT ?>/imagelib/search.php">
							<?= (isset($LANG['H_IMAGES'])?$LANG['H_IMAGES']:'Images') ?>
						</a>
					</li>
					<li>
						<a href="<?= $CLIENT_ROOT ?>/includes/usagepolicy.php">
							<?= (isset($LANG['H_DATA_USAGE'])?$LANG['H_DATA_USAGE']:'Data Use') ?>
						</a>
					</li>
					<li>
						<a href="https://symbiota.org/docs" target="_blank" rel="noopener noreferrer">
							<?= (isset($LANG['H_HELP'])?$LANG['H_HELP']:'Help') ?>
						</a>
					</li>
					<li>
						<a href='<?= $CLIENT_ROOT ?>/sitemap.php'>
							<?= (isset($LANG['H_SITEMAP'])?$LANG['H_SITEMAP']:'Sitemap') ?>
						</a>
					</li>
					<li>
						<a href="#">Example Dropdown</a>
						<ul>
							<li>
								<a href="#">Link 1</a>
							</li>
							<li>
								<a href="#">Link 2</a>
							</li>
							<li>
								<a href="#">Sub Menu</a>
								<ul>
									<li>
										<a href="#">Link 3</a>
									</li>
								</ul>
							</li>	
						</ul>
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
