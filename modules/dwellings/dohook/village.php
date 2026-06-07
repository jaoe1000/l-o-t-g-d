<?php
// Safely handle missing keys from the core engine
$schemas = $args['schemas'] ?? array();
$gatenav = $args['gatenav'] ?? '';

// Only try to draw if gatenav has actual content
if (!empty($gatenav)) {
    tlschema($schemas['gatenav'] ?? 'village');
    addnav($gatenav);
    tlschema();
}

// Add the module specific nav
addnav(array("%s", translate_inline(get_module_setting("villagenav"))), "runmodule.php?module=dwellings");

// Reset the preference
set_module_pref("dwelling_saver", 0);
?>
