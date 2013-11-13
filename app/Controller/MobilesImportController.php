<?php
/**
 * Import content controller.
 *
 * Este arquivo ira renderizar as visões contidas em views/LandlinesImport/
 *
 * PHP 5
 *
 * @copyright     Copyright 2013-2013, Nasza Produtora
 * @link          http://www.nasza.com.br/ Nasza(tm) Project
 * @package       app.Controller
 */

App::uses('ImportsController', 'Controller');

/**
 * Import content controller
 *
 * Este controlador contem regras de negócio aplicadas ao model State
 *
 * @package       app.Controller
 * @link http://.framework.nasza.com.br/2.0/controller/LandlinesImport.html
 */
class MobilesImportController extends ImportsController {
	/**
	* Método run
	* Este método importa os telefones Fixos no modelo da base de dados do Natt para o Sistema
	*
	* @return void
	*/
	public function run($uf=null){
		/**
		* Verifica se a chave do modulo de importacao esta ativa
		*/
		if(!$this->Settings->active($this->name)){
			die;
		}
		
		/**
		* Desabilita o contador landline e habilita o mobile
		*/
		$this->Counter->updateAll(array('Counter.active' => null), array('Counter.table' => 'landlines'));
		$this->Counter->updateAll(array('Counter.active' => true), array('Counter.table' => 'mobiles'));

		/**
		* Verifica se foi passado algum estado por parametro
		*/
		if($uf){
			$this->uf = strtoupper($uf);
			
			/**
			* Carrega os models com o nome das tabelas
			*/
			$this->NattMobile->useTable = $this->uf;

			/**
			* Calcula o limite de importacao por reload
			*/
			$this->limit_Per_reload = ($this->Ientity->find('count') + LIMIT_TABLE_IMPORTS);

			/**
			* Calcula o total de registros que sera importado de cada tabela
			*/
			$this->qt_reg = $this->NattMobile->find('count', array('conditions' => array('CPF_CNPJ !=' => '00000000000000000000')));
			$start_time = time();
			$this->Counter->updateAll(array('Counter.extracted' => $this->qt_reg, 'Counter.start_time' => $start_time), array('table' => 'entities', 'active' => '1'));

			/**
			* Inicia o processo de importacao
			*/
			$this->AppImport->__log("Importacao Iniciada.", IMPORT_BEGIN, $this->uf);
			for ($i=0; $i < $this->qt_reg; $i+=LIMIT_BUILD_SOURCE) { 

				/**
				* Recarrega a importacao
				*/
				if(!empty($this->AppImport->counter['entities']['success']) && $this->AppImport->counter['entities']['success'] >= $this->limit_Per_reload){
					$path = dirname(dirname(dirname(__FILE__)));
					shell_exec("setsid sh {$path}/_db/settings/mobiles_reload.sh > /dev/null 2>/dev/null &");
				}


				/**
				* Carrega o proximo registro das tabelas de pessoa, telefone e endereco q ainda nao foram importado
				*/
				$this->AppImport->timing_ini(TUNING_LOAD_NEXT_REGISTER);
				$entities = $this->NattMobile->next($i, LIMIT_BUILD_SOURCE);
				$this->AppImport->timing_end();

				foreach ($entities as $k => $v) {	
					/**
					* Verifica se a chave do modulo de importacao esta ativa
					*/
					$this->AppImport->timing_ini(TUNING_ON_OF);
					if(!$this->Settings->active($this->name)){
						$this->AppImport->__log("Importacao Pausada.", IMPORT_PAUSED, $this->uf);
						die;
					}
					$this->AppImport->timing_end();

					if(isset($v['pessoa'])){
						/**
						* Gera o hash do nome da entidade
						*/
						$hash = $this->AppImport->getHash($this->AppImport->clearName($v['pessoa']['NOME_RAZAO']));

						/**
						* Trata os dados da entidade para a importacao
						*/
						//Carrega o tipo de documento
						$doc_type = $this->AppImport->getTypeDoc($v['pessoa']['CPF_CNPJ'], $this->AppImport->clearName($v['pessoa']['NOME_RAZAO']), $this->AppImport->clearName($v['pessoa']['MAE']), $this->AppImport->getBirthday($v['pessoa']['DT_NASCIMENTO']));
						$this->AppImport->timing_ini(TUNING_ENTITY_LOAD);
						$data = array(
							'Ientity' => array(
								'doc' => $v['pessoa']['CPF_CNPJ'],
								'name' => $this->AppImport->clearName($v['pessoa']['NOME_RAZAO']),
								'mother' => $this->AppImport->clearName($v['pessoa']['MAE']),
								'type' => $doc_type,
								'gender' => $this->AppImport->getGender($v['pessoa']['SEXO'], $doc_type, $v['pessoa']['NOME_RAZAO']),
								'birthday' => $this->AppImport->getBirthday($v['pessoa']['DT_NASCIMENTO']),
								'h1' => $hash['h1'],
								'h2' => $hash['h2'],
								'h3' => $hash['h3'],
								'h4' => $hash['h4'],
								'h5' => $hash['h5'],
								'h_all' => $hash['h_all'],
								'h_first_last' => $hash['h_first_last'],
								'h_last' => $hash['h_last'],
								'h_first1_first2' => $hash['h_first1_first2'],
								'h_last1_last2' => $hash['h_last1_last2'],
								'h_mother' => $this->AppImport->getHash($v['pessoa']['MAE'], 'h_all'),
								)
							);
						$this->AppImport->timing_end();

						/**
						* Executa a importacao da tabela Entity
						* e carrega o id da entidade importada
						*/
						$this->AppImport->timing_ini(TUNING_ENTITY_IMPORT);
						$this->importEntity($data);
						$this->AppImport->timing_end();


						/**
						* Exibe o status da importacao no console 
						*/
						// $this->AppImport->progressBar($this->qt_imported, $this->qt_reg, $this->uf);

						/**
						* Inicializa a importacao dos telefones da entidade encontrada
						*/
						if(isset($v['telefone'])){
							foreach ($v['telefone'] as $v2) {
								/**
								* Desmembra o DDD do Telefone
								*/
								$this->AppImport->timing_ini(TUNING_LANDLINE_LOAD);
								$ddd_telefone = $v2['TELEFONE'];
								$ddd = $this->AppImport->getDDDMobile($v2['TELEFONE']);
								$telefone = $this->AppImport->getMobile($v2['TELEFONE']);
							
								/**
								* Extrai o ano de atualizacao do telefone
								*/
								$year = $this->AppImport->getUpdated($v2['DATA_ATUALIZACAO']);

								/**
								* Trata os dados o telefone para a importacao
								*/
								$data = array(
									'Imobile' => array(
										'year' => $year,
										'ddd' => $ddd,
										'tel' => $telefone,
										'tel_full' => "{$ddd}{$telefone}",
										'tel_original' => $v2['TELEFONE'],
										)
									);
								$this->AppImport->timing_end();
								
								/**
								* Executa a importacao do telefone
								* e carrega o id do telefone importado
								*/
								$this->AppImport->timing_ini(TUNING_LANDLINE_IMPORT);
								$this->importMobile($data, $v2['TELEFONE']);
								$this->AppImport->timing_end();


								/**
								* Inicializa a importacao dos telefones da entidade encontrada
								*/
								if(isset($v2['endereco'])){
									/**
									* Inicializa a importacao do CEP do telefone encontrado
									* Trata os dados do CEP para a importacao
									*/				
									$this->AppImport->timing_ini(TUNING_ZIPCODE_LOAD);		
									$data = array(
										'Izipcode' => array(
											'code' => $this->AppImport->getZipcode($v2['endereco']['CEP']),
											'code_original' => $v2['endereco']['CEP']
											)
										);
									$this->AppImport->timing_end();

									/**
									* Executa a importacao do CEP
									* e carrega o id do CEP importado
									*/
									$this->AppImport->timing_ini(TUNING_ZIPCODE_IMPORT);
									$this->importZipcode($data);
									$this->AppImport->timing_end();

									/**
									* Inicializa a importacao do endereco do telefone encontrado
									* Trata os dados do endereço para a importacao
									*/	
									$this->AppImport->timing_ini(TUNING_ADDRESS_LOAD);
									
									$state_id = $this->AppImport->getState($v2['endereco']['UF'], $this->uf);
									$city_id = $this->AppImport->getCityId($v2['endereco']['CIDADE'], $state_id, $this->Izipcode->id);
									$city = $this->AppImport->getCity($v2['endereco']['CIDADE']);
									$zipcode = $this->AppImport->getZipcode($v2['endereco']['CEP']);
									$number = $this->AppImport->getStreetNumber($v2['NUMERO'], $v2['endereco']['NOME_RUA']);

									/**
									* Trata o nome da rua
									*/
									$street = $this->AppImport->getStreet($v2['endereco']['NOME_RUA']);

									/**
									* Gera o hash do nome da rua
									*/
									$hash = $this->AppImport->getHash($street);

									/**
									* Gera o hash do complemento da rua
									*/
									$hash_complement = $this->AppImport->getHash($this->AppImport->getComplement($v2['COMPLEMENTO']), null, false);

									/**
									* Carrega um array com todos os estados
									*/
									$map_states = $this->AppImport->loadStates(true);

									$data = array(
										'Iaddress' => array(
											'state_id' => $state_id,
											'zipcode_id' => $this->Izipcode->id,
											'city_id' => $city_id,
											'state' => $map_states[$state_id],
											'zipcode' => $zipcode,
											'city' => $city,
											'type_address' => $this->AppImport->getTypeAddress($v2['endereco']['RUA'], $v2['endereco']['NOME_RUA']),
											'street' => $street,
											'number' => $number,
											'neighborhood' => $this->AppImport->getNeighborhood($v2['endereco']['BAIRRO']),
											'complement' => $this->AppImport->getComplement($v2['COMPLEMENTO']),
											'h1' => $hash['h1'],
											'h2' => $hash['h2'],
											'h3' => $hash['h3'],
											'h4' => $hash['h4'],
											'h5' => $hash['h5'],
											'h_all' => $hash['h_all'],
											'h_first_last' => $hash['h_first_last'],
											'h_last' => $hash['h_last'],
											'h_first1_first2' => $hash['h_first1_first2'],
											'h_last1_last2' => $hash['h_last1_last2'],
											'h_complement' => $hash_complement['h_all'],
											)
										);
									$this->AppImport->timing_end();

									/**
									* Executa a importacao do Endereço
									* e carrega o id do Endereço importado
									*/
									$this->AppImport->timing_ini(TUNING_ADDRESS_IMPORT);
									$this->importAddress($data);
									$this->AppImport->timing_end();
									
								}

								/**
								* Amarra os registros Entidade, Telefone, CEP e Endereço na tabela associations
								*/

								/**
								* Carrega todos os id coletados ate o momento
								*/
								$this->AppImport->timing_ini(TUNING_LOAD_ALL_DATA);
								$data = array(
									'Iassociation' => array(
										'entity_id' => $this->Ientity->id,
										'landline_id' => null,
										'mobile_id' => $this->Imobile->id,
										'address_id' => $this->Iaddress->id,
										'year' => $year,
										)
									);
								$this->AppImport->timing_end();
								
								$this->AppImport->timing_ini(TUNING_IMPORT_ALL_DATA);
								$this->importAssociation($data);
								$this->AppImport->timing_end();

								/**
								* Salva as contabilizacoes na base de dados
								*/					
								$this->AppImport->__counter('entities');
								$this->AppImport->__counter('mobiles');
								$this->AppImport->__counter('addresses');
								$this->AppImport->__counter('zipcodes');
								$this->AppImport->__counter('associations');	
							}
						}

						/**
						* Salva as contabilizacoes na base de dados
						*/					
						$this->AppImport->__counter('entities');
					}else{
						$this->AppImport->fail('entities');
					}
				}
			}

			/**
			* Finaliza o processo de importacao
			*/
			exit();
		}
	}
}