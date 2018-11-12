<?php

$morse = '';
$ascii = '';
$result = '';

$forward = array(
    '.-'    => 'A',
    '-...'  => 'B',
    '-.-.'  => 'C',
    '-..'   => 'D',
    '.'     => 'E',
    '..-.'  => 'F',
    '--.'   => 'G',
    '....'  => 'H',
    '..'    => 'I',
    '.---'  => 'J',
    '-.-'   => 'K',
    '.-..'  => 'L',
    '--'    => 'M',
    '-.'    => 'N',
    '---'   => 'O',
    '.--.'  => 'P',
    '--.-'  => 'Q',
    '.-.'   => 'R',
    '...'   => 'S',
    '-'     => 'T',
    '..-'   => 'U',
    '...-'  => 'V',
    '.--'   => 'W',
    '-..-'  => 'X',
    '-.--'  => 'Y',
    '--..'  => 'Z',
    '.----' => '1',
    '..---' => '2',
    '...--' => '3',
    '....-' => '4',
    '.....' => '5',
    '-....' => '6',
    '--...' => '7',
    '---..' => '8',
    '----.' => '9',
    '-----' => '0',
    '/'     => ' ',
);
$backward = array_flip($forward);

if (isset($_GET['morse']) && ($morse = $_GET['morse'])) {
    preg_match_all('/[\x20\x09]+|([\/.-]+)|(.*?)/', $morse, $m, PREG_SET_ORDER);
    $str = '';
    foreach ($m as $n) {
        if (isset($n[2]) && $n[2]) {
            $result .= '<p class="error">Unexpected text &lsquo;<code>'.htmlspecialchars($n[2]).'</code>&rsquo;</p>';
        } elseif (isset($n[1]) && $n[1]) {
            if (isset($forward[$n[1]]) && ($chr = $forward[$n[1]])) {
                $ascii .= $chr;
                $str .= $n[1];
            } else {
                $result .= '<p class="error">I don\'t recognise &lsquo;<code>'.htmlspecialchars($n[1]).'</code>&rsquo;</p>';
            }
        } elseif ($n[0]) {
            $str .= '  ';
        }
    }
    $result .= '<p><code class="morse">'.htmlspecialchars($str).'</code> is <code class="ascii">'.htmlspecialchars($ascii).'</code></p>';
} elseif (isset($_GET['ascii']) && ($ascii = strtoupper($_GET['ascii']))) {
    preg_match_all('/([\x20\x09]+)|([A-Z0-9])|(.*?)/', $ascii, $m, PREG_SET_ORDER);
    $arr = array();
    $str = '';
    foreach ($m as $n) {
        if (isset($n[3]) && $n[3]) {
            $result .= '<p class="error">I don\'t recognise &lsquo;<code>'.htmlspecialchars($n[2]).'</code>&rsquo;</p>';
        } elseif (isset($n[1]) && $n[1]) {
            $arr[] = '/';
            $str .= ' ';
        } elseif (isset($n[2]) && $n[2]) {
            if (isset($backward[$n[2]]) && ($chr = $backward[$n[2]])) {
                $arr[] = $chr;
                $str .= $n[2];
            } else {
                // BUG!
                $result .= '<p class="error">I don\'t recognise &lsquo;<code>'.htmlspecialchars($n[2]).'</code>&rsquo;</p>';
            }
        } elseif ($n[0]) {
            $str .= ' ';
        }
    }
    $morse = implode('  ', $arr);
    $result .= '<p><code class="ascii">'.htmlspecialchars($str).'</code> becomes <code class="morse">'.htmlspecialchars($morse).'</code></p>';
}

header('content-type: text/html');
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>Morse code &harr; ASCII</title>
        <style type="text/css">
            form{display:inline-block;margin:0.5em}
            input[type="text"]{width:20em}
            code.ascii{padding:0 0.5em;margin:0 0.5em;white-space:pre;background-color:#eee}
            code.morse{padding:0 0.5em;margin:0 0.5em;white-space:pre}
        </style>
    </head>
    <body>
<?php if ($result) { ?>
        <div><?php echo $result;?></div>
<?php } ?>

        <form method="GET" action="morse.php">
            <input type="text" name="ascii" value="<?php echo $ascii;?>">
            <input type="submit" value="to Morse code &rarr;">
        </form>
        <form method="GET" action="morse.php">
            <input type="submit" value="&larr; to ASCII">
            <input type="text" name="morse" value="<?php echo $morse;?>">
        </form>
    </body>
</html>
