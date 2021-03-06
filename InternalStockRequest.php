<?php

include('includes/DefineStockRequestClass.php');

include('includes/session.inc');
$Title = _('Internal Materials Request');
$ViewTopic = 'Inventory';
$BookMark = 'CreateRequest';
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');

echo '<p class="page_title_text noPrint" ><img src="' . $RootPath . '/css/' . $Theme . '/images/transactions.png" title="' . $Title . '" alt="" />' . ' ' . $Title . '</p>';

if (isset($_GET['Cancel'])) {
	$Title = _('Amend an Internal Materials Request');
	$sql = "UPDATE stockrequest SET closed=1
				WHERE dispatchid='" . $_GET['Cancel'] . "'";
	$result = DB_query($sql, $db);
	$_GET['Edit'] = 'Yes';
}

if (isset($_GET['New'])) {
	unset($_SESSION['Request']);
	$_SESSION['Request'] = new StockRequest();
}

if (isset($_GET['Amend'])) {
	unset($_SESSION['Request']);
	$_SESSION['Request'] = new StockRequest();
	$sql = "SELECT userid,
					loccode,
					departmentid,
					despatchdate,
					narrative
				FROM stockrequest
				WHERE dispatchid='" . $_GET['Amend'] . "'";
	$result = DB_query($sql, $db);
	$myrow = DB_fetch_array($result);
	$_SESSION['Request']->DispatchDate = ConvertSQLDate($myrow['despatchdate']);
	$_SESSION['Request']->ID = $_GET['Amend'];
	$_SESSION['Request']->UserID = $myrow['userid'];
	$_SESSION['Request']->Location = $myrow['loccode'];
	$_SESSION['Request']->Department = $myrow['departmentid'];
	$_SESSION['Request']->Narrative = $myrow['narrative'];
	$_SESSION['Request']->NewRequest = 1;
	$LineSQL = "SELECT dispatchitemsid,
						stockrequestitems.stockid,
						stockrequestitems.decimalplaces,
						stockrequestitems.uom,
						quantity,
						stockmaster.description
					FROM stockrequestitems
					INNER JOIN stockmaster
						ON stockmaster.stockid=stockrequestitems.stockid
					WHERE dispatchid='" . $_GET['Amend'] . "'";
	$LineResult = DB_query($LineSQL, $db);
	while ($LineRow = DB_fetch_array($LineResult)) {
		$_SESSION['Request']->AddLine($LineRow['stockid'], $LineRow['description'], $LineRow['quantity'], $LineRow['uom'], $LineRow['decimalplaces'], $LineRow['dispatchitemsid']);
	}
}

if (isset($_POST['Update'])) {
	$InputError = 0;
	if ($_POST['Department'] == '') {
		prnMsg(_('You must select a Department for the request'), 'error');
		$InputError = 1;
	}
	if ($_POST['Location'] == '') {
		prnMsg(_('You must select a Location to request the items from'), 'error');
		$InputError = 1;
	}
	if ($InputError == 0) {
		$_SESSION['Request']->Department = $_POST['Department'];
		$_SESSION['Request']->Location = $_POST['Location'];
		$_SESSION['Request']->DispatchDate = $_POST['DispatchDate'];
		$_SESSION['Request']->Narrative = $_POST['Narrative'];
	}
}

if (isset($_POST['Edit'])) {
	$_SESSION['Request']->LineItems[$_POST['LineNumber']]->Quantity = $_POST['Quantity'];
}

if (isset($_GET['Delete'])) {
	unset($_SESSION['Request']->LineItems[$_GET['Delete']]);
	echo '<br />';
	prnMsg(_('The line was successfully deleted'), 'success');
	echo '<br />';
}

if (isset($_GET['Edit']) and $_GET['Edit'] == 'Yes') {
	unset($_SESSION['Request']);
	$_SESSION['Request'] = new StockRequest();

	/* Retrieve the requisition header information
	 */
	$sql = "SELECT stockrequest.dispatchid,
					locations.locationname,
					stockrequest.despatchdate,
					stockrequest.narrative,
					departments.description,
					w1.realname as authoriser,
					w2.realname as initiator,
					w1.email
				FROM stockrequest
				INNER JOIN departments
					ON stockrequest.departmentid=departments.departmentid
				INNER JOIN locations
					ON stockrequest.loccode=locations.loccode
				INNER JOIN www_users as w2
					ON w2.userid=stockrequest.userid
				INNER JOIN www_users as w1
					ON w1.userid=departments.authoriser
				WHERE stockrequest.closed=0
					AND authorised=0
					AND w2.userid='" . $_SESSION['UserID'] . "'";
	$result = DB_query($sql, $db);

	echo '<form onSubmit="return VerifyForm(this);" method="post" class="noPrint" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	echo '<table class="selection">';

	/* Create the table for the purchase order header */
	echo '<tr>
			<th>' . _('Request Number') . '</th>
			<th>' . _('Department') . '</th>
			<th>' . _('Initiator') . '</th>
			<th>' . _('Location Of Stock') . '</th>
			<th>' . _('Requested Date') . '</th>
			<th>' . _('Narrative') . '</th>
		</tr>';

	while ($myrow = DB_fetch_array($result)) {

		echo '<tr>
				<td>' . $myrow['dispatchid'] . '</td>
				<td>' . $myrow['description'] . '</td>
				<td>' . $myrow['initiator'] . '</td>
				<td>' . $myrow['locationname'] . '</td>
				<td>' . ConvertSQLDate($myrow['despatchdate']) . '</td>
				<td>' . $myrow['narrative'] . '</td>
				<td><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?Amend=' . $myrow['dispatchid'] . '">' . _('Amend') . '</a></td>
				<td><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?Cancel=' . $myrow['dispatchid'] . '">' . _('Cancel') . '</a></td>
			</tr>';
		$linesql = "SELECT stockrequestitems.dispatchitemsid,
							stockrequestitems.stockid,
							stockrequestitems.decimalplaces,
							stockrequestitems.uom,
							stockmaster.description,
							stockrequestitems.quantity
					FROM stockrequestitems
					INNER JOIN stockmaster
						ON stockmaster.stockid=stockrequestitems.stockid
					WHERE dispatchid='" . $myrow['dispatchid'] . "'";
		$lineresult = DB_query($linesql, $db);

		echo '<tr>
				<td></td>
				<td colspan="5" align="left">
					<table class="selection" align="left">
						<tr>
							<th>' . _('Product') . '</th>
							<th>' . _('Quantity Required') . '</th>
							<th>' . _('Units') . '</th>
						</tr>';

		while ($linerow = DB_fetch_array($lineresult)) {
			echo '<tr>
					<td>' . $linerow['description'] . '</td>
					<td class="number">' . locale_number_format($linerow['quantity'], $linerow['decimalplaces']) . '</td>
					<td>' . $linerow['uom'] . '</td>
				</tr>';
		} // end while order line detail
		echo '</table>
			</td>
		</tr>';
	} //end while header loop
	echo '</table>
		</form>';
	include('includes/footer.inc');
	exit;
}

foreach ($_POST as $key => $value) {
	if (mb_strstr($key, 'StockID')) {
		$Index = mb_substr($key, 7);
		if (filter_number_format($_POST['Quantity' . $Index]) > 0) {
			$StockID = $value;
			$ItemDescription = $_POST['ItemDescription' . $Index];
			$DecimalPlaces = $_POST['DecimalPlaces' . $Index];
			$NewItem_array[$StockID] = filter_number_format($_POST['Quantity' . $Index]);
			$_POST['Units' . $StockID] = $_POST['Units' . $Index];
			$_SESSION['Request']->AddLine($StockID, $ItemDescription, $NewItem_array[$StockID], $_POST['Units' . $StockID], $DecimalPlaces);
		}
	}
}

if (isset($_POST['Submit'])) {
	DB_Txn_Begin($db);
	$InputError = 0;
	if ($_SESSION['Request']->Department == '') {
		prnMsg(_('You must select a Department for the request'), 'error');
		$InputError = 1;
	}
	if ($_SESSION['Request']->Location == '') {
		prnMsg(_('You must select a Location to request the items from'), 'error');
		$InputError = 1;
	}
	if ($InputError == 0) {
		if ($_SESSION['Request']->NewRequest == 0) {
			$_SESSION['Request']->ID = GetNextTransNo(38, $db);
			$HeaderSQL = "INSERT INTO stockrequest (dispatchid,
													loccode,
													userid,
													departmentid,
													despatchdate,
													narrative)
												VALUES(
													'" . $_SESSION['Request']->ID . "',
													'" . $_SESSION['Request']->Location . "',
													'" . $_SESSION['UserID'] . "',
													'" . $_SESSION['Request']->Department . "',
													'" . FormatDateForSQL($_SESSION['Request']->DispatchDate) . "',
													'" . $_SESSION['Request']->Narrative . "')";
		} else {
			$HeaderSQL = "UPDATE stockrequest SET loccode='" . $_SESSION['Request']->Location . "',
													userid='" . $_SESSION['UserID'] . "',
													departmentid='" . $_SESSION['Request']->Department . "',
													despatchdate='" . FormatDateForSQL($_SESSION['Request']->DispatchDate) . "',
													narrative='" . $_SESSION['Request']->Narrative . "'
												WHERE dispatchid='" . $_SESSION['Request']->ID . "'";
		}
		$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The request header record could not be inserted because');
		$DbgMsg = _('The following SQL to insert the request header record was used');
		$Result = DB_query($HeaderSQL, $db, $ErrMsg, $DbgMsg, true);

		foreach ($_SESSION['Request']->LineItems as $LineItems) {
			$sql = "SELECT COUNT(stockid) as total FROM stockrequestitems
									WHERE dispatchid='" . $_SESSION['Request']->ID . "'
										AND dispatchitemsid='" . $LineItems->LineNumber . "'";			$result = DB_query($sql, $db);
			$myrow = DB_fetch_array($result);
			if ($myrow['total'] == 0) {
				$LineSQL = "INSERT INTO stockrequestitems (dispatchitemsid,
															dispatchid,
															stockid,
															quantity,
															decimalplaces,
															uom)
														VALUES(
															'" . $LineItems->LineNumber . "',
															'" . $_SESSION['Request']->ID . "',
															'" . $LineItems->StockID . "',
															'" . $LineItems->Quantity . "',
															'" . $LineItems->DecimalPlaces . "',
															'" . $LineItems->UOM . "')";
			} else {
				$LineSQL = "UPDATE stockrequestitems SET stockid='" . $LineItems->StockID . "',
														quantity='" . $LineItems->Quantity . "',
														decimalplaces='" . $LineItems->DecimalPlaces . "',
														uom='" . $LineItems->UOM . "'
													WHERE dispatchid='" . $_SESSION['Request']->ID . "'
														AND dispatchitemsid='" . $LineItems->LineNumber . "'";
			}
			$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The request line record could not be inserted because');
			$DbgMsg = _('The following SQL to insert the request header record was used');
			$Result = DB_query($LineSQL, $db, $ErrMsg, $DbgMsg, true);
		}

		$EmailSQL = "SELECT email
					FROM www_users, departments
					WHERE departments.authoriser = www_users.userid
						AND departments.departmentid = '" . $_SESSION['Request']->Department . "'";
		$EmailResult = DB_query($EmailSQL, $db);
		if ($myEmail = DB_fetch_array($EmailResult)) {
			$ConfirmationText = _('An internal stock request has been created and is waiting for your authoritation');
			$EmailSubject = _('Internal Stock Request needs your authoritation');
			if ($_SESSION['SmtpSetting'] == 0) {
				mail($myEmail['email'], $EmailSubject, $ConfirmationText);
			} else {
				include('includes/htmlMimeMail.php');
				$mail = new htmlMimeMail();
				$mail->setSubject($EmailSubject);
				$mail->setText($ConfirmationText);
				$result = SendmailBySmtp($mail, array(
					$myEmail['email']
				));
			}
		}

	}
	DB_Txn_Commit($db);
	if ($_SESSION['Request']->NewRequest == 0) {
		prnMsg(_('The internal stock request has been entered and now needs to be authorised'), 'success');
		echo '<br /><div class="centre"><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?New=Yes">' . _('Create another request') . '</a></div>';
	} else {
		prnMsg(_('The internal stock request has been updated'), 'success');
		echo '<br /><div class="centre"><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?Edit=Yes">' . _('Amend another request') . '</a></div>';
	}
	include('includes/footer.inc');
	unset($_SESSION['Request']);
	exit;
}

if (isset($_GET['Edit'])) {
	echo '<form onSubmit="return VerifyForm(this);" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" class="noPrint">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	echo '<table class="selection">';
	echo '<tr>
			<th colspan="2"><h4>' . _('Edit the Request Line') . '</h4></th>
		</tr>';
	echo '<tr>
			<td>' . _('Line number') . '</td>
			<td>' . $_SESSION['Request']->LineItems[$_GET['Edit']]->LineNumber . '</td>
		</tr>
		<tr>
			<td>' . _('Stock Code') . '</td>
			<td>' . $_SESSION['Request']->LineItems[$_GET['Edit']]->StockID . '</td>
		</tr>
		<tr>
			<td>' . _('Item Description') . '</td>
			<td>' . $_SESSION['Request']->LineItems[$_GET['Edit']]->ItemDescription . '</td>
		</tr>
		<tr>
			<td>' . _('Unit of Measure') . '</td>
			<td>' . $_SESSION['Request']->LineItems[$_GET['Edit']]->UOM . '</td>
		</tr>
		<tr>
			<td>' . _('Quantity Requested') . '</td>
			<td><input type="text" class="number" name="Quantity" value="' . locale_number_format($_SESSION['Request']->LineItems[$_GET['Edit']]->Quantity, $_SESSION['Request']->LineItems[$_GET['Edit']]->DecimalPlaces) . '" /></td>
		</tr>';
	echo '<input type="hidden" name="LineNumber" value="' . $_SESSION['Request']->LineItems[$_GET['Edit']]->LineNumber . '" />';
	echo '</table>';
	echo '<div class="centre">
			<input type="submit" name="Edit" value="' . _('Update Line') . '" />
		</div>
		</form>';
	include('includes/footer.inc');
	exit;
}

echo '<form onSubmit="return VerifyForm(this);" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" class="noPrint">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

echo '<table class="selection">';
echo '<tr>
		<th colspan="2"><h4>' . _('Internal Stock Request Details') . '</h4></th>
	</tr>
	<tr>
		<td>' . _('Department') . ':</td>';
if ($_SESSION['AllowedDepartment'] == 0) {
	// any internal department allowed
	$sql = "SELECT departmentid,
				description
			FROM departments
			ORDER BY description";
} else {
	// just 1 internal department allowed
	$sql = "SELECT departmentid,
				description
			FROM departments
			WHERE departmentid = '" . $_SESSION['AllowedDepartment'] . "'
			ORDER BY description";
}
$result = DB_query($sql, $db);
echo '<td><select required="required" minlength="1" name="Department">
		<option value="">' . _('Select a Department') . '</option>';
while ($myrow = DB_fetch_array($result)) {
	if (isset($_SESSION['Request']->Department) and $_SESSION['Request']->Department == $myrow['departmentid']) {
		echo '<option selected="True" value="' . $myrow['departmentid'] . '">' . htmlspecialchars($myrow['description'], ENT_QUOTES, 'UTF-8') . '</option>';
	} else {
		echo '<option value="' . $myrow['departmentid'] . '">' . htmlspecialchars($myrow['description'], ENT_QUOTES, 'UTF-8') . '</option>';
	}
}
echo '</select></td>
	</tr>
	<tr>
		<td>' . _('Location from which to request stock') . ':</td>';

if ($_SESSION['RestrictLocations'] == 0) {
	$sql = "SELECT locationname,
					loccode
				FROM locations
					WHERE internalrequest = 1
				ORDER BY locationname";
} else {
	$sql = "SELECT locationname,
					loccode
				FROM locations
				INNER JOIN www_users
					ON locations.loccode=www_users.defaultlocation
				WHERE www_users.userid='" . $_SESSION['UserID'] . "'
					AND internalrequest = 1
				ORDER BY locationname";
}

$result = DB_query($sql, $db);
echo '<td><select required="required" minlength="1" name="Location">
		<option value="">' . _('Select a Location') . '</option>';
while ($myrow = DB_fetch_array($result)) {
	if (isset($_SESSION['Request']->Location) and $_SESSION['Request']->Location == $myrow['loccode']) {
		echo '<option selected="True" value="' . $myrow['loccode'] . '">' . $myrow['loccode'] . ' - ' . htmlspecialchars($myrow['locationname'], ENT_QUOTES, 'UTF-8') . '</option>';
	} else {
		echo '<option value="' . $myrow['loccode'] . '">' . $myrow['loccode'] . ' - ' . htmlspecialchars($myrow['locationname'], ENT_QUOTES, 'UTF-8') . '</option>';
	}
}
echo '</select></td>
	</tr>
	<tr>
		<td>' . _('Date when required') . ':</td>';
echo '<td><input type="text" class="date" alt="' . $_SESSION['DefaultDateFormat'] . '" name="DispatchDate" minlength="0" maxlength="10" size="11" value="' . $_SESSION['Request']->DispatchDate . '" /></td>
	  </tr>';

echo '<tr>
		<td>' . _('Narrative') . ':</td>
		<td><textarea name="Narrative" cols="30" rows="5">' . $_SESSION['Request']->Narrative . '</textarea></td>
	</tr>
	</table>';

echo '<div class="centre">
		<input type="submit" name="Update" value="' . _('Update') . '" />
	</div>
	</form>';

if (!isset($_SESSION['Request']->Location)) {
	include('includes/footer.inc');
	exit;
}

//****************MUESTRO LA TABLA CON LOS REGISTROS DE LA TRANSFERENCIA*************************************
$i = 0; //Line Item Array pointer
echo '<form onSubmit="return VerifyForm(this);" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" class="noPrint">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<br />
	<table class="selection">
	<tr>
		<th colspan="7"><h4>' . _('Details of Items Requested') . '</h4></th>
	</tr>
	<tr>
		<th>' . _('Line Number') . '</th>
		<th>' . _('Item Code') . '</th>
		<th>' . _('Item Description') . '</th>
		<th>' . _('Quantity Required') . '</th>
		<th>' . _('UOM') . '</th>
	</tr>';

$k = 0;

foreach ($_SESSION['Request']->LineItems as $LineItems) {

	if ($k == 1) {
		echo '<tr class="EvenTableRows">';
		$k = 0;
	} else {
		echo '<tr class="OddTableRows">';
		$k++;
	}
	echo '<td>' . $LineItems->LineNumber . '</td>
			<td>' . $LineItems->StockID . '</td>
			<td>' . $LineItems->ItemDescription . '</td>
			<td class="number">' . locale_number_format($LineItems->Quantity, $LineItems->DecimalPlaces) . '</td>
			<td>' . $LineItems->UOM . '</td>
			<td><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?Edit=' . $LineItems->LineNumber . '">' . _('Edit') . '</a></td>
			<td><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?Delete=' . $LineItems->LineNumber . '">' . _('Delete') . '</a></td>
		</tr>';
}

echo '</table>
	<br />
	<div class="centre">
		<input type="submit" name="Submit" value="' . _('Submit') . '" />
	</div>
	</form>';

echo '<form onSubmit="return VerifyForm(this);" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" class="noPrint">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

echo '<p class="page_title_text noPrint" ><img src="' . $RootPath . '/css/' . $Theme . '/images/inventory.png" title="' . _('Inventory Items') . '" alt="" />' . ' ' . _('Inventory Items') . '</p>';

$SQL = "SELECT stockcategory.categoryid,
				stockcategory.categorydescription
			FROM stockcategory, internalstockcatrole
			WHERE stockcategory.categoryid = internalstockcatrole.categoryid
				AND internalstockcatrole.secroleid= " . $_SESSION['AccessLevel'] . "
			ORDER BY stockcategory.categorydescription";
$result1 = DB_query($SQL, $db);
if (DB_num_rows($result1) == 0) {
	echo '<p class="bad">' . _('Problem Report') . ':<br />' . _('There are no stock categories currently defined please use the link below to set them up') . '</p>';
	echo '<br />
		<a href="' . $RootPath . '/StockCategories.php">' . _('Define Stock Categories') . '</a>';
	exit;
}
echo '<table class="selection">
	<tr>
		<td>' . _('In Stock Category') . ':<select minlength="0" name="StockCat">';

if (!isset($_POST['StockCat'])) {
	$_POST['StockCat'] = '';
}
if ($_POST['StockCat'] == 'All') {
	echo '<option selected="True" value="All">' . _('All Categories') . '</option>';
} else {
	echo '<option value="All">' . _('All Categories') . '</option>';
}
while ($myrow1 = DB_fetch_array($result1)) {
	if ($myrow1['categoryid'] == $_POST['StockCat']) {
		echo '<option selected="True" value="' . $myrow1['categoryid'] . '">' . $myrow1['categorydescription'] . '</option>';
	} else {
		echo '<option value="' . $myrow1['categoryid'] . '">' . $myrow1['categorydescription'] . '</option>';
	}
}
echo '</select></td>
	<td>' . _('Enter partial') . '<b> ' . _('Description') . '</b>:</td>';
if (isset($_POST['Keywords'])) {
	echo '<td><input type="text" name="Keywords" value="' . $_POST['Keywords'] . '" size="20" minlength="0" maxlength="25" /></td>';
} else {
	echo '<td><input type="text" name="Keywords" size="20" minlength="0" maxlength="25" /></td>';
}
echo '</tr>
		<tr>
			<td></td>
			<td><h3>' . _('OR') . ' ' . '</h3>' . _('Enter partial') . ' <b>' . _('Stock Code') . '</b>:</td>';

if (isset($_POST['StockCode'])) {
	echo '<td><input type="text" autofocus="autofocus" name="StockCode" value="' . $_POST['StockCode'] . '" size="15" minlength="0" maxlength="18" /></td>';
} else {
	echo '<td><input type="text" autofocus="autofocus" name="StockCode" size="15" minlength="0" maxlength="18" /></td>';
}
echo '</tr>
	</table>
	<div class="centre">
		<input type="submit" name="Search" value="' . _('Search Now') . '" />
	</div>';

echo '</form>';

if (isset($_POST['Search']) or isset($_POST['Next']) or isset($_POST['Prev'])) {

	if ($_POST['Keywords'] != '' and $_POST['StockCode'] == '') {
		prnMsg(_('Order Item description has been used in search'), 'warn');
	} elseif ($_POST['StockCode'] != '' and $_POST['Keywords'] == '') {
		prnMsg(_('Stock Code has been used in search'), 'warn');
	} elseif ($_POST['Keywords'] == '' and $_POST['StockCode'] == '') {
		prnMsg(_('Stock Category has been used in search'), 'warn');
	}
	if (isset($_POST['Keywords']) and mb_strlen($_POST['Keywords']) > 0) {
		//insert wildcard characters in spaces
		$_POST['Keywords'] = mb_strtoupper($_POST['Keywords']);
		$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';

		if ($_POST['StockCat'] == 'All') {
			$SQL = "SELECT stockmaster.stockid,
							stockmaster.description,
							stockmaster.units as stockunits,
							stockmaster.decimalplaces
					FROM stockmaster,
						stockcategory,
						internalstockcatrole
					WHERE stockmaster.categoryid=stockcategory.categoryid
						AND stockcategory.categoryid = internalstockcatrole.categoryid
						AND internalstockcatrole.secroleid= " . $_SESSION['AccessLevel'] . "
						AND stockmaster.mbflag <>'G'
						AND stockmaster.description " . LIKE . " '" . $SearchString . "'
						AND stockmaster.discontinued=0
					ORDER BY stockmaster.stockid";
		} else {
			$SQL = "SELECT stockmaster.stockid,
							stockmaster.description,
							stockmaster.units as stockunits,
							stockmaster.decimalplaces
					FROM stockmaster,
						stockcategory,
						internalstockcatrole
					WHERE stockmaster.categoryid=stockcategory.categoryid
						AND stockcategory.categoryid = internalstockcatrole.categoryid
						AND internalstockcatrole.secroleid= " . $_SESSION['AccessLevel'] . "
						AND stockmaster.mbflag <>'G'
						AND stockmaster.discontinued=0
						AND stockmaster.description " . LIKE . " '" . $SearchString . "'
						AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
					ORDER BY stockmaster.stockid";
		}

	} elseif (mb_strlen($_POST['StockCode']) > 0) {

		$_POST['StockCode'] = mb_strtoupper($_POST['StockCode']);
		$SearchString = '%' . $_POST['StockCode'] . '%';

		if ($_POST['StockCat'] == 'All') {
			$SQL = "SELECT stockmaster.stockid,
							stockmaster.description,
							stockmaster.units as stockunits,
							stockmaster.decimalplaces
					FROM stockmaster,
						stockcategory,
						internalstockcatrole
					WHERE stockmaster.categoryid=stockcategory.categoryid
						AND stockcategory.categoryid = internalstockcatrole.categoryid
						AND internalstockcatrole.secroleid= " . $_SESSION['AccessLevel'] . "
						AND stockmaster.stockid " . LIKE . " '" . $SearchString . "'
						AND stockmaster.mbflag <>'G'
						AND stockmaster.discontinued=0
					ORDER BY stockmaster.stockid";
		} else {
			$SQL = "SELECT stockmaster.stockid,
							stockmaster.description,
							stockmaster.units as stockunits,
							stockmaster.decimalplaces
					FROM stockmaster,
						stockcategory,
						internalstockcatrole
					WHERE stockmaster.categoryid=stockcategory.categoryid
						AND stockcategory.categoryid = internalstockcatrole.categoryid
						AND internalstockcatrole.secroleid= " . $_SESSION['AccessLevel'] . "
						AND stockmaster.stockid " . LIKE . " '" . $SearchString . "'
						AND stockmaster.mbflag <>'G'
						AND stockmaster.discontinued=0
						AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
					ORDER BY stockmaster.stockid";
		}

	} else {
		if ($_POST['StockCat'] == 'All') {
			$SQL = "SELECT stockmaster.stockid,
							stockmaster.description,
							stockmaster.units as stockunits,
							stockmaster.decimalplaces
					FROM stockmaster,
						stockcategory,
						internalstockcatrole
					WHERE stockmaster.categoryid=stockcategory.categoryid
						AND stockcategory.categoryid = internalstockcatrole.categoryid
						AND internalstockcatrole.secroleid= " . $_SESSION['AccessLevel'] . "
						AND stockmaster.mbflag <>'G'
						AND stockmaster.discontinued=0
					ORDER BY stockmaster.stockid";
		} else {
			$SQL = "SELECT stockmaster.stockid,
							stockmaster.description,
							stockmaster.units as stockunits,
							stockmaster.decimalplaces
					FROM stockmaster,
						stockcategory,
						internalstockcatrole
					WHERE stockmaster.categoryid=stockcategory.categoryid
						AND stockcategory.categoryid = internalstockcatrole.categoryid
						AND internalstockcatrole.secroleid= " . $_SESSION['AccessLevel'] . "
						AND stockmaster.mbflag <>'G'
						AND stockmaster.discontinued=0
						AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
					ORDER BY stockmaster.stockid";
		}
	}

	if (isset($_POST['Next'])) {
		$Offset = $_POST['NextList'];
	}
	if (isset($_POST['Prev'])) {
		$Offset = $_POST['Previous'];
	}
	if (!isset($Offset) or $Offset < 0) {
		$Offset = 0;
	}
	$SQL = $SQL . ' LIMIT ' . $_SESSION['DefaultDisplayRecordsMax'] . ' OFFSET ' . ($_SESSION['DefaultDisplayRecordsMax'] * $Offset);

	$ErrMsg = _('There is a problem selecting the part records to display because');
	$DbgMsg = _('The SQL used to get the part selection was');
	$SearchResult = DB_query($SQL, $db, $ErrMsg, $DbgMsg);

	if (DB_num_rows($SearchResult) == 0) {
		prnMsg(_('There are no products available meeting the criteria specified'), 'info');
	}
	if (DB_num_rows($SearchResult) < $_SESSION['DisplayRecordsMax']) {
		$Offset = 0;
	}

} //end of if search
/* display list if there is more than one record */
if (isset($searchresult) and !isset($_POST['Select'])) {

	echo '<form onSubmit="return VerifyForm(this);" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" class="noPrint">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	$ListCount = DB_num_rows($searchresult);
	if ($ListCount > 0) {
		// If the user hit the search button and there is more than one item to show
		$ListPageMax = ceil($ListCount / $_SESSION['DisplayRecordsMax']);
		if (isset($_POST['Next'])) {
			if ($_POST['PageOffset'] < $ListPageMax) {
				$_POST['PageOffset'] = $_POST['PageOffset'] + 1;
			}
		}
		if (isset($_POST['Previous'])) {
			if ($_POST['PageOffset'] > 1) {
				$_POST['PageOffset'] = $_POST['PageOffset'] - 1;
			}
		}
		if ($_POST['PageOffset'] > $ListPageMax) {
			$_POST['PageOffset'] = $ListPageMax;
		}
		if ($ListPageMax > 1) {
			echo '<div class="centre"><br />&nbsp;&nbsp;' . $_POST['PageOffset'] . ' ' . _('of') . ' ' . $ListPageMax . ' ' . _('pages') . '. ' . _('Go to Page') . ': ';
			echo '<select minlength="0" name="PageOffset">';
			$ListPage = 1;
			while ($ListPage <= $ListPageMax) {
				if ($ListPage == $_POST['PageOffset']) {
					echo '<option value=' . $ListPage . ' selected>' . $ListPage . '</option>';
				} else {
					echo '<option value=' . $ListPage . '>' . $ListPage . '</option>';
				}
				$ListPage++;
			}
			echo '</select>
				<input type="submit" name="Go" value="' . _('Go') . '" />
				<input type="submit" name="Previous" value="' . _('Previous') . '" />
				<input type="submit" name="Next" value="' . _('Next') . '" />
				<input type="hidden" name=Keywords value="' . $_POST['Keywords'] . '" />
				<input type="hidden" name=StockCat value="' . $_POST['StockCat'] . '" />
				<input type="hidden" name=StockCode value="' . $_POST['StockCode'] . '" />
				<br />
				</div>';
		}
		echo '<table cellpadding="2">';
		echo '<tr>
				<th>' . _('Code') . '</th>
				<th>' . _('Description') . '</th>
				<th>' . _('Total Qty On Hand') . '</th>
				<th>' . _('Units') . '</th>
				<th>' . _('Stock Status') . '</th>
			</tr>';
		$j = 1;
		$k = 0; //row counter to determine background colour
		$RowIndex = 0;
		if (DB_num_rows($searchresult) <> 0) {
			DB_data_seek($searchresult, ($_POST['PageOffset'] - 1) * $_SESSION['DisplayRecordsMax']);
		}
		while (($myrow = DB_fetch_array($searchresult)) and ($RowIndex <> $_SESSION['DisplayRecordsMax'])) {
			if ($k == 1) {
				echo '<tr class="EvenTableRows">';
				$k = 0;
			} else {
				echo '<tr class="OddTableRows">';
				$k++;
			}
			if ($myrow['mbflag'] == 'D') {
				$qoh = _('N/A');
			} else {
				$qoh = locale_number_format($myrow['qoh'], $myrow['decimalplaces']);
			}
			if ($myrow['discontinued'] == 1) {
				$ItemStatus = '<p class="bad">' . _('Obsolete') . '</p>';
			} else {
				$ItemStatus = '';
			}

			echo '<td><input type="submit" name="Select" value="' . $myrow['stockid'] . '" /></td>
					<td>' . $myrow['description'] . '</td>
					<td class="number">' . $qoh . '</td>
					<td>' . $myrow['units'] . '</td>
					<td><a target="_blank" href="' . $RootPath . '/StockStatus.php?StockID=' . $myrow['stockid'] . '">' . _('View') . '</a></td>
					<td>' . $ItemStatus . '</td>
				</tr>';
			//end of page full new headings if
		}
		//end of while loop
		echo '</table>
			</form>';
	}
}
/* end display list if there is more than one record */

if (isset($SearchResult)) {
	echo '<br />';
	echo '<div class="page_help_text noPrint">' . _('Select an item by entering the quantity required.  Click Order when ready.') . '</div>';
	echo '<br />';
	$j = 1;
	echo '<form onSubmit="return VerifyForm(this);" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" class="noPrint" id="orderform">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	echo '<table class="table1">';
	echo '<tr><td>
			<input type="hidden" name="Previous" value="' . ($Offset - 1) . '" />
			<input tabindex="' . ($j + 8) . '" type="submit" name="Prev" value="' . _('Prev') . '" /></td>';
	echo '<td style="text-align:center" colspan="6">
			<input type="hidden" name="order_items" value="1" />
			<input tabindex="' . ($j + 9) . '" type="submit" value="' . _('Add to Requisition') . '" /></td>';
	echo '<td>
			<input type="hidden" name="NextList" value="' . ($Offset + 1) . '" />
			<input tabindex="' . ($j + 10) . '" type="submit" name="Next" value="' . _('Next') . '" /></td></tr>';
	echo '<tbody>
			<tr>
			<th class="SortableColumn">' . _('Code') . '</th>
			<th class="SortableColumn">' . _('Description') . '</th>
			<th>' . _('Units') . '</th>
			<th>' . _('On Hand') . '</th>
			<th>' . _('On Demand') . '</th>
			<th>' . _('On Order') . '</th>
			<th>' . _('Available') . '</th>
			<th>' . _('Quantity') . '</th>
		</tr>';
	$ImageSource = _('No Image');

	$k = 0; //row colour counter
	$i = 0;
	while ($myrow = DB_fetch_array($SearchResult)) {
		if ($myrow['decimalplaces'] == '') {
			$DecimalPlacesSQL = "SELECT decimalplaces
								FROM stockmaster
								WHERE stockid='" . $myrow['stockid'] . "'";
			$DecimalPlacesResult = DB_query($DecimalPlacesSQL, $db);
			$DecimalPlacesRow = DB_fetch_array($DecimalPlacesResult);
			$DecimalPlaces = $DecimalPlacesRow['decimalplaces'];
		} else {
			$DecimalPlaces = $myrow['decimalplaces'];
		}

		$QOHSQL = "SELECT sum(locstock.quantity) AS qoh
							   FROM locstock
							   WHERE locstock.stockid='" . $myrow['stockid'] . "' AND
							   loccode = '" . $_SESSION['Request']->Location . "'";
		$QOHResult = DB_query($QOHSQL, $db);
		$QOHRow = DB_fetch_array($QOHResult);
		$QOH = $QOHRow['qoh'];

		// Find the quantity on outstanding sales orders
		$sql = "SELECT SUM(salesorderdetails.quantity-salesorderdetails.qtyinvoiced) AS dem
				 FROM salesorderdetails INNER JOIN salesorders
				 ON salesorders.orderno = salesorderdetails.orderno
				 WHERE salesorders.fromstkloc='" . $_SESSION['Request']->Location . "'
				 AND salesorderdetails.completed=0
				 AND salesorders.quotation=0
				 AND salesorderdetails.stkcode='" . $myrow['stockid'] . "'";
		$ErrMsg = _('The demand for this product from') . ' ' . $_SESSION['Request']->Location . ' ' . _('cannot be retrieved because');
		$DemandResult = DB_query($sql, $db, $ErrMsg);

		$DemandRow = DB_fetch_row($DemandResult);
		if ($DemandRow[0] != null) {
			$DemandQty = $DemandRow[0];
		} else {
			$DemandQty = 0;
		}

		// Find the quantity on purchase orders
		$sql = "SELECT SUM(purchorderdetails.quantityord-purchorderdetails.quantityrecd)*purchorderdetails.conversionfactor AS dem
				 FROM purchorderdetails LEFT JOIN purchorders
					ON purchorderdetails.orderno=purchorders.orderno
				 WHERE purchorderdetails.completed=0
				 AND purchorders.status<>'Cancelled'
				 AND purchorders.status<>'Rejected'
				 AND purchorders.status<>'Completed'
				AND purchorderdetails.itemcode='" . $myrow['stockid'] . "'";

		$ErrMsg = _('The order details for this product cannot be retrieved because');
		$PurchResult = DB_query($sql, $db, $ErrMsg);

		$PurchRow = DB_fetch_row($PurchResult);
		if ($PurchRow[0] != null) {
			$PurchQty = $PurchRow[0];
		} else {
			$PurchQty = 0;
		}

		// Find the quantity on works orders
		$sql = "SELECT SUM(woitems.qtyreqd - woitems.qtyrecd) AS dedm
			   FROM woitems
			   WHERE stockid='" . $myrow['stockid'] . "'";
		$ErrMsg = _('The order details for this product cannot be retrieved because');
		$WoResult = DB_query($sql, $db, $ErrMsg);

		$WoRow = DB_fetch_row($WoResult);
		if ($WoRow[0] != null) {
			$WoQty = $WoRow[0];
		} else {
			$WoQty = 0;
		}

		if ($k == 1) {
			echo '<tr class="EvenTableRows">';
			$k = 0;
		} else {
			echo '<tr class="OddTableRows">';
			$k = 1;
		}
		$OnOrder = $PurchQty + $WoQty;
		$Available = $QOH - $DemandQty + $OnOrder;
		echo '<td>' . $myrow['stockid'] . '</td>
				<td>' . $myrow['description'] . '</td>
				<td>' . $myrow['stockunits'] . '</td>
				<td class="number">' . locale_number_format($QOH, $DecimalPlaces) . '</td>
				<td class="number">' . locale_number_format($DemandQty, $DecimalPlaces) . '</td>
				<td class="number">' . locale_number_format($OnOrder, $DecimalPlaces) . '</td>
				<td class="number">' . locale_number_format($Available, $DecimalPlaces) . '</td>
				<td><input class="number" autofocus="autofocus" tabindex="' . ($j + 7) . '" type="text" size="6" name="Quantity' . $i . '" value="0" />
				<input type="hidden" name="StockID' . $i . '" value="' . $myrow['stockid'] . '" />
				</td>
			</tr>';
		echo '<input type="hidden" name="DecimalPlaces' . $i . '" value="' . $myrow['decimalplaces'] . '" />';
		echo '<input type="hidden" name="ItemDescription' . $i . '" value="' . $myrow['description'] . '" />';
		echo '<input type="hidden" name="Units' . $i . '" value="' . $myrow['stockunits'] . '" />';

		$i++;
		//end of page full new headings if
	}
	//end of while loop
	echo '</tbody><tr><td><input type="hidden" name="Previous" value="' . ($Offset - 1) . '" />
			<input tabindex="' . ($j + 7) . '" type="submit" name="Prev" value="' . _('Prev') . '" /></td>';
	echo '<td style="text-align:center" colspan="6"><input type="hidden" name="order_items" value="1" />
		<input tabindex="' . ($j + 8) . '" type="submit" value="' . _('Add to Requisition') . '" /></td>';
	echo '<td><input type="hidden" name="NextList" value="' . ($Offset + 1) . '" />
		<input tabindex="' . ($j + 9) . '" type="submit" name="Next" value="' . _('Next') . '" /></td><tr/>';
	echo '</table>
		</form>';

} //end if SearchResults to show

//*********************************************************************************************************
include('includes/footer.inc');
?>