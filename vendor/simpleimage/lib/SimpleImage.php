<?php
/*

  The PHP SimpleImage class - v2
  
    By Cory LaViska for A Beautiful Site, LLC. (http://www.abeautifulsite.net/)
  
  License:
  
    This software is dual-licensed under the GNU General Public License and 
    the MIT License and is copyright A Beautiful Site, LLC.
	
*/

class SimpleImage {
	
	private $image, $filename, $original_info, $width, $height;
	
	function __construct($filename = null) {
		if( $filename ) $this->load($filename);
	}
	
	function __destruct() {
		if( $this->image ) imagedestroy($this->image);
	}
	
	//
	// Load an image
	//
	//	$filename - the image to be loaded (required)
	//
	public function load($filename) {
		
		// Require GD library
		if( !extension_loaded('gd') ) throw new Exception('Required extension GD is not loaded.');
		
		$this->filename = $filename;
		
		$info = getimagesize($this->filename);
		
		switch( $info['mime'] ) {
			
			case 'image/gif':
				$this->image = imagecreatefromgif($this->filename);
				break;
				
			case 'image/jpeg':
				$this->image = imagecreatefromjpeg($this->filename);
				break;
					
			case 'image/png':
				$this->image = imagecreatefrompng($this->filename);
				break;
				
			default:
				throw new Exception('Invalid image: ' . $this->filename);
				break;
				
		}
		
		$this->original_info = array(
			'width' => $info[0],
			'height' => $info[1],
			'orientation' => $this->get_orientation(),
			'exif' => function_exists('exif_read_data') ? $this->exif = @exif_read_data($this->filename) : null,
			'format' => preg_replace('/^image\//', '', $info['mime']),
			'mime' => $info['mime']
		);
		
		$this->width = $info[0];
		$this->height = $info[1];
		
		imagesavealpha($this->image, true);
		imagealphablending($this->image, true);
		
		return $this;
		
	}
	
	//
	// Save an image
	//
	//		$filename - the filename to save to (defaults to original file)
	//		$quality - 0-9 for PNG, 0-100 for JPEG
	//
	//	Notes:
	//
	//		The resulting format will be determined by the file extension.
	//
	public function save($filename = null, $quality = null) {
		
		if( !$filename ) $filename = $this->filename;
		
		// Determine format via file extension (fall back to original format)
		$format = $this->file_ext($filename);
		if( !$format ) $format = $this->original_info['format'];
		
		// Determine output format
		switch( strtolower($format) ) {
			
			case 'gif':
				$result = imagegif($this->image, $filename);
				break;
			
			case 'jpg':
			case 'jpeg':
				if( $quality === null ) $quality = 85;
				$quality = $this->keep_within($quality, 0, 100);
				$result = imagejpeg($this->image, $filename, $quality);
				break;
			
			case 'png':
				if( $quality === null ) $quality = 9;
				$quality = $this->keep_within($quality, 0, 9);
				$result = imagepng($this->image, $filename, $quality);
				break;
			
			default:
				throw new Exception('Unsupported format');
			
		}
		
		if( !$result ) throw new Exception('Unable to save image: ' . $filename);
		
		return $this;
		
	}
	
	//
	// Get info about the original image
	//
	//	Returns
	//
	//	array(
	//		width => 320,
	//		height => 200,
	//		orientation => ['portrait', 'landscape', 'square'],
	//		exif => array(...),
	//		mime => ['image/jpeg', 'image/gif', 'image/png'],
	//		format => ['jpeg', 'gif', 'png']
	//	)
	//
	public function get_original_info() {
		return $this->original_info;
	}
	
	//
	// Get the current width
	//
	public function get_width() {
		return imagesx($this->image);
	}
	
	//
	// Get the current height
	//
	public function get_height() {
		return imagesy($this->image);
	}
	
	//
	// Get the current orientation ('portrait', 'landscape', or 'square')
	//
	public function get_orientation() {
		
		if( imagesx($this->image) > imagesy($this->image) ) return 'landscape';
		if( imagesx($this->image) < imagesy($this->image) ) return 'portrait';
		return 'square';
		
	}
	
	//
	// Flip an image horizontally or vertically
	//
	//	$direction - 'x' or 'y'
	//
	public function flip($direction) {
		
		$new = imagecreatetruecolor($this->width, $this->height);
		imagealphablending($new, false);
		imagesavealpha($new, true);
		
		switch( strtolower($direction) ) {
			
			case 'y':
				for( $y = 0; $y < $this->height; $y++ ) imagecopy($new, $this->image, 0, $y, 0, $this->height - $y - 1, $this->width, 1);
				break;
			
			default:
				for( $x = 0; $x < $this->width; $x++ ) imagecopy($new, $this->image, $x, 0, $this->width - $x - 1, 0, 1, $this->height);
				break;
			
		}
		
		$this->image = $new;
		
		return $this;
		
	}
	
	//
	// Rotate an image
	//
	//	$angle - 0 - 360 (required)
	//	$bg_color - hex color for the background
	//
	public function rotate($angle, $bg_color = '#000000') {
		
		$rgb = $this->hex2rgb($bg_color);
		$bg_color = imagecolorallocate($this->image, $rgb['r'], $rgb['g'], $rgb['b']);
		
		$new = imagerotate($this->image, -($this->keep_within($angle, -360, 360)), $bg_color);
		imagesavealpha($new, true);
		imagealphablending($new, true);
		
		$this->width = imagesx($new);
		$this->height = imagesy($new);
		$this->image = $new;
		
		return $this;
		
	}
	
	//
	// Rotates and/or flips an image automatically so the orientation will 
	// be correct (based on exif 'Orientation')
	//
	public function auto_orient() {
		
		// Adjust orientation
		switch( $this->original_info['exif']['Orientation'] ) {
			case 1:
				// Do nothing
				break;
			case 2:
				// Flip horizontal
				$this->flip('x');
				break;
			case 3:
				// Rotate 180 counterclockwise
				$this->rotate(-180);
				break;
			case 4:
				// vertical flip
				$this->flip('y');
				break;
			case 5:
				// Rotate 90 clockwise and flip vertically
				$this->flip('y');
				$this->rotate(90);
				break;
			case 6:
				// Rotate 90 clockwise
				$this->rotate(90);
				break;
			case 7:
				// Rotate 90 clockwise and flip horizontally
				$this->flip('x');
				$this->rotate(90);
				break;
			case 8:
				// Rotate 90 counterclockwise
				$this->rotate(-90);
				break;
		}
		
		return $this;
		
	}
	
	//
	// Resize an image to the specified dimensions
	//
	//	$width - the width of the resulting image
	//	$height - the height of the resulting image
	//
	public function resize($width, $height) {
		
		$new = imagecreatetruecolor($width, $height);
		imagealphablending($new, false);
		imagesavealpha($new, true);
		imagecopyresampled($new, $this->image, 0, 0, 0, 0, $width, $height, $this->width, $this->height);
		
		$this->width = $width;
		$this->height = $height;
		$this->image = $new;
		
		return $this;
		
	}
	
	//
	// Fit to width (proportionally resize to specified width)
	//
	public function fit_to_width($width) {
		$aspect_ratio = $this->height / $this->width;
		$height = $width * $aspect_ratio;
		return $this->resize($width, $height);
	}	
	
	//
	// Fit to height (proportionally resize to specified height)
	//
	public function fit_to_height($height) {
		$aspect_ratio = $this->height / $this->width;
		$width = $height / $aspect_ratio;
		return $this->resize($width, $height);
	}	
	
	//
	// Best fit (proportionally resize to fit in specified width/height)
	//
	public function best_fit($max_width, $max_height) {
		
		// If it already fits, there's nothing to do
		if( $this->width <= $max_width && $this->height <= $max_height ) return $this;
		
		// Determine aspect ratio
		$aspect_ratio = $this->height / $this->width;
		
		// Make width fit into new dimensions
		if( $this->width > $max_width ) {
			$width = $max_width;
			$height = $width * $aspect_ratio;
		} else {
			$width = $this->width;
			$height = $this->height;
		}
	   
		// Make height fit into new dimensions
		if( $height > $max_height ) {
			$height = $max_height;
			$width = $height / $aspect_ratio;
		}
		
		return $this->resize($width, $height);
		
	}	
	
	//
	// Crop an image
	//
	//	$x1 - left
	//	$y1 - top
	//	$x2 - right
	//	$y2 - bottom
	//
	public function crop($x1, $y1, $x2, $y2) {
		
		// Determine crop size
		if( $x2 < $x1 ) list($x1, $x2) = array($x2, $x1);
		if( $y2 < $y1 ) list($y1, $y2) = array($y2, $y1);
		$crop_width = $x2 - $x1;
		$crop_height = $y2 - $y1;
		
		$new = imagecreatetruecolor($crop_width, $crop_height);
		imagealphablending($new, false);
		imagesavealpha($new, true);
		imagecopyresampled($new, $this->image, 0, 0, $x1, $y1, $crop_width, $crop_height, $crop_width, $crop_height);
		
		$this->width = $crop_width;
		$this->height = $crop_height;
		$this->image = $new;
		
		return $this;
		
	}	
	
	//
	// Square crop (great for thumbnails)
	//
	//	$size - the size in pixels of the resulting image (width and height are the same) (optional)
	//
	public function square_crop($size = null) {
		
		// Calculate measurements
		if( $this->width > $this->height ) {
			// Landscape
			$x_offset = ($this->width - $this->height) / 2;
			$y_offset = 0;
			$square_size = $this->width - ($x_offset * 2);
		} else {
			// Portrait
			$x_offset = 0;
			$y_offset = ($this->height - $this->width) / 2;
			$square_size = $this->height - ($y_offset * 2);
		}
		
		// Trim to square
		$this->crop($x_offset, $y_offset, $x_offset + $square_size, $y_offset + $square_size);
		
		// Resize
		if( $size ) $this->resize($size, $size);
		
		return $this;
		
	}
	
	//
	// Desaturate (grayscale)
	//
	public function desaturate() {
		imagefilter($this->image, IMG_FILTER_GRAYSCALE);
		return $this;
	}
	
	//
	// Invert
	//
	public function invert() {
		imagefilter($this->image, IMG_FILTER_NEGATE);
		return $this;
	}
	
	//
	// Brightness
	//
	//	$level - darkest = -255, lightest = 255 (required)
	//
	public function brightness($level) {
		imagefilter($this->image, IMG_FILTER_BRIGHTNESS, $this->keep_within($level, -255, 255));
		return $this;
	}
	
	//
	// Contrast
	//
	//	$level - min = -100, max, 100 (required)
	//
	public function contrast($level) {
		imagefilter($this->image, IMG_FILTER_CONTRAST, $this->keep_within($level, -100, 100));
		return $this;
	}
	
	//
	// Colorize (requires PHP 5.2.5+)
	//
	//	$color - any valid hex color (required)
	//	$opacity - 0 - 1 (required)
	//
	public function colorize($color, $opacity) {
		$rgb = $this->hex2rgb($color);
		$alpha = $this->keep_within(127 - (127 * $opacity), 0, 127);
		imagefilter($this->image, IMG_FILTER_COLORIZE, $this->keep_within($rgb['r'], 0, 255), $this->keep_within($rgb['g'], 0, 255), $this->keep_within($rgb['b'], 0, 255), $alpha);
		return $this;
	}
	
	//
	// Edge Detect
	//
	public function edges() {
		imagefilter($this->image, IMG_FILTER_EDGEDETECT);
		return $this;
	}
	
	//
	// Emboss
	//
	public function emboss() {
		imagefilter($this->image, IMG_FILTER_EMBOSS);
		return $this;
	}
	
	//
	// Mean Remove
	//
	public function mean_remove() {
		imagefilter($this->image, IMG_FILTER_MEAN_REMOVAL);
		return $this;
	}
	
	//
	// Blur
	//
	//	$type - 'selective' or 'gaussian' (default = selective)
	//	$passes - the number of times to apply the filter
	//
	public function blur($type = 'selective', $passes = 1) {
		
		switch( strtolower($type) ) {
			case 'gaussian':
				$type = IMG_FILTER_GAUSSIAN_BLUR;
				break;
			default:
				$type = IMG_FILTER_SELECTIVE_BLUR;
				break;
		}
		
		for( $i = 0; $i < $passes; $i++ ) imagefilter($this->image, $type);
		
		return $this;
		
	}
	
	//
	// Sketch
	//
	public function sketch() {
		imagefilter($this->image, IMG_FILTER_MEAN_REMOVAL);
		return $this;
	}
	
	//
	// Smooth
	//
	//	$level - min = -10, max = 10
	//
	public function smooth($level) {
		imagefilter($this->image, IMG_FILTER_SMOOTH, $this->keep_within($level, -10, 10));
		return $this;
	}
	
	//
	// Pixelate (requires PHP 5.3+)
	//
	//	$block_size - the size in pixels of each resulting block (default = 10)
	//
	public function pixelate($block_size = 10) {
		imagefilter($this->image, IMG_FILTER_PIXELATE, $block_size, true);
		return $this;
	}
	
	//
	// Sepia
	//
	public function sepia() {
		imagefilter($this->image, IMG_FILTER_GRAYSCALE);
		imagefilter($this->image, IMG_FILTER_COLORIZE, 100, 50, 0);
		return $this;
	}
	
	//
	// Overlay (overlay an image on top of another; works with 24-big PNG alpha-transparency)
	//
	//	$overlay_file - the image to use as a overlay (required)
	//	$position - 'center', 'top', 'left', 'bottom', 'right', 'top left', 
	//				'top right', 'bottom left', 'bottom right'
	//	$opacity - overlay opacity (0 - 1)
	//	$x_offset - horizontal offset in pixels
	//	$y_offset - vertical offset in pixels
	//
	public function overlay($overlay_file, $position = 'center', $opacity = 1, $x_offset = 0, $y_offset = 0) {
		
		// Load overlay image
		$overlay = new SimpleImage($overlay_file);
		
		// Convert opacity
		$opacity = $opacity * 100;
		
		// Determine position
		switch( strtolower($position) ) {
			
			case 'top left':
				$x = 0 + $x_offset;
				$y = 0 + $y_offset;
				break;
			
			case 'top right':
				$x = $this->width - $overlay->width + $x_offset;
				$y = 0 + $y_offset;
				break;
			
			case 'top':
				$x = ($this->width / 2) - ($overlay->width / 2) + $x_offset;
				$y = 0 + $y_offset;
				break;
			
			case 'bottom left':
				$x = 0 + $x_offset;
				$y = $this->height - $overlay->height + $y_offset;
				break;
			
			case 'bottom right':
				$x = $this->width - $overlay->width + $x_offset;
				$y = $this->height - $overlay->height + $y_offset;
				break;
			
			case 'bottom':
				$x = ($this->width / 2) - ($overlay->width / 2) + $x_offset;
				$y = $this->height - $overlay->height + $y_offset;
				break;
			
			case 'left':
				$x = 0 + $x_offset;
				$y = ($this->height / 2) - ($overlay->height / 2) + $y_offset;
				break;
			
			case 'right':
				$x = $this->width - $overlay->width + $x_offset;
				$y = ($this->height / 2) - ($overlay->height / 2) + $y_offset;
				break;
			
			case 'center':
			default:
				$x = ($this->width / 2) - ($overlay->width / 2) + $x_offset;
				$y = ($this->height / 2) - ($overlay->height / 2) + $y_offset;
				break;
			
		}
		
		$this->imagecopymerge_alpha($this->image, $overlay->image, $x, $y, 0, 0, $overlay->width, $overlay->height, $opacity);  
		
		return $this;
		
	}		
	
	// 
	// Text (adds text to an image)
	//
	//	$text - the text to add (required)
	//	$font_file - the font to use (required)
	//	$font_size - font size in points
	//	$color - font color in hex
	//	$position - 'center', 'top', 'left', 'bottom', 'right', 'top left', 
	//				'top right', 'bottom left', 'bottom right'
	//	$x_offset - horizontal offset in pixels
	//	$y_offset - vertical offset in pixels
	//
	public function text($text, $font_file, $font_size = '12', $color = '#000000', $position = 'center', $x_offset = 0, $y_offset = 0) {
		
		// todo - this method could be improved to support the text angle
		$angle = 0;
		$rgb = $this->hex2rgb($color);
		$color = imagecolorallocate($this->image, $rgb['r'], $rgb['g'], $rgb['b']);
		
		// Determine textbox size
		$box = imagettfbbox($font_size, $angle, $font_file, $text);
		if( !$box ) throw new Exception('Unable to load font: ' . $font_file);
		$box_width = abs($box[6] - $box[2]);
		$box_height = abs($box[7] - $box[1]);
		
		// Determine position
		switch( strtolower($position) ) {
			
			case 'top left':
				$x = 0 + $x_offset;
				$y = 0 + $y_offset + $box_height;
				break;
			
			case 'top right':
				$x = $this->width - $box_width + $x_offset;
				$y = 0 + $y_offset + $box_height;
				break;
			
			case 'top':
				$x = ($this->width / 2) - ($box_width / 2) + $x_offset;
				$y = 0 + $y_offset + $box_height;
				break;
			
			case 'bottom left':
				$x = 0 + $x_offset; 
				$y = $this->height - $box_height + $y_offset + $box_height;
				break;
			
			case 'bottom right':
				$x = $this->width - $box_width + $x_offset;
				$y = $this->height - $box_height + $y_offset + $box_height;
				break;
			
			case 'bottom':
				$x = ($this->width / 2) - ($box_width / 2) + $x_offset;
				$y = $this->height - $box_height + $y_offset + $box_height;
				break;
			
			case 'left':
				$x = 0 + $x_offset;
				$y = ($this->height / 2) - (($box_height / 2) - $box_height) + $y_offset;
				break;
			
			case 'right';
				$x = $this->width - $box_width + $x_offset;
				$y = ($this->height / 2) - (($box_height / 2) - $box_height) + $y_offset;
				break;
			
			case 'center':
			default:
				$x = ($this->width / 2) - ($box_width / 2) + $x_offset;
				$y = ($this->height / 2) - (($box_height / 2) - $box_height) + $y_offset;
				break;
			
		}
		
		imagettftext($this->image, $font_size, $angle, $x, $y, $color, $font_file, $text);
		
		return $this;
		
	}

	//
	// Outputs image without saving
	//
	public function output($format = null, $quality = null) {
		
		switch( strtolower($format) ) {
			
			case 'gif':
				$mimetype = 'image/gif';
				break;
			
			case 'jpeg':
			case 'jpg':
				$mimetype = 'image/jpeg';
				break;
			
			case 'png':
				$mimetype = 'image/png';
				break;
			
			default:
				$info = getimagesize($this->filename);
				$mimetype = $info['mime'];
				break;
		}
		
		// Output the image
		header('Content-Type: ' . $mimetype);
		
		switch( $mimetype ) {
			
			case 'image/gif':
				imagegif($this->image);
				break;
			
			case 'image/jpeg':
				if( $quality === null ) $quality = 85;
				$quality = $this->keep_within($quality, 0, 100);
				imagejpeg($this->image, null, $quality);
				break;
			
			case 'image/png':
				if( $quality === null ) $quality = 9;
				$quality = $this->keep_within($quality, 0, 9);
				imagepng($this->image, null, $quality);
				break;
			
			default:
				throw new Exception('Unsupported image format: ' . $this->filename);
				break;
		}
		
		// Since no more output can be sent, call the destuctor to free up memory
		$this->__destruct();
		
	}
	
	// Same as PHP's imagecopymerge() function, except preserves alpha-transparency in 24-bit PNGs
	// Courtest of: http://www.php.net/manual/en/function.imagecopymerge.php#88456
	private function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct ) { 
		$pct /= 100;
		// Get image width and height
		$w = imagesx($src_im);
		$h = imagesy($src_im);
		// Turn alpha blending off
		imagealphablending($src_im, false);
		// Find the most opaque pixel in the image (the one with the smallest alpha value)
		$minalpha = 127;
		for( $x = 0; $x < $w; $x++ ) {
			for( $y = 0; $y < $h; $y++ ) {
				$alpha = (imagecolorat( $src_im, $x, $y ) >> 24) & 0xFF;
				if( $alpha < $minalpha ) {
					$minalpha = $alpha;
				}
			}
		}
		// Loop through image pixels and modify alpha for each
		for( $x = 0; $x < $w; $x++ ) {
			for( $y = 0; $y < $h; $y++ ) {
				// Get current alpha value (represents the TANSPARENCY!)
				$colorxy = imagecolorat($src_im, $x, $y);
				$alpha = ($colorxy >> 24) & 0xFF;
				// Calculate new alpha
				if( $minalpha !== 127 ) {
					$alpha = 127 + 127 * $pct * ($alpha - 127) / (127 - $minalpha);
				} else {
					$alpha += 127 * $pct;
				}
				// Get the color index with new alpha
				$alphacolorxy = imagecolorallocatealpha($src_im, ($colorxy >> 16) & 0xFF, ($colorxy >> 8) & 0xFF, $colorxy & 0xFF, $alpha);
				// Set pixel with the new color + opacity
				if( !imagesetpixel($src_im, $x, $y, $alphacolorxy) ) return false;
			}
		}
		imagesavealpha($dst_im, true);
		imagealphablending($dst_im, true);
		imagesavealpha($src_im, true);
		imagealphablending($src_im, true);
		imagecopy($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h);
	} 
	
	//
	// Ensures $value is always within $min and $max range.
	// If lower, $min is returned. If higher, $max is returned.
	//
	private function keep_within($value, $min, $max) {
		if( $value < $min ) return $min;
		if( $value > $max ) return $max;
		return $value;
	}
	
	//
	// Returns the file extension of the specified file
	//
	private function file_ext($filename) {
		
		if( !preg_match('/\./', $filename) ) return '';
		
		return preg_replace('/^.*\./', '', $filename);
		
	}
	
	//
	// Converts a hex color value to its RGB equivalent
	//
	private function hex2rgb($hex_color) {
		
		if( $hex_color[0] == '#' ) $hex_color = substr($hex_color, 1);
		if( strlen($hex_color) == 6 ) {
			list($r, $g, $b) = array(
				$hex_color[0] . $hex_color[1],
				$hex_color[2] . $hex_color[3],
				$hex_color[4] . $hex_color[5]
			);
		} elseif( strlen($hex_color) == 3 ) {
			list($r, $g, $b) = array(
				$hex_color[0] . $hex_color[0],
				$hex_color[1] . $hex_color[1],
				$hex_color[2] . $hex_color[2]
			);
		} else {
			return false;
		}
		
	    return array(
			'r' => hexdec($r),
			'g' => hexdec($g),
			'b' => hexdec($b)
		);
		
	}
	
    function imageSmoothArcDrawSegment (&$img, $cx, $cy, $a, $b, $aaAngleX, $aaAngleY, $color, $start, $stop, $seg)
    {
        // Originally written from scratch by Ulrich Mierendorff, 06/2006
        // Rewritten and improved, 04/2007, 07/2007

        // Please do not use THIS function directly. Scroll down to imageSmoothArc(...).

        $fillColor = imageColorExactAlpha( $img, $color[0], $color[1], $color[2], $color[3] );

        $xStart = abs($a * cos($start));
        $yStart = abs($b * sin($start));
        $xStop  = abs($a * cos($stop));
        $yStop  = abs($b * sin($stop));
        $dxStart = 0;
        $dyStart = 0;
        $dxStop = 0;
        $dyStop = 0;
        if ($xStart != 0)
            $dyStart = $yStart/$xStart;
        if ($xStop != 0)
            $dyStop = $yStop/$xStop;
        if ($yStart != 0)
            $dxStart = $xStart/$yStart;
        if ($yStop != 0)
            $dxStop = $xStop/$yStop;
        if (abs($xStart) >= abs($yStart)) {
            $aaStartX = true;
        } else {
            $aaStartX = false;
        }
        if ($xStop >= $yStop) {
            $aaStopX = true;
        } else {
            $aaStopX = false;
        }
        //$xp = +1; $yp = -1; $xa = +1; $ya = 0;
        for ( $x = 0; $x < $a; $x += 1 ) {
            /*$y = $b * sqrt( 1 - ($x*$x)/($a*$a) );

            $error = $y - (int)($y);
            $y = (int)($y);

            $diffColor = imageColorExactAlpha( $img, $color[0], $color[1], $color[2], 127-(127-$color[3])*$error );*/

            $_y1 = $dyStop*$x;
            $_y2 = $dyStart*$x;
            if ($xStart > $xStop)
            {
                $error1 = $_y1 - (int)($_y1);
                $error2 = 1 - $_y2 + (int)$_y2;
                $_y1 = $_y1-$error1;
                $_y2 = $_y2+$error2;
            }
            else
            {
                $error1 = 1 - $_y1 + (int)$_y1;
                $error2 = $_y2 - (int)($_y2);
                $_y1 = $_y1+$error1;
                $_y2 = $_y2-$error2;
            }
            /*
            if ($aaStopX)
                $diffColor1 = imageColorExactAlpha( $img, $color[0], $color[1], $color[2], 127-(127-$color[3])*$error1 );
            if ($aaStartX)
                $diffColor2 = imageColorExactAlpha( $img, $color[0], $color[1], $color[2], 127-(127-$color[3])*$error2 );
            */

            if ($seg == 0 || $seg == 2)
            {
                $i = $seg;
                if (!($start > $i*M_PI/2 && $x > $xStart)) {
                    if ($i == 0) {
                        $xp = +1; $yp = -1; $xa = +1; $ya = 0;
                    } else {
                        $xp = -1; $yp = +1; $xa = 0; $ya = +1;
                    }
                    if ( $stop < ($i+1)*(M_PI/2) && $x <= $xStop ) {
                        $diffColor1 = imageColorExactAlpha( $img, $color[0], $color[1], $color[2], 127-(127-$color[3])*$error1 );
                        $y1 = $_y1; if ($aaStopX) imageSetPixel($img, $cx+$xp*($x)+$xa, $cy+$yp*($y1+1)+$ya, $diffColor1);

                    } else {
                        $y = $b * sqrt( 1 - ($x*$x)/($a*$a) );
                        $error = $y - (int)($y);
                        $y = (int)($y);
                        $diffColor = imageColorExactAlpha( $img, $color[0], $color[1], $color[2], 127-(127-$color[3])*$error );
                        $y1 = $y; if ($x < $aaAngleX ) imageSetPixel($img, $cx+$xp*$x+$xa, $cy+$yp*($y1+1)+$ya, $diffColor);
                    }
                    if ($start > $i*M_PI/2 && $x <= $xStart) {
                        $diffColor2 = imageColorExactAlpha( $img, $color[0], $color[1], $color[2], 127-(127-$color[3])*$error2 );
                        $y2 = $_y2; if ($aaStartX) imageSetPixel($img, $cx+$xp*$x+$xa, $cy+$yp*($y2-1)+$ya, $diffColor2);
                    } else {
                        $y2 = 0;
                    }
                    if ($y2 <= $y1) imageLine($img, $cx+$xp*$x+$xa, $cy+$yp*$y1+$ya , $cx+$xp*$x+$xa, $cy+$yp*$y2+$ya, $fillColor);
                }
            }

            if ($seg == 1 || $seg == 3)
            {
                $i = $seg;
                if (!($stop < ($i+1)*M_PI/2 && $x > $xStop)) {
                    if ($i == 1) {
                        $xp = -1; $yp = -1; $xa = 0; $ya = 0;
                    } else {
                        $xp = +1; $yp = +1; $xa = 1; $ya = 1;
                    }
                    if ( $start > $i*M_PI/2 && $x < $xStart ) {
                        $diffColor2 = imageColorExactAlpha( $img, $color[0], $color[1], $color[2], 127-(127-$color[3])*$error2 );
                        $y1 = $_y2; if ($aaStartX) imageSetPixel($img, $cx+$xp*$x+$xa, $cy+$yp*($y1+1)+$ya, $diffColor2);

                    } else {
                        $y = $b * sqrt( 1 - ($x*$x)/($a*$a) );
                        $error = $y - (int)($y);
                        $y = (int) $y;
                        $diffColor = imageColorExactAlpha( $img, $color[0], $color[1], $color[2], 127-(127-$color[3])*$error );
                        $y1 = $y; if ($x < $aaAngleX ) imageSetPixel($img, $cx+$xp*$x+$xa, $cy+$yp*($y1+1)+$ya, $diffColor);
                    }
                    if ($stop < ($i+1)*M_PI/2 && $x <= $xStop) {
                        $diffColor1 = imageColorExactAlpha( $img, $color[0], $color[1], $color[2], 127-(127-$color[3])*$error1 );
                        $y2 = $_y1; if ($aaStopX)  imageSetPixel($img, $cx+$xp*$x+$xa, $cy+$yp*($y2-1)+$ya, $diffColor1);
                    } else {
                        $y2 = 0;
                    }
                    if ($y2 <= $y1) imageLine($img, $cx+$xp*$x+$xa, $cy+$yp*$y1+$ya, $cx+$xp*$x+$xa, $cy+$yp*$y2+$ya, $fillColor);
                }
            }
        }

        ///YYYYY

        for ( $y = 0; $y < $b; $y += 1 ) {
            /*$x = $a * sqrt( 1 - ($y*$y)/($b*$b) );

            $error = $x - (int)($x);
            $x = (int)($x);

            $diffColor = imageColorExactAlpha( $img, $color[0], $color[1], $color[2], 127-(127-$color[3])*$error );
            */
            $_x1 = $dxStop*$y;
            $_x2 = $dxStart*$y;
            if ($yStart > $yStop)
            {
                $error1 = $_x1 - (int)($_x1);
                $error2 = 1 - $_x2 + (int)$_x2;
                $_x1 = $_x1-$error1;
                $_x2 = $_x2+$error2;
            }
            else
            {
                $error1 = 1 - $_x1 + (int)$_x1;
                $error2 = $_x2 - (int)($_x2);
                $_x1 = $_x1+$error1;
                $_x2 = $_x2-$error2;
            }
    /*
            if (!$aaStopX)
                $diffColor1 = imageColorExactAlpha( $img, $color[0], $color[1], $color[2], 127-(127-$color[3])*$error1 );
            if (!$aaStartX)
                $diffColor2 = imageColorExactAlpha( $img, $color[0], $color[1], $color[2], 127-(127-$color[3])*$error2 );
    */

            if ($seg == 0 || $seg == 2)
            {
                $i = $seg;
                if (!($start > $i*M_PI/2 && $y > $yStop)) {
                    if ($i == 0) {
                        $xp = +1; $yp = -1; $xa = 1; $ya = 0;
                    } else {
                        $xp = -1; $yp = +1; $xa = 0; $ya = 1;
                    }
                    if ( $stop < ($i+1)*(M_PI/2) && $y <= $yStop ) {
                        $diffColor1 = imageColorExactAlpha( $img, $color[0], $color[1], $color[2], 127-(127-$color[3])*$error1 );
                        $x1 = $_x1; if (!$aaStopX) imageSetPixel($img, $cx+$xp*($x1-1)+$xa, $cy+$yp*($y)+$ya, $diffColor1);
                    } 
                    if ($start > $i*M_PI/2 && $y < $yStart) {
                        $diffColor2 = imageColorExactAlpha( $img, $color[0], $color[1], $color[2], 127-(127-$color[3])*$error2 );
                        $x2 = $_x2; if (!$aaStartX) imageSetPixel($img, $cx+$xp*($x2+1)+$xa, $cy+$yp*($y)+$ya, $diffColor2);
                    } else {
                        $x = $a * sqrt( 1 - ($y*$y)/($b*$b) );
                        $error = $x - (int)($x);
                        $x = (int)($x);
                        $diffColor = imageColorExactAlpha( $img, $color[0], $color[1], $color[2], 127-(127-$color[3])*$error );
                        $x1 = $x; if ($y < $aaAngleY && $y <= $yStop ) imageSetPixel($img, $cx+$xp*($x1+1)+$xa, $cy+$yp*$y+$ya, $diffColor);
                    }
                }
            }

            if ($seg == 1 || $seg == 3)
            {
                $i = $seg;
                if (!($stop < ($i+1)*M_PI/2 && $y > $yStart)) {
                    if ($i == 1) {
                        $xp = -1; $yp = -1; $xa = 0; $ya = 0;
                    } else {
                        $xp = +1; $yp = +1; $xa = 1; $ya = 1;
                    }
                    if ( $start > $i*M_PI/2 && $y < $yStart ) {
                        $diffColor2 = imageColorExactAlpha( $img, $color[0], $color[1], $color[2], 127-(127-$color[3])*$error2 );
                        $x1 = $_x2; if (!$aaStartX) imageSetPixel($img, $cx+$xp*($x1-1)+$xa, $cy+$yp*$y+$ya,  $diffColor2);
                    } 
                    if ($stop < ($i+1)*M_PI/2 && $y <= $yStop) {
                        $diffColor1 = imageColorExactAlpha( $img, $color[0], $color[1], $color[2], 127-(127-$color[3])*$error1 );
                        $x2 = $_x1; if (!$aaStopX)  imageSetPixel($img, $cx+$xp*($x2+1)+$xa, $cy+$yp*$y+$ya, $diffColor1);
                    } else {
                        $x = $a * sqrt( 1 - ($y*$y)/($b*$b) );
                        $error = $x - (int)($x);
                        $x = (int)($x);
                        $diffColor = imageColorExactAlpha( $img, $color[0], $color[1], $color[2], 127-(127-$color[3])*$error );
                        $x1 = $x; if ($y < $aaAngleY  && $y < $yStart) imageSetPixel($img,$cx+$xp*($x1+1)+$xa,  $cy+$yp*$y+$ya, $diffColor);
                    }
                }
            }
        }
    }


    function imageSmoothArc ( &$img, $cx, $cy, $w, $h, $color, $start, $stop)
    {
        // Originally written from scratch by Ulrich Mierendorff, 06/2006
        // Rewritten and improved, 04/2007, 07/2007
        // compared to old version:
        // + Support for transparency added
        // + Improved quality of edges & antialiasing

        // note: This function does not represent the fastest way to draw elliptical
        // arcs. It was written without reading any papers on that subject. Better
        // algorithms may be twice as fast or even more.

        // what it cannot do: It does not support outlined arcs, only filled

        // Parameters:
        // $cx      - Center of ellipse, X-coord
        // $cy      - Center of ellipse, Y-coord
        // $w       - Width of ellipse ($w >= 2)
        // $h       - Height of ellipse ($h >= 2 )
        // $color   - Color of ellipse as a four component array with RGBA
        // $start   - Starting angle of the arc, no limited range!
        // $stop    - Stop     angle of the arc, no limited range!
        // $start _can_ be greater than $stop!
        // If any value is not in the given range, results are undefined!

        // This script does not use any special algorithms, everything is completely
        // written from scratch; see http://de.wikipedia.org/wiki/Ellipse for formulas.

        while ($start < 0)
            $start += 2*M_PI;
        while ($stop < 0)
            $stop += 2*M_PI;

        while ($start > 2*M_PI)
            $start -= 2*M_PI;

        while ($stop > 2*M_PI)
            $stop -= 2*M_PI;


        if ($start > $stop)
        {
            imageSmoothArc ( $img, $cx, $cy, $w, $h, $color, $start, 2*M_PI);
            imageSmoothArc ( $img, $cx, $cy, $w, $h, $color, 0, $stop);
            return;
        }

        $a = 1.0*round ($w/2);
        $b = 1.0*round ($h/2);
        $cx = 1.0*round ($cx);
        $cy = 1.0*round ($cy);

        $aaAngle = atan(($b*$b)/($a*$a)*tan(0.25*M_PI));
        $aaAngleX = $a*cos($aaAngle);
        $aaAngleY = $b*sin($aaAngle);

        $a -= 0.5; // looks better...
        $b -= 0.5;

        for ($i=0; $i<4;$i++)
        {
            if ($start < ($i+1)*M_PI/2)
            {
                if ($start > $i*M_PI/2)
                {
                    if ($stop > ($i+1)*M_PI/2)
                    {
                        $this->imageSmoothArcDrawSegment($img, $cx, $cy, $a, $b, $aaAngleX, $aaAngleY , $color, $start, ($i+1)*M_PI/2, $i);
                    }
                    else
                    {
                        $this->imageSmoothArcDrawSegment($img, $cx, $cy, $a, $b, $aaAngleX, $aaAngleY, $color, $start, $stop, $i);
                        break;
                    }
                }
                else
                {
                    if ($stop > ($i+1)*M_PI/2)
                    {
                        $this->imageSmoothArcDrawSegment($img, $cx, $cy, $a, $b, $aaAngleX, $aaAngleY, $color, $i*M_PI/2, ($i+1)*M_PI/2, $i);
                    }
                    else
                    {
                        $this->imageSmoothArcDrawSegment($img, $cx, $cy, $a, $b, $aaAngleX, $aaAngleY, $color, $i*M_PI/2, $stop, $i);
                        break;
                    }
                }
            }
        }
    }
}