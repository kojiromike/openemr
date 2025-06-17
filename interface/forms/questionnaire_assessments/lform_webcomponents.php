<?php

declare(strict_types=1);

// change root to public path when ready
$root =  $GLOBALS['webroot'] . "/interface/forms/questionnaire_assessments";

$style = sprintf("<link href='%s/assets/styles/styles_openemr_lforms.css' media='screen' rel='stylesheet' />", $root) . "\n";
$style_lform = sprintf("<link href='%s/lforms/webcomponent/styles.css' media='screen' rel='stylesheet' />", $root) . "\n";

$insert = <<< insert
<script src="{$root}/lforms/webcomponent/assets/lib/zone.min.js"></script>
<script src="{$root}/lforms/webcomponent/runtime.js"></script>
<script src="{$root}/lforms/webcomponent/polyfills.js"></script>
<script src="{$root}/lforms/webcomponent/main.js"></script>
<script src="{$root}/lforms/fhir/R4/lformsFHIR.min.js"></script>
insert;

$insert_all = $style . $insert;
if ($GLOBALS['questionnaire_display_style'] == 1) {
    $insert_all = $style_lform . $insert;
}

echo $insert_all;
