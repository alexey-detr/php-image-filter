<?php

/**
 * Based on the Imagick image filters.
 */
class ImageFilter
{
	const FORMAT_JPEG = 'jpeg';
	const FORMAT_PNG = 'png';
	const FORMAT_GIF = 'gif';
	const FORMAT_BMP = 'bmp';

	const RESIZE_DECREASE = 1;
	const RESIZE_INCREASE = 2;
	const RESIZE_BOTH = 3;

	/**
	 * @var Imagick
	 */
	private $_image = null;

	/**
	 * @var string
	 */
	private $_extension = null;

	public function __construct($pathIn)
	{
		$this->_image = new Imagick($pathIn);
		$extension = pathinfo($pathIn, PATHINFO_EXTENSION);
		$this->_extension = strtolower($extension);
	}

	/**
	 * Checks if current resize behavior requires resizing actions.
	 * @param integer $newWidth
	 * @param integer $newHeight
	 * @param integer $resize
	 * @return boolean
	 */
	private function _checkResizeBehavior($newWidth, $newHeight, $resize)
	{
		if ($resize == ImageFilter::RESIZE_DECREASE) {
			if ($newWidth > $this->_image->getimagewidth() && $newHeight > $this->_image->getimageheight()) {
				return false;
			}
		}
		if ($resize == ImageFilter::RESIZE_INCREASE) {
			if ($newWidth < $this->_image->getimagewidth() && $newHeight < $this->_image->getimageheight()) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Resize image.
	 * @param integer $newWidth
	 * @param integer $newHeight
	 * @param integer $resize Defines behavior for resizing.
	 * @param integer $quality Quality of result image compressing (i.e. JPEG compression quality).
	 */
	public function filterResize($newWidth, $newHeight, $resize = ImageFilter::RESIZE_BOTH, $quality = 100)
	{
		if (!$this->_checkResizeBehavior($newWidth, $newHeight, $resize)) {
			return $this;
		}
		$this->_image->setImageCompressionQuality($quality);
		if ($this->_extension == 'gif') {
			foreach ($this->_image as $frame) {
				$frame->thumbnailImage($newWidth, $newHeight, true);
				$w = $frame->getImageWidth();
				$h = $frame->getImageHeight();
				$frame->setImagePage($w, $h, 0, 0);
			}
		} else {
			$this->_image->thumbnailimage($newWidth, $newHeight, true);
		}
		return $this;
	}

	/**
	 * Resize image with cropping. Result will be image with exact width and height.
	 * @param integer $newWidth
	 * @param integer $newHeight
	 * @param integer $resize Defines behavior for resizing.
	 * @param integer $quality Quality of result image compressing (i.e. JPEG compression quality).
	 */
	public function filterResizeCrop($newWidth, $newHeight, $resize = ImageFilter::RESIZE_BOTH, $quality = 100)
	{
		if (!$this->_checkResizeBehavior($newWidth, $newHeight, $resize)) {
			return $this;
		}
		$this->_image->setImageCompressionQuality($quality);
		if ($this->_extension == 'gif') {
			foreach ($this->_image as $frame) {
				$this->_image->cropthumbnailimage($newWidth, $newHeight);
				$w = $frame->getImageWidth();
				$h = $frame->getImageHeight();
				$frame->setImagePage($w, $h, 0, 0);
			}
		} else {
			$this->_image->cropthumbnailimage($newWidth, $newHeight);
		}
		return $this;
	}

	/**
	 * Resize image. Output will be image with equals width and height.
	 * @param integer $newWidthAndHeight
	 * @param integer $resize Defines behavior for resizing.
	 * @param integer $quality Quality of result image compressing (i.e. JPEG compression quality).
	 */
	public function filterResizeQuad($newWidthAndHeight, $resize = ImageFilter::RESIZE_BOTH, $quality = 100)
	{
		if (!$this->_checkResizeBahavior($newWidthAndHeight, $newWidthAndHeight, $resize)) {
			return $this;
		}
		$this->_image->setImageCompressionQuality($quality);
		if ($this->_extension == 'gif') {
			foreach ($this->_image as $frame) {
				$this->_image->cropThumbnailImage($newWidthAndHeight, $newWidthAndHeight);
				$frame->setImagePage($newWidthAndHeight, $newWidthAndHeight, 0, 0);
			}
		} else {
			$this->_image->cropthumbnailimage($newWidthAndHeight, $newWidthAndHeight);
		}
		return $this;
	}

	/**
	 * Desaturates image.
	 */
	public function filterDesaturate()
	{
		$this->_image->modulateimage(100, 0, 100);
		return $this;
	}

	/**
	 * The function returns the pixel with the averaged color by pixels in a 2-parameters.
	 * @return ImagickPixel
	 */
	private function _getAvgColor($pixel1, $pixel2)
	{
		$resultR = ($pixel1->getColorValue(imagick::COLOR_RED) +
			$pixel2->getColorValue(imagick::COLOR_RED))/2;
		$resultG = ($pixel1->getColorValue(imagick::COLOR_GREEN) +
			$pixel2->getColorValue(imagick::COLOR_GREEN))/2;
		$resultB = ($pixel1->getColorValue(imagick::COLOR_BLUE) +
			$pixel2->getColorValue(imagick::COLOR_BLUE))/2;

		$resultPixel = new ImagickPixel();
		$resultPixel->setColorValue(imagick::COLOR_RED, $resultR);
		$resultPixel->setColorValue(imagick::COLOR_GREEN, $resultG);
		$resultPixel->setColorValue(imagick::COLOR_BLUE, $resultB);

		return $resultPixel;
	}

	/**
	 * Adds a border to an image.
	 * @param image Imagick source image
	 * @param dir string border addiction direction must be 'left', 'right' or 'both'
	 * @param width integer required image width
	 * @param height integer required image height
	 */
	private function _addBorder($dir, $width, $height)
	{
		if($dir == 'width')
		{
			// get pixel color
			$leftPix = $this->_image->getImagePixelColor(0, $this->_image->getImageHeight() / 2);
			$rightPix = $this->_image->getImagePixelColor($this->_image->getImageWidth(),
				$this->_image->getImageHeight() / 2);

			$avgColor = $this->_getAvgColor($leftPix, $rightPix);
			$this->_image->frameImage($avgColor, ($width - $this->_image->getImageWidth()) / 2 , 0, 0, 0);
		} else if ($dir == 'height') {
			$pixColor = $this->_image->getImagePixelColor(0, $this->_image->getImageWidth() / 2);
			$this->_image->frameImage($pixColor, 0, ($height - $this->_image->getImageHeight()) / 2, 0, 0);
		} else {
			// both
			$pixColor = $this->_image->getImagePixelColor(0, $this->_image->getImageHeight() / 2);
			$this->_image->frameImage($pixColor, ($width - $this->_image->getImageWidth()) / 2,
											($height - $this->_image->getImageHeight()) / 2, 0, 0);
		}
	}

	/**
	 * Adds border to an image. Border color calculates from average
	 * pixel color of image frame of 1px width.
	 * @param integer $width
	 * @param integer $height
	 */
	public function addBorder($width, $height)
	{
		$imgWidth = $this->_image->getImageWidth();
		$imgHeight = $this->_image->getImageHeight();

		if ($imgWidth < $width || $imgHeight < $height) {
			$this->_addBorder('both', $width, $height);
		}
	}

	/**
	 * Sets image format.
	 * @param string $format
	 */
	public function setImageFormat($format)
	{
		$this->_image->setimageformat($format);
		return $this;
	}

	/**
	 * Saves image to file by specified outup path.
	 * @param string $pathOut
	 */
	public function writeImage($pathOut)
	{
		if ($this->_extension == 'gif') {
			return $this->_image->writeImages($pathOut, true) === true;
		} else {
			return $this->_image->writeImage($pathOut) === true;
		}
	}

	/**
	 * Destroys imagick object.
	 */
	public function destory()
	{
		$this->_image->destroy();
	}
}
