<?php
if($LANG_TAG == 'en' || !file_exists($SERVER_ROOT.'/content/lang/templates/header.' . $LANG_TAG . '.php'))
	include_once($SERVER_ROOT . '/content/lang/templates/header.en.php');
else include_once($SERVER_ROOT . '/content/lang/templates/header.' . $LANG_TAG . '.php');
$SHOULD_USE_HARVESTPARAMS = $SHOULD_USE_HARVESTPARAMS ?? false;
$collectionSearchPage = $SHOULD_USE_HARVESTPARAMS ? '/collections/index.php' : '/collections/search/index.php';
?>
<div class="header-wrapper">
	<header>
		<div class="top-wrapper">
			<a class="screen-reader-only" href="#end-nav"><?= $LANG['H_SKIP_NAV'] ?></a>
			<nav class="top-login" aria-label="horizontal-nav">
				<?php
				if ($USER_DISPLAY_NAME) {
					?>
					<div class="welcome-text bottom-breathing-room-rel">
						<?= $LANG['H_WELCOME'] . ' ' . $USER_DISPLAY_NAME ?>!
					</div>
					<span style="white-space: nowrap;" class="button button-tertiary bottom-breathing-room-rel">
						<a href="<?= $CLIENT_ROOT ?>/profile/viewprofile.php"><?= $LANG['H_MY_PROFILE'] ?></a>
					</span>
					<span style="white-space: nowrap;" class="button button-secondary bottom-breathing-room-rel">
						<a href="<?= $CLIENT_ROOT ?>/profile/index.php?submit=logout"><?= $LANG['H_LOGOUT'] ?></a>
					</span>
					<?php
				} else {
					?>
					<span class="button button-secondary">
						<a href="<?= $CLIENT_ROOT . "/profile/index.php?refurl=" . htmlspecialchars($_SERVER['SCRIPT_NAME'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "?" . htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES); ?>">
							<?= $LANG['H_LOGIN'] ?>
						</a>
					</span>
					<?php
				}
				?>
			</nav>
			<div class="top-brand">
				<!-- <a href="<?= $CLIENT_ROOT ?>">
					<div class="image-container">
						<img src="<?= $CLIENT_ROOT ?>/images/layout/logo_symbiota.png" alt="Symbiota logo">
					</div>
				</a>
				-->
				<div class="brand-name">
					<h1>Algae Herbarium Portal</h1>
					<h2>A consortium of algae collections</h2>
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
							<?= $LANG['H_HOME'] ?>
						</a>
					</li>
					<li>
						<a href="<?= $CLIENT_ROOT . $collectionSearchPage ?>">
							<?= $LANG['H_SEARCH'] ?>
						</a>
					</li>
					<li>
						<a href="<?= $CLIENT_ROOT ?>/collections/map/index.php" rel="noopener noreferrer">
							<?= $LANG['H_MAP_SEARCH'] ?>
						</a>
					</li>
                                        <li>
                                                <a href="<?= $CLIENT_ROOT ?>/collections/exsiccati/index.php">Exsiccatae</a>
                                        </li>
					<li>
						<a href="<?= $CLIENT_ROOT ?>/checklists/index.php">Flora Projects</a>
					</li>
					<li>
						<a href="<?= $CLIENT_ROOT ?>/imagelib/search.php">
							<?= $LANG['H_IMAGES'] ?>
						</a>
					</li>
					<li>
                                                <a href="<?= $CLIENT_ROOT ?>/checklists/dynamicmap.php?interface=checklist">Dynamic Checklist</a>
                                        </li>
					<li>
						<a href="<?= $CLIENT_ROOT ?>/includes/usagepolicy.php">
							<?= $LANG['H_DATA_USAGE'] ?>
						</a>
					</li>
					<li>
						<a href='<?= $CLIENT_ROOT ?>/sitemap.php'>
							<?= $LANG['H_SITEMAP'] ?>
						</a>
					</li>
				</ul>
			</nav>
		</div>
		<div id="end-nav"></div>
	</header>
</div>
