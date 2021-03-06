
<?php
App::uses('AppHelper', 'View/Helper');


class AppUtilsHelper extends AppHelper {

	public $helpers = array('Main.AppPermissions', 'Html');

	private $menuPath;
	private $menuTemplate;

	/**
	 * Altera as chaves do array para o padrao de variaveis de template
	 */
	public function key2var($array){
		$values = array();
		foreach ($array as $k => $v) {
			if (!is_array($v)) {
				$values["%{$k}%"] = $v;
			}
		}

		return $values;
	}

	/**
	 * A funcao pluralName recebe uma string e a converte para o plural e tras para o diminutivo todas as letras da palavra. 
	 * Exemplo: $this->Ultils->pluralName("Exemplo") retorna "exemplos".
	 */
	public function pluralName($name) {
		return Inflector::variable(Inflector::pluralize($name));
	}

	/**
	* Por padrao, a funcao boolTxt retorna "Sim" quando o parametro $bool for 
	* verdadeira e "Nao" quando for falso, sendo que o texto de retorno pode ser alterados 
	* atraves do parametro $trueTxt e $falseTxt
	*/
	public function boolTxt($bool, $trueTxt="Yes", $falseTxt="No"){
		$txt = (isset($bool) && $bool == true)?$trueTxt:$falseTxt;
		$color = (isset($bool) && $bool == true)?'green':'red';
		$txt = "<font color=\"{$color}\">{$txt}</font>";

		return $txt;
	}

	/**
	* Por padrao, a funcao limitTxt retorna a string passada pelo parametro limitada a 100
	* caracteres, e caso o tamanho da string seja maior que o limite informado, sera concatenado
	* redicencias no final da string
	*/
	public function limitTxt($string, $limit=100){
		$txt = substr($string, 0, $limit);
		$dots = (strlen($string) > $limit)?'...':'';
		$txt .= $dots;

		return $txt;
	}

	/**
	* Método num2db
	* Retorna o valor passador por parametro no formado de banco
	* Ex.: $valor = $this->AppUtils->num2db('1.000,00');
	* No exemplo acima, a variavel $valor tera o numero formatado como: 1000.00
	*/
	public function num2db($number){
		if(strstr($number, ',')){
			return str_replace(',', '.', str_replace('.', '', $number));
		}
	}

	/**
	* Método num2br
	* Retorna o valor passador por parametro no formado de Real Brasileiro
	* Ex.: $valor = $this->AppUtils->num2br('1000.00');
	* No exemplo acima, a variavel $valor tera o numero formatado como: 1.000,00
	*/
	public function num2br($number){
		return number_format($number, 2, ',', '.');
	}

	/**
	* Método num2qt
	* Retorna o valor passador por parametro no quantitativo
	* Ex.: $valor = $this->AppUtils->num2qt('1000000');
	* No exemplo acima, a variavel $valor tera o numero formatado como: 1.000.000
	*/
	public function num2qt($number){
		return number_format($number, 0, '', '.');
	}

	/**
	* Método dt2br
	* Transforma uma data no formato americado para o formato brasileiro
	* Ex.: $data = $this->AppUtils->dt2br('20130130');
	* No exemplo acima, a variavel $data tera o a data formatada como: 30/01/2013
	*/
	public function dt2br($date, $hours=false){
		$date = ($date)?$date:date('Y-m-d');
		//Formata a data caso ela nao esteja formatada
		if(!preg_match('/[\-\/\.]/si', $date)){
			$data = substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
		}

		if($hours){
			$date = date('d/m/Y H:i:s', strtotime($date));
		}else{
			$date = date('d/m/Y', strtotime($date));
		}

		return $date;
	}

	/**
	* Método dt2db
	* Quebra a data para remontar no formato para inserção do banco de dados
	* Ex.: $data = $this->AppUtils->dt2db('31/01/2013');
	* No exemplo acima, a variavel $data tera o a data formatada como: yyyy-mm-dd [hh:ii:ss]
	*
	* @param string $date|br
	* @return string $date|eua/db
	*/
	public function dt2db($date=false, $hours=false){
		if(preg_match('%(0[1-9]|[12][0-9]|3[01])[\./-]?(0[1-9]|1[012])[\./-]?([12][0-9]{3})([ ].*)?([01][0-9]|2[03]:[05][09])?%si', $date, $dt)){
			$date = $dt[3] . '-' . $dt[2] . '-' . $dt[1];
			/**
			 * Verifica se a data contem hh:ii:ss, caso tenha é concatenado a data
			 */
			if (isset($dt[4])){
				$date .= ' ' . $dt[4];
			}
		}

		return $date;
	}

	/**
	 * Esta funcao constroi o menu apartir do array passado pelo parametro
	 */
	public function buildMenu($menus, $menuOption=array()) {

        //Verifica se foi requisitado um template fora do padrao
		$templateName = (isset($menuOption['template']))?$menuOption['template']:'menu';
        //Carrega o template do elemento
		$menuTemplate = $this->loadTemplate($templateName);

		preg_match('/^(.*?)%repeat%/si', $menuTemplate, $map);
		$menu_init = "{$map[1]}\r\n";

		preg_match('/%\/repeat%(.*)$/si', $menuTemplate, $map);
		$menu_end = "{$map[1]}\r\n";

		preg_match('/%repeat%.*?(<.*?>).*?%\/repeat%/si', $menuTemplate, $map);
		$menu_repeat_init = trim($map[1]) . "\r\n";

		preg_match('/%repeat%.*?(<\/.*?>).*?%\/repeat%/si', $menuTemplate, $map);
		$menu_repeat_end = trim($map[1]) . "\r\n";


		$return = $menu_init;
		foreach ($menus as $k => $v) {
        	//Variavel que guarda a classe do menu ativo
			$classActive = '';
			$controllerDefault = (!is_array($v))?$v:'';
        	//Valores padroes do menu
			$defaults = array(
				'classActive' => 'active',
				'isActive' => '',
				'url' => false,
				'controller' => $controllerDefault,
				'action' => 'index',
				'label' => __($controllerDefault),
				'icon_left' => '',
				'icon_right' => '',
				'plugin' => null
				);

			//Funde os elementos dos arrays
			$attr = array_merge($defaults, (array)$v, $menuOption);

        	//Verifica se o usuario logado tem permissao para acessar o item do menu, caso nao tenha, o menu nao sera exibido
        	$controller = isset($attr['url']['controller'])?$attr['url']['controller']:$attr['controller'];
        	$action = isset($attr['url']['action'])?$attr['url']['action']:$attr['action'];

			if(isset($v['children']) || $this->AppPermissions->check(Inflector::camelize($controller) . '.' . $action)){
				
				//Monta a URL do menu
				$url = ($attr['url'])?$attr['url']:array('controller' => Inflector::variable($controller), 'action' => $action, 'plugin' => $attr['plugin']);

				//Anula a Url caso o menu tenha filhos
				if(isset($v['children'])){
					$url = 'javascript:void(0);';
				}

				//Seta o scape do link como false caso o escape nao tenha sido setado
				if(!isset($attr['params']['escape'])){
					$attr['params']['escape'] = false;
				}

				//verifica se a aba esta ativa
				if($this->params['action'] == 'index'){
					if($this->params->here != $this->Html->url($url)) {
						unset($attr['classActive']);
					}
				}else{
					if(strtolower($this->params['controller']) != strtolower($attr['controller'])) {
						unset($attr['classActive']);
					}
				}

	        	//Altera as chaves do array para o padrao de variaveis do template
				$values = $this->key2var($attr);
		        //Carrega as variaveis do template do menu com os valores passados por parametro
				$menu_repeat_init = str_replace(array_keys($values), $values, $menu_repeat_init);

		        //Inicializa a tag LI ou a tag que ira se repetir no menu
				$return .= $menu_repeat_init;

				//Retira a classActive da tag
				if(isset($attr['classActive'])){
					$menu_repeat_init = str_replace($attr['classActive'], '', $menu_repeat_init);
				}

				$return .= $this->Html->link($attr['icon_left'] . $attr['label'] . $attr['icon_right'], $url, $attr['params']);
				$return = str_replace(array('%classActive%'), '', $return);

				//recursive
	            if (isset($v['children'])) {
	                $return .= $this->buildMenu($v['children'], array('template' => 'menu-children', 'classActive' => 'page-active'));
	            }

				$return .= $menu_repeat_end;
			}
		}


		$return .= $menu_end;
		return $return;
	}

	/**
	* Método cnpj
	* Formata os numeros passados pelo parametro nos padroes de CPF
	* Ex.: $cpf = $this->AppUtils->cpf('123321000112');
	* No exemplo acima, a variavel $cpf tera o cpf formatado como: 00.123.321/0001-12
	*
	* @param string $cpf
	* @return string $mask
	*/
	public function cnpj($cnpj){
		// Elimina possivel mascara
		$cnpj = preg_replace('[^0-9]', '', $cnpj);
		$cnpj = str_pad(substr($cnpj, -14), 14, '0', STR_PAD_LEFT);
	 
		// Verifica se o numero de digitos informados é igual a 14 
		if (strlen($cnpj) != 14) {
		    return false;
		}

		$cnpj = $this->format($cnpj, '##.###.###/####-##', 14);

		return $cnpj;
	}

	/**
	* Método cpf
	* Formata os numeros passados pelo parametro nos padroes de CPF
	* Ex.: $cpf = $this->AppUtils->cpf('123456789');
	* No exemplo acima, a variavel $cpf tera o cpf formatado como: 001.234.567-89
	*
	* @param string $cpf
	* @return string $mask
	*/
	public function cpf($cpf){
		// Elimina possivel mascara
		$cpf = preg_replace('[^0-9]', '', $cpf);
		$cpf = str_pad(substr($cpf, -11), 11, '0', STR_PAD_LEFT);
	 
		// Verifica se o numero de digitos informados é igual a 11 
		if (strlen($cpf) != 11) {
		    return false;
		}

		$cpf = $this->format($cpf, '###.###.###-##');

		return $cpf;
	}

	/**
	* Método tel
	* Formata os numeros passados pelo parametro nos padroes de Telefone
	* Ex.: $tel = $this->AppUtils->tel('2733411002');
	* No exemplo acima, a variavel $tel tera o tel formatado como: (27) 3341-1002
	*
	* @param string $tel
	* @return string $mask
	*/
	public function tel($tel){
		$tel_size = strlen(preg_replace('[^0-9]', '', $tel));
		switch ($tel_size) {
			case 11:
				$tel = $this->format($tel, '(##) #####-####');
				break;
			case 10:
				$tel = $this->format($tel, '(##) ####-####');
				break;
			case 8:
				$tel = $this->format($tel, '####-####');
				break;
		}

		return $tel;
	}

	/**
	* Método zipcode
	* Formata os numeros passados pelo parametro nos padroes de CEP
	* Ex.: $zipcode = $this->AppUtils->zipcode('29168450');
	* No exemplo acima, a variavel $zipcode tera o CEP formatado como: 29168-450
	*
	* @param string $zipcode
	* @return string $mask
	*/
	public function zipcode($zipcode){
		$zipcode = $this->format($zipcode, '#####-###');

		return $zipcode;
	}

	/**
	* Método format
	* Formata qualquer numero/documento com base na mascara passada por parametro
	* Ex.: $cpf = $this->AppUtils->format('123456789', '###.###.###-##', 11);
	* No exemplo acima, a variavel $cpf tera o cpf formatado como: 001.234.567-89
	*
	* @param string $doc
	* @param string $mask
	* @param string $digits
	* @return string $mask
	*/
	private function format($doc, $mask){
		if(!empty($doc)){
			// Elimina possivel mascara
			$doc = preg_replace('[^0-9]', '', $doc);

			/**
			* Aplica a mascara ao numero
			*/
			$index = -1;
			for ($i=0; $i < strlen($mask); $i++){
				if ($mask[$i]=='#'){
					$mask[$i] = $doc[++$index];
				} 
			}
		}

		return $mask;
	}



	/**
	 * Carrega os templates
	 */
	public function loadTemplate($templateName){
		//Remove quaisquer tipo de extenção que o nome do template possa ter
		$templateName = str_replace(array('.ctp', '.php', '.html'), '', $templateName);
		//Seta os diretorios de templates do sistema
		$path = PATH_TEMPLATE . DS . $templateName;
		//Valores padroes que serao levados ao template
		$options = array(
			'plugin' => false
			);

        //Verifica se a aplicacao contem um template personalizado, caso nao tenha um template proprio, busca um template padrao do sistema
		if(!is_file(PATH_TEMPLATE . DS . $templateName . '.ctp')){
			$options['plugin'] = 'Main';
		}

		return  $this->_View->element("Templates/{$templateName}", $options);
	}	
}

