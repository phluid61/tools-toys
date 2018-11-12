<?php

if (!function_exists('ip2long')) {
	function ip2long($ip) {
		if (preg_match('/^0*(1?\\d?\\d|2[0-4]\\d|25[0-5])\\.0*(1?\\d?\\d|2[0-4]\\d|25[0-5])\\.0*(1?\\d?\\d|2[0-4]\\d|25[0-5])\\.0*(1?\\d?\\d|2[0-4]\\d|25[0-5])$/', $ip, $m)) {
			return (intval($m[1]) << 24) + (intval($m[2]) << 16) + (intval($m[3]) << 8) + intval($m[4]);
		}
		return false;
	}
}
if (!function_exists('long2ip')) {
	function long2ip($long) {
		if ($long < 0 || $long > 0xffffffff) {
			return false;
		}
		return sprintf('%d.%d.%d.%d', ($long >> 24) & 0xff, ($long >> 16) & 0xff, ($long >> 8) & 0xff, $long & 0xff);
	}
}

function desperate2long($ip) {
	if (preg_match('/^0*(1?\\d?\\d|2[0-4]\\d|25[0-5])\\.0*(1?\\d?\\d|2[0-4]\\d|25[0-5])\\.0*(1?\\d?\\d|2[0-4]\\d|25[0-5])\\.?$/', $ip, $m)) {
		return (intval($m[1]) << 24) + (intval($m[2]) << 16) + (intval($m[3]) << 8) + 1;
	} elseif (preg_match('/^0*(1?\\d?\\d|2[0-4]\\d|25[0-5])\\.0*(1?\\d?\\d|2[0-4]\\d|25[0-5])\\.?$/', $ip, $m)) {
		return (intval($m[1]) << 24) + (intval($m[2]) << 16) + 1;
	} elseif (preg_match('/^0*(1?\\d?\\d|2[0-4]\\d|25[0-5])\\.?$/', $ip, $m)) {
		return (intval($m[1]) << 24) + 1;
	}
	return false;
}

?><!DOCTYPE html>
<html lang="en">
 <head>
  <meta charset="utf-8">
  <title>IPv4 Address Tool</title>
  <link rel="stylesheet" href="//s.library.qut.edu.au/css/minimal-blue-20180105.css">
  <link rel="icon" href="//s.library.qut.edu.au/favicon-20170112.ico">
  <style type="text/css">.ipv6{font-family:monospace;border:1px solid #e2e2ff}a.rfc{font-size:.8em}a.rfc::before{content:'['}a.rfc::after{content:']'}pre.rdns,pre.whois{border:1px solid #d8d8d8}.filter{margin:0 .5em}input:not(.filter)+.filter{margin-left:1em}</style>
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
  <h1>IPv4 Address Tool</h1>
<?php

$nonstandard = @$_GET['nonstandard'];
$rdns  = @$_GET['rdns'];
$whois = @$_GET['whois'];

if ($addr = @$_GET['addr']) {
	$long = ip2long($addr);
	if ($long === false) {
		$long = desperate2long($addr);
	}
	if ($long === false) {
		$addr = '';
	} else {
		$addr = long2ip($long);
	}
} else {
	$addr = '';
	$long = false;
	$rdns = 1;
	$whois = 1;
}

echo '<form method="GET" action="./">';
echo '<input type="string" name="addr" id="addr" value="'.($addr?$addr:$_SERVER['REMOTE_ADDR']).'">';
echo '<input type="submit">';
echo '<select class="filter" name="rdns" title="Perform a reverse DNS lookup on the address?"><option value=""'.((!$rdns)?' selected':'').'>no</option><option value="1"'.(($rdns==1)?' selected':'').'>short</option><option value="2"'.(($rdns&&$rdns!=1)?' selected':'').'>long</option></select>reverse DNS';
echo '<label class="filter" title="Perform a whois lookup on the address?"><input type="checkbox" name="whois" value="1"'.($whois?' checked':'').'>whois</label>';
echo '<label class="filter" title="If checked, calculate non-standard values (e.g. 6to4 network addresses for non-internet addresses)"><input type="checkbox" name="nonstandard" value="1"'.($nonstandard?' checked':'').'>non-standard</label>';
echo '</form>';

if ($addr) {
	echo '<p><b>Hex</b>: <tt>'.sprintf('%08x', $long).'</tt><br><b>Decimal</b>: '.$long.'</p>';

	echo '<p>';
	if (($long & 0xf0000000) == 0xf0000000) {
		echo '<b>Class E</b> - Experimental';
	} elseif (($long & 0xe0000000) == 0xe0000000) {
		echo '<b>Class D</b> - Multicast';
	} elseif (($long & 0xc0000000) == 0xc0000000) {
		echo '<b>Class C</b> - Small network';
	} elseif (($long & 0x80000000) == 0x80000000) {
		echo '<b>Class B</b> - Medium network';
	} else {
		echo '<b>Class A</b> - Very large network';
	}
	echo '</p>';

	$internet = true;
	$iana = '';
	if ($long == 0xffffffff) {
		$internet = false;
		$iana .= '<li>Limited Broadcast Address <a href="https://tools.ietf.org/html/rfc919" class="rfc">RFC 919</a></li>';
	}
	if (($long & 0xf0000000) == 0xf0000000) {
		$internet = false;
		$iana .= '<li>(Reserved) <a href="https://tools.ietf.org/html/rfc1112" class="rfc">RFC 1112</a></li>';
	}
	if (($long & 0xff000000) == 0x7f000000) {
		$internet = false;
		$iana .= '<li>Loopback: 127.0.0.0/8 <a href="https://tools.ietf.org/html/rfc1122" class="rfc">RFC 1122</a></li>';
	}
	if (($long & 0xff000000) == 0x00000000) {
		$internet = false;
		$iana .= '<li>"This host on this network": 0.0.0.0/8 <a href="https://tools.ietf.org/html/rfc1122" class="rfc">RFC 1122</a></li>';
	}
	if (($long & 0xff000000) == 0x0a000000) {
		$internet = false;
		$iana .= '<li>Private Use: 10.0.0.0/8 <a href="https://tools.ietf.org/html/rfc1918" class="rfc">RFC 1918</a></li>';
	}
	if (($long & 0xfff00000) == 0xac100000) {
		$internet = false;
		$iana .= '<li>Private Use: 172.16.0.0/12 <a href="https://tools.ietf.org/html/rfc1918" class="rfc">RFC 1918</a></li>';
	}
	if (($long & 0xffff0000) == 0xc0a80000) {
		$internet = false;
		$iana .= '<li>Private Use: 192.168.0.0/16 <a href="https://tools.ietf.org/html/rfc1918" class="rfc">RFC 1918</a></li>';
	}
	if (($long & 0xfffe0000) == 0xc6120000) {
		$internet = false;
		$iana .= '<li>Benchmarking: 198.18.0.0/15 <a href="https://tools.ietf.org/html/rfc2544" class="rfc">RFC 2544</a></li>';
	}
	if (($long & 0xffff0000) == 0xa9fe0000) {
		$internet = false;
		$iana .= '<li>Link Local: 169.254.0.0/16 <a href="https://tools.ietf.org/html/rfc3927" class="rfc">RFC 3927</a></li>';
	}
	if (($long & 0xffffff00) == 0xc0000200) {
		$internet = false;
		$iana .= '<li>Documentation (TEST-NET-1): 192.0.2.0/24 <a href="https://tools.ietf.org/html/rfc5737" class="rfc">RFC 5737</a></li>';
	}
	if (($long & 0xffffff00) == 0xc6336400) {
		$internet = false;
		$iana .= '<li>Documentation (TEST-NET-2): 198.51.100.0/24 <a href="https://tools.ietf.org/html/rfc5737" class="rfc">RFC 5737</a></li>';
	}
	if (($long & 0xffffff00) == 0xcb007100) {
		$internet = false;
		$iana .= '<li>Documentation (TEST-NET-3): 203.0.113.0/24 <a href="https://tools.ietf.org/html/rfc5737" class="rfc">RFC 5737</a></li>';
	}
	if (($long & 0xffc00000) == 0x64400000) {
		$internet = false;
		$iana .= '<li>Shared Address Space: 100.64.0.0/10 <a href="https://tools.ietf.org/html/rfc6598" class="rfc">RFC 6598</a></li>';
	}
	if (($long & 0xffffff00) == 0xc0000000) {
		$iana .= '<li>IETF Protocol: 192.0.0.0/24 <a href="https://tools.ietf.org/html/rfc6890" class="rfc">RFC 6890</a></li>';
	}
	if ($long == 0xc00000aa || $long == 0xc00000ab) {
		$iana .= '<li>NAT64/DNS64 Discovery <a href="https://tools.ietf.org/html/rfc7050" class="rfc">RFC 7050</a></li>';
	}
	if (($long & 0xfffffff8) == 0xc0000000) {
		$internet = false;
		$iana .= '<li>IPv4 Service Continuity: 192.0.0.0/29 <a href="https://tools.ietf.org/html/rfc7335" class="rfc">RFC 7335</a></li>';
	}
	if (($long & 0xffffff00) == 0xc034c100) {
		$iana .= '<li>Automatic Multicast Tunneling (AMT): 192.52.193.0/24 <a href="https://tools.ietf.org/html/rfc7450" class="rfc">RFC 7450</a></li>';
	}
	if (($long & 0xffffff00) == 0xc0586300) {
		$internet = false;
		$iana .= '<li><i>Deprecated</i> (6to4 Relay Anycast): 192.88.99.0/24 <a href="https://tools.ietf.org/html/rfc7526" class="rfc">RFC 7526</a></li>';
	}
	if (($long & 0xffffff00) == 0xc0af3000) {
		$internet = false;
		$iana .= '<li>Direct Delegation AS112 Service: 192.175.48.0/24 <a href="https://tools.ietf.org/html/rfc7534" class="rfc">RFC 7534</a></li>';
	}
	if (($long & 0xffffff00) == 0xc01fc400) {
		$internet = false;
		$iana .= '<li>AS112-v4: 192.31.196.0/24 <a href="https://tools.ietf.org/html/rfc7535" class="rfc">RFC 7535</a></li>';
	}
	if ($long == 0xc0000008) {
		$internet = false;
		$iana .= '<li>IPv4 Dummy Address <a href="https://tools.ietf.org/html/rfc7600" class="rfc">RFC 7600</a></li>';
	}
	if ($long == 0xc0000009) {
		$internet = false;
		$iana .= '<li>Port Control Protocol (PCP) Anycast Address <a href="https://tools.ietf.org/html/rfc7723" class="rfc">RFC 7723</a></li>';
	}
	if ($long == 0xc000000a) {
		$internet = false;
		$iana .= '<li>Traversal Using Relays around NAT (TURN) Anycast Address <a href="https://tools.ietf.org/html/rfc8155" class="rfc">RFC 8155</a></li>';
	}

	echo '<p><b>IANA IPv4 Special-Purpose Address:</b> <a href="https://www.iana.org/assignments/iana-ipv4-special-registry/iana-ipv4-special-registry.xhtml" class="rfc">IANA</a></p>';
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

	if ($internet || $nonstandard) {
		echo '<p><b>6to4</b>: <span class="ipv6">2002:'.dechex(($long >> 16) & 0xffff).':'.dechex($long & 0xffff).'::/48</span></p>';
		echo '<p><b>IPv4-mapped IPv6</b>: <span class="ipv6">::FFFF:'.dechex(($long >> 16) & 0xffff).':'.dechex($long & 0xffff).'</span> = <span class="ipv6 decimal">::FFFF:'.$addr.'</span></p>';
		echo '<p><b>IPv4-compatible IPv6</b>: <span class="ipv6">::'.dechex(($long >> 16) & 0xffff).':'.dechex($long & 0xffff).'</span></p>';
	}
	if ($rdns==1) {
		$host = @gethostbyaddr($addr);
		if ($host === false) {
			echo '<p><i>Failed reverse DNS lookup &ndash; bad address</i></p>';
		} elseif ($host == $addr) {
			echo '<p><i>Failed reverse DNS lookup</i></p>';
		} else {
			echo '<p><b>Reverse DNS</b>: <span class="rdns">'.$host.'</span>';
		}
	} elseif ($rdns) {
		@exec("nslookup $addr", $rdns, $retval);
		if (!$retval) {
			// FIXME
			echo '<p><b>Reverse DNS</b>:</p><pre class="rdns">';
			foreach ($rdns as $line) {
				echo htmlspecialchars(wordwrap($line,110,"\n",true)).'<br>';
			}
			echo '</pre>';
		} else {
			echo '<p><i>Failed reverse DNS lookup</i></p>';
		}
	}
	if ($whois) {
		$command = 'whois ';
		$command .= '--host whois.arin.net --port 43 ';
		#$command .= '--no-redirect ';
		$command .= '--raw ';
		#$command .= "'z $addr'";
		$command .= "'$addr'";
		@exec($command, $whois, $retval);
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
}

?>
</body>
</html>
