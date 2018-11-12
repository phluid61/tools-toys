<?php

function dbg($obj) {
	#return htmlspecialchars(preg_replace('/\s+/',' ',var_export($obj,1)));
	return preg_replace('/\s+/',' ',var_export($obj,1));
}
function dbgstr($str) {
	$dbg = '';
	foreach (unpack('C*',$str) as $c) {
		$dbg .= sprintf('\\x%02X', $c);
	}
	return "\"$dbg\"";
}

function hex2bytes($hex) {
	$bytes = array();
	foreach (str_split($hex, 2) as $hexbyte) {
		$bytes[] = intval($hexbyte, 16);
	}
	return $bytes;
}
function string2bytes($string) {
	return unpack('C*', $string);
}

function bytes2hex($bytes) {
	$hex = '';
	foreach ($bytes as $byte) {
		$hex .= sprintf('%02X', $byte);
	}
	return $hex;
}
function bytes2str($bytes) {
	$str = '';
	foreach ($bytes as $byte) {
		if ($byte > 0xff) { return false; }
		$str .= pack('C', $byte);
	}
	return $str;
}
function bytes2C($bytes) {
	$hex = '';
	foreach ($bytes as $byte) {
		$hex .= sprintf('\\x%02X', $byte);
	}
	return $hex;
}

function is_utf16($str, $be=true, $astral=true) {
	$surrogate = false;
	foreach (str_split($str,2) as $word) {
		if (strlen($word) < 2) {
			return false;
		}
		list($_,$n) = unpack($word, $be ? 'n' : 'v');
		if ($surrogate) {
			if ($n >= 0xdc00 && $n <= 0xdfff) {
				$surrogate = false;
			} else {
				return false;
			}
		} else {
			if (($n >= 0x0000 && $n <= 0xd7ff) || ($n >= 0xe000 && $n <= 0xffff)) {
				// cool
			} elseif (($n >= 0xd800 && $n <= 0xdbff) && $astral) {
				$surrogate = true;
			} else {
				return false;
			}
		}
	}
	if ($surrogate) {
		return false;
	}
	return true;
}

/* true/false - can it be interpreted as $enc ? */
function bytes_are($bytes, $enc) {
	return string_is(bytes2str($bytes), $enc);
}
function string_is($string, $enc) {
	// XXX
	if ($enc == 'UTF-16' || $enc == 'UTF-16LE') {
		return is_utf16($string, false);
	} elseif ($enc == 'UTF-16BE') {
		return is_utf16($string, true);
	} elseif ($enc == 'UCS-2' || $enc == 'UCS-2LE') {
		return is_utf16($string, false, false);
	} elseif ($enc == 'UCS-2BE') {
		return is_utf16($string, true, false);
	} elseif ($enc == 'Windows-1252') {
		return true;
	}
	#$rv = iconv($enc, 'UTF-8', $string);
	$rv = mb_detect_encoding($string, array($enc), true);
	return $rv !== false;
}

/* interpret as $enc, get array of codepoints (integers) */
function bytes2codepoints($bytes, $enc) {
	return string2codepoints(bytes2str($bytes), $enc);
}
function string2codepoints($string, $enc) {
	// XXX
	if (0 && $enc == 'Windows-1252') {
		$cp = array();
		while ($string) {
			$cp[] = ord(substr($string,0,1));
			$string = substr($string, 1);
		}
		return $cp;
	}

	if (!string_is($string, $enc)) {
		return false;
	}
	#$utf32 = iconv($enc, 'UTF-32BE', $string);
	$utf32 = mb_convert_encoding($string, 'UTF-32BE', $enc);
	if ($utf32 === false) { return false; }
	$codepoints = array();
	while ($utf32) {
		list($_,$cp) = unpack('N', $utf32);
		$utf32 = substr($utf32, 4);
		$codepoints[] = $cp;
	}
	return $codepoints;
}

function codepoints2bytes($codepoints, $enc) {
	$string = codepoints2string($codepoints, $enc);
	if ($string === false) {
		return false;
	}
	return string2bytes($string);
}
function codepoints2string($codepoints, $enc) {
	// XXX
	if (0 && $enc == 'Windows-1252') {
		$s = '';
		foreach ($codepoints as $cp) {
			if ($cp > 0xff) { return false; }
			$s .= chr($cp);
		}
		return $s;
	}

	$utf32 = '';
	foreach ($codepoints as $cp) {
		$utf32 .= pack('N', $cp);
	}
	$encoded_string = mb_convert_encoding($utf32, $enc, 'UTF-32BE');
	// FIXME: doesn't fail on invalid codepoints -- let's check the round-trip instead
	if (string2codepoints($encoded_string, $enc) != $codepoints) {
echo "\n<!-- did not roundtrip through $enc:\n  codepoints = " . codepoints2U($codepoints) . "\n  UTF-32 = " . dbgstr($utf32) . "\n  $enc = " . dbgstr($encoded_string) . "\n  round-tripped = " . dbg(string2codepoints($encoded_string, $enc)) . "\n-->\n";
		return false;
	}
	// ---
	return $encoded_string;
}
function codepoints2html($codepoints) {
	$html = '';
	foreach ($codepoints as $cp) {
		$html .= sprintf('&#x%X;', $cp);
	}
	return $html;
}
function codepoints2U($codepoints) {
	$u = array();
	foreach ($codepoints as $cp) {
		$u[] = sprintf('U+%04X', $cp);
	}
	return implode(' ', $u);
}

?><!DOCTYPE html>
<html lang="en">
 <head>
  <meta charset="utf-8">
  <title>Charset/Encoding Tool</title>
  <link rel="stylesheet" href="//s.library.qut.edu.au/css/minimal-blue-20180105.css">
  <link rel="icon" href="//s.library.qut.edu.au/favicon-20170112.ico">
  <style type="text/css">.charset{margin:20px 0;padding:0 20px;border:1px solid #eee}.charset>h3{margin-left:-20px}.unicode,.escaped{display:inline-block;padding:2px;background-color:#f8f8f8;border:1px inset #f0f0f0}.nested{margin-left:20px}a:link,a:visited{text-decoration:none}a:hover,a:active,a:focus{text-decoration:underline}</style>
  <script type="text/javascript">//<![CDATA[
function select(id) {
  var e=document.getElementById(id);
  e.focus();
  e.setSelectionRange(0,e.value.length);
}
  //]]></script>
 </head>
 <body onload="javascript:select('bytes')">
  <div id="masthead" role="navigation">
   <div id="qut">
    <a href="https://www.qut.edu.au/"><img src="//s.library.qut.edu.au/img/qut-20150421" srcset="//s.library.qut.edu.au/img/qut-s-20150806 32w, //s.library.qut.edu.au/img/qut-20150421 52w, //s.library.qut.edu.au/img/qut-2x-20150421 104w" sizes="52px" alt="QUT home"></a><img src="//s.library.qut.edu.au/img/white-bar-20150421" srcset="//s.library.qut.edu.au/img/white-bar-s-20150806 26w, //s.library.qut.edu.au/img/white-bar-20150421 42w, //s.library.qut.edu.au/img/white-bar-2x-20150421 84w" sizes="42px" alt=""><a href="/"><img src="//s.library.qut.edu.au/img/eisas-20180105" srcset="//s.library.qut.edu.au/img/eisas-s-20180105 62w, //s.library.qut.edu.au/img/eisas-20180105 100w, //s.library.qut.edu.au/img/eisas-2x-20180105 200w" sizes="100px" alt="QUT Library home page"></a>
   </div>
   <div id="strap">
     <img src="//s.library.qut.edu.au/img/strapline-20130415" srcset="//s.library.qut.edu.au/img/strapline-20130415 187w, //s.library.qut.edu.au/img/strapline-2x-20141024 374w" sizes="187px" alt="A university for the real world">
   </div>
  </div>
  <h1>Charset/Encoding Tool</h1>

<?php

if (isset($_GET['hex']) && ($hex = $_GET['hex'])) {
	$hex = preg_replace('/\s+/', '', $hex);;
	if (preg_match('/[^0-9A-F]/i', $hex)) {
		echo "<div class=\"error\">Bad input: not hexadecimal <code>".htmlspecialchars($hex)."</code></div>";
		$bytes = null;
	} else {
		$hex = strtoupper($hex);
		$bytes = hex2bytes($hex);
	}
} elseif (isset($_GET['utf8']) && ($utf8 = $_GET['utf8'])) {
	$bytes = string2bytes($utf8);
	$hex = bytes2hex($bytes);
} else {
	$hex = 'F09F92A9';
	$bytes = null;
}

if ($bytes && ($cps = bytes2codepoints($bytes, 'UTF-8')) !== false) {
	$utf8 = codepoints2html($cps);
} else {
	$utf8 = '';
}

?>
<form action="./" method="GET">
<input type="text" length="20" name="hex" id="hex" value="<?php echo $hex;?>">
<input type="submit" value="&larr; Interpret hex-coded Bytes ...">
</form>
<form action="./" method="GET">
<input type="text" length="20" name="utf8" id="utf8" value="<?php echo $utf8;?>">
<input type="submit" value="&larr; Interpret raw UTF-8 ...">
</form>
<?php

$encodings = array(
	'UTF-8' => 'UTF-8',
#	'UTF-16BE' => 'UTF-16 (Big-Endian)',
#	'UTF-16LE' => 'UTF-16 (Little-Endian)',
	'Windows-1252' => 'Windows-1252/ISO-8859-1',
	'CP850'        => 'Codepage 850 (DOS/OEM)',
	'EUC-CN'       => 'EUC-CN/GBK (Simplified Chinese)',
	'CP936'        => 'Codepage 936/GBK (Simplified Chinese)',
	'Big-5'        => 'Big-5 (Hong Kong/Taiwan, Traditional Chinese)',
	'SHIFT_JIS'    => 'SHIFT_JIS (Japanese)',
);

if ($bytes) {
	echo '<p>Bytes: <code class="escaped">'.bytes2C($bytes).'</code></p><p>Can be interpreted as:</p>';

	$any = false;
	foreach ($encodings as $enc=>$desc) {
		$cps = bytes2codepoints($bytes, $enc);
		if ($cps === false) {
			continue;
		}
		$any = true;

		echo '<div class="charset"><h3>'.$desc.'</h3>';
		echo '<span class="codepoints">'.codepoints2U($cps).'</span><br>';
		echo '<a href="./?utf8='.codepoints2html($cps).'" class="unicode">'.codepoints2html($cps).'</a><br>';

		// what other encodings include all these codepoints?
		foreach ($encodings as $enc2=>$desc2) {
			if ($enc2 == $enc) {
				continue;
			}

			$bytes2 = codepoints2bytes($cps, $enc2);
			if ($bytes2 !== false) {
				echo '<div class="nested charset">';
				echo '<p>Converting those codepoints to <b>'.$desc2.'</b> gives:<br>Bytes: <code class="escaped">'.bytes2C($bytes2).'</code></p><p>Can be interpreted as:</p>';

				// how do THESE bytes look in other encodings?
				$any2 = false;
				foreach ($encodings as $enc3=>$desc3) {
					if ($enc3 == $enc2) {
						continue;
					}

					$cps3 = bytes2codepoints($bytes2, $enc3);
					if ($cps3 === false ) {
						continue;
					}
					$any2 = true;

					echo '<div class="nested charset"><h3>'.$flair.$desc3.$flair.'</h3>';
					echo '<span class="codepoints">'.codepoints2U($cps3).'</span><br>';
					echo '<a href="./?utf8='.codepoints2html($cps3).'" class="unicode">'.codepoints2html($cps3).'</a><br>';
					/*** special cases ***/
					if ($enc == $enc3) {
						if ($enc == 'UTF-8' && $enc2 == 'Windows-1252') {
							echo '<b>*** Could be doubly-encoded from '.$desc2.' to '.$desc.' ***</b>';
						} else {
							echo '<i>*** Warning, double-encoding '.$desc.' via '.$desc2.' ***</i>';
						}
					}
					/*** --- ***/
					echo '</div>';
				}
				if (!$any2) {
					echo '<p>&ndash;</p>';
				}

				echo '</div>';
			}
		}

		if (!$any) {
			echo '<p>&ndash;</p>';
		}

		echo '</div>';
	}
}

?>
</body>
</html>
