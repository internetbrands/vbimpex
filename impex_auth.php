<?#shebang#?><?php
/*======================================================================*\
|| ######################################################################## ||
|| # vBulletin Impex
|| # ----------------------------------------------------------------
|| # All PHP code in this file is Copyright 2000-2014 vBulletin Solutions Inc.
|| # This code is made available under the Modified BSD License -- see license.txt
|| # http://www.vbulletin.com
|| ######################################################################## ||
\*======================================================================*/
// #############################################################################
// Now we have the config file we can do the auth
// #############################################################################

if (!defined('IDIR')) { die; }

if ($_POST['do'] == 'login')
{
	if ($_POST['customerid'] AND (md5($_POST['customerid']) == CUSTOMER_NUMBER))
	{
		setcookie('bbcustomerid', CUSTOMER_NUMBER, 0, '/', '');

		?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html>
		<head>
		<title>ImpEx</title>
		<meta http-equiv="Content-Type" content="text/html" />
		<meta http-equiv="Refresh" content="1; URL="<?php echo htmlspecialchars($auth_redirect); ?>"/>
		</head>
		<body>
		<p>&nbsp;</p><p>&nbsp;</p>
		<blockquote><blockquote><p>
		<b><?php echo $impex_phrases['successful']; ?></b><br />
		<span><a href="<?php echo htmlspecialchars($auth_redirect); ?>"><?php echo $impex_phrases['redirecting']; ?></a></span>
		</p></blockquote></blockquote>
		</body>
		</html>
		<?php
		exit;
	}
}


// #############################################################################
if ($_COOKIE['bbcustomerid'] != CUSTOMER_NUMBER)
{
	?>
	<html>
	<body>
	<form action="<?php echo htmlspecialchars($auth_redirect); ?>?do=login" method="post">
	<input type="hidden" name="redirect" value="<?php echo htmlspecialchars($auth_redirect); ?>" />
	<input type="hidden" name="do" value="login" />
	<p>&nbsp;</p><p>&nbsp;</p>
	<table class="tborder" cellpadding="0" cellspacing="0" border="0" width="450" align="center"><tr><td>

		<!-- header -->
		<div class="tcat" style="padding:4px; text-align:center"><b><?php echo $impex_phrases['enter_customer_number']; ?></b></div>
		<!-- /header -->

		<table cellpadding="4" cellspacing="0" border="0" width="100%" class="logincontrols">
		<col width="50%" style="text-align:right; white-space:nowrap"></col>
		<col></col>
		<col width="50%"></col>
		<tr valign="top">
			<td>&nbsp;<br />
			<td class="smallfont"><input type="text" style="padding-left:5px; font-weight:bold; width:250px" name="customerid" value="" tabindex="1" /><br /></td>
			<td>&nbsp;</td>
		</tr>
		<!-- /login fields -->
		<!-- submit row -->
		<tr>
			<td colspan="3" align="center">
				<input type="submit" class="button" value="<?php echo $impex_phrases['continue']; ?>" accesskey="s" tabindex="3" />
			</td>
		</tr>
		<!-- /submit row -->
		</table>
	</td></tr></table>
	</form>
	</body>
	</html>
	<?php
	exit;
}

// #############################################################################
// AUTH OVER
// #############################################################################
?>
