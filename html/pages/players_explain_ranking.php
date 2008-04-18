<?
function row($name = NULL, $amount = 0, $multiplier = 0, $extra_multiplier = true) {
	static $i = 0;
	if (empty($name)) {
		echo '<tr><td colspan="4" height="3"></td></tr>';
		$i = 0;
		return(0);
	}
	$i++;
	$class = ($i%2) ? 'grey' : 'grey2';
	if ($extra_multiplier) $multiplier *= 60;
	$points = $amount * $multiplier;
	
	$d_points = get_dp($points);
	if ($points % 1 == 0) $d_points = ceil($points); 
	echo '<tr>';
	echo '<td class="dark">'. htmlentities($name) .'</td>';
	echo '<td class="'.$class.'" align="center">'. $amount .'</td>';
	echo '<td class="'.$class.'" align="center">'. $multiplier .'</td>';
	echo '<td class="'.$class.'" align="right">'. $d_points .'</td>';
	echo '</tr>';
	return($points);
}



$pid = isset($pid) ? addslashes($pid) : addslashes($_GET['pid']);
$gid = isset($gid) ? addslashes($gid) : addslashes($_GET['gid']);

$r_info = small_query("SELECT name, country, banned FROM uts_pinfo WHERE id = '$pid'");
if (!$r_info) {
	echo "Player not found";
	include("includes/footer.php");
	exit;
}

if ($r_info['banned'] == 'Y') {
	if (isset($is_admin) and $is_admin) {
		echo "Warning: Banned player - Admin override<br>";
	} else {
		echo "Sorry, this player has been banned!";
		include("includes/footer.php");
		exit;
	}
}

$playername = $r_info['name'];

$r_game = small_query("SELECT name, gamename FROM uts_games WHERE id = '$gid'");
if (!$r_game) {
	echo "Game ($gid) not found.";
	include("includes/footer.php");
	exit;
}
$real_gamename = $r_game['gamename'];


$r_cnt = small_query("SELECT
		SUM(frags) AS frags, SUM(deaths) AS deaths, SUM(suicides) AS suicides, SUM(teamkills) AS teamkills,
		SUM(flag_taken) AS flag_taken, SUM(flag_pickedup) AS flag_pickedup, SUM(flag_return) AS flag_return, SUM(flag_capture) AS flag_capture, SUM(flag_cover) AS flag_cover,
		SUM(flag_seal) AS flag_seal, SUM(flag_assist) AS flag_assist, SUM(flag_kill) AS flag_kill,
		SUM(dom_cp) AS dom_cp, SUM(ass_obj) AS ass_obj,
		SUM(spree_double) AS spree_double, SUM(spree_multi) AS spree_multi, SUM(spree_ultra) AS spree_ultra, SUM(spree_monster) AS spree_monster,
		SUM(spree_kill) AS spree_kill, SUM(spree_rampage) AS spree_rampage, SUM(spree_dom) AS spree_dom, SUM(spree_uns) AS spree_uns, SUM(spree_god) AS spree_god,
		SUM(gametime) AS gametime 
		FROM uts_player WHERE pid = $pid and gid = $gid");




echo'
<table border="0" cellpadding="1" cellspacing="2" width="720">
  <tbody><tr>
    <td class="heading" align="center"><a href="?pinfo&amp;pid='.$pid.'">'.FlagImage($r_info['country'], false).' '.htmlentities($playername).'</a>\'s '. htmlentities($r_game['name']) .' ranking explained </td>
  </tr>
</tbody></table>';
echo '<br /><br />';





echo '
<table class="box" border="0" cellpadding="1" cellspacing="1">
<tbody>
	<tr>
		<td class="smheading" width="250"></td>
		<td class="smheading" width="80" align="center">Amount</td>
		<td class="smheading" width="80" align="center">Multiplier</td>
		<td class="smheading" width="100" align="right">Points</t>
	</tr>';

$t_points = 0;
$t_points += row('Frags', $r_cnt['frags'], 0.5);
$t_points += row('Deaths', $r_cnt['deaths'], -0.25);
$t_points += row('Suicides', $r_cnt['suicides'], -0.25 );
$t_points += row('Teamkills', $r_cnt['teamkills'], -2);
row();
$t_points += row('Flag Takes', $r_cnt['flag_taken'], 1);
$t_points += row('Flag Pickups', $r_cnt['flag_pickedup'], 1);
$t_points += row('Flag Returns', $r_cnt['flag_return'], 5);
$t_points += row('Flag Captures', $r_cnt['flag_capture'], 10);
$t_points += row('Flag Covers', $r_cnt['flag_cover'], 3);
$t_points += row('Flag Seals', $r_cnt['flag_seal'], 2);
$t_points += row('Flag Assists', $r_cnt['flag_assist'], 5);
$t_points += row('Flag Kills', $r_cnt['flag_kill'], 3);
row();
$t_points += row('Controlpoint Captures', $r_cnt['dom_cp'], 1);
if (strpos($real_gamename, 'Assault') !== false) {
	$t_points += row('Assault Objectives', $r_cnt['ass_obj'], 10);
} else {
	$t_points += row('Assault Objectives', 0, 10);
}
if (strpos($real_gamename, 'JailBreak') !== false) {
	$t_points += row('Team Releases', $r_cnt['ass_obj'], 1.5);
} else {
	$t_points += row('Team Releases', 0, 1.5);
} 
row();
$t_points += row('Double Kills', $r_cnt['spree_double'], 1);
$t_points += row('Multi Kills', $r_cnt['spree_multi'], 1);
$t_points += row('Ultra Kills', $r_cnt['spree_ultra'], 1);
$t_points += row('Monster Kills', $r_cnt['spree_monster'], 2);
row();
$t_points += row('Killing Sprees', $r_cnt['spree_kill'], 1);
$t_points += row('Rampages', $r_cnt['spree_rampage'], 1);
$t_points += row('Dominatings', $r_cnt['spree_dom'], 1.5);
$t_points += row('Unstoppables', $r_cnt['spree_uns'], 2);
$t_points += row('Godlikes', $r_cnt['spree_god'], 3);

row();	
row();	
echo '<tr>	<td class="dark">Total</td>
				<td class="grey" align="center"></td>
				<td class="grey" align="center"></td>
				<td class="grey" align="right">'. ceil($t_points) .'</td>
		</tr>';

$gametime = ceil($r_cnt['gametime'] / 60);
$t_points = $t_points / $gametime;
echo '<tr>	<td class="dark">Divided by game minutes</td>
				<td class="grey2" align="center">'.$gametime.'</td>
				<td class="grey2" align="center"></td>
				<td class="grey2" align="right">'. get_dp($t_points) .'</td>
		</tr>';

/*
IF ($gametime < 10) {
	$t_points += row('Penalty for playing < 10 minutes', get_dp($t_points), 0, false);
}

IF ($gametime >= 10 && $gametime < 50) {
	$t_points += row('Penalty for playing < 50 minutes', get_dp($t_points), -0.75, false);
}

IF ($gametime >= 50 && $gametime < 100) {
	$t_points += row('Penalty for playing < 100 minutes', get_dp($t_points), -0.5, false);
}

IF ($gametime >= 100 && $gametime < 200) {
	$t_points += row('Penalty for playing < 200 minutes', get_dp($t_points), -0.3, false);
}

IF ($gametime >= 200 && $gametime < 300) {
	$t_points += row('Penalty for playing < 300 minutes', get_dp($t_points), -0.15, false);
}
*/
row();	
echo '<tr>	<td class="darkgrey"><strong>Total</strong></td>
				<td class="darkgrey" align="center"></td>
				<td class="darkgrey" align="center"></td>
				<td class="darkgrey" align="right"><strong>'. get_dp($t_points) .'</strong></td>
		</tr>';




echo '</tbody></table>';

?>
