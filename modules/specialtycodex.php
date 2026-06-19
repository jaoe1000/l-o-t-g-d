<?php
/**
 * Specialty Codex Module
 * Integrates into the FAQ to show players a comprehensive directory of specialty powers.
 * Designed for PHP 8.4 Strict Compliance
 */

function specialtycodex_getmoduleinfo() {
    return [
        "name" => "Specialty Codex",
        "version" => "1.0",
        "author" => "Dragon.NDGaming",
        "category" => "Specialties",
        "download" => "",
        "settings" => [],
        "prefs" => []
    ];
}

function specialtycodex_install() {
    module_addhook("faq-toc");
    debug("Installed 'Specialty Codex' module.");
    return true;
}

function specialtycodex_uninstall() {
    debug("Uninstalled 'Specialty Codex' module.");
    return true;
}

function specialtycodex_dohook($hookname, $args) {
    switch ($hookname) {
        case "faq-toc":
            $t = translate_inline("`@Guide to Specialty Powers`0");
            output_notl("&#149;<a href='runmodule.php?module=specialtycodex&op=view'>%s</a><br/>", $t, true);
            break;
    }
    return $args;
}

function specialtycodex_run() {
    global $session;
    $op = httpget('op');
    
    if ($op == "view") {
        popup_header("Specialty Powers Guide");
        
        output("`c`b`@Specialty Powers Guide`0`b`c`n");
        output("`@Here you will find a complete directory of all combat specialties, their paths, costs, and gameplay effects.`n`n");
        
        $names = modulehook("specialtynames", []);
        $colors = modulehook("specialtycolor", []);
        $codex = modulehook("specialty-codex", []);
        
        if (empty($codex)) {
            output("`7No specialties are currently configured or active.`n");
        } else {
            foreach ($codex as $spec => $info) {
                $ccode = $colors[$spec] ?? '`@';
                $disp_name = $names[$spec] ?? $info['name'];
                
                output("`n`b%s%s`0`b`n", $ccode, $disp_name);
                if (isset($info['desc']) && $info['desc'] !== "") {
                    output("`&%s`0`n", $info['desc']);
                }
                
                // If it has subclasses (like Bard)
                if (isset($info['subclasses']) && is_array($info['subclasses'])) {
                    foreach ($info['subclasses'] as $subKey => $subInfo) {
                        output("`n  `#`b%s`b`0`n", $subInfo['name']);
                        foreach ($subInfo['techniques'] as $tKey => $tInfo) {
                            output("    `^&#149; %s`0 (Cost: `&%s`0) - `v%s`0`n", $tInfo['name'], $tInfo['cost'], $tInfo['desc']);
                        }
                    }
                } 
                // If flat techniques (like Ninjutsu, Paladin)
                elseif (isset($info['techniques']) && is_array($info['techniques'])) {
                    foreach ($info['techniques'] as $tKey => $tInfo) {
                        output("  `^&#149; %s`0 (Cost: `&%s`0) - `v%s`0`n", $tInfo['name'], $tInfo['cost'], $tInfo['desc']);
                    }
                }
                output("`n`$-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-`0`n");
            }
        }
        
        output_notl("<br/><br/>");
        $back = translate_inline("Back to FAQ Contents");
        rawoutput("<a href='petition.php?op=faq'>&lt;&lt; $back</a>");
        
        popup_footer();
    }
}
?>
