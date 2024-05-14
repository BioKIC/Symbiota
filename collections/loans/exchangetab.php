<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT . '/classes/OccurrenceLoans.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT . '/content/lang/collections/loans/loan_langs.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/collections/loans/loan_langs.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/loans/loan_langs.en.php');

$collid = $_REQUEST['collid'];

$loanManager = new OccurrenceLoans();
if($collid) $loanManager->setCollId($collid);

$transInstList = $loanManager->getTransInstList($collid);
if(!$transInstList) echo '<script type="text/javascript">displayNewExchange();</script>';
?>
<div id="exchangeToggle" style="float:right;margin:10px;">
	<a href="#" onclick="displayNewExchange()">
		<img src="../../images/add.png" style="width:1.5em;" title="<?php echo $LANG['CREATE_NEW_EXCHANGE']; ?>" alt="<?php echo $LANG['CREATE_NEW_EXCHANGE']; ?>" />
	</a>
</div>
<div id="newexchangediv" style="display:<?php echo ($transInstList?'none':'block'); ?>;width:550px;">
	<form name="newexchangegiftform" action="exchange.php" method="post" onsubmit="return verfifyExchangeAddForm(this)">
		<fieldset>
			<legend><?php echo $LANG['NEW_GIFT']; ?></legend>
			<div style="padding-top:10px;float:left;">
				<span>
					<b><?php echo $LANG['TRANS_NO']; ?>:</b>
					<input type="text" autocomplete="off" name="identifier" maxlength="255" style="width:120px;border:2px solid black;text-align:center;font-weight:bold;color:black;" value="1" />
				</span>
			</div>
			<div style="clear:left;padding-top:6px;float:left;">
				<span>
					<?php echo $LANG['TRANS_TYPE']; ?>:
				</span><br />
				<span>
					<select name="transactiontype" style="width:120px;" >
						<option value="Shipment" SELECTED ><?php echo $LANG['SHIPMENT']; ?></option>
						<option value="Adjustment"><?php echo $LANG['ADJUSTMENT']; ?></option>
					</select>
				</span>
			</div>
			<div style="padding-top:6px;margin-left:20px;float:left;">
				<span>
					<?php echo $LANG['ENTERED_BY']; ?>:
				</span><br />
				<span>
					<input type="text" autocomplete="off" name="createdby" maxlength="32" style="width:100px;" value="<?php echo $PARAMS_ARR['un']; ?>" onchange=" " />
				</span>
			</div><br />
			<div style="padding-top:6px;float:left;">
				<span>
					<?php echo $LANG['INSTITUTION']; ?>:
				</span><br />
				<span>
					<select name="iid" style="width:400px;" >
						<option value=""><?php echo $LANG['SEL_INST']; ?></option>
						<option value="">------------------------------------------</option>
						<?php
						$instArr = $loanManager->getInstitutionArr();
						foreach($instArr as $k => $v){
							echo '<option value="' . $k . '">' . $v . '</option>';
						}
						?>
					</select>
				</span>
				<span>
					<a href="../misc/institutioneditor.php?emode=1" target="_blank" title="<?php echo $LANG['ADD_NEW_INST']; ?>">
						<img src="../../images/add.png" style="width:1.5em;" />
					</a>
				</span>
			</div>
			<div style="clear:both;padding-top:8px;">
				<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
				<input type="hidden" name="tabindex" value="2" />
				<input name="formsubmit" type="hidden" value="createExchange" />
				<button name="submitbutton" type="submit" value="createExchange"><?php echo $LANG['CREATE_EXCHANGE']; ?></button>
			</div>
		</fieldset>
	</form>
</div>
<div style="margin-top:10px;">
	<?php
	if($transInstList){
		?>
		<h3><?php echo $LANG['TRANS_BY_INST']; ?></h3>
		<ul>
			<?php
			foreach($transInstList as $k => $transArr){
				?>
				<li>
					<?php echo ($transArr['institutioncode'] ? $transArr['institutioncode'] : ($transArr['institutionname'] ? $transArr['institutionname'] : '[no name]')); ?>
					<?php
					$bal = $transArr['invoicebalance'];
					echo '(Balance: ' . ($bal?($bal < 0?'<span style="color:red;font-weight:bold;">' . $bal . '</span>':$bal):0) . ')';
					?>
					<a href="#" onclick="toggle('<?php echo $k; ?>');"><img src="../../images/tochild.png" style="width:1em;" /></a>
					<div id="<?php echo $k; ?>" style="display:none;">
						<ul>
							<?php
							$transList = $loanManager->getTransactions($collid,$k);
							foreach($transList as $t => $transArr){
								echo '<li>';
								echo '<a href="exchange.php?collid=' . htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&exchangeid=' . htmlspecialchars($t, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">#' . $transArr['identifier'] . ' <img src="../../images/edit.png" style="width:1.2em;" /></a>: ';
								if($transArr['transactiontype'] == 'Shipment'){
									if($transArr['in_out'] == 'Out'){
										echo $LANG['OUTGOING_EX_SENT'];
										echo $transArr['datesent'] . '; ' . $LANG['INCLUDING'] . ': ';
									}
									else{
										echo $LANG['INCOMING_EX_RECEIVED'];
										echo $transArr['datereceived'] . '; ' . $LANG['INCLUDING'] . ': ';
									}
									echo ($transArr['totalexmounted']?$transArr['totalexmounted'] . ' ' . $LANG['MOUNTED'] . ', ':'');
									echo ($transArr['totalexunmounted']?$transArr['totalexunmounted'] . ' ' . $LANG['UNMOUNTED'] . ', ':'');
									echo ($transArr['totalgift']?$transArr['totalgift'] . ' ' . $LANG['GIFT'] . ', ':'');
									echo ($transArr['totalgiftdet']?$transArr['totalgiftdet'] . ' ' . $LANG['GIFT_FOR_DET'] . ', ':'');
									echo $LANG['BALANCE'] . ': ' . $transArr['invoicebalance'];
								}
								else{
									echo $LANG['ADJUSTMENT_OF'] . $transArr['adjustment'] . ' ' . $LANG['SPECIMENS'];
								}
								echo '</li>';
							}
							?>
						</ul>
					</div>
				</li>
				<?php
			}
			?>
		</ul>
		<?php
	}
	else{
		echo '<div style="font-weight:bold;font-size:120%;margin-top:10px;">' . $LANG['NO_TRANSACTIONS'] . '</div>';
	}
	?>
</div>