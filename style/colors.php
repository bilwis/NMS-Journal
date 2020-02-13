<?php

require_once('color_vars.php');
header("Content-type: text/css");
?>

:root {
	--main-bg-color: <?=$main_bg_color?>;
	--main-text-color: <?=$main_text_color?>;
	--region-color: <?=$region_color?>;
	--system-color: <?=$system_color?>;
	--planet-color: <?=$planet_color?>;
	--ship-color: <?=$ship_color?>;
	--tool-color: <?=$tool_color?>;
	--base-color: <?=$base_color?>;
	--fauna-color: <?=$fauna_color?>;
	--flora-color: <?=$flora_color?>;
	--border-color: <?=$border_color?>;
	--header-color: <?=$header_color?>;
	--system-header-text-color: <?=$system_header_text_color?>;
	--planet-header-text-color: <?=$planet_header_text_color?>;
    --fauna-header-text-color: <?=$fauna_header_text_color?>;
    --flora-header-text-color: <?=$flora_header_text_color?>;
	--header-text-color: <?=$header_text_color?>;
	--header-highlight-color: <?=$header_highlight_color?>;
}
