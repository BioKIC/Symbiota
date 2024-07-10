<?php
$name = $catEl['name'] ?? '';
if($catEl['acronym']) $name .= ' ('.$catEl['acronym'].')';
$catIcon = $catEl['icon'];
unset($catEl['name']);
unset($catEl['acronym']);
unset($catEl['icon']);
$idStr = $collCnt . '-' . $catId;
?>
<section class="gridlike-form-row bottom-breathing-room-rel">
    <?php
    if($displayIcons){
        ?>
        <div class="<?php echo ($catIcon?'cat-icon-div':''); ?>">
        <?php
        if($catIcon){
            $catIcon = (substr($catIcon,0,6)=='images'?$CLIENT_ROOT:'').$catIcon;
            echo '<img alt="" src="' . $catIcon . '" style="border:0px;width:30px;height:30px;" />';
        }
        ?>
        </div>
    <?php
    }
    ?>
        <?php
        $catSelected = false;
        if(!$catSelArr && !$collSelArr) $catSelected = true;
        elseif(in_array($catid, $catSelArr)) $catSelected = true;
        $ariaLabel = $name . '(' . $collTypeLabel . ')' . '-' . $uniqGrouping;
        echo '<input style="margin:0" data-ccode="' . $catid . '" aria-label="' . $ariaLabel . '"   data-role="none" id="cat-' . $idStr . '-' . $collTypeLabel . '-' . $uniqGrouping . '-Input" name="cat[]" value="' . $catid.'" type="checkbox" onclick="selectAllCat(this,\'cat-' . $idStr . '\')" ' . ($catSelected?'checked':'') . ' />';
        echo '<label for="'. 'cat-' . $idStr . '-' . $collTypeLabel . '-' . $uniqGrouping . '-Input">' . $name . " (" . $collTypeLabel . ")".'</label>';
        ?>
    <div>
        <a href="#" class="condense-expand-flex" onclick="toggleCat('<?php echo htmlspecialchars($idStr, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>');return false;">
        <div class="condense-expand-button-set">
            <img id="plus-<?php echo $idStr; ?>" src="<?php echo $CLIENT_ROOT; ?>/images/plus.png" style="display:none; width:1em;" alt="plus sign to expand menu" />
            <img id="minus-<?php echo $idStr; ?>" src="<?php echo $CLIENT_ROOT; ?>/images/minus.png" style="width:1em;" alt="minus sign to condense menu" />
            <p id="ptext-<?php echo $idStr; ?>" style="<?php echo ((0 != $catid)?'':'display:none;') ?>">
                <?php echo $LANG['CONDENSE'] ?>
            </p>
            <p id="mtext-<?php echo $idStr; ?>" style="<?php echo ((0 != $catid)?'display:none;':'') ?>" >
                <?php echo $LANG['EXPAND'] ?>
            </p>
        </div>
        </a>
    </div>
</section>
<?php
    include('./collectionGroupSubcollectionList.php');
?>
