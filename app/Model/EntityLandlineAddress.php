<?php
App::uses('AppModelClean', 'Model');
/**
 * EntityLandlineAddress Model
 *
 * Esta classe é responsável ​​pela gestão de quase tudo o que acontece a respeito do(a) Estado, 
 * é responsável também pela validação dos seus dados.
 *
 * PHP 5
 *
 * @copyright     Copyright 2013-2013, Nasza Produtora
 * @link          http://www.nasza.com.br/ Nasza(tm) Project
 * @package       app.Model
 *
 * EntityLandlineAddress Model
 *
 * @property Country $Country
 * @property City $City
 */
class EntityLandlineAddress extends AppModelClean {
	public $useTable = 'entities_landlines_addresses';


		public $hasMany = array(
	        'Address' => array(
	            'className' => 'Address',
	            'foreignKey' => 'address_id'
	        ),
	        'Landline' => array(
	            'className' => 'Landline',
	            'foreignKey' => 'landline_id'
	        ),
	        'Entity' => array(
	            'className' => 'Entity',
	            'foreignKey' => 'entity_id'
	        )
    	);

}
