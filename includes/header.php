<?php
if($LANG_TAG == 'en' || !file_exists($SERVER_ROOT.'/content/lang/header.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/header.en.php');
else include_once($SERVER_ROOT.'/content/lang/header.'.$LANG_TAG.'.php');
include_once($SERVER_ROOT.'/classes/ProfileManager.php');
$pHandler = new ProfileManager();
$isAccessiblePreferred = $pHandler->getAccessibilityPreference($SYMB_UID);
?>
<div class="usa-overlay"></div>
<section
  class="usa-banner"
  aria-label="Official website of the United States government"
>
  <div class="usa-accordion">
    <header class="usa-banner__header">
      <div class="usa-banner__inner">
        <div class="grid-col-auto">
          <img
            aria-hidden="true"
            class="usa-banner__header-flag"
            src="<?php echo $CLIENT_ROOT ?>/assets/uswds/img/us_flag_small.png"
            alt=""
          />
        </div>
        <div class="grid-col-fill tablet:grid-col-auto" aria-hidden="true">
          <p class="usa-banner__header-text">
            An official website of the United States government
          </p>
          <p class="usa-banner__header-action">Here’s how you know</p>
        </div>
        <button
          type="button"
          class="usa-accordion__button usa-banner__button"
          aria-expanded="false"
          aria-controls="gov-banner-default-default"
        >
          <span class="usa-banner__button-text">Here’s how you know</span>
        </button>
      </div>
    </header>
    <div
      class="usa-banner__content usa-accordion__content"
      id="gov-banner-default-default"
    >
      <div class="grid-row grid-gap-lg">
        <div class="usa-banner__guidance tablet:grid-col-6">
          <img
            class="usa-banner__icon usa-media-block__img"
            src="/assets/img/icon-dot-gov.svg"
            role="img"
            alt=""
            aria-hidden="true"
          />
          <div class="usa-media-block__body">
            <p>
              <strong>Official websites use .gov</strong><br />A
              <strong>.gov</strong> website belongs to an official government
              organization in the United States.
            </p>
          </div>
        </div>
        <div class="usa-banner__guidance tablet:grid-col-6">
          <img
            class="usa-banner__icon usa-media-block__img"
            src="/assets/img/icon-https.svg"
            role="img"
            alt=""
            aria-hidden="true"
          />
          <div class="usa-media-block__body">
            <p>
              <strong>Secure .gov websites use HTTPS</strong><br />A
              <strong>lock</strong> (
              <span class="icon-lock"
                ><svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="52"
                  height="64"
                  viewBox="0 0 52 64"
                  class="usa-banner__lock-image"
                  role="img"
                  aria-labelledby="banner-lock-description-default"
                  focusable="false"
                >
                  <title id="banner-lock-title-default">Lock</title>
                  <desc id="banner-lock-description-default">Locked padlock icon</desc>
                  <path
                    fill="#000000"
                    fill-rule="evenodd"
                    d="M26 0c10.493 0 19 8.507 19 19v9h3a4 4 0 0 1 4 4v28a4 4 0 0 1-4 4H4a4 4 0 0 1-4-4V32a4 4 0 0 1 4-4h3v-9C7 8.507 15.507 0 26 0zm0 8c-5.979 0-10.843 4.77-10.996 10.712L15 19v9h22v-9c0-6.075-4.925-11-11-11z"
                  />
                </svg> </span
              >) or <strong>https://</strong> means you’ve safely connected to
              the .gov website. Share sensitive information only on official,
              secure websites.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<header role="banner" class="usa-header usa-header--basic usa-header--megamenu">
  <div class="usa-nav-container">
    <div class="usa-navbar">
      <div class="usa-logo">
        <!-- <div style="display: flex"> -->
        <img src="<?php echo $CLIENT_ROOT ?>/assets/uswds/img/ars-color-lockup.png"/>
        <em class="usa-logo__text">
          <a href="/" title="USDA Biocollections Portal">
              USDA Biocollections Portal
            </a>
          </em>
        <!-- </div> -->
      </div>
      <button type="button" class="usa-menu-btn">Menu</button>
    </div>
    <nav aria-label="Primary navigation" class="usa-nav" style="justify-content: center;">
      <button type="button" class="usa-nav__close">
        <img src="<?php echo $CLIENT_ROOT ?>/assets/uswds/img/usa-icons/close.svg" role="img" alt="Close" />
      </button>
      <ul class="usa-nav__primary usa-accordion">
        <li class="usa-nav__primary-item usa-current">
          <a href="javascript:void(0);" class="usa-nav-link"
            >Home</a
          >
        </li>
        <li class="usa-nav__primary-item">
          <button
            type="button"
            class="usa-accordion__button usa-nav__link"
            aria-expanded="false"
            aria-controls="basic-mega-nav-section-one"
          >
            <span>Search Collections</span>
          </button>
          <div
            id="basic-mega-nav-section-one"
            class="usa-nav__submenu usa-megamenu"
          >
            <div class="grid-row grid-gap-4">
              <div class="usa-col">
                <ul class="usa-nav__submenu-list">
                  <li class="usa-nav__submenu-item">
                    <a href="<?php echo $CLIENT_ROOT?>/collections/harvestparams.php">Search All Collections</a>
                  </li>
                  <li class="usa-nav__submenu-item">
                    <a href="<?php echo $CLIENT_ROOT?>/collections/harvestparams.php?db=<?php echo  $NA_COLLID?>">Search National Arboretum Herbarium</a>
                  </li>
                  <li class="usa-nav__submenu-item">
                    <a href="<?php echo $CLIENT_ROOT?>/collections/harvestparams.php?db=<?php echo  $BARC_COLLID?>">Search National Seed Herbarium</a>
                  </li>
                  <li class="usa-nav__submenu-item">
                    <a href="<?php echo $CLIENT_ROOT?>/collections/harvestparams.php?db=<?php echo  $BPI_SNAPSHOT_COLLID?>">Search National Fungus Collections</a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </li>
        <li class="usa-nav__primary-item">
          <button
            type="button"
            class="usa-accordion__button usa-nav__link"
            aria-expanded="false"
            aria-controls="basic-mega-nav-section-two"
          >
            <span>Map Search</span>
          </button>
          <div
            id="basic-mega-nav-section-two"
            class="usa-nav__submenu usa-megamenu"
          >
            <div class="grid-row grid-gap-4">
              <div class="usa-col">
                <ul class="usa-nav__submenu-list">
                  <li class="usa-nav__submenu-item">
                    <a href="<?php echo $CLIENT_ROOT ?>/collections/map/index.php">Map Search All Collections</a>
                  </li>
                  <li class="usa-nav__submenu-item">
                    <a href="<?php echo $CLIENT_ROOT?>/collections/map/index.php?db=<?php echo  $NA_COLLID?>">Map Search National Arboretum Herbarium</a>
                  </li>
                  <li class="usa-nav__submenu-item">
                    <a href="<?php echo $CLIENT_ROOT?>/collections/map/index.php?db=<?php echo  $BARC_COLLID?>">Map Search National Seed Herbarium</a>
                  </li>
                  <li class="usa-nav__submenu-item">
                    <a href="<?php echo $CLIENT_ROOT?>/collections/map/index.php?db=<?php echo  $BPI_SNAPSHOT_COLLID?>">Map Search National Fungus Collections</a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </li>
        <li class="usa-nav__primary-item">
          <button
            type="button"
            class="usa-accordion__button usa-nav__link"
            aria-expanded="false"
            aria-controls="basic-mega-nav-section-three"
          >
            <span>About Collections</span>
          </button>
          <div
            id="basic-mega-nav-section-three"
            class="usa-nav__submenu usa-megamenu"
          >
            <div class="grid-row grid-gap-4">
              <div class="usa-col">
                <ul class="usa-nav__submenu-list">
                  <li class="usa-nav__submenu-item">
                    <a href="<?php echo $CLIENT_ROOT?>/collections/misc/collprofiles.php?collid=<?php echo  $NA_COLLID?>">About National Arboretum Herbarium</a>
                  </li>
                  <li class="usa-nav__submenu-item">
                    <a href="<?php echo $CLIENT_ROOT?>/collections/misc/collprofiles.php?collid=<?php echo  $BARC_COLLID?>">About National Seed Herbarium</a>
                  </li>
                  <li class="usa-nav__submenu-item">
                    <a href="<?php echo $CLIENT_ROOT?>/collections/misc/collprofiles.php?collid=<?php echo  $BPI_SNAPSHOT_COLLID?>">About National Fungus Collections</a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </li>
        <li class="usa-nav__primary-item">
          <a href="javascript:void(0);" class="usa-nav-link"
            >Data Use</a
          >
        </li>
        <li class="usa-nav__primary-item">
          <a href="javascript:void(0);" class="usa-nav-link"
            >Help</a
          >
        </li>
        <li class="usa-nav__primary-item">
          <a href='<?php echo $CLIENT_ROOT; ?>/sitemap.php' class="usa-nav-link"
            >Sitemap</a
          >
        </li>
      </ul>
    </nav>
  </div>
</header>
<div class="navpath">
</div>