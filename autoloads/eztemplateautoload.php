<?php

// Operator autoloading

$eZTemplateOperatorArray = array();
$eZTemplateOperatorArray[] = array( 'script' => 'extension/aplinfobox/classes/aplinfoboxoperators.php',
												'class' => 'AplInfoboxOperators',
												'operator_names' => array('infobox', 'multi_infobox') );
?>
