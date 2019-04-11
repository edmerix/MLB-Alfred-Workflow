# MLB Scores Alfred Workflow

Workflow for Alfred V2 that uses the MLB API to check info about current games (i.e. quickly check the score, who's on base, who's pitching etc.)

Keyword is mlb, followed by the 2 or 3 letter code for the team you're looking for (e.g. nyy for the Yankees).

Shows runners on base at a glance using icons in the Alfred response, see [screenshots](#screenshots) below.

If a game has already finish today it'll show the final result, including R|H|E, if a game is scheduled for today it'll give upcoming info.

## Screenshots

### Example response during a game
![Screenshot of workflow during game](screenshots/active_game.png?raw=true "A screenshot of workflow during game")

### Example response after a game finishes
![Screenshot of workflow after a game](screenshots/final.png?raw=true "A screenshot of workflow after a game")

### Example response with a game later this day
![Screenshot of workflow before a game](screenshots/upcoming.png?raw=true "A screenshot of workflow before a game")

## Misc

It also shows who's pitching (_+stats_), who's batting (_+stats_), current balls/strikes, if anyone's (and who's) on base, current win-lose record for both teams, and where the game is being broadcast on TV and radio.

You don't need the space between mlb and the team code, so could call the Yankees just as _"mlbnyy"_

I started to update how variables are printed within strings to <pre>"Hello world, $variablename!"</pre> rather than <pre>"Hello world, ". $variablename. "!"</pre> but am yet to finish. Needs doing.
