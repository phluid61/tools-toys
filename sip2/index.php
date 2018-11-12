<!DOCTYPE html>
<html lang="en">
 <head>
  <title>SIP2 Message Decoder</title>
  <link rel="stylesheet" href="//s.library.qut.edu.au/css/minimal-blue-20180105.css">
  <link rel="icon" href="//s.library.qut.edu.au/favicon-20170112.ico">
  <style type="text/css">

.message{margin-left:2em;margin-top:1em}
.message::before{content:'* ';display:inline-block;margin-left:-2em;font-family:monospace;white-space:pre}

.field{margin-left:2em}
.field::before{content:'+ ';display:inline-block;margin-left:-2em;font-family:monospace;white-space:pre}

.warning{color:#990}
.error{color:#c30}
code{background-color:#d8d8d8;white-space:pre}
textarea[name="sip2"]{width:100%;height:15em;white-space:pre}

</style>
 </head>
 <body>
  <div id="masthead" role="navigation">
   <div id="qut">
    <a href="https://www.qut.edu.au/"><img src="//s.library.qut.edu.au/img/qut-20150421" srcset="//s.library.qut.edu.au/img/qut-s-20150806 32w, //s.library.qut.edu.au/img/qut-20150421 52w, //s.library.qut.edu.au/img/qut-2x-20150421 104w" sizes="52px" alt="QUT home"></a><img src="//s.library.qut.edu.au/img/white-bar-20150421" srcset="//s.library.qut.edu.au/img/white-bar-s-20150806 26w, //s.library.qut.edu.au/img/white-bar-20150421 42w, //s.library.qut.edu.au/img/white-bar-2x-20150421 84w" sizes="42px" alt=""><a href="/"><img src="//s.library.qut.edu.au/img/eisas-20180105" srcset="//s.library.qut.edu.au/img/eisas-s-20180105 62w, //s.library.qut.edu.au/img/eisas-20180105 100w, //s.library.qut.edu.au/img/eisas-2x-20180105 200w" sizes="100px" alt="QUT Library home page"></a>
   </div>
   <div id="strap">
     <img src="//s.library.qut.edu.au/img/strapline-20130415" srcset="//s.library.qut.edu.au/img/strapline-20130415 187w, //s.library.qut.edu.au/img/strapline-2x-20141024 374w" sizes="187px" alt="A university for the real world">
   </div>
  </div>
  <h1>SIP2 Decoder</h1>
  <section>
  <form method="POST" action="./">
  <textarea name="sip2"><?php
if (isset($_POST['sip2']) && ($src = $_POST['sip2'])) {
	echo htmlspecialchars($src);
} else {
	echo '23&#x20;&#x20;&#x20;20180208&#x20;&#x20;&#x20;&#x20;162000AOqut|AAmatty|ACsecret|ADsafe|&#xd;11NY20180208&#x20;&#x20;&#x20;&#x20;16200020190208&#x20;&#x20;&#x20;&#x20;162000AOqut|AAmatty|AB123|ACsecret|BIN&#xd;';
}
?></textarea>
   <br>
   <input type="submit">
  </form>
<?php

$TZ_TABLE = array(
	'A' => '+01:00',
	'B' => '+02:00',
	'C' => '+03:00',
	'D' => '+04:00',
	'E' => '+05:00',
	'F' => '+06:00',
	'G' => '+07:00',
	'H' => '+08:00',
	'I' => '+09:00',
	'K' => '+10:00',
	'L' => '+11:00',
	'M' => '+12:00',
	'N' => '-01:00',
	'O' => '-02:00',
	'P' => '-03:00',
	'Q' => '-04:00',
	'R' => '-05:00',
	'S' => '-06:00',
	'T' => '-07:00',
	'U' => '-08:00',
	'V' => '-09:00',
	'W' => '-10:00',
	'X' => '-11:00',
	'Y' => '-12:00',
	'Z' => '+00:00',
);

function parse_date($src) {
	global $TZ_TABLE;
	if (preg_match('/^(?<y>\d{4})(?<m>\d{2})(?<d>\d{2})(?<z>.{4})(?<h>\d{2})(?<i>\d{2})(?<s>\d{2})$/', $src, $m)) {
		$tz = null;
		if ($m['z'] == '    ') {
			$tz = ' (local time)';
		} elseif (preg_match('/[A-Z]$/', $m['z'], $n)) {
			$tz = $TZ_TABLE[$n[0]];
		} else {
			$tz = '?['.$tz.']?';
		}

		// FIXME: invalid day/month values?
		return sprintf('%04d-%02d-%02dT%02d:%02d:%02d%s',
			intval($m['y']), intval($m['m']), intval($m['d']),
			intval($m['h']), intval($m['i']), intval($m['s']),
			$tz
		);
	} else {
		return null;
	}
}

$FEE_TYPE_TABLE = array(
	'01' => 'other/unknown',
	'02' => 'administrative',
	'03' => 'damage',
	'04' => 'overdue',
	'05' => 'processing',
	'06' => 'rental',
	'07' => 'replacement',
	'08' => 'computer access charge',
	'09' => 'hold fee',
);

function parse_fee_type($src) {
	global $FEE_TYPE_TABLE;
	if (isset($FEE_TYPE_TABLE[$src])) {
		return "[$src] ".$FEE_TYPE_TABLE[$src];
	} else {
		return '?['.$src.']?';
	}
}

$CIRC_STATUS_TABLE = array(
	'01' => 'other',
	'02' => 'on order',
	'03' => 'available',
	'04' => 'charged',
	'05' => 'charged; not to be recalled until earliest recall date',
	'06' => 'in process',
	'07' => 'recalled',
	'08' => 'waiting on hold shelf',
	'09' => 'waiting to be re-shelved',
	'10' => 'in transit between library locations',
	'11' => 'claimed returned',
	'12' => 'lost',
	'13' => 'missing',
);

function parse_circ_status($src) {
	global $CIRC_STATUS_TABLE;
	if (isset($CIRC_STATUS_TABLE[$src])) {
		return "[$src] ".$CIRC_STATUS_TABLE[$src];
	} else {
		return '?['.$src.']?';
	}
}

function parse_hold_mode($src) {
	switch ($src) {
	case '+': return '[+] add patron to the hold queue for the item';
	case '-': return '[-] delete patron from the hold queue for the item';
	case '*': return '[*] change the hold to match the message parameters';
	default:  return "?[$src]?";
	}
}

$HOLD_TYPE_TABLE = array(
	'01' => 'other',
	'02' => 'any copy of a title',
	'03' => 'a specific copy of a title',
	'04' => 'any copy at a single branch or sublocation',
);

function parse_hold_type($src) {
	global $HOLD_TYPE_TABLE;
	if (isset($HOLD_TYPE_TABLE[$src])) {
		return "[$src] ".$HOLD_TYPE_TABLE[$src];
	} else {
		return '?['.$src.']?';
	}
}

$LANGUAGE_TABLE = array(
	'000' => 'Unknown (default)',
	'001' => 'English',
	'002' => 'French',
	'003' => 'German',
	'004' => 'Italian',
	'005' => 'Dutch',
	'006' => 'Swedish',
	'007' => 'Finnish',
	'008' => 'Spanish',
	'009' => 'Danish',
	'010' => 'Portuguese',
	'011' => 'Canadian-French',
	'012' => 'Norwegian',
	'013' => 'Hebrew',
	'014' => 'Japanese',
	'015' => 'Russian',
	'016' => 'Arabic',
	'017' => 'Polish',
	'018' => 'Greek',
	'019' => 'Chinese',
	'020' => 'Korean',
	'021' => 'North American Spanish',
	'022' => 'Tamil',
	'023' => 'Malay',
	'024' => 'United Kingdom', // LOL
	'025' => 'Icelandic',
	'026' => 'Belgian',
	'027' => 'Taiwanese',
);

function parse_lang($src) {
	global $LANGUAGE_TABLE;
	if (isset($LANGUAGE_TABLE[$src])) {
		return "[$src] ".$LANGUAGE_TABLE[$src];
	} else {
		return '?['.$src.']?';
	}
}

$MEDIA_TYPE_TABLE = array(
	'000' => 'other',
	'001' => 'book',
	'002' => 'magazine',
	'003' => 'bound journal',
	'004' => 'audio tape',
	'005' => 'video tape',
	'006' => 'CD/CDROM',
	'007' => 'diskette',
	'008' => 'book with diskette',
	'009' => 'book with CD',
	'010' => 'book with autio tape',
);

function parse_media_type($src) {
	global $MEDIA_TYPE_TABLE;
	if (isset($MEDIA_TYPE_TABLE[$src])) {
		return "[$src] ".$MEDIA_TYPE_TABLE[$src];
	} else {
		return '?['.$src.']?';
	}
}

$PATRON_STATUS_FIELDS = array(
	'charge privileges denied',
	'renewal priviliges denied',
	'recall privileges denied',
	'hold privileges denied',
	'card reported lost',
	'too many items charged',
	'too many items overdue',
	'too many renewals',
	'too many claims of items returned',
	'too many items lost',
	'excessive outstanding fines',
	'excessive outstanding fees',
	'recall overdue',
	'too many items billed',
);

function parse_patron_status($src) {
	global $PATRON_STATUS_FIELDS;
	$flags = array();
	foreach (str_split($src) as $i=>$v) {
		if ($v == 'Y') {
			$flags[] = $PATRON_STATUS_FIELDS[$i];
		}
	}
	if ($flags) {
		return implode(', ', $flags);
	} else {
		return '-';
	}
}

$PAYMENT_TYPE_TABLE = array(
	'00' => 'cash',
	'01' => 'VISA',
	'02' => 'credit card',
);

function parse_payment_type($src) {
	global $PAYMENT_TYPE_TABLE;
	if (isset($PAYMENT_TYPE_TABLE[$src])) {
		return "[$src] ".$PAYMENT_TYPE_TABLE[$src];
	} else {
		return '?['.$src.']?';
	}
}

$SECURITY_MARKER_TABLE = array(
	'00' => 'other',
	'01' => 'None',
	'02' => '3M Tattle-Tape Security Strip',
	'03' => '3M Whisper Tape',
);

function parse_security_marker($src) {
	global $SECURITY_MARKER_TABLE;
	if (isset($SECURITY_MARKER_TABLE[$src])) {
		return "[$src] ".$SECURITY_MARKER_TABLE[$src];
	} else {
		return '?['.$src.']?';
	}
}

function parse_status_code($src) {
	switch ($src) {
	case '0': return "[$src] SC unit is OK";
	case '1': return "[$src] SC printer is out of paper";
	case '2': return "[$src] SC is about to shut down";
	default:  return "?[$src]?";
	}
}

$SUMMARY_FIELDS = array(
	'hold items',
	'overdue items',
	'charged items',
	'fine items',
	'recall items',
	'unavailable holds',
);

function parse_summary($src) {
	global $SUMMARY_FIELDS;
	$flags = array();
	foreach (str_split($src) as $i=>$v) {
		if ($v == 'Y') {
			if (isset($SUMMARY_FIELDS[$i])) {
				$flags[] = $SUMMARY_FIELDS[$i];
			} else {
				$flags[] = "?[$i]?";
			}
		}
	}
	if ($flags) {
		return implode(', ', $flags);
	} else {
		return '-';
	}
}

$SUPPORTED_MESSAGES_FIELDS = array(
	'Patron Status Request',
	'Checkout',
	'Checkin',
	'Block Patron',
	'SC/ACS Status',
	'Request SC/ACS Resend',
	'Login',
	'Patron Information',
	'End Patron Session',
	'Fee Paid',
	'Item Information',
	'Item Status Update',
	'Patron Enabled',
	'Hold',
	'Renew',
	'Renew All',
);

function parse_supported_messages($src) {
	global $SUPPORTED_MESSAGES_FIELDS;
	// NOTE: if $src is shorter than $SUPPORTED_MESSAGE_FIELDS, assume 'N'
	$flags = array();
	foreach (str_split($src) as $i=>$v) {
		if ($v == 'Y') {
			if (isset($SUPPORTED_MESSAGES_FIELDS[$i])) {
				$flags[] = $SUPPORTED_MESSAGES_FIELDS[$i];
			} else {
				$flags[] = "?[$i]?";
			}
		}
	}
	if ($flags) {
		return implode(', ', $flags);
	} else {
		return '-';
	}
}

function trim_varl($src) {
	if (substr($src,-1) == '|') {
		return substr($src,0,-1);
	} else {
		return null;
	}
}

function consume_field(&$src, $spec) {
	if (is_array($spec)) {
		list($spec, $parser) = $spec;
	} else {
		$parser = null;
	}
	$result = null;
	$pattern = "/^(?:$spec)/";
	if (preg_match($pattern, $src, $m)) {
		if ($parser) {
			$result = $parser($m[0]);
		} else {
			$result = $m[0];
		}
	}
	if ($result === null) {
		return null;
	}
	$src = preg_replace($pattern, '', $src);
	return $result;
}

if (isset($_POST['sip2']) && ($src = $_POST['sip2'])) {
	$DATE = array('.{18}', 'parse_date');
	$BIT  = '[01]';
	$BOOL = '[YN]';
	$TRIS = '[YNU]';
	$COUNT = '(?:\d{4}|\x20{4})';
	$FEET  = array('\d\d', 'parse_fee_type');
	$CIRC  = array('\d\d', 'parse_circ_status');
	$LANG  = array('...', 'parse_lang');
	$PTRN  = array('[Y\x20]{14}', 'parse_patron_status');

	$variable_fields = array(
		'AA' => array('patron identifier', null),
		'AB' => array('item identifier', null),
		'AC' => array('terminal password', null),
		'AD' => array('patron password', null),
		'AE' => array('personal name', null),
		'AF' => array('screen message', null),
		'AG' => array('print line', null),
		'AH' => array('due date', null),
		'AJ' => array('title identifier', null),
		'AM' => array('library name', null),
		'AN' => array('terminal location', null),
		'AO' => array('institution id', null),
		'AP' => array('current location', null),
		'AQ' => array('permanent location', null),
		'AS' => array('hold items', '2.00'),
		'AT' => array('overdue items', '2.00'),
		'AU' => array('charged items', '2.00'),
		'AV' => array('fine items', '2.00'),
		'AY' => array('sequence number', null, '\d'),
		'AZ' => array('checksum', null, '(?i:[A-Z0-9]{4})'),
		'BD' => array('home address', '2.00'),
		'BE' => array('e-mail address', '2.00'),
		'BF' => array('home phone number', '2.00'),
		'BG' => array('owner', '2.00'),
		'BH' => array('currency type', '2.00', '...'), // TODO: ISO-4217:1995 ?
		'BI' => array('cancel', '2.00', $BOOL),
		'BK' => array('transaction id', '2.00'),
		'BL' => array('valid patron', '2.00', $BOOL),
		'BM' => array('renewed items', '2.00'),
		'BN' => array('unrenewed items', '2.00'),
		'BO' => array('fee acknowledged', '2.00', $BOOL),
		'BP' => array('start item', '2.00'),
		'BQ' => array('end item', '2.00'),
		'BR' => array('queue position', '2.00'),
		'BS' => array('pickup location', '2.00'),
		'BT' => array('fee type', '2.00', $FEET),
		'BU' => array('recall items', '2.00'),
		'BV' => array('fee amount', '2.00'),
		'BW' => array('expiration due', '2.00', $DATE),
		'BX' => array('supported messages', '2.00', array('[YN]+(?=\|)','parse_supported_messages')), // variable length fields-array ??
		'BY' => array('hold type', '2.00', array('\d','parse_hold_type')),
		'BZ' => array('hold items limit', '2.00', '\d\d\d\d'),
		'CA' => array('overdue items limit', '2.00', '\d\d\d\d'),
		'CB' => array('charged items limit', '2.00', '\d\d\d\d'),
		'CC' => array('fee limit', '2.00'),
		'CD' => array('unavailable hold items', '2.00'),
		'CF' => array('hold queue length', '2.00'),
		'CG' => array('fee identifier', '2.00'),
		'CH' => array('item properties', '2.00'),
		'CI' => array('security inhibit', '2.00', $BOOL),
		'CJ' => array('recall date', '2.00', $DATE),
		'CK' => array('media type', '2.00', array('...', 'parse_media_type')),
		'CL' => array('sort bin', '2.00'),
		'CM' => array('hold pickup date', '2.00', $DATE),
		'CN' => array('login user id', '2.00'),
		'CO' => array('login password', '2.00'),
		'CP' => array('location id', '2.00'),
		'CQ' => array('valid patron password', '2.00', $BOOL),
	);
	$messages = array(
		'23' => array('command', 'Patron Status Request',  null,   array('language'=>$LANG,'transaction date'=>$DATE), array('AO','AA','AC','AD',), array()),
		'11' => array('command', 'Checkout',               null,   array('SC renewal policy'=>$BOOL,'no block'=>$BOOL,'transaction date'=>$DATE, 'nb due date'=>$DATE), array('AO','AA','AB','AC'), array('CH','AD','BO','BI')),
		'09' => array('command', 'Checkin',                null,   array('no block'=>$BOOL, 'transaction date'=>$DATE, 'return date'=>$DATE), array('AP','AO','AB','AC'), array('CH','BI')),
		'01' => array('command', 'Block Patron',           null,   array('card retained'=>$BOOL, 'transaction date'=>$DATE), array('AO','AL','AA','AC'), array()),
		'99' => array('command', 'SC Status',              null,   array('status code'=>array('[012]','parse_status_code'), 'max print width'=>'\d{3}', 'protocol version'=>'\d\.\d\d'), array(), array()),
		'97' => array('command', 'Request ACS Resend',     null,   array(), array(), array()),
		'93' => array('command', 'Login',                  '2.00', array('UID algorithm'=>'.', 'PWD algorithm'=>'.'), array('CN','CO'), array('CP')),
		'63' => array('command', 'Patron Information',     '2.00', array('language'=>$LANG,'transaction date'=>$DATE,'summary'=>array('[Y\x20]{10}','parse_summary')), array('AO','AA'), array('AC','AD','BP','BQ')),
		'35' => array('command', 'End Patron Session',     '2.00', array('transaction date'=>$DATE), array('AO','AA'), array('AC','AD')),
		'37' => array('command', 'Fee Paid',               '2.00', array('transaction date'=>$DATE,'fee type'=>$FEET,'payment type'=>array('\d\d','parse_payment_type'),'currency type'=>'...'), array('V','AO','AA'), array('AC','AD','CG','BK')),
		'17' => array('command', 'Item Information',       '2.00', array('transaction date'=>$DATE), array('AO','AB'), array('AC')),
		'19' => array('command', 'Item Status Update',     '2.00', array('transaction date'=>$DATE), array('AO','AB','CH'), array('AC')),
		'25' => array('command', 'Patron Enable',          '2.00', array('transaction date'=>$DATE), array('AO','AA'), array('AC','AD')),
		'15' => array('command', 'Hold',                   '2.00', array('hold mode'=>array('[*+-]','parse_hold_mode'),'transaction date'=>$DATE,'expiration date'=>$DATE), array('AO','AA'), array('BW','BS','BY','AD','AB','AJ','AC','BO')),
		'29' => array('command', 'Renew',                  '2.00', array('third party allowed'=>$BOOL,'no block'=>$BOOL,'transaction date'=>$DATE,'nb due date'=>$DATE), array('AO','AA'), array('AD','AB','AJ','AC','CH','BO')),
		'65' => array('command', 'Renew All',              '2.00', array('transaction date'=>$DATE), array('AO','AA'), array('AD','AC','BO')),
		'24' => array('response', 'Patron Status Response',null,   array('patron status'=>$PTRN,'language'=>$LANG,'transaction date'=>$DATE), array('AO','AA','AE'), array('BL','CQ','BH','BV','AF','AG')),
		'12' => array('response', 'Checkout Response',     null,   array('ok'=>$BIT,'renewal ok'=>$BOOL,'magnetic media'=>$TRIS,'desensitize'=>$TRIS,'transaction date'=>$DATE), array('AO','AA','AB','AJ','AH'), array('BT','CI','BH','BV','CK','CH','BK','AF','AG')),
		'10' => array('response', 'Checkin Response',      null,   array('ok'=>$BIT,'resensitize'=>$BOOL,'magnetic media'=>$TRIS,'alert'=>$BOOL,'transaction date'=>$DATE), array('AO','AB','AQ'), array('AJ','CL','AA','CK','CH','AF','AG')),
		'98' => array('response', 'ACS Status',            null,   array('on-line status'=>$BOOL,'checkin ok'=>$BOOL,'checkout ok'=>$BOOL,'ACS renewal policy'=>$BOOL,'status update ok'=>$BOOL,'off-line ok'=>$BOOL,'timeout period'=>'...','retried allowed'=>'...','date / time sync'=>$DATE,'protocol version'=>'\d\.\d\d'), array('AO','BX'), array('AM','AN','AF','AG')),
		'96' => array('response', 'Request SC Resend',     null,   array(), array(), array()),
		'94' => array('response', 'Login Response',        '2.00', array('ok'=>$BIT), array(), array()),
		'64' => array('response', 'Patron Information Response','2.00', array('patron status'=>$PTRN,'language'=>$LANG,'transaction date'=>$DATE,'hold items count'=>$COUNT,'overdue items count'=>$COUNT,'charged items count'=>$COUNT,'fine items count'=>$COUNT,'recall items count'=>$COUNT,'unavailable holds count'=>$COUNT), array('AO','AA','AE'), array('BZ','CA','CB','BL','CQ','BH','BV','CC','AS','AT','AU','AV','BU','CD','BD','BE','BF','AF','AG')), # NB: AS/AT/AU/AV/BU/CD depend "summary" field of Patron Information command
		'36' => array('response', 'End Session Response',       '2.00', array('end session'=>$BOOL,'transaction date'=>$DATE), array('AO','AA'), array('AF','AG')),
		'38' => array('response', 'Fee Paid Response',          '2.00', array('payment accepted'=>$BOOL,'transaction date'=>$DATE), array('AO','AA'), array('BK','AF','AG')),
		'18' => array('response', 'Item Information Response',  '2.00', array('circulation status'=>$CIRC,'security marker'=>array('\d\d','parse_security_marker'),'fee type'=>$FEET,'transaction date'=>$DATE), array('AB','AJ'), array('CF','AH','CJ','CM','BG','BH','BV','CK','AQ','AP','CH','AF','AG')),
		'20' => array('response', 'Item Status Update Response','2.00', array('item properties ok'=>$BIT,'transaction date'=>$DATE), array('AB'), array('AJ','CH','AF','AG')),
		'26' => array('response', 'Patron Enable Response','2.00', array('patron status'=>$PTRN,'language'=>$LANG,'transaction date'=>$DATE), array('AO','AA','AE'), array('BL','CQ','AF','AG')),
		'16' => array('response', 'Hold Response',         '2.00', array('ok'=>$BIT,'available'=>$BOOL,'transaction date'=>$DATE), array('AO','AA'), array('BW','BR','BS','AB','AJ','AF','AG')),
		'30' => array('response', 'Renew Response',        '2.00', array('ok'=>$BIT,'renewal ok'=>$BOOL,'magnetic media'=>$TRIS,'desensitize'=>$TRIS,'transaction due'=>$DATE), array('AO','AA','AB','AJ','AH'), array('BT','CI','BH','BV','CK','CH','BK','AF','AG')),
		'66' => array('response', 'Renew All Response',    '2.00', array('ok'=>$BIT,'renewed count'=>$COUNT,'unrenewed count'=>$COUNT,'transaction date'=>$DATE), array('AO'), array('BM','BN','AF','AG')), # BM/BN expected 0..n times
	);
	/**/
	$message_pattern = '/^(\d{2})([^\r]*)(\r\n|\r|$)/';
	$argid_pattern = '/^(..)/';
	$varl_spec = '([^|]{0,255})\|';
	while (strlen($src) > 0) {
		if (preg_match($message_pattern, $src, $m)) {
			$src = preg_replace($message_pattern, '', $src);
			$message_id   = $m[1];
			$message_body = $m[2];
			$terminator   = $m[3];

			// HACK: checksum
			if (preg_match('/(.+AZ)([0-9A-F]{4})(\r|\n|$)/', $message_body, $n)) {
				$sum = 0;
				foreach (str_split($message_id.$n[1]) as $char) {
					$sum = ($sum + ord($char)) & 0xFFFF;
				}
				$sum = (-$sum) & 0xFFFF;
				$hexsum = sprintf('%04X', $sum);

				$msgsum = strtoupper($n[2]);

				if ($hexsum != $msgsum) {
					echo '<div class="error">Checksum mismatch!  Got '.$hexsum.', expected '.$msgsum.' [<code>'.htmlspecialchars($message_body).'</code>]</div>';
				}
			}

			if (isset($messages[$message_id]) && ($msg = $messages[$message_id])) {
				list($message_type, $message_name, $message_version, $fixed, $required, $optional) = $msg;
				echo '<div class="message">[<code>'.htmlspecialchars($message_id).'</code>] '.htmlspecialchars($message_name).' ('.htmlspecialchars($message_type).')';
				if ($message_version) {
					echo ' (v<i>'.htmlspecialchars($message_version).'</i>)';
				}

				$varls = array(); // detected variable-length fields

				// Consume fixed-length required args
				if (is_array($fixed)) {
					foreach ($fixed as $fname=>$fspec) {
						$result = consume_field($message_body, $fspec);
						if ($result !== null) {
							echo '<div class="field">'.htmlspecialchars($fname).' = '.htmlspecialchars($result).'</div>';
						} else {
							echo '<div class="error">Invalid fixed-length field '.htmlspecialchars($fname).' [<code>'.htmlspecialchars($message_body).'</code>]</div>';
							$message_body = false;
						}
					}
				} elseif ($fixed) {
					if (preg_match($fixed, $message_body, $n)) {
						$message_body = preg_replace($fixed, '', $message_body);
						foreach ($n as $k=>$v) {
							if (is_string($k)) {
								echo '<div class="field">'.htmlspecialchars(str_replace('_',' ',$k)).' = [<code>'.htmlspecialchars($v).'</code>]</div>';
							}
						}
					} else {
						echo '<div class="error">Message body doesn\'t match pattern <code>'.htmlspecialchars($message_id).htmlspecialchars($message_body).'</code></div>';
					}
				}
				else { echo '((no regexp))'; }

				// Consume optional/varlength args
				while (strlen($message_body) > 0) {
					if (preg_match($argid_pattern, $message_body, $o)) {
						$message_body = preg_replace($argid_pattern, '', $message_body);
						$field_id = $o[1];

						if (isset($variable_fields[$field_id]) && ($field_def = $variable_fields[$field_id])) {
							list($field_name, $field_version, $field_spec) = $field_def;

							echo '<div class="field">[<code>'.htmlspecialchars($field_id).'</code>] '.htmlspecialchars($field_name);
							if ($field_version) {
								echo ' (v<i>'.htmlspecialchars($field_version).'</i>)';
							}

							if ($field_spec !== null) {
								$result = consume_field($message_body, $field_spec);
								if ($result === null) {
									echo '<div class="error">Invalid optional field '.htmlspecialchars($field_name).' [<code>'.htmlspecialchars($message_body).'</code>]</div>';
								} else {
									echo ' = '.htmlspecialchars($result);
								}

								if (substr($message_body,0,1) == '|') {
									$message_body = substr($message_body,1);
									if ($field_id == 'AY' || $field_id == 'AZ') {
										echo '<div class="warning">Detected pipe character after '.htmlspecialchars($field_name).' field</div>';
									}
								} elseif ($field_id != 'AY' && $field_id != 'AZ') {
									echo '<div class="warning">Missing pipe character after fixed-length field (required by SIP2)</div>';
								}
							} else {
								// assume variable-length field
								$field_body = consume_field($message_body, array($varl_spec, 'trim_varl'));
								if ($field_body === null) {
									echo '<div class="error">Invalid variable-length field '.htmlspecialchars($field_name).' [<code>'.htmlspecialchars($message_body).'</code>]</div>';
								} else {
									echo ' = [<code>'.htmlspecialchars($field_body).'</code>]';
									if (strlen($field_body) == 0) {
										echo '<div class="warning">Zero-length variable-length field</div>';
									}
								}
							}
							echo '</div>';
						} else {
							// unrecognised fields HAVE to be variable length, otherwise I can't parse them!
							$field_body = consume_field($message_body, array($varl_spec, 'trim_varl'));
							// TODO: null?
							echo '<div class="warning">Unrecognised field code <code>'.htmlspecialchars($field_id).'</code> <code>'.htmlspecialchars($field_body).'</code></div>';
						}

						if ($field_id == 'AY') {
							// check seqnum is incremented from last message
							// check next field is 'AZ'
						} elseif ($field_id == 'AZ') {
							// check message checksum
							// check EOM
						} elseif ($required && in_array($field_id, $required)) {
							$varls[] = $field_id;
						} elseif ($optional && in_array($field_id, $optional)) {
							// k
						} else {
							echo '<div class="warning">Field <code>'.htmlspecialchars($field_id).'</code> is not specified for message '.htmlspecialchars($message_name).'</div>';
						}
					} else {
						echo '<div class="error">Invalid variable-length field <code>'.htmlspecialchars($message_body).'</code></div>';
						$varls = null;
						break;
					}
				}

				if (!is_null($varls)) {
					$missing = array_diff($required, $varls);
					if (count($missing) > 0) {
						echo '<div class="error">Message missing required fields:<ul>';
						foreach ($missing as $id) {
							echo '<li>[<code>'.htmlspecialchars($id).'</code>]</li>';
						}
						echo '</ul></div>';
					}
				}
				echo '</div>'; #</message>
			} else {
				echo '<div class="warning">Unrecognised message code <code>'.htmlspecialchars($message_id).'</code> <code>'.htmlspecialchars($message_body).'</code></div>';
			}

			if ($terminator == '') {
				echo '<div class="warning">No $0D message terminator on final message</div>';
			} elseif ($terminator == "\r\n") {
				#echo '<div class="warning">Detected $0A after $0D; assuming part of message terminator</div>';
			}
		} else {
			echo '<div class="error">Invalid: <code>'.htmlspecialchars($src).'</code></div>';
			break;
		}
	}
}

?>
  </section>
 </body>
</html>
