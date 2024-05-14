<?php
?>
    <section class="gridlike-form-row bottom-breathing-room-rel">
        <?php
        if($displayIcons){
            ?>
            <div class="<?php ($cArr["icon"]?'cat-icon-div':''); ?>">
                <?php
                if($cArr["icon"]){
                    $cIcon = (substr($cArr["icon"],0,6)=='images'?$CLIENT_ROOT:'').$cArr["icon"];
                    ?>
                    <a href = '<?php echo htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/collections/misc/collprofiles.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>'>
                        <img alt="Icon associated with collection:  <?php echo isset($cArr["collname"]) ? substr($cArr["collname"],0, 20) : substr($cArr["instcode"],0, 20) ?>" src="<?php echo htmlspecialchars($cIcon, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" style="border:0px;width:30px;height:30px;" />
                    </a>
                    <?php
                }
                ?>
                &nbsp;
            </div>
            <?php
        }
        ?>
        <div class="collection-checkbox">
            <?php
            $codeStr = '(';
            if(array_key_exists('instcode', $cArr)){
                $codeStr = ' (' . $cArr['instcode'];
            }
            if(array_key_exists('collcode', $cArr)){
                $codeStr .= '-' . $cArr['collcode'];
            } 
            $codeStr .= ')';
            echo '<input data-chip="Collection: ' . $codeStr . '" aria-label="select collection ' . $collid . '" data-role="none" id="collection-' . $collid . '" name="db[]" value="' . $collid . '" type="checkbox" onclick="uncheckAll()" '.(!$collSelArr || in_array($collid, $collSelArr)?'checked':'').' />';
            ?>
        </div>
        <div>
            <div class="collectiontitle">
                <?php
                $codeStr = '('.$cArr['instcode'];
                if($cArr['collcode']) $codeStr .= '-'.$cArr['collcode'];
                $codeStr .= ')';
                echo '<div class="collectionname">'.$cArr["collname"].'</div> <div class="collectioncode">'.$codeStr.'</div> ';
                ?>
                <a href = '<?php echo htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/collections/misc/collprofiles.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>' target="_blank">
                    <?php echo (isset($LANG['MORE_INFO'])?$LANG['MORE_INFO']:'more info...'); ?>
                </a>
            </div>
        </div>
    </section>
<?php
?>