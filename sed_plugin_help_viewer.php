<?php

$plugin['name'] = 'sed_plugin_help_viewer';
$plugin['version'] = '0.3';
$plugin['author'] = 'Stephen Dickinson';
$plugin['author_uri'] = 'http://txp-plugins.netcarving.com';
$plugin['description'] = "Quickly check your plugin's help section from the plugin cache dirctory.";

$plugin['type'] = 1; // 0 = regular plugin; public only, 1 = admin plugin; public + admin, 2 = library

@include_once('../zem_tpl.php');

if (0) {
?>
<!-- CSS & HELP
# --- BEGIN PLUGIN CSS ---
<style type="text/css">
div#sed_help td { vertical-align:top; }
div#sed_help code { font-weight:bold; font: 105%/130% "Courier New", courier, monospace; background-color: #FFFFCC;}
div#sed_help code.sed_code_tag { font-weight:normal; border:1px dotted #999; background-color: #f0e68c; display:block; margin:10px 10px 20px; padding:10px; }
div#sed_help a:link, div#sed_help a:visited { color: blue; text-decoration: none; border-bottom: 1px solid blue; padding-bottom:1px;}
div#sed_help a:hover, div#sed_help a:active { color: blue; text-decoration: none; border-bottom: 2px solid blue; padding-bottom:1px;}
div#sed_help h1 { color: #369; font: 20px Georgia, sans-serif; margin: 0; text-align: center; }
div#sed_help h2 { border-bottom: 1px solid black; padding:10px 0 0; color: #369; font: 17px Georgia, sans-serif; }
div#sed_help h3 { color: #693; font: bold 12px Arial, sans-serif; letter-spacing: 1px; margin: 10px 0 0;text-transform: uppercase;}
</style>
# --- END PLUGIN CSS ---
# --- BEGIN PLUGIN HELP ---
<div id="sed_help">

h1(#intro). Plugin Help Section Viewer.

sed_plugin_help_viewer plugin, v0.3 (April 2nd, 2007)

Allows you to view the help section of any plugin in your cache directory.

If the file matches ZEM's template then the help section will get run through the textile formatter before display, otherwise it will be treated as straight HTML.

h2(#versions). Version History

v0.3

* Updated to allow processing of TxP 4.0.4 plugins marked as @allow_html_help@.

v0.2

* Fixed undefined variable error when cache directory is empty. (Thanks Rigel.)

v0.1 Implements the following features&#8230;

* Scanning the cache directory for plugins.
** When clicked, pull out the help section, Textile it if needed, display it.

h2(#credits). Credits

Sections of this plugin use code from <strong>Alex (a.k.a. Zem)</strong> and <strong>Yura (a.k.a Inspired)</strong> with permission.<br/>Many thanks for helping the community guys!

</div>
# --- END PLUGIN HELP ---
-->
<?php
}

# --- BEGIN PLUGIN CODE ---
if(@txpinterface == 'admin') {
	add_privs('sed_plugin_help_viewer','1,2');
	register_tab('extensions', 'sed_plugin_help_viewer', 'Help Viewer');
	register_callback('sed_plugin_help_viewer', 'sed_plugin_help_viewer');
	}

function sed_plugin_help_viewer ($event, $step) {
	if( !$step or !in_array( $step , array('view_help') ) ) {
		_sed_list_plugins_from_cache();
		}
	else
		$step();
	}

function view_help($message='') {
	pagetop(gTxt('edit_plugins'),$message);

	$filename = gps('filename');
	$plugin = array();

	if( !empty($filename) ) {
		$content = file($filename);
		$source_lines = count($content);
		$format = 'none';

		for ($i=0; $i < $source_lines; $i++) {
			$content[$i] = rtrim($content[$i]);
			}

		$format = 'unknown';

		//	Check for ZEM plugin...
		$plugin['help'] = _zem_extract_section($content, 'HELP');
		if( '' != $plugin['help'] )
			$format = 'zem_help';
		else {
			//	check for ied style help section...
			$plugin['help'] = _ied_extract_section($content, 'HELP');
			if ('' != $plugin['help'] )
				$format = 'ied_help';
			}

		echo startTable('edit');
		switch( $format ) {
			case 'zem_help':echo tr(tda( '<p>Plugin is in zem template format.</p>', ' width="600"'));
							if( !isset( $plugin['allow_html_help'] ) or ( 0 === $plugin['allow_html_help'] ) )
								{
								#	Textile...
								$plugin['css']  = _zem_extract_section($content, 'CSS' );
								include_once txpath.'/lib/classTextile.php';
								if ( class_exists('Textile')  )  {
									$textile = new Textile();
									$plugin['help'] = $plugin['css']."\n".$textile->TextileThis($plugin['help']);
									echo tr(tda( '<p>Extracted and Textile processed help section follows&#8230;</p><hr>', ' width="600"'));
									}
								else
									echo tr(tda( '<p>Extracted help section follows, <strong>Textile Processing Failed</strong>&#8230;</p><hr>', ' width="600"'));
								}
							else
								{
								# (x)html...
								$plugin['css']  = _zem_extract_section($content, 'CSS' );
								$plugin['help'] = $plugin['css']."\n".$plugin['help_raw'];
								}
							echo tr(tda($plugin['help'], ' width="600"'));
							break;
			case 'ied_help':echo tr(tda( '<p>Plugin is in ied template format.</p>', ' width="600"'));
							echo tr(tda( '<p>Extracted raw help section follows&#8230;</p><hr>', ' width="600"'));
							echo tr(tda($plugin['help'], ' width="600"'));
							break;
			default:		echo tr(tda( '<p><strong>Unknown plugin file format or empty help section.</strong></p><hr>', ' width="600"'));
							break;
			}


		echo endTable();
		}
	else {
		echo( "Help not accessible from that file." );
		}
	}

function _zem_extract_section($lines, $section) {
	$start_delim = "# --- BEGIN PLUGIN $section ---";
	$end_delim = "# --- END PLUGIN $section ---";

	$start = array_search($start_delim, $lines);
	if( false === $start )
		return '';
	else
		$start += 1;
	$end = array_search($end_delim, $lines);

	$content = array_slice($lines, $start, $end-$start);

	return join("\n", $content);
	}

function _ied_extract_section($lines, $section) {
//	$meta_delim = '--- PLUGIN METADATA ---';
	$help_delim = '--- BEGIN PLUGIN HELP ---';
	$end_delim  = '--- END PLUGIN HELP & METADATA ---';

//	$code_start = 1;
	$help_line = array_search($help_delim, $lines);
	if( false === $help_line )
		return '';	// This is not an ied file.
	$help_line += 1;
	$end_line = array_search($end_delim, $lines);
	$content = array_slice($lines, $help_line, $end_line-$help_line);

	return join("\n", $content);
	}

function _sed_list_plugins_from_cache($message='') {
	pagetop(gTxt('edit_plugins'),$message);
	echo startTable('list');

	$filenames = array();

	if (!empty($GLOBALS['prefs']['plugin_cache_dir'])) {
		$dir = dir($GLOBALS['prefs']['plugin_cache_dir']);
		while ($file = $dir->read()) {
			if($file != "." && $file != "..") {
				$fileaddr = $GLOBALS['prefs']['plugin_cache_dir'].'/'.$file;

				if (!is_dir($fileaddr)) {
					$filenames[]=$fileaddr;
					}
				}
			}
		$dir->close();
		($filenames and (count($filenames) > 0) ) ? natcasesort($filenames) : '';
		}

	echo tr(
	tda(
	tag('Plugins found in the plugin cache directory: '.$GLOBALS['prefs']['plugin_cache_dir'],'h1')
	,' colspan="7" style="border:0;height:50px;text-align:left"')
	);

	echo assHead('plugin','','','','','','Link');

	if( count( $filenames ) > 0 ) {
		foreach($filenames as $filename) {
			$elink = '<a href="?event=sed_plugin_help_viewer&#38;step=view_help&#38;filename='.$filename.'">'.gTxt('help').'</a>';
			$fileext= array_pop(explode ('.',$filename));
			if ($fileext=='php') {
				echo
				tr(
				 td( tag($filename,'div',' style="color:gray;border:0px solid gray;padding:1px 2px 2px 1px;"').(isset($plugin['name'])?$plugin['name'].'<br />':'').' ' )
				.td( '&nbsp;')
				.td( '&nbsp;',  10)
				.td( '&nbsp;', 260)
				.td( '')
				.td( tag('&nbsp;','span',' style="color:gray"') )
				.td( strong($elink) )
				);
				}
			}
		}


	echo endTable();
	}

# --- END PLUGIN CODE ---

?>
