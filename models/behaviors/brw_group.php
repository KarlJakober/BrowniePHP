<?php

class BrwGroupBehavior extends ModelBehavior {

	function setup($Model, $config = array()) {
		$Model->brwConfig = $this-> _brwConfig($Model);
		$Model->validate = $this->_validate($Model);
		$Model->bindModel(array('hasMany' => array('BrwUser')));
		$Model->Behaviors->attach('Tree');
	}

	function _brwConfig($Model) {
		$defaultBrwConfig = array(
			'names' => array(
				'section' => 'Grupos de usuarios',
				'plural' => 'Grupos de usuarios',
				'singular' => 'Grupo de usuarios',
			),
		);
		if(empty($Model->brwConfig)) {
			$Model->brwConfig = array();
		}
		return Set::merge($defaultBrwConfig, $Model->brwConfig);
	}

	function _validate($Model) {
		return array();

		$defaultValidate = array(
			'email' => array(
				array(
					'rule' => 'email'
				),
				array(
					'rule' => 'notEmpty',
					'on' => 'create',
					'required' => true,
				)
			),
			'password' => array(
				'rule' => 'notEmpty',
				'on' => 'create',
				'required' => true,
			),
		);
		return Set::merge($defaultValidate, $Model->validate);
	}

	function brwBeforeEdit($data) {
		$data['BrwUser']['password'] = $data['BrwUser']['repeat_password'] = '';
		return $data;
	}

}