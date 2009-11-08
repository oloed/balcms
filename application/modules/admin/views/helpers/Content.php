<?php
require_once 'Zend/View/Helper/Abstract.php';
class Bal_View_Helper_Content extends Zend_View_Helper_Abstract {
	
	public $view;
	public function setView (Zend_View_Interface $view) {
		$this->view = $view;
	}
	
	public function content ( ) {
		return $this;
	}
	
	
	public function render ( $content ) {
		return $this->view->getHelper('widget')->renderAll($content);
	}
	
	protected function renderPendingWidget ( $code, $params = array() ) {
		return '<strong>'.$code.' widget should go here with params: '.var_export($params,true).'</strong>';
	}
	public function renderCarouselWidget ( $params = array() ) {
		return $this->renderPendingWidget('carousel', $params);
	}
	
	public function renderTaglistWidget ( $params = array() ) {
		return $this->renderPendingWidget('taglist', $params);
	}
	
	public function renderRecentlistWidget ( $params = array() ) {
		return $this->renderPendingWidget('recentlist', $params);
	}
	
}