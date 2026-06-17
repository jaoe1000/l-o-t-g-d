<?php
// translator ready
// addnews ready
// mail ready

function expbar_getmoduleinfo(): array
{
    $info = [
        "name" => "Experience Bar",
        "author" => "Antigravity",
        "category" => "Stat Display",
        "version" => "1.0",
        "download" => "",
        "requires" => []
    ];
    return $info;
}

function expbar_install(): bool
{
    module_addhook("charstats");
    return true;
}

function expbar_uninstall(): bool
{
    return true;
}

function expbar_dohook(string $hookname, array $args): array
{
    global $session;
    if ($hookname == "charstats") {
        if ($session['user']['loggedin'] && $session['user']['alive']) {
            $u = $session['user'];
            $level = (int)$u['level'];
            
            if ($level >= 15) {
                // Max level in base game
                $pct = 100;
                $display_val = "Max Level";
            } else {
                require_once("lib/experience.php");
                $current_exp = (int)$u['experience'];
                $prev_req = ($level == 1) ? 0 : exp_for_next_level($level - 1, (int)$u['dragonkills']);
                $next_req = exp_for_next_level($level, (int)$u['dragonkills']);
                
                $exp_in_level = $current_exp - $prev_req;
                $total_in_level = $next_req - $prev_req;
                
                if ($total_in_level <= 0) {
                    $pct = 0;
                } else {
                    $pct = round(($exp_in_level / $total_in_level) * 100, 1);
                }
                if ($pct < 0) $pct = 0;
                if ($pct > 100) $pct = 100;
                
                $display_val = "$current_exp / $next_req ($pct%)";
            }
            
            // Build the progress bar HTML using modern CSS Grid/Flex styled classes
            $bar_html = "
            <div class='exp-bar-container' title='{$display_val}'>
              <div class='exp-bar-fill' style='width: {$pct}%'></div>
              <span class='exp-bar-text'>{$display_val}</span>
            </div>";
            
            // Update the display of Experience in character stats
            setcharstat("Vital Info", "Experience", $bar_html);
            
            global $charstat_info;
            if (isset($charstat_info['Vital Info'])) {
                $vital = $charstat_info['Vital Info'];
                $new_vital = [];
                foreach ($vital as $k => $v) {
                    if ($k !== 'Experience' && $k !== 'Bladder' && $k !== 'Odor' && $k !== 'Hunger') {
                        $new_vital[$k] = $v;
                    }
                }
                if (isset($vital['Experience'])) {
                    $new_vital['Experience'] = $vital['Experience'];
                }
                if (isset($vital['Bladder'])) {
                    $new_vital['Bladder'] = $vital['Bladder'];
                }
                if (isset($vital['Odor'])) {
                    $new_vital['Odor'] = $vital['Odor'];
                }
                if (isset($vital['Hunger'])) {
                    $new_vital['Hunger'] = $vital['Hunger'];
                }
                $charstat_info['Vital Info'] = $new_vital;
            }
        }
    }
    return $args;
}
?>
