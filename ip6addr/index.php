<?php

function _parse_ip6_hextets($str) {
	$hextets = array();
	if (!$str) { return $hextets; }
	foreach (explode(':',$str) as $c) {
		if (preg_match('/^[0-9a-f]{1,4}$/i', $c)) {
			$hextets[] = intval($c, 16);
		// FIXME: only the last two hextets can be encoded as IPv4
		} elseif (preg_match('/^0*(1?\\d?\\d|2[0-4]\\d|25[0-5])\\.0*(1?\\d?\\d|2[0-4]\\d|25[0-5])\\.0*(1?\\d?\\d|2[0-4]\\d|25[0-5])\\.0*(1?\\d?\\d|2[0-4]\\d|25[0-5])$/', $c, $m)) {
			$hextets[] = (intval($m[1]) << 8) + intval($m[2]);
			$hextets[] = (intval($m[3]) << 8) + intval($m[4]);
		} else {
			throw new Exception("Not an IPv6 chunk '$c'");
		}
	}
	return $hextets;
}

function ip6toarray($ip) {
	if (strpos($ip,'::') !== false) {
		list($before, $after) = explode('::', $ip, 2);
		$bhextets = _parse_ip6_hextets($before);
		$ahextets = _parse_ip6_hextets($after);
		if (count($bhextets) + count($ahextets) > 8) {
			throw new Exception("Too many hextets in '$ip'");
		}

		$hextets = array(0,0,0,0,0,0,0,0);
		foreach ($bhextets as $i=>$c) {
			$hextets[$i] = $c;
		}
		foreach (array_reverse($ahextets) as $i=>$c) {
			$hextets[7-$i] = $c;
		}
	} else {
		$hextets = _parse_ip6_hextets($ip);
		if (count($hextets) != 8) {
			throw new Exception("Wrong number of hextets in '$ip'");
		}
	}
	return $hextets;
}
function array2ip6($hextets) {
	if (($n=count($hextets)) != 8) {
		throw new Exception("Wrong number of hextets ($n, should be 8)");
	}
	$bits = array();
	foreach ($hextets as $l) {
		if ($l < 0 || $l > 0xffff) {
			throw new Exception("Not a hextet '$l'");
		}
		$bits[] = sprintf('%04x', $l);
	}
	return implode(':', $bits);
}

function squish($ip) {
	if (is_array($ip)) {
		$ip = array2ip6($ip);
	}
	$ip = preg_replace('/(^|:)0+([0-9a-f]+(?:|$))/i', '\\1\\2', $ip);
	$ip = preg_replace('/(^|:)(0(:|$)){2,}/', '::', $ip, 1);
	return $ip;
}

function maskbits($ip6, $bits, $as_array=false) {
	if (is_array($ip6)) {
		$longs = $ip6;
	} else {
		$longs = ip62array($ip6);
	}
	$numhex = intval($bits / 16);
	$numbit = $bits % 16;
	$results = array();
	for ($i = 0; $i < $numhex; $i++) {
		$results[] = $longs[$i];
	}
	if ($numbit) {
		$shift = 16 - $numbit;
		$mask = (0xffff >> $shift) << $shift;
		$results[$i] = $longs[$i] & $mask;
		$i++;
	}
	for (; $i < 8; $i++) {
		$results[] = 0;
	}
	if ($as_array) {
		return $results;
	}
	return squish(array2ip6($results));
}

?><!DOCTYPE html>
<html lang="en">
 <head>
  <meta charset="utf-8">
  <title>IPv6 Address Tool</title>
  <link rel="stylesheet" href="//s.library.qut.edu.au/css/minimal-blue-20180105.css">
  <link rel="icon" href="//s.library.qut.edu.au/favicon-20170112.ico">
  <style type="text/css">.ipv4,.ipv6{font-family:monospace;border:1px solid #e2e2ff}a.rfc{font-size:.8em}a.rfc::before{content:'['}a.rfc::after{content:']'}pre.rdns,pre.whois,fieldset,legend{border:1px solid #d8d8d8}fieldset{margin:1em 0;padding:0 0.3em}fieldset>legend{margin-left:0.5em;padding:0 0.5em;border-bottom:0}.filter{margin:0 .5em}input:not(.filter)+.filter{margin-left:1em}</style>
  <script type="text/javascript">//<![CDATA[
function select(id) {
  var e=document.getElementById(id);
  e.focus();
  e.setSelectionRange(0,e.value.length);
}
  //]]></script>
 </head>
 <body onload="javascript:select('addr')">
  <div id="masthead" role="navigation">
   <div id="qut">
    <a href="https://www.qut.edu.au/"><img src="//s.library.qut.edu.au/img/qut-20150421" srcset="//s.library.qut.edu.au/img/qut-s-20150806 32w, //s.library.qut.edu.au/img/qut-20150421 52w, //s.library.qut.edu.au/img/qut-2x-20150421 104w" sizes="52px" alt="QUT home"></a><img src="//s.library.qut.edu.au/img/white-bar-20150421" srcset="//s.library.qut.edu.au/img/white-bar-s-20150806 26w, //s.library.qut.edu.au/img/white-bar-20150421 42w, //s.library.qut.edu.au/img/white-bar-2x-20150421 84w" sizes="42px" alt=""><a href="/"><img src="//s.library.qut.edu.au/img/eisas-20180105" srcset="//s.library.qut.edu.au/img/eisas-s-20180105 62w, //s.library.qut.edu.au/img/eisas-20180105 100w, //s.library.qut.edu.au/img/eisas-2x-20180105 200w" sizes="100px" alt="QUT Library home page"></a>
   </div>
   <div id="strap">
     <img src="//static.library.qut.edu.au/img/strapline-20130415" srcset="//static.library.qut.edu.au/img/strapline-20130415 187w, //static.library.qut.edu.au/img/strapline-2x-20141024 374w" sizes="187px" alt="A university for the real world">
   </div>
  </div>
  <h1>IPv6 Address Tool</h1>
<?php

$rdns  = @$_GET['rdns'];
$whois = @$_GET['whois'];
$nonstandard = @$_GET['nonstandard'];

if ($addr = @$_GET['addr']) {
	try {
		$hextets = ip6toarray($addr);
		$addr = array2ip6($hextets);
		$short = squish($addr);
	} catch (Exception $e) {
		echo '<pre>'.var_export($e,1).'</pre>';
		$addr = '';
		$short = false;
		$hextets = false;
	}
} else {
	$addr = '';
	$short = false;
	$hextets = false;
	$rdns = 1;
	$whois = 1;
}

echo '<form method="GET" action="./">';
echo '<input type="string" name="addr" id="addr" value="'.($addr?$short:('::'.$_SERVER['REMOTE_ADDR'])).'">';
echo '<input type="submit">';
echo '<label class="filter" title="Perform a reverse DNS lookup on the address?"><input type="checkbox" name="rdns" value="1"'.($rdns?' checked':'').'>reverse DNS</label>';
echo '<label class="filter" title="Perform a whois lookup on the address?"><input type="checkbox" name="whois" value="1"'.($whois?' checked':'').'>whois</label>';
echo '<label class="filter" title="If checked, calculate non-standard values (e.g. interface identifiers for multicast addresses)"><input type="checkbox" name="nonstandard" value="1"'.($nonstandard?' checked':'').'>non-standard</label>';
echo '</form>';

if ($addr) {
	echo '<p><b>Full</b>: <span class="ipv6">'.$addr.'</span><br><b>Compact</b>: <span class="ipv6">'.$short.'</span></p>';

	$internet = true;
	$unicast  = true;
	$multicast = false;
	$linklocal = false;
	$iana = '';
	$mask104= maskbits($hextets,104);
	$mask96 = maskbits($hextets, 96);
	$mask32 = maskbits($hextets, 32);
	$mask28 = maskbits($hextets, 28);
	$mask16 = maskbits($hextets, 16);
	$mask10 = maskbits($hextets, 10);
	$mask8  = maskbits($hextets,  8);
	$mask7  = maskbits($hextets,  7);
	if ($short == '::') {
		$internet = false;
		$unicast = false;
		$iana .= '<li>Unspecified address <a href="https://tools.ietf.orghtml/rfc4291" class="rfc">RFC 4291</a></li>';
	}
	if ($short == '::1') {
		$internet = false;
		$unicast = false;
		$linklocal = true;
		$iana .= '<li>Loopback address <a href="https://tools.ietf.orghtml/rfc4291" class="rfc">RFC 4291</a></li>';
	}
	if ($mask96 == '::ffff:0:0') {
		$internet = false;
		$iana .= '<li>IPv4-Mapped Address <a href="https://tools.ietf.orghtml/rfc4291" class="rfc">RFC 4291</a></li>';
	}
	if ($mask96 == '::') {
		// FIXME: ::, ::1, etc?
		$internet = false;
		$iana .= '<li>IPv4-Compatible Address (deprecated) <a href="https://tools.ietf.orghtml/rfc4291" class="rfc">RFC 4291</a></li>';
	}
	if ($mask10 == 'fe80::') {
		$internet = false;
		$iana .= '<li>Link-Scoped Unicast Address <a href="https://tools.ietf.orghtml/rfc4291" class="rfc">RFC 4291</a></li>';
	}
	if ($mask7 == 'fc00::') {
		$internet = false; // with exceptions
		$iana .= '<li>Unique-Local Address <a href="https://tools.ietf.orghtml/rfc4193" class="rfc">RFC 4193</a></li>';
	}
	if ($mask32 == '2001:db8::') {
		$internet = false;
		$iana .= '<li>Documentation Address <a href="https://tools.ietf.orghtml/rfc3849" class="rfc">RFC 3849</a></li>';
	}
	if ($mask16 == '2002::') {
		// FIXME: exclude RFC3964, Section 5.3.1
		$internet = false;
		$iana .= '<li>6to4 Address <a href="https://tools.ietf.orghtml/rfc3056" class="rfc">RFC 3056</a></li>';
	}
	if ($mask32 == '2001::') {
		$internet = false;
		$iana .= '<li>Teredo Address <a href="https://tools.ietf.orghtml/rfc4380" class="rfc">RFC 4380</a></li>';
	}
	if ($mask8 == '5f00::') {
		$iana .= '<li>6bone, Original Series <a href="https://tools.ietf.orghtml/rfc1897" class="rfc">RFC 1897</a></li>';
	}
	if ($mask16 == '3ffe::') {
		$iana .= '<li>6bone, Next Generation <a href="https://tools.ietf.orghtml/rfc2471" class="rfc">RFC 2471</a></li>';
	}
	if ($mask28 == '2001:10::') {
		$internet = false;
		$iana .= '<li>Overlay Routable Cryptographic Hash IDentifiers (ORCHID) <a href="https://tools.ietf.orghtml/rfc4843" class="rfc">RFC 4843</a></li>';
	}
	// TODO: iana-ipv6-special-registry
	if ($mask8 == 'ff00::') {
		$unicast = false;
		$multicast = true;
		$iana .= '<li>Multicast address <a href="https://tools.ietf.orghtml/rfc4291" class="rfc">RFC 4291</a></li>';
	}

	echo '<p><b>IANA IPv6 Special-Purpose Address:</b> <a href="https://www.iana.org/assignments/iana-ipv6-special-registry/iana-ipv6-special-registry.xhtml" class="rfc">IANA</a></p>';
	echo '<ul>';
	if ($iana) {
		echo $iana;
	} else {
		echo '<li>(Not special)</li>';
	}
	echo '</ul>';

	if (!$internet) {
		echo '<p>(Not to be routed on the open internet.)</p>';
	}

	if ($hextets[0] == 0x2002) {
		$ip4 = long2ip($hextets[1] << 16 | $hextets[2]);
		$subnet = $hextets[3];
		$host = array(0,0,0,0,$hextets[4],$hextets[5],$hextets[6],$hextets[7]);
		echo '<p><b>6to4</b>: <span class="ipv4">'.$ip4.'</span> &larr; <span class="ipv6">'.squish(array2ip6($host)).($subnet ? '/'.$subnet : '').'</span></p>';
	}
	if ($mask96 == '::ffff:0:0') {
		$ip4 = long2ip($hextets[6] << 16 | $hextets[7]);
		echo '<p><b>IPv4-mapped IPv6</b>: <span class="ipv4">'.$ip4.'</span></p>';
	}
	if ($mask96 == '::') {
		// FIXME: also includes '::' and '::1', and friends
		$ip4 = long2ip($hextets[6] << 16 | $hextets[7]);
		echo '<p><b>IPv4-compatible IPv6</b>: <span class="ipv4">'.$ip4.'</span></p>';
	}

	if ($unicast || $nonstandard) {
		echo '<p><b>Unicast/Anycast</b>';
		if (!$unicast) echo ' <i>(non-standard interpretation)</i>';
		echo '</p><fieldset>';
		if ($hextets[0] & 0xe000 != 0) {
			echo '<legend>Modified EUI-64 IID</legend>';
		} else {
			echo '<legend><i>Unknown prefix length; assuming 64-bit</i></legend>';
		}
		$net_prefix = ($hextets[0] << 48) | ($hextets[1] << 32) | ($hextets[2] << 16) | $hextets[3];
		$interface  = ($hextets[4] << 48) | ($hextets[5] << 32) | ($hextets[6] << 16) | $hextets[7];
		echo '<p><b>Network prefix</b>: '.sprintf('%016x', $net_prefix).'<br><b>Interface ID</b>: '.sprintf('%016x', $interface).'</p>';

		// interpret as IEEE EUI-64 id
		$c = (($hextets[4] & 0xfc00) << 6) | (($hextets[4] & 0xff) << 8) | (($hextets[5] & 0xff00) >> 8);
		$u =  ($hextets[4] & 0x0200);
		$g =  ($hextets[4] & 0x0100);
		$m = $interface & 0xffffffffff;
		echo '<p>Interpreting Interface ID as IEEE EUI-64 id: company='.sprintf('%x',$c).', universal='.($u?'0':'1').', group='.($g?'0':'1').', mfr.id='.sprintf('%x',$m).'</p>';

		// maybe interpret as IEEE 802 48-bit MAC
		if (($m & 0xffff000000) == 0xfffe000000) {
			$mac = $m & 0xffffff;
			$mac = sprintf('%06x', $mac);
			$mac = chunk_split($mac, 2, ':');
			echo '<p>Interpreting Interface ID as IEEE 802 48-bit MAC: '.$mac.'</p>';
		}

		echo '</fieldset>';
	} elseif ($linklocal) {
		echo '<p><b>Link-Local Unicast</b></p><fieldset>';
		$interface  = ($hextets[4] << 48) | ($hextets[5] << 32) | ($hextets[6] << 16) | $hextets[7];
		echo '<p><b>Interface ID</b>: '.sprintf('%016x', $interface).'</p>';

		// interpret as IEEE EUI-64 id
		$c = (($hextets[4] & 0xfc00) << 6) | (($hextets[4] & 0xff) << 8) | (($hextets[5] & 0xff00) >> 8);
		$u =  ($hextets[4] & 0x0200);
		$g =  ($hextets[4] & 0x0100);
		$m = $interface & 0xffffffffff;
		echo '<p>Interpreting Interface ID as IEEE EUI-64 id: company='.sprintf('%x',$c).', universal='.($u?'0':'1').', group='.($g?'0':'1').', mfr.id='.sprintf('%x',$m).'</p>';

		// maybe interpret as IEEE 802 48-bit MAC
		if (($m & 0xffff000000) == 0xfffe000000) {
			$mac = $m & 0xffffff;
			$mac = sprintf('%06x', $mac);
			$mac = chunk_split($mac, 2, ':');
			echo '<p>Interpreting Interface ID as IEEE 802 48-bit MAC: '.$mac.'</p>';
		}

		echo '</fieldset>';
	}

	if ($multicast || $nonstandard) {
		$flag1 = ($hextets[0] >> 4) & 0xf;
		$scope = ($hextets[0] & 0xf);

		echo '<p><b>Multicast</b>';
		if (!$multicast) echo ' <i>(non-standard interpretation)</i>';
		echo '</p><fieldset>';
		echo '<p><b>Flags</b>: '.(($flag1&8)?'<i>X</i>':'_').(($flag1&4)?'R':'_').(($flag1&2)?'P':'_').(($flag1&1)?'T':'_').'</p>';
		switch ($scope) {
		case 0x0: //reserved
		case 0xf:
			echo '<p><b>**RESERVED SCOPE**</b></p>';
			break;
		case 0x1: //interface-local = loopback
			echo '<p><b>Scope</b>: interface-local (127.0.0.0/8)</p>';
			break;
		case 0x2: //link-local
			echo '<p><b>Scope</b>: link-local (224.0.0.0/24)</p>';
			// solicited-node?
			break;
		case 0x3: //realm-local (RFC7346)
			echo '<p><b>Scope</b>: realm-local (239.255.0.0/16)</p>';
			break;
		case 0x4: //admin-local
			echo '<p><b>Scope</b>: admin-local</p>';
			break;
		case 0x5: //site-local
			echo '<p><b>Scope</b>: site-local</p>';
			break;
		case 0x8: // organization-local
			echo '<p><b>Scope</b>: organization-local (239.192.0.0/14)</p>';
			break;
		case 0xe: //global
			echo '<p><b>Scope</b>: global (224.0.1.0&mdash;238.255.255.255)</p>';
			break;
		default:
			echo '<p><b>??UNKNOWN SCOPE??</b></p>';
		}
		echo '</fieldset>';

		echo '<fieldset><legend>Old Mode [RFC 2373]</legend>';
		echo '<p><b>Group ID</b>: ';
		for ($i = 1; $i < 8; $i++) {
			printf('%04x', $hextets[$i]);
		}
		echo '</p></fieldset>';

		$flag2 = ($hextets[1] >> 12);
		$reserved = ($hextets[1] >> 8) & 0xf;
		$plen  = ($hextets[1] & 0xff);
		$net_prefix = ($hextets[2] << 48) | ($hextets[3] << 32) | ($hextets[4] << 16) | $hextets[5];
		$group_id = ($hextets[6] << 16) | $hextets[7];
		echo '<fieldset><legend>New Mode [RFC 3306+7371]</legend>';
		echo '<p><b>Flags 2</b>: '.(($flag1&8)?'r':'_').(($flag1&4)?'r':'_').(($flag1&2)?'r':'_').(($flag1&1)?'r':'_').'</p>';
		echo '<p><b>Prefix Length</b>: '.(($flag1&2)?$plen:('unused ('.$plen.')')).'</p>';
		if (($flag1 & 2) && $plen == 0 && $net_prefix == 0) {
			echo '<p>Source-Specific Multicast Address [RFC 3306]</p>';
		}
		if ($flag1 == 0x7) {
			echo '<p>Modified Unicase-Prefix-based Address Format [RFC 3956]: ';
			$riid = ($hextets[1] >> 8) & 0xf; // MUST be sent as zero and MUST be ignored on receipt
			// $plen MUST NOT be zero, and MUST NOT be greater than 64
			$rp = array($hextets[2], $hextets[3], $hextets[4], $hextets[5], 0, 0, 0, 0);
			$rp = maskbits($rp, $plen, true);
			$rp[7] |= $riid;
			echo 'RP=<span class="ipv6">'.squish($rp).'</span></p>';
		}
		echo '</fieldset>';

		echo '<fieldset>';
		preg_match('/^ff0[0-9a-f]::([0-9a-f:]*)$/', $short, $mc_match);
		if ($mc_match && $mc_match[1] == '') {
			echo '<p>Reserved multicase address</p>';
		} elseif ($short == 'ff01::1') {
			echo '<p>All nodes on the local interface</p>';
		} elseif ($short == 'ff02::1') {
			echo '<p>All nodes on the local network segment</p>';
		} elseif ($short == 'ff01::2') {
			echo '<p>All routers on the local interface</p>';
		} elseif ($short == 'ff02::2') {
			echo '<p>All routers on the local network segment</p>';
		} elseif ($short == 'ff05::2') {
			echo '<p>All routers on the local site</p>';
		} elseif ($short == 'ff02::5') {
			echo '<p>OSPFv3 All SPF routers</p>';
		} elseif ($short == 'ff02::6') {
			echo '<p>OSPFv3 All DR routers</p>';
		} elseif ($short == 'ff02::8') {
			echo '<p>IS-IS for IPv6 routers</p>';
		} elseif ($short == 'ff02::9') {
			echo '<p>RIP routers</p>';
		} elseif ($short == 'ff02::a') {
			echo '<p>EIGRP routers</p>';
		} elseif ($short == 'ff02::d') {
			echo '<p>PIM routers</p>';
		} elseif ($short == 'ff02::16') {
			echo '<p>MLDv2 reports [RFC 3810]</p>';
		} elseif ($short == 'ff02::1:2') {
			echo '<p>All DHCP servers and relay agents on the local network segment [RFC 3315]</p>';
		} elseif ($short == 'ff02::1:3') {
			echo '<p>All LLMNR hosts on the local network segment [RFC 4795]</p>';
		} elseif ($short == 'ff05::1:3') {
			echo '<p>All DHCP servers on the local network site [RFC 3315]</p>';
		} elseif ($mc_match && $mc_match[1] == 'c') {
			echo '<p>Simple Service Discovery Protocol</p>';
		} elseif ($mc_match && $mc_match[1] == 'fb') {
			echo '<p>Multicast DNS</p>';
		} elseif ($mc_match && $mc_match[1] == '101') {
			echo '<p>Network Time Protocol</p>';
		} elseif ($mc_match && $mc_match[1] == '108') {
			echo '<p>Network Information Service</p>';
		} elseif ($mc_match && $mc_match[1] == '181') {
			echo '<p>Precision Time Protocol (PTP) version 2 messages (Sync, Announce, etc.) except peer delay measurement</p>';
		} elseif ($short == 'ff02::6b') {
			echo '<p>Precision Time Protocol (PTP) version 2 peer delay measurement messages</p>';
		} elseif ($mc_match && $mc_match[1] == '114') {
			echo '<p>Used for experiments</p>';
		} elseif ($flag1 == 0x3 && $scope <= 2 && $hextets[1] == 0x00ff) {
			// Link-Scoped Multicast Address [RFC 4489]
			echo '<p><b>Link-Scoped Multicast Address</b>: IID='.sprintf('%08x',$net_prefix).', group ID='.sprintf('%04x', $groupid).'</p>';
		} elseif ($mask104 == 'ff02::1:ff00:0') {
			echo '<p><b>Solicited-Node multicast address</b>: address suffix='.sprintf('%02x:%04x', $hextets[6]&0xff, $hextets[7]).'</p>';
		}
		echo '</fieldset>';
	}

	if ($rdns) {
		$host = @gethostbyaddr($addr);
		if ($host === false) {
			echo '<p><i>Failed reverse DNS lookup &ndash; bad address</i></p>';
		} elseif ($host == $addr) {
			echo '<p><i>Failed reverse DNS lookup</i></p>';
		} else {
			echo '<p><b>Reverse DNS</b>: <span class="rdns">'.$host.'</span>';
		}
	}

/*
	if ($whois) {
		@exec("whois -a $addr", $whois, $retval);
		if (!$retval) {
			echo '<p><b>Whois</b>:</p><pre class="whois">';
			foreach ($whois as $line) {
				if (preg_match('/^(.*?\s)([\\d.]+)(\s*-\s*)([\\d.]+)((?:\s.*)?)$/', $line, $m)) {
					echo htmlspecialchars($m[1]) . '<a href="../iprange?ranges='.$m[2].'-'.$m[4].'" target="_blank">'.htmlspecialchars($m[2].$m[3].$m[4]).'</a>'.htmlspecialchars($m[5]).'<br>';
				} elseif (preg_match('/^(.*?\s)([\\d.]+\\/\\d+)((?:\s.*)?)$/', $line, $m)) {
					echo htmlspecialchars($m[1]) . '<a href="../iprange?ranges='.$m[2].'" target="_blank">'.htmlspecialchars($m[2]).'</a>'.htmlspecialchars($m[3]).'<br>';
				} elseif (strlen($line) > 120) {
					echo htmlspecialchars(wordwrap($line,110,"\n",true)).'<br>';
				} else {
					echo htmlspecialchars($line).'<br>';
				}
			}
			echo '</pre>';
		} else {
			echo '<p><i>Failed whois lookup</i></p>';
		}
	}
*/
}

?>
</body>
</html>
