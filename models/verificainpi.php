<?php

/**
 * PIIT Verifica Inpi Model
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.verificainpi
 * @copyright   Copyright (C) 2019-2020 Plataforma PITT. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */


// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\Factory;

jimport('joomla.application.component.model');

require_once JPATH_SITE . '/plugins/fabrik_cron/verificainpi/models/registro.php';
require_once JPATH_SITE . '/plugins/fabrik_cron/verificainpi/models/alerta.php';


/**
 * The cron verifica inpi plugin model.
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.notification
 * @since       3.0
 */
class FabrikModelVerificaInpi extends FabModel {

	protected $nomes_arq;
	protected $nomes_arq_varredura;
  	protected $status_download;
	protected $status_unzip;
	protected $xml, $pro_tag, $revista, $data, $sufixo, $varridos, $secao;
	protected $params;
	protected $table_alerta;
	protected $table_patente;
	protected $table_inpi;
	protected $log;


	/**
	 * Constructor
	 *
	 * @param   array  $config  An array of configuration options (name, state, dbo, table_path, ignore_request).
	 *
	 * @since   11.1
	 */
	public function __construct($config = array())
	{
		$this->params = $config["params"];

		$idAlerta = $this->params->get('table_verificainpi_alerta');
		$idInpi = $this->params->get('table_verificainpi_inpi');
		$idPatente = $this->params->get('table_verifica');

		$this->log = '';

		$this->table_alerta = $this->getTable(null, null, array($idAlerta));
		$this->table_inpi = $this->getTable(null, null, array($idInpi));
		$this->table_patente = $this->getTable(null, null, array($idPatente));

		parent::__construct($config);
	}

  	public function statusDownload() {
		return $this->status_download;
  	}

  	public function statusUnzip() {
  		return $this->status_unzip;
	}
	
	public function getLog() {
  		return $this->log;
	}

	/**
	 * Get Rows from Revista
	 *
	 * @return  array
	 */
	public function getRevista()
	{
		$attr_revista = preg_split("/___/", $this->params->get('element_verificainpi_inpi_revista'));

		$db = FabrikWorker::getDbo();
		$query = $db->getQuery(true);
		$query->select('MAX('.$db->quoteName($attr_revista[1]).') as revista')->from($this->table_inpi);
		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/**
	 * Get the table object for the models _id
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return   object	table
	 */
	public function getTable($name = '', $prefix = 'Table', $options = array())
	{
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_fabrik/tables');
		$this->table = FabTable::getInstance('List', 'FabrikTable');

		if ($options[0] !== 0){
			$this->table->load($options[0]);
		}

		if (trim($this->table->db_primary_key) !== ''){
			$this->table->db_primary_key = FabrikString::safeColName($this->table->db_primary_key);
		}

		return $this->table->db_table_name;
	}



	public function downloadRevista($revista){
		$this->nomes_arq[0] = 'https://revistas.inpi.gov.br/txt/CT'.$revista.'.zip';
		$this->nomes_arq[1] = 'https://revistas.inpi.gov.br/txt/DI'.$revista.'.zip';
		$this->nomes_arq[2] = 'https://revistas.inpi.gov.br/txt/P'.$revista.'.zip';
		$this->nomes_arq[3] = 'https://revistas.inpi.gov.br/txt/PC'.$revista.'.zip';
		$this->nomes_arq[4] = 'https://revistas.inpi.gov.br/txt/RM'.$revista.'.zip';

		foreach ($this->nomes_arq as $key => $url) {
			switch ($key) {
				case 0:
					$destino = JPATH_SITE . "/tmp/Zip//CT".$revista.'.zip';	//Destino da descompactação
					break;
				case 1:
					$destino = JPATH_SITE . "/tmp/Zip//DI".$revista.'.zip';	//Destino da descompactação
					break;
				case 2:
					$destino = JPATH_SITE . "/tmp/Zip//P".$revista.'.zip';	//Destino da descompactação
					break;
				case 3:
					$destino = JPATH_SITE . "/tmp/Zip//PC".$revista.'.zip';	//Destino da descompactação
					break;
				case 4:
					$destino = JPATH_SITE . "/tmp/Zip//RM".$revista.'.zip';	//Destino da descompactação
					break;
				default:
					$this->log .=  "Arquivo destino não encontrado.<br>";
					break;
			}

			$headers = @get_headers($url);
			if(strpos($headers[1],'404') === false)		//Verifica se URL é valida para downloads.
			{											
				$ch = curl_init($url);
				
				if (!is_dir(JPATH_SITE . "/tmp/Zip/")) {
					mkdir(JPATH_SITE . "/tmp/Zip/", 0755, true);
				}

				//if(file_exists($destino)){
				//	$this->log .= "File in " . $destino . "already exists <br>"; 
				//	$this->status_download = true;
				//} else {
					$fp = fopen($destino, "wb");

					curl_setopt($ch, CURLOPT_URL, $url);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_FILE, $fp);
					curl_setopt($ch, CURLOPT_HEADER, 0);
					curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
					curl_setopt($ch, CURLOPT_ENCODING, "");

					$saida = curl_exec($ch);
					if ($saida) {
						$this->log .= "Download da revista " . $revista . '.zip' . " realizado!<br>";
					}	else {
						$this->log .= "Erro no download da revista " . $revista . '.zip' . "<br>";
						$this->status_download = false;
					}

					fclose($fp);
					curl_close($ch);
				//}
			} else {
				$this->log .= "URL ($url) Not Exists<br>";
			  	//$this->status_download = false;
			  	//return;
			}
		}

		$this->status_download = true;
  }

  public function descompactaRevista($revista) //Argumento 'nome-do-arquivo.zip'
	{
		$this->nomes_arq[0] = 'CT'.$revista.'.zip';
		$this->nomes_arq[1] = 'DI'.$revista.'.zip';
		$this->nomes_arq[2] = 'P'.$revista.'.zip';
		$this->nomes_arq[3] = 'PC'.$revista.'.zip';
		$this->nomes_arq[4] = 'RM'.$revista.'.zip';

		foreach ($this->nomes_arq as $key => $value) {
			$arquivo = JPATH_SITE . "/tmp/Zip//".$value;	//Local do arquivo .zip
			$destino = JPATH_SITE . "/tmp/Arquivos//";	//Destino da descompactação

			if(!file_exists($arquivo)) {
				continue;
			}

			$zip = new \ZipArchive;
			$zip->open($arquivo);

			if($zip->open($arquivo) === true) {
				if($zip->extractTo($destino) == TRUE){
					$this->log .= "Arquivo descompactado com sucesso.<br>";
				} else {
					$this->log .= "O Arquivo {$arquivo} não pode ser descompactado.<br>";
					//$this->status_unzip = false;
					//return;
				}
				$zip->close();
			}
		}

		$this->status_unzip = true;
  }

	public function getCodigoPatente(){
		$attr_cod = preg_split("/___/", $this->params->get('element_table_verifica'));
		$attr_proj = preg_split("/___/", $this->params->get('element_table_verifica_projeto'));
		$attr_rede = preg_split("/___/", $this->params->get('element_table_verifica_rede'));
		
		$db = FabrikWorker::getDbo();
		$query = $db->getQuery(true);
		$query->select(array('id', $attr_cod[1] . ' AS codigoPedido', $attr_proj[1] . ' AS title', 'rede', $attr_rede[1] . ' AS rede'))->from($this->table_patente);
		$db->setQuery($query);

		return $db->loadObjectList();
	}

	public function varrer($revista, $cods)
	{
		$this->revista = $revista;

		$this->nomes_arq_varredura[0] = 'Patente_';
		$this->nomes_arq_varredura[1] = 'DesenhoIndustrial_';
		$this->nomes_arq_varredura[2] = 'Contratos_';
		$this->nomes_arq_varredura[3] = 'Programa_';

		// BEGIN - Search for megazine RM
		$this->nomes_arq_varredura[4] = 'RM';
		// END - Search for megazine RM

		foreach ($this->nomes_arq_varredura as $key => $value) {
			$this->log .= "<br>VARRENDO SEÇÃO $value<br><hr>";
			
			// BEGIN - Search for megazine RM
			$filesgb = glob(JPATH_SITE   . '/tmp/Arquivos/'. $value . $revista . '*.xml');
			if(!file_exists($filesgb[0])) {
				$filesgb = glob(JPATH_SITE   . '/tmp/Arquivos/'. $revista . '.xml');
			}
			// END - Search for megazine RM
      
			if (file_exists($filesgb[0])) {
				if($key == 0){
					$this->pro_tag = 'processo-patente';	//Tag's de controle do .xml de cada seção
					$this->secao = 'Secao VI - PI e MU';
				} elseif ($key == 1) {
					$this->pro_tag = 'processo-patente';
					$this->secao = 'Secao III - DI';
				} elseif($key == 2){
					$this->pro_tag = 'processo-contrato';
					$this->secao = 'Secao II - CT';
				} elseif ($key == 3) {
					$this->pro_tag = 'processo-programa';
					$this->secao = 'Secao VII - PC';
				} elseif($key == 4) {
					$this->pro_tag = 'processo';
					$this->secao = 'Secao V - RM';
				}
				$this->compare($cods, $filesgb[0]);
				$this->log .= "<hr>";
				$this->gerarAlertas();
				$this->varridos = [];
			} else {
				$this->log .= "The file does not exist<br>";
			}
		}
	}

	public function compare($cods, $local){
		$this->xml = simplexml_load_file($local); //carrega o arquivo XML e retornando um Array
		$id = 0;

		if ((is_array($this->xml->despacho) || is_object($this->xml->despacho)) && isset($this->xml->despacho)){
			foreach ($this->xml->despacho as $des) {	//Loop percorre todo o arquivo xml, captando cada tag 'despacho'
				foreach ($cods as $value) {
					$numeral = preg_replace('/\s+/', ' ', (string)$des->{$this->pro_tag}->numero);
					$codigoPedido = preg_replace('/\s+/', ' ', $value->codigoPedido);
					$numeral = str_replace(array(' ', '-'), '', $numeral);
					$codigoPedido = str_replace(array(' ', '-'), '', $codigoPedido);  
				 
					if($numeral == $codigoPedido && ($numeral && $codigoPedido)) {
						$this->varridos[$id]['revista'] = $this->revista;
						$this->varridos[$id]['secao'] = $this->secao;
						$this->varridos[$id]['codigo'] = $des->codigo;
						$this->varridos[$id]['titulo'] = $des->titulo;
						$this->varridos[$id]['numero'] = (string) $des->{$this->pro_tag}->numero;
						$this->varridos[$id]['idRow'] = $value->id;
						$this->varridos[$id]['rede'] = $value->rede;
	
	
						if (isset($des->{$this->pro_tag}->titulo) && !empty($des->{$this->pro_tag}->titulo)){
							$this->varridos[$id]['projeto'] = (string) $des->{$this->pro_tag}->titulo;
						} elseif (isset($value->title) && !empty($value->title)){
							$this->varridos[$id]['projeto'] = $value->title;
						} else {
							$this->varridos[$id]['projeto'] = '--'; 
						}
	
						$id++;
						$this->log .= "<br><br>";
						$this->log .= 'Adicionado '.$id.'. Codigo : '.(string) $des->{$this->pro_tag}->numero.' Titulo : '.$des->titulo."<br>";
						break;
					}
				}
			}
		}

		// BEGIN - Search for megazine RM
		if ((is_array($this->xml->processo) || is_object($this->xml->processo)) && isset($this->xml->processo)){
			foreach ($this->xml->processo as $des) {	//Loop percorre todo o arquivo xml, captando cada tag 'despacho'
				foreach ($cods as $value) {
				
					$numeral = preg_replace('/\s+/', ' ', (string) $des->attributes()->numero[0]);
                    $codigoPedido = preg_replace('/\s+/', ' ', $value->codigoPedido);
                    $numeral = str_replace(array(' ', '-'), '', $numeral);
                    $codigoPedido = str_replace(array(' ', '-'), '', $codigoPedido);  

					if(($numeral == $codigoPedido) && ($numeral && $codigoPedido)) {
						$title = (string) $des->despachos->despacho->attributes()->nome;
						$code = (string) $des->despachos->despacho->attributes()->codigo;
						$this->varridos[$id]['revista'] = $this->revista;
						$this->varridos[$id]['secao'] = $this->secao;
						$this->varridos[$id]['codigo'] = $code;
						$this->varridos[$id]['titulo'] = $title;
						$this->varridos[$id]['numero'] = (string) $des->attributes()->numero[0];
						$this->varridos[$id]['idRow'] = $value->id;
						$this->varridos[$id]['rede'] = $value->rede;

						if (isset($title) && !empty($title)){
							$this->varridos[$id]['projeto'] = $title;
						} elseif (isset($value->title) && !empty($value->title)){
							$this->varridos[$id]['projeto'] = $value->title;
						} else {
							$this->varridos[$id]['projeto'] = '--';
						}
						$id++;
						$this->log .= "<br><br>";
						$this->log .= 'Adicionado '.$id.'. Codigo : '.$code.' Titulo : '.$title."<br>";
						break;
					} 
				}
			}
		}
		// END - Search for megazine RM

		if(empty($this->varridos)){
			$this->varridos = Array();
			$this->varridos[0]['revista'] = $this->revista;
			$this->varridos[0]['secao'] = $this->secao;
		}
	}

	public function createRegistro(Registro $r){	//Cria novo registro na TABLE_VERIFICA.
		$db = Factory::getContainer()->get('DatabaseDriver');

		$attr_revista = preg_split("/___/", $this->params->get('element_verificainpi_inpi_revista'));
		$attr_secao = preg_split("/___/", $this->params->get('element_verificainpi_inpi_secao'));
		$attr_patentes_citadas = preg_split("/___/", $this->params->get('element_verificainpi_inpi_patentes_citadas'));
		$attr_patentes_n_citadas = preg_split("/___/", $this->params->get('element_verificainpi_inpi_patentes_n_citadas'));
		$attr_data = preg_split("/___/", $this->params->get('element_verificainpi_inpi_data'));

		// Create a new query object.
		$query = $db->getQuery(true);

		// Insert columns.
		$columns = array($attr_data[1], $attr_revista[1], $attr_secao[1], $attr_patentes_citadas[1], $attr_patentes_n_citadas[1]);

		// Insert values.
		$values = array('CURRENT_TIMESTAMP()', $r->getRevista(), $db->quote($r->getSecao()), $db->quote($r->getPatentesCitadas()), $db->quote($r->getPatentes_N_Citadas()));

		// Prepare the insert query.
		$query->insert($db->quoteName($this->table_inpi))
			->columns($db->quoteName($columns))
			->values(implode(',', $values));

		// Set the query using our newly populated query object and execute it.
		$db->setQuery($query);
		$db->execute();

		$this->log .= "Registro Inserido com exito !<br>";
	}

	public function gerarAlertas(){
		if(!array_key_exists('codigo', $this->varridos[0])){
			$this->log .= "Nenhuma patente foi citada na revista ".$this->revista."<br>";
		} else {
			//Program to display complete URL 
			$link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']; 
		
			$shortedLink = '';
			if(preg_match('/administrator\//i', $link)){
				$splited = preg_split('/administrator\//i', $link);
				foreach($splited as $key => $value){
					$shortedLink .= $value;
				}
			}

			$alerta = new Alerta();

			foreach ($this->varridos as $id => $value) {	//Preenche descrição de cada alerta
				if(isset($value['numero']) && !empty($value['numero'])){
					//Build the complete URL
					if($shortedLink == ''){
						$linkForAlert = $link . '?option=com_fabrik&view=details&formid=' .  $this->params->get('table') . '&rowid=' . $value['idRow'];
					} else {
						$linkForAlert = $shortedLink . '?option=com_fabrik&view=details&formid=' .  $this->params->get('table') . '&rowid=' . $value['idRow'];
					}

					$desc = 'O pedido '.$value['numero'].' (Título da PI : '.$value['projeto'].') foi mencionado na revista '.$value['revista'].', na '.$this->secao.'. No intuito : '.$value['titulo'].' ('.$value['codigo'].')';
					$descricaoCompleta = '<a href="'.$linkForAlert.'">' . $desc . '</a>';

					$this->log .= "<br><br>";

					$alerta->setDescricao($descricaoCompleta);
					$alerta->setStatus('0 - Nao tratado');
					$alerta->setRede($value['rede']);
					$alerta->setIdPi($value['idRow']);

					$this->createAlerta($alerta);
				}
			}
		}

		$cods = $this->getCodigoPatente();
		$verifica = new Registro($cods, $this->varridos);
		$this->createRegistro($verifica);
	}

	public function createAlerta(Alerta $a){	//Cria novo alerta na tabela TABLE_ALERTA.
		$db = Factory::getContainer()->get('DatabaseDriver');

		$attr_descricao = preg_split("/___/", $this->params->get('element_verificainpi_alerta_desc'));
		$attr_situacao = preg_split("/___/", $this->params->get('element_verificainpi_alerta_status'));
		$attr_data = preg_split("/___/", $this->params->get('element_verificainpi_alerta_data'));
		$attr_rede = preg_split("/___/", $this->params->get('element_table_verifica_rede'));

		// Create a new query object.
		$query = $db->getQuery(true);

		// Insert columns.
		$columns = array($attr_descricao[1], $attr_situacao[1], $attr_data[1], $attr_rede[1], 'tipo', 'id_da_pi', 'codigo');

		// Insert values.
		$values = array($db->quote($a->getDescricao()), $db->quote($a->getStatus()), 'CURRENT_TIMESTAMP()', $db->quote($a->getRede()), $db->quote('PI'), $db->quote($a->getIdPi()), $db->quote($a->getIdPi()));

		// Prepare the insert query.
		$query->insert($db->quoteName($this->table_alerta))
			->columns($db->quoteName($columns))
			->values(implode(',', $values));

		// Set the query using our newly populated query object and execute it.
		$db->setQuery($query);
		$db->execute();

		$this->log .= "Alerta Inserido com exito !<br>";
	}

}