<?php

header('content-type: text/plain');

$rolls = array();

if (isset($_GET['spec']) && ($spec = $_GET['spec'])) {
    $orig = str_replace(' ', '+', $spec);
    try {
        $spec = '+' . $spec;
    
        $a = array();
        // NB: accept ' ' as '+', to make GET params easier
        while (preg_match('/\A([ +-])(?:(\d*)d(\d+)(\!?)|(\d+))(.*)/', $spec, $m)) {
            $op = $m[1];
            $d_n = $m[2];
            $d_f = $m[3];
            $drop = $m[4];
            $c = $m[5];
            $spec = $m[6];
    
            if ($d_n) {
                $d_n = $d_n == '' ? 1 : intval($d_n);
                $d_f = intval($d_f);
    
                $num_outcomes = pow($d_f, $d_n);
                if ($num_outcomes > 1000000) {
                    throw new Exception("too many outcomes in ${d_f}d${d_n}");
                }
    
                if ($num_outcomes * count($a) > 1000000) {
                    throw new Exception("too many outcomes");
                }
    
                // initialise temporary array with outcomes for a single die roll
                $tmp = array();
                for ($f = 1; $f <= $d_f; $f++) {
                    $tmp[] = array($f);
                }
    
                // for every subsequent die roll, cross-product the temporary array
                for ($i = 1; $i < $d_n; $i++) {
                    $tmp2 = array();
                    foreach ($tmp as $roll) {
                        for ($f = 1; $f <= $d_f; $f++) {
                            $tmp2[] = array_merge($roll, array($f));
                        }
                    }
                    $tmp = $tmp2;
                }
                
                // add the rolls
                if ($drop == '!') {
                    $tmp2 = array();
                    foreach ($tmp as $rr) {
                        $tmp2[] = array_sum($rr) - min($rr);
                    }
                } else {
                    $tmp2 = array();
                    foreach ($tmp as $rr) {
                        $tmp2[] = array_sum($rr);
                    }
                }
    
                // cross-product this set of outcomes into the existing array
                if (count($a) == 0) {
                    $a = $tmp2;
                } else {
                    $tmp = array();
                    foreach ($tmp2 as $v1) {
                        if ($op == '-') {
                            $v1 = -$v1;
                        }
                        foreach ($a as $v2) {
                            $tmp[] = $v1 + $v2;
                        }
                    }
                    $a = $tmp;
                }
            } else {
                $c = intval($c);
                if ($op == '-') {
                    $c = -$c;
                }
    
                $b = array();
                foreach ($a as $k => $v) {
                    $b[$k] = $v + $c;
                }
                $a = $b;
            }
        }
        if ($spec) {
            throw new Exception('unable to parse "'.htmlspecialchars($orig).'" at "'.htmlspecialchars($spec).'"');
        }
        $rolls[$orig] = $a;
    } catch (Exception $e) {
        echo $e;
    }
} else {
    $rolls['1d20'] = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20);
    
    $rolls['2d10'] = array();
    for ($a = 1; $a <= 10; $a++) {
    	for ($b = 1; $b <= 10; $b++) {
    		$rolls['2d10'][] = $a + $b;
    	}
    }
    
    $rolls['3d6'] = array();
    for ($a = 1; $a <= 6; $a++) {
    	for ($b = 1; $b <= 6; $b++) {
    		for ($c = 1; $c <= 6; $c++) {
    			$rolls['3d6'][] = $a + $b + $c;
    		}
    	}
    }
    
    $rolls['4d6 drop-the-lowest'] = array();
    for ($a = 1; $a <= 6; $a++) {
    	for ($b = 1; $b <= 6; $b++) {
    		for ($c = 1; $c <= 6; $c++) {
    			for ($d = 1; $d <= 6; $d++) {
    				$rolls['4d6 drop-the-lowest'][] = $a + $b + $c + $d - min($a, $b, $c, $d);
    			}
    		}
    	}
    }
    
    $rolls['5d4'] = array();
    for ($a = 1; $a <= 4; $a++) {
    	for ($b = 1; $b <= 4; $b++) {
    		for ($c = 1; $c <= 4; $c++) {
    		    for ($d = 1; $d <= 4; $d++) {
    		        for ($e = 1; $e <= 4; $e++) {
    			        $rolls['5d4'][] = $a + $b + $c + $d + $e;
    		        }
    		    }
    		}
    	}
    }
    
    $rolls['2d8+2'] = array();
    for ($a = 1; $a <= 8; $a++) {
    	for ($b = 1; $b <= 8; $b++) {
    		$rolls['2d8+2'][] = $a + $b + 2;
    	}
    }
    
    $rolls['3d8 drop-the-lowest +2'] = array();
    for ($a = 1; $a <= 8; $a++) {
    	for ($b = 1; $b <= 8; $b++) {
    		for ($c = 1; $c <= 8; $c++) {
    			$rolls['3d8 drop-the-lowest +2'][] = $a + $b + $c - min($a, $b, $c) + 2;
    		}
    	}
    }
    
    $rolls['4d4+2'] = array();
    for ($a = 1; $a <= 4; $a++) {
    	for ($b = 1; $b <= 4; $b++) {
    		for ($c = 1; $c <= 4; $c++) {
    			for ($d = 1; $d <= 4; $d++) {
    				$rolls['4d4+2'][] = $a + $b + $c + $d + 2;
    			}
    		}
    	}
    }
}

$maxes = array();
$hist  = array();
$means = array();
$modes = array();
$meds  = array();
$count = array();
$devs  = array();
foreach ($rolls as $key => $pop) {
	$hist[$key] = array();
	$s = 0;
	$n = 0;
	foreach ($pop as $p) {
		if (!isset($hist[$key][$p])) {
			$hist[$key][$p] = 0;
		}
		$hist[$key][$p] ++;

		$s += $p;
		$n ++;

		if (!isset($maxes[$key])) {
		    $maxes[$key] = $p;
		} elseif ($p > $maxes[$key]) {
		    $maxes[$key] = $p;
		}
	}
	$means[$key] = $s / $n;
	$count[$key] = $n;

	sort($pop);
	$meds[$key] = $pop[intval((count($pop) - 1) / 2)];
}
foreach ($rolls as $key => &$pop) {
	$m = $means[$key];
	$d = 0;
	$n = 0;
	foreach ($pop as $p) {
		$diff = $p - $m;
		$d += ($diff * $diff);
		$n ++;
	}
	$devs[$key] = sqrt($d / $n);
}
foreach ($hist as $key => &$hh) {
    $max_peak = array();
    $max_hist = -1;
    foreach ($hh as $r => $n) {
        if ($n > $max_hist) {
            $max_hist = $n;
            $max_peak = array($r);
        } elseif ($n == $max_hist) {
            $max_peak[] = $r;
        }
    }
    $modes[$key] = implode(',', $max_peak);
}

$__GRAPH_HEIGHT__ = 20;
foreach ($rolls as $key => $_) {
	echo "# $key\n\n";

    $WIDTH = max(20, $maxes[$key]);

	$h = &$hist[$key];
	$hist_peak = max($h);
	$increment = $hist_peak / $__GRAPH_HEIGHT__;

	for ($n = $__GRAPH_HEIGHT__; $n > 0; $n--) {
		$m = $n * $increment;
		$m2 = $m - $increment;
		printf(' %4.1f%%│', $m * 100 / $count[$key]);
		for ($r = 1; $r <= $WIDTH; $r++) {
			if ($h[$r] >= $m) {
				echo '██';
			} elseif ($h[$r] > $m2) {
			    $partial = ($h[$r] - $m2) / $increment;
			    if ($partial >= 0.5) {
			        echo '▄▄';
			    } else {
			        echo '__';
			    }
			} else {
				echo '  ';
			}
			echo ' ';
		}
		echo "\n";
	}

	echo '      └';
	for ($r = 1; $r <= $WIDTH; $r++) {
		echo '──┬';
	}
	echo "\n";

	echo '       ';
	for ($r = 1; $r <= $WIDTH; $r++) {
		printf('%2d ', $r);
	}
	echo "\n";

	echo "\n";
	#printf("Peak:    %d / %d\n", $hist_peak, count($_));
	printf("Mean:    %05.2f\n", $means[$key]);
	printf("Std.Dev: %5.2f\n", $devs[$key]);
	printf("Mode:    %s\n", $modes[$key]);
	printf("Median:  %d\n", $meds[$key]);
	echo "\n";
}

