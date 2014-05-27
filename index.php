<?php

require_once( dirname(__FILE__) . '/rg2-config.php' );

// override allows testing of a local configuration such as c:/xampp/htdocs/rg2
if (file_exists( dirname(__FILE__) . '/rg2-override-config.php')) {
	$override = true;
	require_once ( dirname(__FILE__) . '/rg2-override-config.php');
} else {
	$override = false;
}

if (defined('OVERRIDE_UI_THEME')) {
	$ui_theme = OVERRIDE_UI_THEME;
} else {
	$ui_theme = UI_THEME;
}

if (defined('HEADER_COLOUR')) {
	$header_colour = HEADER_COLOUR;
} else {
	$header_colour = '#002bd9';
}
if (defined('HEADER_TEXT_COLOUR')) {
	$header_text_colour = HEADER_TEXT_COLOUR;
} else {
	$header_text_colour = '#ffffff';
}

if (defined('OVERRIDE_BASE_DIRECTORY')) {
	$json_url = OVERRIDE_BASE_DIRECTORY . "/rg2/rg2api.php";
	if (defined('OVERRIDE_SOURCE_DIRECTORY')) {
		$source_url = OVERRIDE_SOURCE_DIRECTORY . "/rg2";
	} else {
		$source_url = OVERRIDE_BASE_DIRECTORY . "/rg2";
	}
} else {
	$json_url = RG_BASE_DIRECTORY . "/rg2/rg2api.php";
	if (defined('OVERRIDE_SOURCE_DIRECTORY')) {
		$source_url = OVERRIDE_SOURCE_DIRECTORY . "/rg2";
	} else {
		$source_url = RG_BASE_DIRECTORY . "/rg2";
	}
}

if (defined('OVERRIDE_KARTAT_DIRECTORY')) {
  $maps_url = OVERRIDE_KARTAT_DIRECTORY;
} else {
  $maps_url = RG_BASE_DIRECTORY . "/kartat/";
}

// include manager function as parameter for now until we decide the best way forward
if (isset($_GET['manage'])) {
	$manager = TRUE;
  if (defined('OVERRIDE_KARTAT_DIRECTORY')) {
    $manager_url = OVERRIDE_KARTAT_DIRECTORY;
  } else {
    $manager_url = "../kartat/";
  }
  // simple cookie generator! Don't need unique, just need something vaguely random
  $keksi = substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890"), 0, 20);
  file_put_contents($manager_url."keksi.txt", $keksi.PHP_EOL); 
} else {
	$manager=  FALSE;
}

// include debug function as parameter for now until we decide the best way forward
if (isset($_GET['debug']) || $override) {
	$debug = TRUE;
} else {
	$debug = FALSE;
}

// include language file if requested
if (isset($_GET['lang'])) {
	$lang = $_GET['lang'];
} else {
	if ((defined('START_LANGUAGE'))) {
		$lang = START_LANGUAGE;
	} else {
		$lang = "";
	}
}
$langdir = dirname(__FILE__) . '/lang/';
if (file_exists($langdir.$lang.'.txt')) {
	$dictionary = 'dictionary: {'.PHP_EOL;
	$dictionary .= file_get_contents($langdir.$lang.'.txt').PHP_EOL;
	$dictionary .= '}'.PHP_EOL; 
} else {
	$dictionary = "dictionary: {}".PHP_EOL;
}

// create list of available languages
$languages = "languages: {".PHP_EOL;
foreach(glob($langdir.'*.txt') as $file) {
	// requested language will already be defined so don't add it again
	// xx is a dummy file to hold the master list of terms
  if ($file != $langdir.'xx.txt') {
    $lines = explode(PHP_EOL, file_get_contents($file));
    $code = "";
		$name = "";
    foreach ($lines as $line) {
      if (strpos($line, 'code') !== FALSE) {
      	$codepos = strpos($line, "'") + 1;
				$code = substr($line, $codepos, 2);
			}
      if (strpos($line, 'language') !== FALSE) {
      	$namepos = strpos($line, "'");
				$name = substr($line, $namepos);
				$name = rtrim($name, ',');
				break;
      }
		}
		if (($code != "") && ($name != "")) {
		  $languages .= $code.': '.$name.','.PHP_EOL;
		}
	}
}
$languages .= '},'.PHP_EOL;

?>
<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!-->
<html class="no-js">
  <!--<![endif]-->
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title>Routegadget 2</title>
    <meta name="description" content="View and save route choices for orienteering events">
    <meta name="viewport" content="user-scalable=no, width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="shortcut icon" href="img/favicon.ico"/>
    <link rel="stylesheet" href='<?php echo $source_url ."/css/normalize.min.css'>"; ?>  
    <link rel="stylesheet" href='<?php echo $source_url ."/css/rg2.css'>"; ?>
    <link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/themes/<?php echo $ui_theme; ?>/jquery-ui.min.css">
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css">
  </head>
  <body>
    <!--[if lt IE 7]>
    <p class="chromeframe">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">activate Google Chrome Frame</a> to improve your experience.</p>
    <![endif]-->
    <?php include 'html/header.html';?>
    <noscript><h3>You have javascript disabled. Routegadget cannot run. Please update your browser configuration.</h3></noscript>
    <div id="rg2-container">
      <div id="rg2-info-panel">
        <div id="rg2-info-panel-tab-headers">
        <ul>
          <li id="rg2-events-tab">
            <a href="#rg2-event-list">Events</a>
          </li>
          <li id="rg2-courses-tab">
            <a href="#rg2-course-list">Courses</a>
          </li>
          <li id="rg2-results-tab">
            <a href="#rg2-result-list">Results</a>
          </li>
          <li id="rg2-draw-tab">
            <a href="#rg2-draw">Draw</a>
          </li>
          <?php if ($manager) { ?>
          <li id="rg2-login-tab">
            <a href="#rg2-manage-login">Login</a>
          </li>
          <li id="rg2-create-tab">
          	<a href="#rg2-manage-create">Add event</a>
          </li>
          <li id="rg2-edit-tab">
          	<a href="#rg2-manage-edit">Edit event</a>
          </li>
          <li id="rg2-map-tab">
            <a href="#rg2-manage-map">Add map</a>
          </li>
          <?php } ?>
   			</ul>
        </div>
        <div id="rg2-info-panel-tab-body">
        <div id="rg2-event-list"></div>
        <div id="rg2-course-list">
          <div id="rg2-course-table"></div>
        </div>
        <div id="rg2-result-list"></div>
      <div id="rg2-draw">
          <h3 class="no-top-margin" id='rg2-draw-title'>Draw route</h3>
          <div id="rg2-select-course">
           <label for='rg2-course-select'>Select course: </label>
           <select  id="rg2-course-select"></select>
          </div>     
          <div id="rg2-select-name">
           <label for='rg2-name-select'>Select name: </label>
           <select id="rg2-name-select"></select>
          </div>
          <div id="rg2-enter-name">
           <label for='rg2-name'>Enter name: </label>
           <p id = "rg2-name"><input id="rg2-name-entry" class="pushright" type="text"></p>
           <p id = "rg2-time">Enter time (mm:ss): <input  id="rg2-time-entry" class="pushright" type="text"></p>
          </div>
          <div>
           <textarea id="rg2-new-comments"></textarea>
          </div>
       <button id="btn-three-seconds">+3 sec</button>
       <button id="btn-undo">Undo</button>
       <button id="btn-save-route">Save</button>
       <button class="pushright" id="btn-reset-drawing">Reset</button>
          <hr class="rg2-hr">
          <h3 id='rg2-load-gps-title'>Load GPS file (GPX or TCX)</h3>
          <div id="rg2-select-gps-file">
           <input type='file' accept='.gpx, .tcx' id='rg2-load-gps-file'>
          </div>
       <input type=checkbox id="btn-move-all"><label for="btn-move-all"> Move track and map together (or right click-drag)</label>
       <ul>
        <li>Left click to add/lock/unlock a handle<ul><li>Green: draggable</li><li>Red: locked</li></ul></li>
        <li>Right click to delete a handle</li>
        <li>Drag a handle to adjust track around locked point(s)</li>
       </ul>
       <button id="btn-undo-gps-adjust">Undo</button>
       <button class="pushright" id="btn-save-gps-route">Save GPS route</button> 
    </div>
    <?php if ($manager) {include  'html/manager.html'; } ?>     
       </div>
      </div>
      <?php include 'html/animation.html'; ?>   
      <?php include 'html/options.html'; ?>
      <?php include 'html/misc.html'; ?>
    </div>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js"></script>
<script type="text/javascript">
var rg2Config = {
json_url: "<?php echo $json_url; ?>",
maps_url: "<?php echo $maps_url; ?>",
header_colour: "<?php echo $header_colour; ?>",
header_text_colour: "<?php echo $header_text_colour; ?>",
<?php if (defined('SPLITSBROWSER_DIRECTORY')) { ?>
enable_splitsbrowser: true,
<?php } else { ?>
enable_splitsbrowser: false,
<?php } ?>
<?php if ($manager) { ?>
keksi: "<?php echo $keksi; ?>",
<?php if (defined('EPSG_CODE')) { ?>
epsg_code: "<?php echo EPSG_CODE; ?>",
epsg_params: "<?php echo EPSG_PARAMS; ?>",
<?php } ?>
<?php } ?>
<?php echo $languages; ?>
<?php echo $dictionary; ?>
};
</script>
<?php if ($debug) { ?>
<script src='<?php echo $source_url . "/js/events.js"; ?>'></script>
<script src='<?php echo $source_url . "/js/results.js"; ?>'></script>
<script src='<?php echo $source_url . "/js/gpstrack.js"; ?>'></script>
<script src='<?php echo $source_url . "/js/controls.js"; ?>'></script>
<script src='<?php echo $source_url . "/js/courses.js"; ?>'></script>
<script src='<?php echo $source_url . "/js/draw.js"; ?>'></script>
<script src='<?php echo $source_url . "/js/animation.js"; ?>'></script>
<script src='<?php echo $source_url . "/js/runner.js"; ?>'></script>
<script src='<?php echo $source_url . "/js/plugins.js"; ?>'></script>
<script src='<?php echo $source_url . "/js/rg2.js"; ?>'></script><?php } else { ?>
<script src='<?php echo $source_url . "/js/rg2all.min.js"; ?>'></script><?php } ?>  
<?php if ($manager) { ?><?php if ($debug) { ?>
<script src='<?php echo $source_url . "/js/manager.js"; ?>'></script><?php } else {?>
<script src='<?php echo $source_url . "/js/rg2manager.min.js"; ?>'></script><?php } ?>      
<script src='<?php echo $source_url . "/js/lib/proj4js-compressed.js"; ?>'></script><?php } ?>
</body>
</html>
