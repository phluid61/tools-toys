<?php

$SUFFIX = '';
if ($FORMAT = @$_REQUEST['format']) {
	switch ($FORMAT) {
	case 'range':
		break;
	case 'blank':
	case 'cidr':
		$USE_SUFFIX = true;
		break;
	case 'star':
		$USE_SUFFIX = true;
		$SUFFIX = '.*';
		break;
	default:
		$USE_SUFFIX = true;
		$FORMAT = 'dot';
	}
}

########################
### Regex sub-patterns

# $1=dec
$OCTET = '(?:0*(\\d{1,2}|1\\d{2}|2[0-4]\\d|25[0-5]))';

# $1.$2.$3.$4
$IP = "$OCTET\\.$OCTET\\.$OCTET\\.$OCTET";

# no capture groups
$HEXTET = '(?:0*[0-9A-Fa-f]{1,4})';
$IP6CHUNK  = "(?:$HEXTET(?:[:]$HEXTET)*)";
$IP6 = "(?:$IP6CHUNK+|$IP6CHUNK*::$IP6CHUNK*)";

########################
### Regex patterns

# $1.$2.$3.$4-$5.$6.$7.$8
$IPRANGE = "/^$IP-$IP$/";

# $1.[$2.[$3.[$4]]]
$IP1BYTE = "/^$OCTET(?:\\.\\*?)?$/";
$IP2BYTE = "/^$OCTET\\.$OCTET(?:\\.\\*?)?$/";
$IP3BYTE = "/^$OCTET\\.$OCTET\\.$OCTET(?:\\.\\*?)?$/";
$IP4BYTE = "/^$IP$/";

# $1.$2.$3.$4/$5
$CIDR = "/^$IP\\/0*([12]?\\d|3[0-2])$/";
$CIDR1BYTE = "/^$OCTET\\/0*([12]?\\d|3[0-2])$/";
$CIDR2BYTE = "/^$OCTET\\.$OCTET\\/0*([12]?\\d|3[0-2])$/";
$CIDR3BYTE = "/^$OCTET\\.$OCTET\\.$OCTET\\/0*([12]?\\d|3[0-2])$/";

$IP6ADDR = "/^($IP6)$/";
$IP6CIDR = "/^($IP6)\\/0*(\\d{1,2}|1[01]\\d|12[0-8])$/";

########################


# In: ?, ?, ?, ?
# Out: int
function digits_to_int($a, $b, $c, $d) {
	return (intval($a) << 24) + (intval($b) << 16) + (intval($c) << 8) + intval($d);
}

# In: ?, ?, ?, ?
# Out: str = '12.34.56.78'
function digits_to_str($a, $b, $c, $d) {
	return sprintf('%d.%d.%d.%d', $a&0xff, $b&0xff, $c&0xff, $d&0xff);
}

# In: int
# Out: [str, str, str, str]
function int_to_digits($i) {
	$a = ''.(($i & 0xff000000) >> 24);
	$b = ''.(($i & 0x00ff0000) >> 16);
	$c = ''.(($i & 0x0000ff00) >> 8);
	$d = ''. ($i & 0x000000ff);
	return array($a, $b, $c, $d);
}

# In: int
# Out: str = '12.34.56.78'
function int_to_str($i) {
	return implode('.', int_to_digits($i));
}

# In: str = '1234:5678::90ab'
# Out: [int, int, int, int, int, int, int, int]
function ip6_to_ints($s) {
	$before = array();
	$zeroes = array();
	$after = array();

	$state = 0; // 0=before, 1=zeroes, 2=after
	foreach (explode(':', $s) as $p) {
		switch ($state) {
		case 0:
			if ($p === '') {
				$state ++;
			} else {
				$before[] = hexdec($p);
			}
			break;
		case 1:
			if ($p === '') {
				// ignore
				break;
			}
			$state ++;
			// fall through
		case 2:
			$after[] = hexdec($p);
			break;
		}
	}

	$n0 = count($before);
	$n2 = count($after);
	$n1 = 8 - ($n0 + $n2);
	for ($i = 0; $i < $n1; $i++) {
		$zeroes[] = 0;
	}

	return array_merge($before, $zeroes, $after);;
}

# In: [int, int, int, int, int, int, int, int]
# Out: str = '1234:5678::90ab'
function ints_to_ip6($a) {
	$b = array();
	foreach ($a as $i) {
		$b[] = dechex($i);
	}
	$s = implode(':', $b);
	$s = preg_replace('/(^|:)0(:0)+(:|$)/', '::', $s, 1);
	return $s;
}

# In: str
# Out: [int, int]
function parse_range($r) {
	global $IP1BYTE, $IP2BYTE, $IP3BYTE, $IP4BYTE, $IPRANGE, $CIDR, $CIDR1BYTE, $CIDR2BYTE, $CIDR3BYTE, $IP6ADDR, $IP6CIDR;
	if (preg_match($IP1BYTE, $r, $m)) {
		$a = digits_to_int($m[1], 0, 0, 0);
		$b = digits_to_int($m[1], 255, 255, 255);
	} elseif (preg_match($IP2BYTE, $r, $m)) {
		$a = digits_to_int($m[1], $m[2], 0, 0);
		$b = digits_to_int($m[1], $m[2], 255, 255);
	} elseif (preg_match($IP3BYTE, $r, $m)) {
		$a = digits_to_int($m[1], $m[2], $m[3], 0);
		$b = digits_to_int($m[1], $m[2], $m[3], 255);
	} elseif (preg_match($IP4BYTE, $r, $m)) {
		$a = digits_to_int($m[1], $m[2], $m[3], $m[4]);
		$b = $a;
	} elseif (preg_match($IPRANGE, $r, $m)) {
		$a = digits_to_int($m[1], $m[2], $m[3], $m[4]);
		$b = digits_to_int($m[5], $m[6], $m[7], $m[8]);
	} elseif (preg_match($CIDR, $r, $m)) {
		$ip = digits_to_int($m[1], $m[2], $m[3], $m[4]);
		$bits = intval($m[5]);
		$lobits = pow(2, 32-$bits) - 1;
		$hibits = 0xffffffff ^ $lobits;

		$a = $ip & $hibits;
		$b = $a | $lobits;
	} elseif (preg_match($CIDR3BYTE, $r, $m)) {
		$ip = digits_to_int($m[1], $m[2], $m[3], 0);
		$bits = intval($m[4]);
		$lobits = pow(2, 32-$bits) - 1;
		$hibits = 0xffffffff ^ $lobits;

		$a = $ip & $hibits;
		$b = $a | $lobits;
	} elseif (preg_match($CIDR2BYTE, $r, $m)) {
		$ip = digits_to_int($m[1], $m[2], 0, 0);
		$bits = intval($m[3]);
		$lobits = pow(2, 32-$bits) - 1;
		$hibits = 0xffffffff ^ $lobits;

		$a = $ip & $hibits;
		$b = $a | $lobits;
	} elseif (preg_match($CIDR1BYTE, $r, $m)) {
		$ip = digits_to_int($m[1], 0, 0, 0);
		$bits = intval($m[2]);
		$lobits = pow(2, 32-$bits) - 1;
		$hibits = 0xffffffff ^ $lobits;

		$a = $ip & $hibits;
		$b = $a | $lobits;
	} elseif (preg_match($IP6ADDR, $r, $m)) {
		$a = $b = ip6_to_ints($m[1]);
	} elseif (preg_match($IP6CIDR, $r, $m)) {
		$a = $b = ip6_to_ints($m[1]);
		$bits = intval($m[2]);

		for ($i = 0; $i < 128; $i += 16) {
			$idx = ($i / 16);
			if ($bits >= ($i+16)) {
				# this idx is all network
			} elseif ($bits > $i) {
				# this idx is partial
				$lobits = pow(2, 16-($bits-$i)) - 1;
				$hibits = 0xffff ^ $lobits;
				$a[$idx] &= $hibits;
				$b[$idx] = $a[$idx] | $lobits;
			} else {
				# this idx is all address
				$a[$idx] = 0x0000;
				$b[$idx] = 0xffff;
			}
		}
	} else {
		throw new Exception("can't parse IP range '$r'");
	}
	return array($a, $b);
}

# In: int, int
# Out: [int=ip, int=bits]
function range_to_cidr($a, $b) {
	$c = null;
	$bitmask1 = 0x80000000;
	$bitmask0 = 0x7fffffff;
	$cidr = 1;
	$best = array(null, 32);

	$c = $a & $bitmask1;
	$same_start = $c == ($b & $bitmask1);
	$same_end   = ($a & $bitmask0) ^ ($b & $bitmask0) == $bitmask0;
	while ($cidr < 32 && $same_start) {
		if ($same_end) {
			$best = array($c, $cidr);
		}
		$cidr += 1;
		$bitmask1 = ($bitmask1 >> 1) | 0x80000000;
		$bitmask0 = ($bitmask0 >> 1) & 0x7fffffff;
		$c = $a & $bitmask1;
		$same_start = $c == ($b & $bitmask1);
		$same_end   = ($a & $bitmask0) ^ ($b & $bitmask0) == $bitmask0;
	}

	return $best;
}

# In: [int...], [int...]
# Out: [[int...]=ip6, int=bits]
function range6_to_cidr6($a, $b) {
	$ip6 = array();
	$cidr = 0;
	$state = 0; # 0=network, 1=address
	foreach ($a as $i=>$x) {
		switch ($state) {
		case 0:
			// in the network
			$y = $b[$i];
			if ($x == $y) {
				// this hextet matches
				$ip6[] = $x;
				$cidr += 16;
			} else {
				// first non-identical hextet
				list($ip4, $cidr4) = range_to_cidr($x, $y);
				if ($ip4 === null) {
					// no common prefix in this hextet
					if ($i == 0) {
						// no network prefix at all = no cidr
						return array(null, 128);
					} else {
						$ip6[] = 0;
					}
				} else {
					// there is a common prefix in this hextet
					$ip6[] = $ip4 & 0xffff;
					$cidr += ($cidr4 - 16);
				}
				$state ++;
			}
			break;
		case 1:
			// in the address
			$ip6[] = 0;
			break;
		}
	}
	return array($ip6, $cidr);
}

# In: int, int
# Out: str
function range_to_str($a, $b) {
	global $FORMAT, $USE_SUFFIX, $SUFFIX;

	if ($a > $b) {
		$_ = $a;
		$a = $b;
		$b = $_;
	} elseif ($a == $b) {
		return int_to_str($a);
	}

	if ($FORMAT != 'range') {
		list($c, $cidr) = range_to_cidr($a, $b);

		if ($c !== null) {
			$z = int_to_digits($c);
		}
		if ($USE_SUFFIX && $cidr == 24) {
			$s = (($FORMAT == 'cidr') ? '/24' : $SUFFIX);
			return $z[0].'.'.$z[1].'.'.$z[2].$s;
		} elseif ($USE_SUFFIX && $cidr == 16) {
			$s = (($FORMAT == 'cidr') ? '/16' : $SUFFIX);
			return $z[0].'.'.$z[1].$s;
		} elseif ($USE_SUFFIX && $cidr == 8) {
			$s = (($FORMAT == 'cidr') ? '/8' : $SUFFIX);
			return $z[0].$s;
		} elseif ($cidr < 32 && $cidr > 0) {
			$addr = int_to_str($c);
			if ($FORMAT == 'cidr') {
				$addr = preg_replace('/(\\.0)+$/', '', $addr);
			}
			return $addr.'/'.$cidr;
		}
	}
	return int_to_str($a).'-'.int_to_str($b);
}

function cmp6($a, $b) {
	foreach ($a as $i=>$x) {
		$y = $b[$i];
		if ($x < $y) {
			return -1;
		} elseif ($x > $y) {
			return 1;
		}
	}
	return 0;
}

function min6($a, $b) {
	$cmp = cmp6($a, $b);
	if ($cmp <= 0) {
		return $a;
	} else {
		return $b;
	}
}

function max6($a, $b) {
	$cmp = cmp6($a, $b);
	if ($cmp >= 0) {
		return $a;
	} else {
		return $b;
	}
}

function range6_to_str($a, $b) {
	global $FORMAT;

	$cmp = cmp6($a, $b);
	if ($cmp > 0) {
		$_ = $a;
		$a = $b;
		$b = $_;
	} elseif ($cmp == 0) {
		return ints_to_ip6($a);
	}

	if ($FORMAT != 'range') {
		list($c, $cidr) = range6_to_cidr6($a, $b);

		if ($c !== null) {
			return ints_to_ip6($c).'/'.$cidr;
		}
	}
	return ints_to_ip6($a) . '-' . ints_to_ip6($b);
}

########################

?><!DOCTYPE html>
<html lang="en">
 <head>
  <meta charset="utf-8">
  <title>IP Range Tool</title>
  <link rel="stylesheet" href="//s.library.qut.edu.au/css/minimal-blue-20180105.css">
  <link rel="icon" href="//s.library.qut.edu.au/favicon-20170112.ico">
  <style type="text/css">select,option{font-family:monospace;white-space:pre;white-space:pre-wrap}</style>
 </head>
 <body>
  <div id="masthead" role="navigation">
   <div id="qut">
    <a href="https://www.qut.edu.au/"><img src="//s.library.qut.edu.au/img/qut-20150421" srcset="//s.library.qut.edu.au/img/qut-s-20150806 32w, //s.library.qut.edu.au/img/qut-20150421 52w, //s.library.qut.edu.au/img/qut-2x-20150421 104w" sizes="52px" alt="QUT home"></a><img src="//s.library.qut.edu.au/img/white-bar-20150421" srcset="//s.library.qut.edu.au/img/white-bar-s-20150806 26w, //s.library.qut.edu.au/img/white-bar-20150421 42w, //s.library.qut.edu.au/img/white-bar-2x-20150421 84w" sizes="42px" alt=""><a href="/"><img src="//s.library.qut.edu.au/img/eisas-20180105" srcset="//s.library.qut.edu.au/img/eisas-s-20180105 62w, //s.library.qut.edu.au/img/eisas-20180105 100w, //s.library.qut.edu.au/img/eisas-2x-20180105 200w" sizes="100px" alt="QUT Library home page"></a>
   </div>
   <div id="strap">
     <img src="//static.library.qut.edu.au/img/strapline-20130415" srcset="//static.library.qut.edu.au/img/strapline-20130415 187w, //static.library.qut.edu.au/img/strapline-2x-20141024 374w" sizes="187px" alt="A university for the real world">
   </div>
  </div>
  <h1>IP Range Tool</h1>
<?php

try {
	$input = array();
	$output = array();

	$raw = @$_REQUEST['ranges'];
	$raw = preg_replace('/\s+/',' ',$raw);
	$ranges = array();
	$ranges6 = array();
	foreach (explode(' ', $raw) as $line) {
		$line = trim($line);
		if ($line) {
			$input[] = $line;
			$range = parse_range($line);
			if (is_array($range[0])) {
				$ranges6[] = $range;
			} else {
				$ranges[] = $range;
			}
		}
	}

	$dedup = array();
	$prev = null;

	sort($ranges);
	foreach ($ranges as $range) {
		list($a, $b) = $range;
		if ($prev !== null && $prev[0] <= $b && $prev[1] >= $a) {
			$prev[0] = min($prev[0], $a);
			$prev[1] = max($prev[1], $b);
		} else {
			if ($prev !== null) {
				$dedup[] = $prev;
			}
			$prev = $range;
		}
	}
	if ($prev !== null) {
		$dedup[] = $prev;
	}

	foreach ($dedup as $range) {
		list($a, $b) = $range;
		$output[] = range_to_str($a, $b);
	}

	$dedup6 = array();
	$prev = null;
	usort($ranges6, 'cmp6');
	foreach ($ranges6 as $range) {
		list($a, $b) = $range;
		if ($prev !== null && cmp6($prev[0], $b) <= 0 && cmp6($prev[1], $a) >= 0) {
			$prev[0] = min6($prev[0], $a);
			$prev[1] = max6($prev[1], $b);
		} else {
			if ($prev !== null) {
				$dedup6[] = $prev;
			}
			$prev = $range;
		}
	}
	if ($prev !== null) {
		$dedup6[] = $prev;
	}

	foreach ($dedup6 as $range) {
		list($a, $b) = $range;
		$output[] = range6_to_str($a, $b);
	}

	// default, for display purposes
	if (count($input) == 0) {
		$input = array(
			'255.255.255.255/32', #RFC919
			'240.0.0.0-255.255.255.255', # RFC1112
			'0.0.0.0-0.255.255.255', '127.0.0.0-127.255.255.255', #RFC1122
			'10.0.0.0-10.255.255.255', '172.16.0.0-172.31.255.255', '192.168.0.0-192.168.255.255', #RFC1918
			'198.18.0.0-198.19.255.255', #RFC2544
			'169.254.0.0-169.254.255.255', #RFC3927
			'192.0.2.0/24', '198.51.100.0/24', '203.0.113.0/24', #RFC5737
			'100.64.0.0-100.127.255.255', #RFC6598
			'192.0.0.0-192.255.255.255', #RFC6890
			'192.0.0.170/32', '192.0.0.171/32', #RFC7050
			'192.0.0.0-192.0.0.7', #RFC7335
			'192.52.193.0/24', #RFC7450
			'192.88.99.0-192.88.99.255', #RFC7526
			'192.175.48.0-192.175.48.255', #RFC7534
			'192.31.196.0-192.31.196.255', #RFC7535
			'192.0.0.8/32', #RFC7600
			'192.0.0.9/32', #RFC7723
			'192.0.0.10/32', #RFC8155

			'2001::/23', #RFC2928
			'2002::/16', #RFC3056
			'2001:28::/32', #RFC3849
			'fc00::/7', #RFC4193
			'::1/128', '::/128', '::ffff:0:0/96', 'fe80::/10', #RFC4291
			'2001::/32', #RFC4380
			'2001:10::/28', #RFC4843
			'2001:2::/48', #RFC5180
			'64:ff9b::/96', #RFC6052
			'100::/64', #RFC6666
			'2001:3::/32', #RFC7450
			'2620:4f:8000::/48', #RFC7534
			'2001:4:112::/48', #RFC7535
			'2001:1::1/128', #RFC7723
			'2001:5::/32', #RFC7954
			'2001:1::2/128', #RFC8155
		);
	}

	echo '<div style="display:inline-block;width:40%;text-align:center;vertical-align:top">';
	echo '<form method="POST" action="./">';
	echo '<textarea rows="25" cols="40" name="ranges">'.implode("\n",$input).'</textarea><br>';
	echo 'Display: <select name="format">';
	echo '<option value=""' . ((!$FORMAT) ? ' selected' : '') . '>12.34.56.0/24</option>';
	echo '<option value="blank"' . (($FORMAT == 'blank') ? ' selected' : '') . '>12.34.56 &nbsp; (or /xx)</option>';
	echo '<option value="dot"'   . (($FORMAT == 'dot'  ) ? ' selected' : '') . '>12.34.56. &nbsp;(or /xx)</option>';
	echo '<option value="star"'  . (($FORMAT == 'star' ) ? ' selected' : '') . '>12.34.56.* (or /xx)</option>';
	echo '<option value="cidr"'  . (($FORMAT == 'cidr' ) ? ' selected' : '') . '>12.34.56/24</option>';
	echo '<option value="range"' . (($FORMAT == 'range') ? ' selected' : '') . '>12.34.56.0-12.34.56.255</option>';
	echo '</select><br>';
	echo '<input type="submit">';
	echo '</div>';
	echo '<div style="display:inline-block;width:5%;text-align:center;vertical-align:middle">&rArr;</div>';
	echo '<div style="display:inline-block;width:40%;text-align:center;vertical-align:top"><textarea rows="25" cols="40" readonly>'.implode("\n",$output).'</textarea></div>';
} catch (Exception $e) {
	echo "<p>An error occurred: ".$e->getMessage()."</p>";
}
?>
</body>
</html>
