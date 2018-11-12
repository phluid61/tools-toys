<?php

header('content-type: text/html;charset=utf-8');

function natural_join($arr) {
	if (count($arr) > 2) {
		$_last = count($arr) - 1;
		$arr[$_last] = 'and ' . $arr[$_last];
		return implode(', ', $arr);
	} else {
		return implode(' and ', $arr);
	}
}

function selected($cond) {
	if ($cond) {
		echo 'selected="selected"';
	}
}

function checked($cond) {
	if ($cond) {
		echo 'checked="checked"';
	}
}

function type_form() {
	global $type;
?>
      <select name="type">
       <option value="-" <?php selected($type == '-');?>>file</option>
       <option value="d" <?php selected($type == 'd');?>>directory</option>
       <option value="l" <?php selected($type == 'l');?>>link</option>
       <option value="p" <?php selected($type == 'p');?>>named pipe</option>
       <option value="s" <?php selected($type == 's');?>>socket</option>
       <option value="c" <?php selected($type == 'c');?>>character device</option>
       <option value="b" <?php selected($type == 'b');?>>block device</option>
       <option value="D" <?php selected($type == 'D');?>>door</option>
      </select>
<?php
}

function describe($type) {
	switch ($type) {
	case '-': return 'file';
	case 'd': return 'directory';
	case 'l': return 'link';
	case 'p': return 'named pipe';
	case 's': return 'socket';
	case 'c': return 'character device';
	case 'b': return 'block device';
	case 'D': return 'door';
	}
}

function bits_to_numeric() {
	global $numeric;
	global $attr_setuid, $attr_setgid, $attr_sticky;
	global $user_r, $user_w, $user_x;
	global $group_r, $group_w, $group_x;
	global $others_r, $others_w, $others_x;

	$num = 0;

	$num |= ($attr_setuid ? 04000 : 0);
	$num |= ($attr_seguid ? 02000 : 0);
	$num |= ($attr_sticky ? 01000 : 0);

	$num |= ($user_r ? 0400 : 0);
	$num |= ($user_w ? 0200 : 0);
	$num |= ($user_x ? 0100 : 0);

	$num |= ($group_r ? 040 : 0);
	$num |= ($group_w ? 020 : 0);
	$num |= ($group_x ? 010 : 0);

	$num |= ($others_r ? 04 : 0);
	$num |= ($others_w ? 02 : 0);
	$num |= ($others_x ? 01 : 0);

	$numeric = $num;
}

function bits_to_symbolic() {
	global $symbolic;
	global $type;
	global $attr_setuid, $attr_setgid, $attr_sticky;
	global $user_r, $user_w, $user_x;
	global $group_r, $group_w, $group_x;
	global $others_r, $others_w, $others_x;

	$str = '';
	$str .= $type;
	$str .= ($user_r ? 'r' : '-');
	$str .= ($user_w ? 'w' : '-');
	$str .= ($user_x ? ($attr_setuid ? 's' : 'x') : ($attr_setuid ? 'S' : '-'));
	$str .= ($group_r ? 'r' : '-');
	$str .= ($group_w ? 'w' : '-');
	$str .= ($group_x ? ($attr_setgid ? 's' : 'x') : ($attr_setgid ? 'S' : '-'));
	$str .= ($others_r ? 'r' : '-');
	$str .= ($others_w ? 'w' : '-');
	$str .= ($others_x ? ($attr_sticky ? 't' : 'x') : ($attr_sticky ? 'T' : '-'));

	$symbolic = $str;
}

function numeric_to_bits() {
	global $numeric;
	global $attr_setuid, $attr_setgid, $attr_sticky;
	global $user_r, $user_w, $user_x;
	global $group_r, $group_w, $group_x;
	global $others_r, $others_w, $others_x;

	$attr_setuid = $numeric & 04000;
	$attr_setgid = $numeric & 02000;
	$attr_sticky = $numeric & 01000;

	$user_r = $numeric & 0400;
	$user_w = $numeric & 0200;
	$user_x = $numeric & 0100;

	$group_r = $numeric & 040;
	$group_w = $numeric & 020;
	$group_x = $numeric & 010;

	$others_r = $numeric & 04;
	$others_w = $numeric & 02;
	$others_x = $numeric & 01;
}

$err = '';
$rand = true;
if (isset($_GET['bits'])) {
	$type = $_GET['type'];

	$attr_setuid = $_GET['attr_setuid'];
	$attr_setgid = $_GET['attr_setgid'];
	$attr_sticky = $_GET['attr_sticky'];

	$user_r = $_GET['user_r'];
	$user_w = $_GET['user_w'];
	$user_x = $_GET['user_x'];

	$group_r = $_GET['group_r'];
	$group_w = $_GET['group_w'];
	$group_x = $_GET['group_x'];

	$others_r = $_GET['others_r'];
	$others_w = $_GET['others_w'];
	$others_x = $_GET['others_x'];

	bits_to_numeric();
	bits_to_symbolic();

	$rand = false;
} elseif (isset($_GET['symbolic'])) {
	if (preg_match('/\A([-dlpscbD]?)([r-])([w-])([sSx-])([r-])([w-])([sSx-])([r-])([w-])([tTx-])[+.@]?\z/', $_GET['symbolic'], $m)) {
		$type = $m[1] ? $m[1] : '-';

		$attr_setuid = ($m[4] == 's' || $m[4] == 'S');
		$attr_setgid = ($m[7] == 's' || $m[7] == 'S');
		$attr_sticky = ($m[10] == 't' || $m[10] == 'T');

		$user_r = ($m[2] == 'r');
		$user_w = ($m[3] == 'w');
		$user_x = ($m[4] == 'x' || $m[4] == 's');

		$group_r = ($m[5] == 'r');
		$group_w = ($m[6] == 'w');
		$group_x = ($m[7] == 'x' || $m[7] == 's');

		$others_r = ($m[8] == 'r');
		$others_w = ($m[9] == 'w');
		$others_x = ($m[10] == 'x' || $m[10] == 't');

		bits_to_numeric();
		$symbolic = $type . substr($_GET['symbolic'], -9);

		$rand = false;
	} else {
		$err = '<code>'.htmlspecialchars($_GET['symbolic']).'</code> is not a valid symbolic representation';
	}
} elseif (isset($_GET['numeric'])) {
	if (preg_match('/\A([0-7]?)([0-7])([0-7])([0-7])\z/', $_GET['numeric'], $m)) {
		$type = $_GET['type'];

		$attr   = intval($m[1] ? $m[1] : '0');
		$user   = intval($m[2]);
		$group  = intval($m[3]);
		$others = intval($m[4]);

		$attr_setuid = $attr & 4;
		$attr_setgit = $attr & 2;
		$attr_sticky = $attr & 1;

		$user_r = $user & 4;
		$user_w = $user & 2;
		$user_x = $user & 1;

		$group_r = $group & 4;
		$group_w = $group & 2;
		$group_x = $group & 1;

		$others_r = $others & 4;
		$others_w = $others & 2;
		$others_x = $others & 1;

		$numeric = intval($_GET['numeric'], 8);
		bits_to_symbolic();

		$rand = false;
	} else {
		$err = '<code>'.htmlspecialchars($_GET['numeric']).'</code> is not a valid numeric representation';
	}
}

if ($rand) {
	$types = array(
		'-','-','-','-','-','-','-','-','-','-',
		'd','d','d','d','d','d','d','d','d','d',
		'l','l','l','l','l',
		'p',
		's',
		'c',
		'b',
		'D',
	);
	$type = $types[rand(0, count($types) - 1)];
	$numeric = rand(0, 07777);
	numeric_to_bits();
	bits_to_symbolic();
}

?><!DOCTYPE html>
<html lang="en">
 <head>
  <meta charset="utf-8">
  <title>UNIX File Permissions Tool</title>
  <link rel="stylesheet" href="//s.library.qut.edu.au/css/minimal-blue-20180105.css">
  <link rel="icon" href="//s.library.qut.edu.au/favicon-20170112.ico">
  <style type="text/css">.mono{font-family:monospace}fieldset{margin:0.5em 0;padding:0.5em;border:1px solid #d8d8d8;border-radius:0.5em}fieldset>legend{padding:0 0.5em;border:1px solid #d8d8d8;border-bottom:0;border-radius:5px 5px 0 0}table{border-collapse:collapse}th,td{padding:0 0.125em}th[colspan]{border:1px solid #d8d8d8;border-width:0 1px}th:not([colspan]){border:1px solid #d8d8d8;border-top:0}td{text-align:center}}</style>
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
  <h1>UNIX File Permissions Tool</h1>

<fieldset><legend>Bits</legend>
  <form action="." method="GET">
   <input type="hidden" name="bits" value="1">

   <table>
    <tr>
     <th rowspan="2">type</th>
     <th colspan="3">attrs</th>
     <th colspan="3">user</th>
     <th colspan="3">group</th>
     <th colspan="3">others</th>
    </tr>
    <tr>
     <th>SUID</th>
     <th>SGID</th>
     <th>sticky</th>

     <th>r</th>
     <th>w</th>
     <th>x</th>

     <th>r</th>
     <th>w</th>
     <th>x</th>

     <th>r</th>
     <th>w</th>
     <th>x</th>
    </tr>
    <tr>
     <td>
<?php type_form(); ?>
     </td>
     <td>
      <input type="checkbox" value="1" name="attr_setuid" <?php checked($attr_setuid);?>>
     </td>
     <td>
      <input type="checkbox" value="1" name="attr_setgid" <?php checked($attr_setgid);?>>
     </td>
     <td>
      <input type="checkbox" value="1" name="attr_sticky" <?php checked($attr_sticky);?>>
     </td>
     <td>
      <input type="checkbox" value="1" name="user_r" <?php checked($user_r);?>>
     </td>
     <td>
      <input type="checkbox" value="1" name="user_w" <?php checked($user_w);?>>
     </td>
     <td>
      <input type="checkbox" value="1" name="user_x" <?php checked($user_x);?>>
     </td>
     <td>
      <input type="checkbox" value="1" name="group_r" <?php checked($group_r);?>>
     </td>
     <td>
      <input type="checkbox" value="1" name="group_w" <?php checked($group_w);?>>
     </td>
     <td>
      <input type="checkbox" value="1" name="group_x" <?php checked($group_x);?>>
     </td>
     <td>
      <input type="checkbox" value="1" name="others_r" <?php checked($others_r);?>>
     </td>
     <td>
      <input type="checkbox" value="1" name="others_w" <?php checked($others_w);?>>
     </td>
     <td>
      <input type="checkbox" value="1" name="others_x" <?php checked($others_x);?>>
     </td>
    </tr>
   </table>

   <input type="submit">
  </form>
</fieldset>

<fieldset><legend>Symbolic</legend>
  <form action="." method="GET">
   <input type="text" class="mono" name="symbolic" value="<?php echo $symbolic;?>">
   <input type="submit">
  </form>
</fieldset>

<fieldset><legend>Numeric</legend>
  <form action="." method="GET">
<?php type_form(); ?>
   <input type="text" class="mono" name="numeric" value="<?php printf('%04o', $numeric);?>">
   <input type="submit">
  </form>
</fieldset>

  <h3>User</h3>
<?php
$user = array();
if ($user_r) $user[] = 'read';
if ($user_w) $user[] = 'write';
if ($user_x) $user[] = ($type == 'd' ? 'list/navigate into' : ($type == 'l' ? 'follow' : 'execute'));
if ($user) {
	echo '<p>The owner of the '.describe($type).' can '.natural_join($user).' it.</p>';
} else {
	echo '<p>The owner of the '.describe($type).' cannot access it at all.</p>';
}
if ($attr_setuid) {
	echo '<p>Additionally the SUID bit is set, ';
	if ($type == '-') {
		if ($user_x || $group_x || $others_x) {
			echo 'which means when it\'s executed the process will run with this file\'s user ID, not the executing user\'s.  In modern shells this usually only affects binary executables, not scripts.';
		} else {
			echo 'which has no effect on non-executable files.';
		}
	} elseif ($type == 'd') {
		echo 'which normally has no effect, but on some systems means new files and subdirectories created in this directory will inherit its user ID.';
	} else {
		echo 'which means different things for different resource types.';
	}
	echo '</p>';
}
?>

  <h3>Group</h3>
<?php
$group = array();
if ($group_r) $group[] = 'read';
if ($group_w) $group[] = 'write';
if ($group_x) $group[] = ($type == 'd' ? 'list/navigate into' : ($type == 'l' ? 'follow' : 'execute'));
if ($group) {
	echo '<p>A member of the '.describe($type).'\'s group other than its owner can '.natural_join($group).' it.</p>';
} else {
	echo '<p>A member of the '.describe($type).'\'s group other than its owner cannot access it at all.</p>';
}
if ($attr_setgid) {
	echo '<p>Additionally the SGID bit is set, ';
	if ($type == '-') {
		if ($user_x || $group_x || $others_x) {
			echo 'which means when it\'s executed, the running process will run with this file\'s group ID, not the executing user\'s.';
		} else {
			echo 'which has no effect on non-executable files.';
		}
	} elseif ($type == 'd') {
		echo 'which means new files and subdirectories created in this directory will inherit its user ID.  The SGID bit is inherited on new subdirectories.  (Existing resources, including any that are moved into this directory, are not affected.)';
	} else {
		echo 'which means different things for different resource types.';
	}
	echo '</p>';
}
?>

  <h3>Others</h3>
<?php
$others = array();
if ($others_r) $others[] = 'read';
if ($others_w) $others[] = 'write';
if ($others_x) $others[] = ($type == 'd' ? 'list/navigate into' : ($type == 'l' ? 'follow' : 'execute'));
if ($others) {
	echo '<p>Any other user on the system can '.natural_join($others).' it.</p>';
} else {
	echo '<p>Any other user on the system cannot access it at all.</p>';
}
if ($attr_sticky) {
	echo '<p>Additionally the <i>sticky</i> bit is set, ';
	if ($type == 'd') {
		echo 'which means any sub-resources in this directory can only be <i>renamed</i>, <i>moved</i>, or <i>deleted</i> by their respective owners.  Note that the sub-resources themselves may be viewed/edited/etc. depending on their own respective permissions.';
	} else {
		echo 'which really only means something on directories, these days.';
	}
}
?>

</body>
</html>
