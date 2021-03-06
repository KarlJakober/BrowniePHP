<?php

class ThumbsController extends BrownieAppController{

	var $name = 'Thumbs';
	var $uses = array();
	var $autoRender = false;

	function beforeFilter() {
		$this->Auth->allow('*');
	}

	/**
	* 150x113 recorta, si es necesario agranda la imagen
	* 200_900 no recorta, no agranda
	*/
	function view($model = '', $recordId = '', $sizes = '', $file = '') {
		$sourceFile = WWW_ROOT . 'uploads' . DS . $model . DS . $recordId . DS . $file;
		if (!file_exists($sourceFile)) {
			$this->cakeError('error404');
		}
		$pathinfo = pathinfo($sourceFile);
		App::import('Vendor', 'Brownie.resizeimage');
		$format = $pathinfo['extension'];
		$cacheDir = WWW_ROOT . 'uploads' . DS . 'thumbs';
		$destDir = $cacheDir . DS . $model . DS . $sizes. DS . $recordId;
		if (!is_dir($destDir)) {
			if (!mkdir($destDir, 0755, true)) {
				$this->log('cant create dir on ' . __FILE__ . ' line ' . __LINE__);
			}
		}
		$cachedFile = $destDir . DS . $file;
		if (!is_file($cachedFile) or 1) {
			ini_set('memory_limit', '128M');
			copy($sourceFile, $cachedFile);
			resizeImage($cachedFile, $sizes);
		}

		if (is_file($cachedFile)) {
			$cachedImage = getimagesize($cachedFile);
			header('Content-Type: '.$cachedImage['mime']);
			readfile($cachedFile);
			exit;
		}

    }

	function _sizes($sizes) {

		$r_sizes = array();

		$s = explode('x', $sizes);
    	if(count($s == 2) and ctype_digit($s[0]) and ctype_digit($s[1])) {
    		$r_sizes = array('w' => $s[0], 'h' => $s[1], 'crop' => 'resizeCrop');
		} else {
			$s = explode('_', $sizes);
			if (count($s == 2) and ctype_digit($s[0]) and ctype_digit($s[1])) {
	    		$r_sizes = array('w' => $s[0], 'h' => $s[1], 'crop' => 'resize');
			}
		}

		return $r_sizes;
    }

}