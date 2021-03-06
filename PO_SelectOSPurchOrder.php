<?php

$PricesSecurity = 1000;

include('includes/session.inc');

$Title = _('Search Outstanding Purchase Orders');

include('includes/header.inc');
include('includes/DefinePOClass.php');

if (isset($_GET['SelectedStockItem'])) {
	$SelectedStockItem = trim($_GET['SelectedStockItem']);
} //isset($_GET['SelectedStockItem'])
elseif (isset($_POST['SelectedStockItem'])) {
	$SelectedStockItem = trim($_POST['SelectedStockItem']);
} //isset($_POST['SelectedStockItem'])
if (isset($_GET['OrderNumber'])) {
	$OrderNumber = $_GET['OrderNumber'];
} //isset($_GET['OrderNumber'])
elseif (isset($_POST['OrderNumber'])) {
	$OrderNumber = $_POST['OrderNumber'];
} //isset($_POST['OrderNumber'])

if (isset($_GET['SelectedSupplier'])) {
	$SelectedSupplier = trim(stripslashes($_GET['SelectedSupplier']));
} //isset($_GET['SelectedSupplier'])
elseif (isset($_POST['SelectedSupplier'])) {
	$SelectedSupplier = trim(stripslashes($_POST['SelectedSupplier']));
} //isset($_POST['SelectedSupplier'])

echo '<form onSubmit="return VerifyForm(this);" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" class="noPrint">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

if (isset($_POST['ResetPart'])) {
	unset($SelectedStockItem);
} //isset($_POST['ResetPart'])

if (isset($OrderNumber) and $OrderNumber != '') {
	if (!is_numeric($OrderNumber)) {
		prnMsg(_('The Order Number entered') . ' <u>' . _('MUST') . '</u> ' . _('be numeric'), 'error');
		unset($OrderNumber);
	}
}

if (isset($_POST['SearchParts'])) {
	//insert wildcard characters in spaces
	$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';

	$SQL = "SELECT stockmaster.stockid,
					stockmaster.description,
					SUM(locstock.quantity) AS qoh,
					stockmaster.units,
					SUM(purchorderdetails.quantityord-purchorderdetails.quantityrecd) AS qord
				FROM stockmaster
				INNER JOIN locstock
					ON stockmaster.stockid = locstock.stockid
				INNER JOIN purchorderdetails
					ON stockmaster.stockid=purchorderdetails.itemcode
				WHERE purchorderdetails.completed=0
					AND stockmaster.description " . LIKE . " '" . $SearchString . "'
					AND stockmaster.stockid " . LIKE . " '%" . $_POST['StockCode'] . "%'
					AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
				GROUP BY stockmaster.stockid,
						stockmaster.description,
						stockmaster.units
				ORDER BY stockmaster.stockid";
	$ErrMsg = _('No stock items were returned by the SQL because');
	$DbgMsg = _('The SQL used to retrieve the searched parts was');
	$StockItemsResult = DB_query($SQL, $db, $ErrMsg, $DbgMsg);
} //isset($_POST['SearchParts'])


/* Not appropriate really to restrict search by date since user may miss older ouststanding orders
$OrdersAfterDate = Date("d/m/Y",Mktime(0,0,0,Date("m")-2,Date("d"),Date("Y")));
*/

if (!isset($OrderNumber) or $OrderNumber == '') {
	if (isset($SelectedSupplier)) {
		echo '<div class="toplink"><a href="' . $RootPath . '/PO_Header.php?NewOrder=Yes&amp;SupplierID=' . $SelectedSupplier . '">' . _('Add Purchase Order') . '</a></div>';
	} else {
		echo '<div class="toplink"><a href="' . $RootPath . '/PO_Header.php?NewOrder=Yes">' . _('Add Purchase Order') . '</a></div>';
	}

	echo '<p class="page_title_text noPrint" ><img src="' . $RootPath . '/css/' . $Theme . '/images/magnifier.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title;
	if (isset($SelectedSupplier)) {
		echo ' ' . _('for Supplier') . ': ' . $SelectedSupplier;
		echo '<input type="hidden" name="SelectedSupplier" value="' . $SelectedSupplier . '" />';
	} //isset($SelectedSupplier)
	if (isset($SelectedStockItem)) {
		if (isset($SelectedSupplier)) {
			echo ' ' . _('and') . ' ';
		}
		echo ' ' . _('for stock item') . ': ' . $SelectedStockItem;
		echo '<input type="hidden" name="SelectedStockItem" value="' . $SelectedStockItem . '" />';
	} //isset($SelectedStockItem)
	echo '</p>';

	echo '<table class="selection">
			<tr>
				<td>' . _('Order Number') . ': <input type="text" name="OrderNumber" autofocus="autofocus" minlength="0" maxlength="8" size="9" value="" />  ' . _('Into Stock Location') . ':
				<select minlength="0" name="StockLocation">';

	if (!isset($_POST['DateFrom'])) {
		$DateSQL = "SELECT min(orddate) as fromdate,
							max(orddate) as todate
						FROM purchorders";
		$DateResult = DB_query($DateSQL, $db);
		$DateRow = DB_fetch_array($DateResult);
		$DateFrom = $DateRow['fromdate'];
		$DateTo = $DateRow['todate'];
	} else {
		$DateFrom = FormatDateForSQL($_POST['DateFrom']);
		$DateTo = FormatDateForSQL($_POST['DateTo']);
	}

	if ($_SESSION['RestrictLocations'] == 0) {
		$sql = "SELECT locationname,
						loccode
					FROM locations";
	} else {
		$sql = "SELECT locationname,
						loccode
					FROM locations
					INNER JOIN www_users
						ON locations.loccode=www_users.defaultlocation
					WHERE www_users.userid='" . $_SESSION['UserID'] . "'";
	}
	$resultStkLocs = DB_query($sql, $db);
	while ($myrow = DB_fetch_array($resultStkLocs)) {
		if (isset($_POST['StockLocation'])) {
			if ($myrow['loccode'] == $_POST['StockLocation']) {
				echo '<option selected="selected" value="' . $myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
			} //$myrow['loccode'] == $_POST['StockLocation']
			else {
				echo '<option value="' . $myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
			}
		} //isset($_POST['StockLocation'])
		elseif ($myrow['loccode'] == $_SESSION['UserStockLocation']) {
			echo '<option selected="selected" value="' . $myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
		} //$myrow['loccode'] == $_SESSION['UserStockLocation']
		else {
			echo '<option value="' . $myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
		}
	} //$myrow = DB_fetch_array($resultStkLocs)
	echo '</select> ' . _('Order Status:') . ' <select minlength="0" name="Status">';
	if (!isset($_POST['Status']) or $_POST['Status'] == 'Pending_Authorised') {
		echo '<option selected="selected" value="Pending_Authorised">' . _('Pending and Authorised') . '</option>';
	} //!isset($_POST['Status']) or $_POST['Status'] == 'Pending_Authorised'
	else {
		echo '<option value="Pending_Authorised">' . _('Pending and Authorised') . '</option>';
	}
	if (isset($_POST['Status']) and $_POST['Status'] == 'Pending') {
		echo '<option selected="selected" value="Pending">' . _('Pending') . '</option>';
	} //$_POST['Status'] == 'Pending'
	else {
		echo '<option value="Pending">' . _('Pending') . '</option>';
	}
	if (isset($_POST['Status']) and $_POST['Status'] == 'Authorised') {
		echo '<option selected="selected" value="Authorised">' . _('Authorised') . '</option>';
	} //$_POST['Status'] == 'Authorised'
	else {
		echo '<option value="Authorised">' . _('Authorised') . '</option>';
	}
	if (isset($_POST['Status']) and $_POST['Status'] == 'Cancelled') {
		echo '<option selected="selected" value="Cancelled">' . _('Cancelled') . '</option>';
	} //$_POST['Status'] == 'Cancelled'
	else {
		echo '<option value="Cancelled">' . _('Cancelled') . '</option>';
	}
	if (isset($_POST['Status']) and $_POST['Status'] == 'Rejected') {
		echo '<option selected="selected" value="Rejected">' . _('Rejected') . '</option>';
	} //$_POST['Status'] == 'Rejected'
	else {
		echo '<option value="Rejected">' . _('Rejected') . '</option>';
	}
	echo '</select>
		' . _('Orders Between') . ':&nbsp;<input type="text" name="DateFrom" value="' . ConvertSQLDate($DateFrom) . '"  class="date" size="10" alt="' . $_SESSION['DefaultDateFormat'] . '"  />
		' . _('and') . ':&nbsp;<input type="text" name="DateTo" value="' . ConvertSQLDate($DateTo) . '"  class="date" size="10" alt="' . $_SESSION['DefaultDateFormat'] . '"  />
		<input type="submit" name="SearchOrders" value="' . _('Search Purchase Orders') . '" />
		</td>
		</tr>
		</table>';
} //!isset($OrderNumber) or $OrderNumber == ''

$SQL = "SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription";
$result1 = DB_query($SQL, $db);

echo '<br /><div class="page_help_text noPrint">' . _('To search for purchase orders for a specific part use the part selection facilities below') . '</div>';

if (!isset($_POST['StockCode'])) {
	$_POST['StockCode'] = '';
}

if (!isset($_POST['Keywords'])) {
	$_POST['Keywords'] = '';
}

echo '<table class="selection">
			<tr>
				<td>' . _('Select a stock category') . ':
					<select minlength="0" name="StockCat">';

while ($myrow1 = DB_fetch_array($result1)) {
	if (isset($_POST['StockCat']) and $myrow1['categoryid'] == $_POST['StockCat']) {
		echo '<option selected="selected" value="' . $myrow1['categoryid'] . '">' . $myrow1['categorydescription'] . '</option>';
	} //isset($_POST['StockCat']) and $myrow1['categoryid'] == $_POST['StockCat']
	else {
		echo '<option value="' . $myrow1['categoryid'] . '">' . $myrow1['categorydescription'] . '</option>';
	}
} //end loop through categories
echo '</select></td>
		<td>' . _('Enter text extracts in the') . ' ' . '<b>' . _('description') . '</b>:</td>
		<td><input type="text" name="Keywords" size="20" minlength="0" maxlength="25" value="' . $_POST['Keywords'] . '" /></td>
	</tr>
	<tr>
		<td></td>
		<td><b>' . _('OR') . '</b>' . ' ' . _('Enter extract of the') . ' ' . '<b>' . _('Stock Code') . '</b>:</td>
		<td><input type="text" name="StockCode" size="15" minlength="0" maxlength="18" value="' . $_POST['StockCode'] . '" /></td>
	</tr>
	</table>
	<table>
		<tr>
			<td><input type="submit" name="SearchParts" value="' . _('Search Parts Now') . '" />
				<input type="submit" name="ResetPart" value="' . _('Show All') . '" /></td>
		</tr>
	</table>';

if (isset($StockItemsResult)) {
	echo '<table cellpadding="2" class="selection">
			<tr>
				<th class="SortableColumn">' . _('Code') . '</th>
				<th class="SortableColumn">' . _('Description') . '</th>
				<th>' . _('On Hand') . '</th>
				<th>' . _('Orders') . '<br />' . _('Outstanding') . '</th>
				<th>' . _('Units') . '</th>
			</tr>';
	$k = 0; //row colour counter

	while ($myrow = DB_fetch_array($StockItemsResult)) {
		if ($k == 1) {
			echo '<tr class="EvenTableRows">';
			$k = 0;
		} //$k == 1
		else {
			echo '<tr class="OddTableRows">';
			$k = 1;
		}

		printf('<td><input type="submit" name="SelectedStockItem" value="%s"</td>
				<td>%s</td>
				<td class="number">%s</td>
				<td class="number">%s</td>
				<td>%s</td></tr>', $myrow['stockid'], $myrow['description'], $myrow['qoh'], $myrow['qord'], $myrow['units']);

	} //end of while loop through search items

	echo '</table>';

} else if (isset($_POST['SearchOrders'])) {
	//figure out the SQL required from the inputs available

	if (!isset($_POST['Status']) or $_POST['Status'] == 'Pending_Authorised') {
		$StatusCriteria = " AND (purchorders.status='Pending' OR purchorders.status='Authorised' OR purchorders.status='Printed') ";
	} elseif ($_POST['Status'] == 'Authorised') {
		$StatusCriteria = " AND (purchorders.status='Authorised' OR purchorders.status='Printed')";
	} elseif ($_POST['Status'] == 'Pending') {
		$StatusCriteria = " AND purchorders.status='Pending' ";
	} elseif ($_POST['Status'] == 'Rejected') {
		$StatusCriteria = " AND purchorders.status='Rejected' ";
	} elseif ($_POST['Status'] == 'Cancelled') {
		$StatusCriteria = " AND purchorders.status='Cancelled' ";
	} //$_POST['Status'] == 'Cancelled'

	//If searching on supplier code
	if (isset($SelectedSupplier) and $SelectedSupplier != '') {
		$SupplierSearchString = " AND purchorders.supplierno='" . DB_escape_string($SelectedSupplier) . "' ";
	} else {
		$SupplierSearchString = '';
	}
	//If searching on order number
	if (isset($OrderNumber) and $OrderNumber != '') {
		$OrderNumberSearchString = " AND purchorders.orderno='" . $OrderNumber . "' ";
	} else {
		$OrderNumberSearchString = '';
	}
	//If searching on order number
	if (isset($SelectedStockItem) and $SelectedStockItem != '') {
		$StockItemSearchString = " AND purchorderdetails.itemcode='" . $SelectedStockItem . "' ";
	} else {
		$StockItemSearchString = '';
	}
	if (isset($_POST['StockLocation'])) {
		$LocationSearchString = " AND purchorders.intostocklocation = '" . $_POST['StockLocation'] . "' ";
	} else {
		$LocationSearchString = '';
	}

	$SQL = "SELECT purchorders.orderno,
					purchorders.realorderno,
					suppliers.suppname,
					purchorders.orddate,
					purchorders.deliverydate,
					purchorders.initiator,
					purchorders.status,
					purchorders.requisitionno,
					purchorders.allowprint,
					suppliers.currcode,
					currencies.decimalplaces AS currdecimalplaces,
					SUM(purchorderdetails.unitprice*purchorderdetails.quantityord) AS ordervalue
				FROM purchorders
				INNER JOIN purchorderdetails
					ON purchorders.orderno=purchorderdetails.orderno
				INNER JOIN suppliers
					ON purchorders.supplierno = suppliers.supplierid
				INNER JOIN currencies
					ON suppliers.currcode=currencies.currabrev
				WHERE purchorderdetails.completed=0
					" . $SupplierSearchString . "
					" . $StockItemSearchString . "
					" . $OrderNumberSearchString . "
					" . $StatusCriteria . "
					" . $LocationSearchString . "
					AND orddate>='" . FormatDateForSQL($_POST['DateFrom']) . "'
					AND orddate<='" . FormatDateForSQL($_POST['DateTo']) . "'
				GROUP BY purchorders.orderno ASC,
						suppliers.suppname,
						purchorders.orddate,
						purchorders.status,
						purchorders.initiator,
						purchorders.requisitionno,
						purchorders.allowprint,
						suppliers.currcode";
	$ErrMsg = _('No orders were returned by the SQL because');
	$PurchOrdersResult = DB_query($SQL, $db, $ErrMsg);

	/*show a table of the orders returned by the SQL */

	echo '<table cellpadding="2" width="97%" class="selection">
			<tr>
				<th class="SortableColumn">' . _('Order #') . '</th>
				<th class="SortableColumn">' . _('Order Date') . '</th>
				<th class="SortableColumn">' . _('Delivery Date') . '</th>
				<th class="SortableColumn">' . _('Initiated by') . '</th>
				<th class="SortableColumn">' . _('Supplier') . '</th>
				<th>' . _('Currency') . '</th>';

	if (in_array($PricesSecurity, $_SESSION['AllowedPageSecurityTokens']) or !isset($PricesSecurity)) {
		echo '<th>' . _('Order Total') . '</th>';
	} //in_array($PricesSecurity, $_SESSION['AllowedPageSecurityTokens']) or !isset($PricesSecurity)
	echo '<th class="SortableColumn">' . _('Status') . '</th>
			<th class="SortableColumn">' . _('Print') . '</th>
			<th>' . _('Receive') . '</th>
		</tr>';

	$k = 0; //row colour counter
	while ($myrow = DB_fetch_array($PurchOrdersResult)) {
		if ($k == 1) {
			/*alternate bgcolour of row for highlighting */
			echo '<tr class="EvenTableRows">';
			$k = 0;
		} else {
			echo '<tr class="OddTableRows">';
			$k++;
		}

		$ModifyPage = $RootPath . '/PO_Header.php?ModifyOrderNumber=' . $myrow['orderno'];
		if ($myrow['status'] == 'Printed') {
			$ReceiveOrder = '<a href="' . $RootPath . '/GoodsReceived.php?PONumber=' . $myrow['orderno'] . '">' . _('Receive') . '</a>';
		} else {
			$ReceiveOrder = '';
		}
		if ($myrow['status'] == 'Authorised' and $myrow['allowprint'] == 1) {
			$PrintPurchOrder = '<a target="_blank" href="' . $RootPath . '/PO_PDFPurchOrder.php?OrderNo=' . $myrow['orderno'] . '">' . _('Print') . '</a>';
		} elseif ($myrow['status'] == 'Authorisied' and $myrow['allowprint'] == 0) {
			$PrintPurchOrder = _('Printed');
		} elseif ($myrow['status'] == 'Printed') {
			$PrintPurchOrder = '<a target="_blank" href="' . $RootPath . '/PO_PDFPurchOrder.php?OrderNo=' . $myrow['orderno'] . '&amp;realorderno=' . $myrow['realorderno'] . '&amp;ViewingOnly=2">
				' . _('Print Copy') . '</a>';
		} else {
			$PrintPurchOrder = _('N/A');
		}

		$FormatedOrderDate = ConvertSQLDate($myrow['orddate']);
		$FormatedDeliveryDate = ConvertSQLDate($myrow['deliverydate']);
		$FormatedOrderValue = locale_number_format($myrow['ordervalue'], $myrow['currdecimalplaces']);
		$sql = "SELECT realname FROM www_users WHERE userid='" . $myrow['initiator'] . "'";
		$UserResult = DB_query($sql, $db);
		$MyUserRow = DB_fetch_array($UserResult);
		$InitiatorName = $MyUserRow['realname'];

		echo '<td><a href="' . $ModifyPage . '">' . $myrow['orderno'] . '</a></td>
			<td>' . $FormatedOrderDate . '</td>
			<td>' . $FormatedDeliveryDate . '</td>
			<td>' . $InitiatorName . '</td>
			<td>' . $myrow['suppname'] . '</td>
			<td>' . $myrow['currcode'] . '</td>';
		if (in_array($PricesSecurity, $_SESSION['AllowedPageSecurityTokens']) or !isset($PricesSecurity)) {
			echo '<td class="number">' . $FormatedOrderValue . '</td>';
		} //in_array($PricesSecurity, $_SESSION['AllowedPageSecurityTokens']) or !isset($PricesSecurity)
		echo '<td>' . _($myrow['status']) . '</td>
					<td>' . $PrintPurchOrder . '</td>
					<td>' . $ReceiveOrder . '</td>
				</tr>';
		//end of page full new headings if
	} //end of while loop around purchase orders retrieved

	echo '</table>';
}

echo '</form>';
include('includes/footer.inc');
?>