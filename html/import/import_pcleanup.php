<?
$sql_tgame = small_query("SELECT teamgame FROM uts_match WHERE id = $matchid");
IF($sql_tgame == "True") {
	$rem_srecord = "DELETE FROM uts_player WHERE matchid = $matchid AND team > 3";
	mysql_query($rem_srecord);
}

$cleaned = false;
// Get list of players
$sql_pname = "SELECT pid, name FROM uts_player, uts_pinfo AS pi WHERE matchid = $matchid AND pid = pi.id";
$q_pname = mysql_query($sql_pname);
while ($r_pname = mysql_fetch_array($q_pname)) {
	$playername = addslashes($r_pname[name]);
	$pid = $r_pname['pid'];


	// Check if player has more than 1 record
	$q_ids = mysql_query("SELECT playerid FROM uts_player WHERE pid = '$pid' AND matchid = $matchid");
	
	IF (mysql_num_rows($q_ids) > 1) {
		$numrecords = mysql_num_rows($q_ids);
		echo $r_pname[name] .' ';
		// get all the ids this player had
		$playerids	= array();
		while ($r_ids = mysql_fetch_array($q_ids)) {
			$playerids[] = $r_ids['playerid'];
		}
		
		$r_newplayerid = small_query("SELECT (MAX(playerid) + 1) AS newplayerid FROM uts_player WHERE matchid = $matchid");
		$newplayerid = $r_newplayerid['newplayerid'];
			
		
		// Fix matchcount in ranking table
		mysql_query("UPDATE uts_rank SET matches = matches - ". ($numrecords - 1) ." WHERE pid = '$pid' AND gid = '$gid'") or die(mysql_error());
		
		// ***********************
		// UPDATE THE KILLS MATRIX
		$sql_kmupdate = "	SELECT 	victim, 
											SUM(kills) AS kills 
								FROM 		uts_killsmatrix 
								WHERE 	matchid = $matchid 
									AND 	killer IN (". implode(",", $playerids) .") 
								GROUP BY	victim;";
								
		$q_kmupdate = mysql_query($sql_kmupdate);
		while ($r_kmupdate = mysql_fetch_array($q_kmupdate)) {
			mysql_query("	INSERT 	
								INTO 		uts_killsmatrix 
								SET 		matchid = $matchid, 
											killer = $newplayerid, 
											victim = $r_kmupdate['victim'],
											kills	= $r_kmupdate['kills'];");
		}
		
		$sql_kmupdate = "	SELECT 	killer, 
											SUM(kills) AS kills 
								FROM 		uts_killsmatrix 
								WHERE 	matchid = $matchid 
									AND 	victim IN (". implode(",", $playerids) .") 
								GROUP BY	killer;";
								
		$q_kmupdate = mysql_query($sql_kmupdate);
		while ($r_kmupdate = mysql_fetch_array($q_kmupdate)) {
			mysql_query("	INSERT 	
								INTO 		uts_killsmatrix 
								SET 		matchid = $matchid, 
											killer = $r_kmupdate['killer'], 
											victim = $newplayerid,
											kills	= $r_kmupdate['kills'];");
		}
		
		mysql_query("	DELETE
							FROM		uts_killsmatrix
							WHERE 	matchid = $matchid 
								AND 	(killer IN (". implode(",", $playerids) .")
								OR		 victim IN (". implode(",", $playerids) ."));");
		
				
		// FINISHED UPDATING THE KILLS MATRiX
		// **********************************
		
		
		// Get non summed information
		
		$r_truepinfo1 = small_query("SELECT insta, pid, team, isabot, country, ip, gid FROM uts_player WHERE pid = '$pid' AND matchid = $matchid LIMIT 0,1");
		
		// Group Player Stuff so we only have 1 player record per match
		$r_truepinfo2 = small_query("SELECT
		SUM(p.gametime) AS gametime,
		SUM(p.gamescore) AS gamescore,
		MIN(p.lowping) AS lowping,
		MAX(p.highping) AS highping,
		AVG(p.avgping) AS avgping,
		SUM(p.frags) AS frags,
		SUM(p.deaths) AS deaths,
		SUM(p.kills) AS kills,
		SUM(p.suicides) AS suicides,
		SUM(p.teamkills) AS teamkills,
		SUM(p.headshots) AS headshots,
		(100*SUM(p.kills)/(SUM(p.kills)+SUM(p.deaths)+SUM(p.suicides)+SUM(p.teamkills))) AS eff,
		LEAST(ROUND(10000*SUM(w.hits)/SUM(w.shots))/100,100) AS accuracy,
		(SUM(p.gametime)/(SUM(p.deaths)+SUM(p.suicides)+COUNT(p.id))) AS ttl,
		SUM(p.flag_taken) AS flag_taken,
		SUM(p.flag_pickedup) AS flag_pickedup,
		SUM(p.flag_dropped) AS flag_dropped,
		SUM(p.flag_return) AS flag_return,
		SUM(p.flag_capture) AS flag_capture,
		SUM(p.flag_cover) AS flag_cover,
		SUM(p.flag_seal) AS flag_seal,
		SUM(p.flag_assist) AS flag_assist,
		SUM(p.flag_kill) AS flag_kill,
		SUM(p.dom_cp) AS dom_cp,
		SUM(p.dom_pts) AS dom_pts,
		SUM(p.ass_obj) AS ass_obj,
		SUM(p.spree_double) AS spree_double,
		SUM(p.spree_triple) AS spree_triple,
		SUM(p.spree_multi) AS spree_multi,
		SUM(p.spree_mega) AS spree_mega,
		SUM(p.spree_ultra) AS spree_ultra,
		SUM(p.spree_monster) AS spree_monster,
		SUM(p.spree_kill) AS spree_kill,
		SUM(p.spree_rampage) AS spree_rampage,
		SUM(p.spree_dom) AS spree_dom,
		SUM(p.spree_uns) AS spree_uns,
		SUM(p.spree_god) AS spree_god,
		SUM(p.pu_pads) AS pu_pads,
		SUM(p.pu_armour) AS pu_armour,
		SUM(p.pu_keg) AS pu_keg,
		SUM(p.pu_invis) AS pu_invis,
		SUM(p.pu_belt) AS pu_belt,
		SUM(p.pu_amp) AS pu_amp,
		SUM(p.pu_boots) AS pu_boots,
		FROM uts_player as p, uts_weaponstats as w
		WHERE p.matchid = '$matchid' AND p.pid = '$pid' AND w.matchid = '$matchid' AND w.pid = '$pid' AND w.weapon = 0");

		// Remove all of this player's records
		$rem_precord = "DELETE FROM uts_player WHERE matchid = '$matchid' AND pid = '$pid'";
		mysql_query($rem_precord);
		
		// Add this new record to match
		$upd_precord = "	INSERT 
								INTO 	uts_player 
								SET		matchid = '$matchid',
										insta = '$r_truepinfo1['insta']',
										playerid = '$newplayerid',
										pid = '$pid',
										team = '$r_truepinfo1['team']',
										isabot = '$r_truepinfo1['isabot']',
										country = '$r_truepinfo1['country']',
										ip = '$r_truepinfo1['ip']',
										gid = '$r_truepinfo1['gid']',
										gametime = '$r_truepinfo2['gametime']',
										gamescore = '$r_truepinfo2['gamescore']',
										lowping = '$r_truepinfo2['lowping']',
										highping = '$r_truepinfo2['highping']',
										avgping = '$r_truepinfo2['avgping']',
										frags = '$r_truepinfo2['frags']',
										deaths = '$r_truepinfo2['deaths']',
										kills = '$r_truepinfo2['kills']',
										suicides = '$r_truepinfo2['suicides']',
										teamkills = '$r_truepinfo2['teamkills']',
										headshots = '$r_truepinfo2['headshots']',
										eff = '$r_truepinfo2['eff']',
										accuracy = '$r_truepinfo2['accuracy']',
										ttl = '$r_truepinfo2['ttl']',
										flag_taken = '$r_truepinfo2['flag_taken']',
										flag_dropped = '$r_truepinfo2['flag_dropped']',
										flag_return = '$r_truepinfo2['flag_return']',
										flag_capture = '$r_truepinfo2['flag_capture']',
										flag_cover = '$r_truepinfo2['flag_cover']',
										flag_seal = '$r_truepinfo2['flag_seal']',
										flag_assist = '$r_truepinfo2['flag_assist']',
										flag_kill = '$r_truepinfo2['flag_kill']',
										flag_pickedup = '$r_truepinfo2['flag_pickedup']',
										dom_cp = '$r_truepinfo2['dom_cp']',
										dom_pts = '$r_truepinfo2['dom_pts']',
										ass_obj = '$r_truepinfo2['ass_obj']',
										spree_double = '$r_truepinfo2['spree_double']',
										spree_triple = '$r_truepinfo2['spree_triple']',
										spree_multi = '$r_truepinfo2['spree_multi']',
										spree_mega = '$r_truepinfo2['spree_mega']',
										spree_ultra = '$r_truepinfo2['spree_ultra']',
										spree_monster = '$r_truepinfo2['spree_monster']',
										spree_kill = '$r_truepinfo2['spree_kill']',
										spree_rampage = '$r_truepinfo2['spree_rampage']',
										spree_dom = '$r_truepinfo2['spree_dom']',
										spree_uns = '$r_truepinfo2['spree_uns']',
										spree_god = '$r_truepinfo2['spree_god']',
										pu_pads = '$r_truepinfo2['pu_pads']',
										pu_armour = '$r_truepinfo2['pu_armour']',
										pu_keg = '$r_truepinfo2['pu_keg']',
										pu_invis = '$r_truepinfo2['pu_invis']',
										pu_belt = '$r_truepinfo2['pu_belt']',
										pu_amp = '$r_truepinfo2['pu_amp']',
										pu_boots = '$r_truepinfo2['pu_boots']';";
		mysql_query($upd_precord) or die(mysql_error());
		$cleaned = true;
	}
}
if ($cleaned and $html) echo "<br />";
?>