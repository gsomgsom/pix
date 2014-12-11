<?php
/**
 * Pix (lorempixel.com clone)
 *
 * @copyright     2014 Zhelnin Evgeniy
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 * @author        Zhelnin Evgeniy (evgeniy@zhelnin.perm.ru)
 * @version       1.0
 * @package       pix
 */

/*
	Usage:

    http://pix.pewpew.ru/400							to get a random picture of 400 x 400 pixels 
    http://pix.pewpew.ru/400/200						to get a random picture of 400 x 200 pixels
    http://pix.pewpew.ru/g/400/200						to get a random gray picture of 400 x 200 pixels
    http://pix.pewpew.ru/400/200/sports					to get a random picture of the sports category
    http://pix.pewpew.ru/400/200/sports/1				to get picture no. 1/10 from the sports category
    http://pix.pewpew.ru/400/200/sports/Dummy-Text		with a custom text on the random Picture
    http://pix.pewpew.ru/400/200/sports/1/Dummy-Text	with a custom text on the selected Picture
	http://pix.pewpew.ru/g/400/200/sports/1/Dummy-Text	with a custom text on the selected gray Picture
*/

ini_set('display_errors', 0);
error_reporting(E_NONE);

date_default_timezone_set('Europe/Moscow');

// Parse query and routing
$temp_url = parse_url("http://".(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost').$_SERVER['REQUEST_URI']);
$dirs = explode('/', $temp_url['path']);

// Init variables
$categories = glob('original/*', GLOB_ONLYDIR);
foreach ($categories as &$entry) {
	$entry = str_replace('original/', '', $entry);
}
$width      = 0;
$height     = 0;
$grayscale  = 0;
$category   = $categories[rand(0, count($categories)-1)];
$number     = 0;
$text       = '';
$max_width	= 4096;
$max_width	= 2048;
$quality	= 70;
$font_size  = 12;
$font_angle = 90;
$font_file  = './font/Lato-Medium.ttf';
$use_cachie = true;

// Parsing
if (isset($dirs[1]) && ($dirs[1] == 'g')) {
	$grayscale = 1;
}
if (isset($dirs[1 + $grayscale])) {
	$width = abs(intval($dirs[1 + $grayscale]));
	$width = $width > $max_width ? $max_width : $width;
}
if (isset($dirs[2 + $grayscale])) {
	$height = abs(intval($dirs[2 + $grayscale]));
	$height = $height > $height ? $height : $height;
}
if (isset($dirs[3 + $grayscale])) {
	$tmp_category = trim($dirs[3 + $grayscale]);
	if (in_array($tmp_category, $categories)) {
		$category = $tmp_category;
	}
}
$numbers = glob('original/'.$category.'/*.*');
foreach ($numbers as &$entry) {
	$entry = str_replace('original/'.$category.'/', '', $entry);
	$entry = str_replace('.jpg', '', $entry);
}
$number = $numbers[rand(0, count($numbers)-1)];
if (isset($dirs[4 + $grayscale])) {
	$tmp = trim($dirs[4 + $grayscale]);
	if ((intval($tmp)) && (intval($tmp) === ($tmp+0))) {
		$number = $tmp;
	}
	else {
		$text = $tmp;
	}
}
if (isset($dirs[5 + $grayscale]) && (!$text)) {
	$text = $dirs[5 + $grayscale];
}
if (!$height) $height = $width;
$text = urldecode($text);

if (!$width) {
echo(file_get_contents("usage.html"));
die();
}

// Processing
if ((!$use_cachie) || (!file_exists("cached/".$category."/".$number."_".$width."_".$height."_".md5($text)."_".intval($grayscale)."_.jpg"))) {
	$original_img_info = getimagesize("original/".$category."/".$number.".jpg");
	$original_width = $original_img_info[0];
	$original_height = $original_img_info[1];
	$proportion = $width / $height;
	$original_proportion = $original_width / $original_height;
	if ($proportion < $original_proportion) {
		$new_width = $width * ($original_proportion / $proportion);
		$new_height = $height;
	}
	else {
		$new_width = $width;
		$new_height = $height * ($proportion / $original_proportion);
	}
	$original_img = imagecreatefromjpeg("original/".$category."/".$number.".jpg");
	$new_img = imagecreatetruecolor($new_width, $new_height);
	imagecopyresampled($new_img, $original_img, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height);
	imagedestroy($original_img);
	$img = imagecreatetruecolor($width, $height);
	imagecopymergegray($img, $new_img, 0, 0, (($new_width - $width) / 2), (($new_height - $height) / 2), $new_width, $new_height, 100);
	if ($grayscale) {
		imagecopymergegray($img, $new_img, 0, 0, (($new_width - $width) / 2), (($new_height - $height) / 2), $new_width, $new_height, 0);
	}
	imagedestroy($new_img);
	$white = imagecolorallocate($img, 0xFF, 0xFF, 0xFF);
	$black = imagecolorallocate($img, 0x00, 0x00, 0x00);
	imagefttext($img, $font_size, $font_angle, 20, $height - 20, $black, $font_file, $text);
	imagefttext($img, $font_size, $font_angle, 21, $height - 21, $white, $font_file, $text);
	imagejpeg($img, "cached/".$category."/".$number."_".$width."_".$height."_".md5($text)."_".intval($grayscale)."_.jpg", $quality);
	imagedestroy($img);
}
header("Content-type: image/jpeg");
echo(file_get_contents("cached/".$category."/".$number."_".$width."_".$height."_".md5($text)."_".intval($grayscale)."_.jpg"));
die();
