<?php
//TODO: I started changing to inline variable names in strings rather than "example ".$variable." string" - need to finish
date_default_timezone_set("America/New_York");

require_once('workflows.php');
$w = new Workflows();

$icon = "icon.png";

$hour = date("H");
if($hour < 3){ // if it's before 3 a.m. we're probably asking for yesterday's game, not the game coming up
	$day = date("d",strtotime('-1 days'));
	$month = date("m",strtotime('-1 days'));
	$year = date("Y",strtotime('-1 days'));
}else{
	$day = date("d");
	$month = date("m");
	$year = date("Y");
}

$teamcodes = ['ARI', 'ATL', 'BAL', 'BOS', 'CHC', 'CWS', 'CIN', 'CLE', 'COL', 'DET', 'FLA', 'HOU', 'KAN', 'LAA', 'LAD', 'MIL', 'MIN', 'NYM', 'NYY', 'OAK', 'PHI', 'PIT', 'SD', 'SF', 'SEA', 'STL', 'TB', 'TEX', 'TOR', 'WAS'];

$team = urlencode($argv[1]);
$team = strtoupper($team);
if(strlen($team) < 2){
	$w->result(0, 'na', "Baseball scores", "Keep typing...", $icon, "no");
	echo $w->toxml();
	exit;
}

if(!in_array($team, $teamcodes)){
	$w->result(0, 'na', "Team $team not found", "", $icon, "no");
}else{

	$url = "http://gd2.mlb.com/components/game/mlb/year_$year/month_$month/day_".sprintf('%02d',$day)."/master_scoreboard.json?now=".date("dmyhms");

	$data = $w->request($url);
	$data = json_decode($data);

	$data = $data->data->games;

	$c = 0;
	$d = 0;
	foreach($data->game as $game){
		if(strcasecmp($game->home_name_abbrev, $team) == 0){
			$c++;
			if(strcasecmp($game->status->status, "In Progress") == 0){
				$info = "$game->away_team_name: $game->linescore->r->away | $game->home_team_name: $game->linescore->r->home";
				$info .= " ($game->status->inning_state of the $game->status->inning";
				switch($game->status->inning){
					case 1:
					$info .= "st";
					break;
					case 2:
					$info .= "nd";
					break;
					case 3:
					$info .= "rd";
					break;
					default:
					$info .= "th";
				}
				$info .= ")";
				$w->result($d++, "na", $info, "Pitcher: ".$game->pitcher->last." (".$game->pitcher->era.") | Batter: ".$game->batter->last. " (".$game->batter->avg.")", $icon, "no");
				// linescore (r, h & e - both teams)
				$w->result($d++, "na", "R: ".$game->linescore->r->home." - ".$game->linescore->r->away." | H: ".$game->linescore->h->home." - ".$game->linescore->h->away." | E:".$game->linescore->e->home." - ".$game->linescore->e->away, "$game->home_team_name | ".$game->away_team_name, $icon, "no");
				// b, s, o
				if(strcasecmp($game->status->inning_state, "top") == 0){
					$inning_info = "$game->home_team_name fielding (".$game->pitcher->last." pitching)";
				}elseif(strcasecmp($game->status->inning_state, "bottom") == 0){
					$inning_info = "$game->home_team_name batting (".$game->batter->last." at bat)";
				}else{
					$inning_info = $game->status->inning_state." of inning";
				}
				$w->result($d++, "na", "Balls: ".$game->status->b." | Strikes: ".$game->status->s." | Outs: ".$game->status->o, $inning_info, $icon, "no");
				// runners_on_base
				$bases = array("-","-","-");
				$base_icon = "000";
				$b = 0;
				if(property_exists($game->runners_on_base,'runner_on_1b')){
					$bases[0] = "1st: ".$game->runners_on_base->runner_on_1b->last;
					$base_icon[0] = "1";
					$b++;
				}
				if(property_exists($game->runners_on_base,'runner_on_2b')){
					$bases[1] = "2nd: ".$game->runners_on_base->runner_on_2b->last;
					$base_icon[1] = "1";
					$b++;
				}
				if(property_exists($game->runners_on_base,'runner_on_3b')){
					$bases[2] = "3rd: ".$game->runners_on_base->runner_on_3b->last;
					$base_icon[2] = "1";
					$b++;
				}
				$base_sub = $b." runner";
				if($base_sub != 1) $base_sub .= "s";
				$base_sub .= " on base";
				if($base_sub != 1) $base_sub .= "s";
				$w->result($d++, "na", implode($bases," | "), $base_sub, $base_icon.".png", "no");
				// away_win, away_loss, home_win, home_loss
				$w->result($d++, "na", "$game->home_team_name: ".$game->home_win."-".$game->home_loss, $game->away_team_name.": ".$game->away_win."-".$game->away_loss, $icon, "no");
				$w->result($d++, "na", "TV: ".$game->broadcast->home->tv, "Radio: ".$game->broadcast->home->radio, $icon, "no");
			}elseif(strcasecmp($game->status->status, "Preview") == 0 || strcasecmp($game->status->status, "Pre-Game") == 0){
				$w->result($d++, "na", $game->away_name_abbrev." @ ".$game->home_name_abbrev." at ".$game->home_time." ".$game->home_ampm, $game->venue, $icon, "no");
				$w->result($d++, "na", "$game->home_team_name: ".$game->home_win."-".$game->home_loss, $game->away_team_name.": ".$game->away_win."-".$game->away_loss, $icon, "no");
				$w->result($d++, "na", "TV: ".$game->broadcast->home->tv, "Radio: ".$game->broadcast->home->radio, $icon, "no");
			}elseif(strcasecmp($game->status->status, "Final") == 0 || strcasecmp($game->status->status, "Game Over") == 0){
				$w->result($d++, "na", "Final: $game->home_team_name ".$game->linescore->r->home." - ".$game->linescore->r->away." ".$game->away_team_name, "R: ".$game->linescore->r->home." - ".$game->linescore->r->away." | H: ".$game->linescore->h->home." - ".$game->linescore->h->away." | E: ".$game->linescore->e->home." - ".$game->linescore->e->away."", $icon, "no");
			}elseif(strcasecmp($game->status->status, "Warmup") == 0){
				$w->result($d++, "na", "Warmup", "Gettin' ready...", $icon, "no");
			}else{
				$w->result($d++, "na", "Unknown game state", "Can't find details for game status: ".$game->status->status, $icon, "no");
			}
		}elseif(strcasecmp($game->away_name_abbrev, $team) == 0){
			$c++;
			if(strcasecmp($game->status->status, "In Progress") == 0){
				$info = "$game->away_team_name: ".$game->linescore->r->away." | ".$game->home_team_name.": ".$game->linescore->r->home;
				$info .= " (".$game->status->inning_state." of the ".$game->status->inning;
				switch($game->status->inning){
					case 1:
					$info .= "st";
					break;
					case 2:
					$info .= "nd";
					break;
					case 3:
					$info .= "rd";
					break;
					default:
					$info .= "th";
				}
				$info .= ")";
				$w->result($d++, "na", $info, "Pitcher: ".$game->pitcher->last." (".$game->pitcher->era.") | Batter: ".$game->batter->last. " (".$game->batter->avg.")", $icon, "no");
				// linescore (r, h & e - both teams)
				$w->result($d++, "na", "R: ".$game->linescore->r->away." - ".$game->linescore->r->home." | H: ".$game->linescore->h->away." - ".$game->linescore->h->home." | E: ".$game->linescore->e->away." - ".$game->linescore->e->home, "$game->away_team_name | ".$game->home_team_name, $icon, "no");
				// b, s, o
				if(strcasecmp($game->status->inning_state, "top") == 0){
					$inning_info = "$game->away_team_name batting (".$game->batter->last." at bat)";
				}elseif(strcasecmp($game->status->inning_state, "bottom") == 0){
					$inning_info = "$game->away_team_name fielding (".$game->pitcher->last." pitching)";
				}else{
					$inning_info = $game->status->inning_state." of inning";
				}
				// ○○○●
				$w->result($d++, "na", "Balls: ".$game->status->b." | Strikes: ".$game->status->s." | Outs: ".$game->status->o, $inning_info, $icon, "no");
				// runners_on_base
				$bases = array("-","-","-");
				$base_icon = "000";
				$b = 0;
				if(property_exists($game->runners_on_base,'runner_on_1b')){
					$bases[0] = "1st: ".$game->runners_on_base->runner_on_1b->last;
					$base_icon[0] = "1";
					$b++;
				}
				if(property_exists($game->runners_on_base,'runner_on_2b')){
					$bases[1] = "2nd: ".$game->runners_on_base->runner_on_2b->last;
					$base_icon[1] = "1";
					$b++;
				}
				if(property_exists($game->runners_on_base,'runner_on_3b')){
					$bases[2] = "3rd: ".$game->runners_on_base->runner_on_3b->last;
					$base_icon[2] = "1";
					$b++;
				}
				$base_sub = $b." runner";
				if($base_sub != 1) $base_sub .= "s";
				$base_sub .= " on base";
				if($base_sub != 1) $base_sub .= "s";
				$w->result($d++, "na", implode($bases," | "), $base_sub, $base_icon.".png", "no");
				// away_win, away_loss, home_win, home_loss
				$w->result($d++, "na", "$game->away_team_name: ".$game->away_win."-".$game->away_loss, $game->home_team_name.": ".$game->home_win."-".$game->home_loss, $icon, "no");
				$w->result($d++, "na", "TV: ".$game->broadcast->away->tv, "Radio: ".$game->broadcast->away->radio, $icon, "no");
			}elseif(strcasecmp($game->status->status, "Preview") == 0 || strcasecmp($game->status->status, "Pre-Game") == 0){
				$w->result($d++, "na", $game->away_name_abbrev." @ ".$game->home_name_abbrev." at ".$game->away_time." ".$game->away_ampm, $game->venue, $icon, "no");
				$w->result($d++, "na", "$game->away_team_name: ".$game->away_win."-".$game->away_loss, $game->home_team_name.": ".$game->home_win."-".$game->home_loss, $icon, "no");
				$w->result($d++, "na", "TV: ".$game->broadcast->away->tv, "Radio: ".$game->broadcast->away->radio, $icon, "no");
			}elseif(strcasecmp($game->status->status, "Final") == 0 || strcasecmp($game->status->status, "Game Over") == 0){
				$w->result($d++, "na", "Final: $game->away_team_name ".$game->linescore->r->away." - ".$game->linescore->r->home." ".$game->home_team_name, "R: ".$game->linescore->r->away." - ".$game->linescore->r->home." | H: ".$game->linescore->h->away." - ".$game->linescore->h->home." | E: ".$game->linescore->e->away." - ".$game->linescore->e->home."", $icon, "no");
			}elseif(strcasecmp($game->status->status, "Delayed start") == 0){
				$w->result($d++, "na", "Delayed game start: ".$game->status->reason, "Info: ".$game->status->note, $icon, "no");
			}elseif(strcasecmp($game->status->status, "Warmup") == 0){
				$w->result($d++, "na", "Warmup", "Gettin' ready...", $icon, "no");
			}else{
				$w->result($d++, "na", "Unknown game state", "Can't find details for game status: ".$game->status->status, $icon, "no");
			}
		}
	}
	if($c == 0){
		$w->result(0, 'na', "No $team game found for today", "", $icon, "no");
	}
}

echo $w->toxml();
?>
