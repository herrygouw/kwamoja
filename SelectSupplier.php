<?php

include('includes/session.inc');
$Title = _('Search Suppliers');

/* KwaMoja manual links before header.inc */
$ViewTopic = 'AccountsPayable';
$BookMark = 'SelectSupplier';

include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');
if (!isset($_SESSION['SupplierID'])) {
	echo '<p class="page_title_text noPrint" ><img src="' . $RootPath . '/css/' . $Theme . '/images/supplier.png" title="' . _('Search') . '" alt="" />' . ' ' . _('Suppliers') . '</p>';
}
if (isset($_GET['SupplierID'])) {
	$_SESSION['SupplierID'] = $_GET['SupplierID'];
}
// only get geocode information if integration is on, and supplier has been selected
if ($_SESSION['geocode_integration'] == 1 and isset($_SESSION['SupplierID'])) {
	$sql = "SELECT * FROM geocode_param WHERE 1";
	$ErrMsg = _('An error occurred in retrieving the information');
	$result = DB_query($sql, $db, $ErrMsg);
	$myrow = DB_fetch_array($result);
	$sql = "SELECT suppliers.supplierid,
					suppliers.lat,
					suppliers.lng
				FROM suppliers
				WHERE suppliers.supplierid = '" . $_SESSION['SupplierID'] . "'
				ORDER BY suppliers.supplierid";
	$ErrMsg = _('An error occurred in retrieving the information');
	$result2 = DB_query($sql, $db, $ErrMsg);
	$myrow2 = DB_fetch_array($result2);
	$lat = $myrow2['lat'];
	$lng = $myrow2['lng'];
	$api_key = $myrow['geocode_key'];
	$center_long = $myrow['center_long'];
	$center_lat = $myrow['center_lat'];
	$map_height = $myrow['map_height'];
	$map_width = $myrow['map_width'];
	$map_host = $myrow['map_host'];
	echo '<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=' . $api_key . '"';
	echo ' type="text/javascript"></script>';
	echo ' <script type="text/javascript">';
	echo 'function load() {
		if (GBrowserIsCompatible()) {
			var map = new GMap2(document.getElementById("map"));
			map.addControl(new GSmallMapControl());
			map.addControl(new GMapTypeControl());';
	echo 'map.setCenter(new GLatLng(' . $lat . ', ' . $lng . '), 11);';
	echo 'var marker = new GMarker(new GLatLng(' . $lat . ', ' . $lng . '));';
	echo 'map.addOverlay(marker);
			GEvent.addListener(marker, "click", function() {
			marker.openInfoWindowHtml(WINDOW_HTML);
			});
			marker.openInfoWindowHtml(WINDOW_HTML);
			}
			}
			</script>
			<body onload="load()" onunload="GUnload()" >';
}

if (!isset($_POST['PageOffset'])) {
	$_POST['PageOffset'] = 1;
} else {
	if ($_POST['PageOffset'] == 0) {
		$_POST['PageOffset'] = 1;
	}
}
if (isset($_POST['Select'])) {
	/*User has hit the button selecting a supplier */
	$_SESSION['SupplierID'] = $_POST['Select'];
	unset($_POST['Select']);
	unset($_POST['Keywords']);
	unset($_POST['SupplierCode']);
	unset($_POST['Search']);
	unset($_POST['Go']);
	unset($_POST['Next']);
	unset($_POST['Previous']);
}
if (isset($_POST['Search']) OR isset($_POST['Go']) OR isset($_POST['Next']) OR isset($_POST['Previous'])) {

	if (mb_strlen($_POST['Keywords']) > 0 and mb_strlen($_POST['SupplierCode']) > 0) {
		prnMsg(_('Supplier name keywords have been used in preference to the Supplier code extract entered'), 'info');
	}
	$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';
	$SQL = "SELECT supplierid,
					suppname,
					currcode,
					address1,
					address2,
					address3,
					address4,
					telephone,
					email,
					url
				FROM suppliers
				WHERE suppname " . LIKE . " '" . $SearchString . "'
					AND supplierid " . LIKE . " '%" . $_POST['SupplierCode'] . "%'
				ORDER BY suppname";
	$result = DB_query($SQL, $db);
	if (DB_num_rows($result) == 1) {
		$myrow = DB_fetch_row($result);
		$SingleSupplierReturned = $myrow[0];
	}
	if (isset($SingleSupplierReturned)) {
		/*there was only one supplier returned */
		$_SESSION['SupplierID'] = DB_escape_string($SingleSupplierReturned);
		unset($_POST['Keywords']);
		unset($_POST['SupplierCode']);
		unset($_POST['Search']);
	} else {
		unset($_SESSION['SupplierID']);
	}
}
if (isset($_SESSION['SupplierID'])) {
	$SupplierName = '';
	$SQL = "SELECT suppliers.suppname
			FROM suppliers
			WHERE suppliers.supplierid ='" . $_SESSION['SupplierID'] . "'";
	$SupplierNameResult = DB_query($SQL, $db);
	if (DB_num_rows($SupplierNameResult) == 1) {
		$myrow = DB_fetch_row($SupplierNameResult);
		$SupplierName = $myrow[0];
	}
	echo '<p class="page_title_text noPrint" ><img src="' . $RootPath . '/css/' . $Theme . '/images/supplier.png" title="' . _('Supplier') . '" alt="" />' . ' ' . _('Supplier') . ' : <b>' . stripslashes($_SESSION['SupplierID']) . ' - ' . $SupplierName . '</b> ' . _('has been selected') . '.</p>';
	echo '<div class="page_help_text noPrint">' . _('Select a menu option to operate using this supplier.') . '</div>';
	echo '<br />
		<table width="90%" cellpadding="4">
		<tr>
			<th style="width:33%">' . _('Supplier Inquiries') . '</th>
			<th style="width:33%">' . _('Supplier Transactions') . '</th>
			<th style="width:33%">' . _('Supplier Maintenance') . '</th>
		</tr>';
	echo '<tr><td valign="top" class="select">';
	/* Inquiry Options */
	echo '<a href="' . $RootPath . '/SupplierInquiry.php?SupplierID=' . urlencode(stripslashes($_SESSION['SupplierID'])) . '">' . _('Supplier Account Inquiry') . '</a>';

	echo '<br /><a href="' . $RootPath . '/PO_SelectOSPurchOrder.php?SelectedSupplier=' . urlencode(stripslashes($_SESSION['SupplierID'])) . '">' . _('Add / Receive / View Outstanding Purchase Orders') . '</a>';
	echo '<br /><a href="' . $RootPath . '/PO_SelectPurchOrder.php?SelectedSupplier=' . urlencode(stripslashes($_SESSION['SupplierID'])) . '">' . _('View All Purchase Orders') . '</a>';
	wikiLink('Supplier', urlencode(stripslashes($_SESSION['SupplierID'])));
	echo '<br /><a href="' . $RootPath . '/ShiptsList.php?SupplierID=' . urlencode(stripslashes($_SESSION['SupplierID'])) . '&amp;SupplierName=' . urlencode($SupplierName) . '">' . _('List all open shipments for') . ' ' . $SupplierName . '</a>';
	echo '<br /><a href="' . $RootPath . '/Shipt_Select.php?SelectedSupplier=' . urlencode(stripslashes($_SESSION['SupplierID'])) . '">' . _('Search / Modify / Close Shipments') . '</a>';
	echo '<br /><a href="' . $RootPath . '/SuppPriceList.php?SelectedSupplier=' . urlencode(stripslashes($_SESSION['SupplierID'])) . '">' . _('Supplier Price List') . '</a>';
	echo '</td><td valign="top" class="select">';
	/* Supplier Transactions */
	echo '<a href="' . $RootPath . '/PO_Header.php?NewOrder=Yes&amp;SupplierID=' . urlencode(stripslashes($_SESSION['SupplierID'])) . '">' . _('Enter a Purchase Order for This Supplier') . '</a>';
	echo '<br /><a href="' . $RootPath . '/SupplierInvoice.php?SupplierID=' . urlencode(stripslashes($_SESSION['SupplierID'])) . '">' . _('Enter a Suppliers Invoice') . '</a>';
	echo '<br /><a href="' . $RootPath . '/SupplierCredit.php?New=true&amp;SupplierID=' . urlencode(stripslashes($_SESSION['SupplierID'])) . '">' . _('Enter a Suppliers Credit Note') . '</a>';
	echo '<br /><a href="' . $RootPath . '/Payments.php?SupplierID=' . urlencode(stripslashes($_SESSION['SupplierID'])) . '">' . _('Enter a Payment to, or Receipt from the Supplier') . '</a>';
	echo '<br /><a href="' . $RootPath . '/ReverseGRN.php?SupplierID=' . urlencode(stripslashes($_SESSION['SupplierID'])) . '">' . _('Reverse an Outstanding Goods Received Note (GRN)') . '</a>';
	echo '</td><td valign="top" class="select">';
	/* Supplier Maintenance */
	echo '<a href="' . $RootPath . '/Suppliers.php">' . _('Add a New Supplier') . '</a>
		<br /><a href="' . $RootPath . '/Suppliers.php?SupplierID=' . urlencode(stripslashes($_SESSION['SupplierID'])) . '">' . _('Modify Or Delete Supplier Details') . '</a>
		<br /><a href="' . $RootPath . '/SupplierContacts.php?SupplierID=' . urlencode(stripslashes($_SESSION['SupplierID'])) . '">' . _('Add/Modify/Delete Supplier Contacts') . '</a>
		<br /><a href="' . $RootPath . '/SellThroughSupport.php?SupplierID=' . urlencode(stripslashes($_SESSION['SupplierID'])) . '">' . _('Set Up Sell Through Support Deals') . '</a>
		<br /><a href="' . $RootPath . '/Shipments.php?NewShipment=Yes">' . _('Set Up A New Shipment') . '</a>
		<br /><a href="' . $RootPath . '/SuppLoginSetup.php">' . _('Supplier Login Configuration') . '</a>
		</td>
		</tr>
		</table>';
	// Supplier is not selected yet
	echo '<br />';
	echo '<table width="90%" cellpadding="4">
		<tr>
			<th style="width:33%">' . _('Supplier Inquiries') . '</th>
			<th style="width:33%">' . _('Supplier Transactions') . '</th>
			<th style="width:33%">' . _('Supplier Maintenance') . '</th>
		</tr>';
	echo '<tr>
			<td valign="top" class="select"></td>
			<td valign="top" class="select"></td>
			<td valign="top" class="select">';
	/* Supplier Maintenance */
	echo '<a href="' . $RootPath . '/Suppliers.php">' . _('Add a New Supplier') . '</a><br />';
	echo '</td>
		</tr>
		</table>';
}
echo '<form onSubmit="return VerifyForm(this);" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" class="noPrint">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<p class="page_title_text noPrint" ><img src="' . $RootPath . '/css/' . $Theme . '/images/magnifier.png" title="' . _('Search') . '" alt="" />' . ' ' . _('Search for Suppliers') . '</p>
	<table cellpadding="3" class="selection">
	<tr>
		<td>' . _('Enter a partial Name') . ':</td>
		<td>';
if (isset($_POST['Keywords'])) {
	echo '<input type="text" name="Keywords" autofocus="autofocus" value="' . $_POST['Keywords'] . '" size="20" minlength="0" maxlength="25" />';
} else {
	echo '<input type="text" name="Keywords" autofocus="autofocus" size="20" minlength="0" maxlength="25" />';
}
echo '</td>
		<td><b>' . _('OR') . '</b></td>
		<td>' . _('Enter a partial Code') . ':</td>
		<td>';
if (isset($_POST['SupplierCode'])) {
	echo '<input type="text" name="SupplierCode" value="' . $_POST['SupplierCode'] . '" size="15" minlength="0" maxlength="18" />';
} else {
	echo '<input type="text" name="SupplierCode" size="15" minlength="0" maxlength="18" />';
}
echo '</td></tr>
		</table>
		<div class="centre"><input type="submit" name="Search" value="' . _('Search Now') . '" /></div>';
//if (isset($result) and !isset($SingleSupplierReturned)) {
if (isset($_POST['Search'])) {
	$ListCount = DB_num_rows($result);
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
	if ($ListPageMax > 1) {
		echo '<p>&nbsp;&nbsp;' . $_POST['PageOffset'] . ' ' . _('of') . ' ' . $ListPageMax . ' ' . _('pages') . '. ' . _('Go to Page') . ': </p>';
		echo '<select minlength="0" name="PageOffset">';
		$ListPage = 1;
		while ($ListPage <= $ListPageMax) {
			if ($ListPage == $_POST['PageOffset']) {
				echo '<option value="' . $ListPage . '" selected="selected">' . $ListPage . '</option>';
			} else {
				echo '<option value="' . $ListPage . '">' . $ListPage . '</option>';
			}
			$ListPage++;
		}
		echo '</select>
			<input type="submit" name="Go" value="' . _('Go') . '" />
			<input type="submit" name="Previous" value="' . _('Previous') . '" />
			<input type="submit" name="Next" value="' . _('Next') . '" />';
		echo '<br />';
	}
	echo '<input type="hidden" name="Search" value="' . _('Search Now') . '" />';
	$k = 0; //row counter to determine background colour
	$RowIndex = 0;
	if (DB_num_rows($result) <> 0) {
		DB_data_seek($result, ($_POST['PageOffset'] - 1) * $_SESSION['DisplayRecordsMax']);
		echo '<table cellpadding="2">
				<tr>
					<th class="SortableColumn">' . _('Code') . '</th>
					<th class="SortableColumn">' . _('Supplier Name') . '</th>
					<th>' . _('Currency') . '</th>
					<th>' . _('Address 1') . '</th>
					<th>' . _('Address 2') . '</th>
					<th>' . _('Address 3') . '</th>
					<th>' . _('Address 4') . '</th>
					<th>' . _('Telephone') . '</th>
					<th>' . _('Email') . '</th>
					<th>' . _('URL') . '</th>
				</tr>';
		while (($myrow = DB_fetch_array($result)) and ($RowIndex <> $_SESSION['DisplayRecordsMax'])) {
			if ($k == 1) {
				echo '<tr class="EvenTableRows">';
				$k = 0;
			} else {
				echo '<tr class="OddTableRows">';
				$k = 1;
			}
			echo '<td><input type="submit" name="Select" value="' . $myrow['supplierid'] . '" /></td>
					<td>' . $myrow['suppname'] . '</td>
					<td>' . $myrow['currcode'] . '</td>
					<td>' . $myrow['address1'] . '</td>
					<td>' . $myrow['address2'] . '</td>
					<td>' . $myrow['address3'] . '</td>
					<td>' . $myrow['address4'] . '</td>
					<td>' . $myrow['telephone'] . '</td>
					<td><a href="mailto://' . $myrow['email'] . '">' . $myrow['email'] . '</a></td>
					<td><a href="'.$myrow['url'].'"target="_blank">' . $myrow['url']. '</a></td>
				</tr>';
			$RowIndex = $RowIndex + 1;
			//end of page full new headings if
		}
		//end of while loop
		echo '</table>';
	} else {
		prnMsg( _('There are no suppliers returned for this criteria. Please enter new criteria'), 'info');
	}
}
//end if results to show
if (isset($ListPageMax) and $ListPageMax > 1) {
	echo '<p>&nbsp;&nbsp;' . $_POST['PageOffset'] . ' ' . _('of') . ' ' . $ListPageMax . ' ' . _('pages') . '. ' . _('Go to Page') . ': </p>';
	echo '<select minlength="0" name="PageOffset">';
	$ListPage = 1;
	while ($ListPage <= $ListPageMax) {
		if ($ListPage == $_POST['PageOffset']) {
			echo '<option value="' . $ListPage . '" selected="selected">' . $ListPage . '</option>';
		} else {
			echo '<option value="' . $ListPage . '">' . $ListPage . '</option>';
		}
		$ListPage++;
	}
	echo '</select>
		<input type="submit" name="Go" value="' . _('Go') . '" />
		<input type="submit" name="Previous" value="' . _('Previous') . '" />
		<input type="submit" name="Next" value="' . _('Next') . '" />';
	echo '<br />';
}
echo '</form>';
// Only display the geocode map if the integration is turned on, and there is a latitude/longitude to display
if (isset($_SESSION['SupplierID']) and $_SESSION['SupplierID'] != '') {
	if ($_SESSION['geocode_integration'] == 1) {
		if ($lat == 0) {
			echo '<br />';
			echo '<div class="centre">' . _('Mapping is enabled, but no Mapping data to display for this Supplier.') . '</div>';
		} else {
			echo '<div class="centre"><br />';
			echo '<tr>
					<td colspan="2">';
			echo '<table width="45%" class="selection">
					<tr>
						<th style="width:33%">' . _('Supplier Mapping') . '</th>
					</tr>';
			echo '</td>
					<td valign="top">';
			/* Mapping */
			echo '<div class="centre">' . _('Mapping is enabled, Map will display below.') . '</div>';
			echo '<div class="centre" id="map" style="width: ' . $map_width . 'px; height: ' . $map_height . 'px"></div></div><br />';
			echo '</td></tr></table>';
		}
	}
	// Extended Info only if selected in Configuration
	if ($_SESSION['Extended_SupplierInfo'] == 1) {
		if ($_SESSION['SupplierID'] != '') {
			$sql = "SELECT suppliers.suppname,
							suppliers.lastpaid,
							suppliers.lastpaiddate,
							suppliersince,
							currencies.decimalplaces AS currdecimalplaces
					FROM suppliers INNER JOIN currencies
					ON suppliers.currcode=currencies.currabrev
					WHERE suppliers.supplierid ='" . $_SESSION['SupplierID'] . "'";
			$ErrMsg = _('An error occurred in retrieving the information');
			$DataResult = DB_query($sql, $db, $ErrMsg);
			$myrow = DB_fetch_array($DataResult);
			// Select some more data about the supplier
			$SQL = "SELECT SUM(-ovamount) AS total FROM supptrans WHERE supplierno = '" . $_SESSION['SupplierID'] . "' and type != '20'";
			$Total1Result = DB_query($SQL, $db);
			$row = DB_fetch_array($Total1Result);
			echo '<br />';
			echo '<table width="45%" cellpadding="4">
					<tr>
						<th style="width:33%" colspan="2">' . _('Supplier Data') . '</th>
					</tr>
					<tr>
						<td valign="top" class="select">';
			/* Supplier Data */
			//echo "Distance to this Supplier: <b>TBA</b><br />";
			if ($myrow['lastpaiddate'] == 0) {
				echo _('No payments yet to this supplier.') . '</td>
					<td valign="top" class="select"></td>
					</tr>';
			} else {
				echo _('Last Paid:') . '</td>
					<td valign="top" class="select"> <b>' . ConvertSQLDate($myrow['lastpaiddate']) . '</b></td>
					</tr>';
			}
			echo '<tr>
					<td valign="top" class="select">' . _('Last Paid Amount:') . '</td>
					<td valign="top" class="select">  <b>' . locale_number_format($myrow['lastpaid'], $myrow['currdecimalplaces']) . '</b></td></tr>';
			echo '<tr>
					<td valign="top" class="select">' . _('Supplier since:') . '</td>
					<td valign="top" class="select"> <b>' . ConvertSQLDate($myrow['suppliersince']) . '</b></td>
					</tr>';
			echo '<tr>
					<td valign="top" class="select">' . _('Total Spend with this Supplier:') . '</td>
					<td valign="top" class="select"> <b>' . locale_number_format($row['total'], $myrow['currdecimalplaces']) . '</b></td>
					</tr>';
			echo '</table>';
		}
	}
}

include('includes/footer.inc');
?>