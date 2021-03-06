<?php

class BrownieAppController extends AppController {

	var $components = array('Auth', 'Session');
	var $helpers = array('Html', 'Session', 'Javascript');
	var $uses = array('BrwUser');
	var $layout = 'brownie_default';
	static $currentUser;

	function beforeFilter() {
		$this->_authSettings();
		$this->_multiSiteSettings();
		//$this->_modelsToDb();
		$this->_menuConfig();
		$this->pageTitle = __d('brownie', 'Control panel', true);

		if (!$this->Session->read('Auth.BrwUser')) {
			$this->Session->delete('BrwSite');
		}

		parent::beforeFilter();
	}

	function beforeRender() {
		$this->_companyName();
		parent::beforeRender();
	}

	function _authSettings() {
		$this->Auth->userModel = 'BrwUser';
		$this->Auth->fields = array('username'  => 'email', 'password'  => 'password');
		$this->Auth->loginAction = array('controller' => 'brownie', 'action' => 'login', 'plugin' => 'brownie');
		$this->Auth->loginRedirect = array('controller' => 'brownie', 'action' => 'index', 'plugin' => 'brownie');
		$this->Auth->loginError = __d('brownie', 'Login failed. Invalid username or password.', true);
		$this->Auth->authError = __d('brownie', 'Please login.', true);
		Configure::write('Auth.BrwUser', $this->Session->read('Auth.BrwUser'));
		$this->set('authUser', $this->Session->read('Auth.BrwUser'));
		$this->set('BrwUser', $this->Session->read('Auth.BrwUser'));
		$this->set('isUserRoot', $this->Session->read('Auth.BrwUser.root'));
		self::$currentUser = $this->Session->read('Auth.BrwUser');
	}

	function _multiSiteSettings() {
		if ($multiSitesModel = Configure::read('multiSitesModel') and $this->Session->read('Auth.BrwUser')) {
			Controller::loadModel($multiSitesModel);
			$sitesOptions = $this->BrwUser->sites($this->Session->read('Auth.BrwUser'));
			if ($this->Session->read('Auth.BrwUser.root')) {
				Configure::write('currentSite', $this->Session->read('BrwSite.Site'));
			} else {
				if (count($sitesOptions) == 1) {
					$siteId = array_shift(array_keys($sitesOptions));
					Configure::write('currentSite', array_shift($this->{$multiSitesModel}->read(null, $siteId)));
				} else {
					if (in_array($this->Session->read('BrwSite.Site.id'), array_keys($sitesOptions))) {
						Configure::write('currentSite', $this->Session->read('BrwSite.Site'));
					} else {
						Configure::write('currentSite', null);
					}
				}
			}
			if (!$this->Session->check('BrwSite.Site')) {
				$this->Session->write('BrwSite.Site', Configure::read('currentSite'));
			}
			$this->set('sitesOptions', $sitesOptions);
		}
	}

	function _currentSite() {
		return Configure::read('currentSite');
	}

	/*
	function _modelsToDb() {
		if (Configure::read('debug')) {
			$modelsHash = $this->_modelsHash();
			if (!$this->Session->check('modelsHash') or $this->Session->read('modelsHash') != $modelsHash) {
				$this->Session->write('modelsHash', $modelsHash);
				$this->BrwModel->toDb();
			}
		}
	}

	function _modelsHash() {
		$handle = opendir(MODELS);
		$toHash = date('h');
		while ($file = readdir($handle)) {
			if ($file != '.' and $file != '..') {
				$toHash .= $file . filesize(MODELS . DS . $file);
			}
		}
		return Security::hash($toHash);
	}*/

	function _menuConfig() {
		$menu = array();
		if	($this->_currentSite() and !empty($this->brwSiteMenu)) {
			$menu = $this->brwSiteMenu;
		} elseif($this->Session->read('Auth.BrwUser.root')) {
			if (!empty($this->brwMenu)) {
				$menu = $this->brwMenu;
			} else {
				$menu = array();
				$models = App::objects('model');
				foreach($models as $model) {
					$button = Inflector::humanize(Inflector::underscore(Inflector::pluralize($model)));
					$menu[$button] = $model;
				}
				$menu = array(__d('brownie', 'Menu', true) => $menu);
			}
		}
		$this->set('brwMenu', $menu);
	}


	function _companyName() {
		if ($this->Session->check('BrwSite')) {
			$this->companyName = $this->Session->read('BrwSite.Site.name');
		} elseif (empty($this->companyName)) {
			$this->companyName = '';
		}
		$this->set('companyName', $this->companyName);
	}

	/*
	function checkAdminSession() {
		// if the admin session hasn't been set
		if (!$this->Session->check('BrwUser')) {
			// set flash message and redirect
			//$this->Session->setFlash(__d('brownie', 'You need to be logged in to access this area', true));
			$this->redirect(array(
				'controller' => 'users',
				'action' => 'login',
				'plugin' => 'brownie'
			));
			exit();
		} else {
			$this->user = $this->Session->read('BrwUser');
		}
	}*/

	/*
	function menuConfig($menu) {
		if ($this->Session->read('BrwUser.root')) {
			return $menu;
		}
		foreach ($menu as $keyGroup => $menuGroup) {
			foreach ($menuGroup as $key => $model) {
				if (!$this->_checkPermissions($model, 'view')) {
					unset($menu[$keyGroup][$key]);
				}
			}
			if (empty($menu[$keyGroup])) {
				unset($menu[$keyGroup]);
			}
		}
		return $menu;
	}*/

	function _brwCheckPermissions($model, $action = 'read', $id = null) {
		$Model = ClassRegistry::getObject($model);
		if (!$Model) {
			return false;
		}
		$noPermission = (
			$siteModel = Configure::read('multiSitesModel')
			and $model != $siteModel
			and $model != 'BrwUser'
			and !Configure::read('Auth.BrwUser.root')
			and empty($this->Model->belongsTo[Configure::read('multiSitesModel')])
		);
		if ($noPermission) {
			return false;
		}

		//really bad patch, solucionar con permisos reales
		if ($action == 'read') {
			return true;
		}

		if ($action == 'js_edit') {
			return true;
		}
		if (in_array($action, array('reorder', 'edit_upload', 'delete_upload'))) {
			$action = 'edit';
		}
		if ($action == 'export') {
			$action = 'view';
		}
		if ($action == 'filter') {
			$action = 'index';
		}
		if (!in_array($action, array('index', 'add', 'view', 'delete', 'edit', 'import'))) {
			return false;
		}
		$Model->Behaviors->attach('Brownie.Panel');
		if (!empty($this->Content)) {
			$actions = $Model->brwConfig['actions'];
			if (!$actions[$action]) {
				return false;
			}
		}

		if ($this->Session->read('Auth.BrwUser.root')) {
			return true;
		} else {
			if ($model == 'BrwUser') {
				switch ($action) {
					case 'add': case 'delete':
						return false;
					break;
					case 'index': case 'edit': case 'view':
						return true;
					break;
				}
			}
		}

		return true;

		/*$User = ClassRegistry::init('BrwUser');
		$User->Behaviors->attach('Containable');
		$user = $User->find('first', array(
			'conditions' => array('BrwUser.id' => $this->Session->read('BrwUser.id')),
			'contain' => array('BrwGroup' => array('BrwPermission' => array('BrwModel')))
		));
		//pr($user);
		$mapActions = array(
			'index' => 'view',
			'view' => 'view',
			'delete' => 'delete',
			'add_images' => 'edit',
			'edit' => 'edit',
		);

		if (empty($user['BrwGroup']['BrwPermission'])) {
			return false;
		} else {
			foreach ($user['BrwGroup']['BrwPermission'] as $permission) {
				if ($model == $permission['BrwModel']['model']) {
					return $permission[$mapActions[$action]];
				}
			}
		}
		*/
		return false;

	}

	function arrayPermissions($model) {
		$ret = array(
			'view' => false,
			'add' => false,
			'view' => false,
			'edit' => false,
			'delete' => false,
			'import' => false
		);
		foreach ($ret as $action => $value) {
			$ret[$action] = $this->_brwCheckPermissions($model, $action);
		}

		return $ret;
	}


}


function enum2array($string) {
	return explode("','", substr($string, 6, -2));
}
