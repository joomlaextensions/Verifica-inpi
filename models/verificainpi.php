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
	private $xml, $pro_tag, $revista, $data, $sufixo, $varridos, $secao;
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
		$this->nomes_arq[0] = 'http://revistas.inpi.gov.br/txt/CT'.$revista.'.zip';
		$this->nomes_arq[1] = 'http://revistas.inpi.gov.br/txt/DI'.$revista.'.zip';
		$this->nomes_arq[2] = 'http://revistas.inpi.gov.br/txt/P'.$revista.'.zip';
		$this->nomes_arq[3] = 'http://revistas.inpi.gov.br/txt/PC'.$revista.'.zip';
		$this->nomes_arq[4] = 'http://revistas.inpi.gov.br/txt/RM'.$revista.'.zip';

		foreach ($this->nomes_arq as $key => $url) {
			switch ($key) {
				case 0:
					$destino = getcwd()."/tmp/Zip//CT".$revista.'.zip';	//Destino da descompactação
					break;
				case 1:
					$destino = getcwd()."/tmp/Zip//DI".$revista.'.zip';	//Destino da descompactação
					break;
				case 2:
					$destino = getcwd()."/tmp/Zip//P".$revista.'.zip';	//Destino da descompactação
					break;
				case 3:
					$destino = getcwd()."/tmp/Zip//PC".$revista.'.zip';	//Destino da descompactação
					break;
				case 4:
					$destino = getcwd()."/tmp/Zip//RM".$revista.'.zip';	//Destino da descompactação
					break;
				default:
					$this->log .=  "Arquivo destino não encontrado.<br>";
					break;
			}

			$headers = @get_headers($url);
			if(strpos($headers[1],'404') === false)		//Verifica se URL é valida para downloads.
			{													
				$ch = curl_init($url);
				
				if (!is_dir(getcwd()."/tmp/Zip/")) {
					mkdir(getcwd()."/tmp/Zip/", 0755, true);
				}

				if(file_exists($destino)){
					$this->log .= "File in " . $destino . "already exists <br>"; 
					$this->status_download = true;
				} else {
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
						$this->log .= "Download da revista " . $revista . '.zip' . "realizado!<br>";
					}	else {
						$this->log .= "Erro no download da revista " . $revista . '.zip' . "<br>";
						$this->status_download = false;
					}

					fclose($fp);
					curl_close($ch);
				}
			} else {
				$this->log .= "URL Not Exists<br>";
			  $this->status_download = false;
			  return;
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
			$arquivo = getcwd()."/tmp/Zip//".$value;	//Local do arquivo .zip
			$destino = getcwd()."/tmp/Arquivos//";	//Destino da descompactação

			$zip = new \ZipArchive;
			$zip->open($arquivo);

			if($zip->extractTo($destino) == TRUE){
				$this->log .= "Arquivo descompactado com sucesso.<br>";
			} else {
				$this->log .= "O Arquivo não pode ser descompactado.<br>";
				$this->status_unzip = false;
				return;
			}
			$zip->close();
		}

		$this->status_unzip = true;
  }

	public function getCodigoPatente(){
		$attr_cod = preg_split("/___/", $this->params->get('element_table_verifica'));
		
		$db = FabrikWorker::getDbo();
		$query = $db->getQuery(true);
		$query->select($attr_cod[1])->from($this->table_patente);
		$db->setQuery($query);

		return $db->loadObjectList();
	}

	public function varrer($revista, $data, $cods){

		$this->revista = $revista;
		$this->data = $data;
		$this->sufixo = $revista.'_'.$data.'.xml';

		$this->nomes_arq_varredura[0] = 'Patente_';
		$this->nomes_arq_varredura[1] = 'DesenhoIndustrial_';
		$this->nomes_arq_varredura[2] = 'Contratos_';
		$this->nomes_arq_varredura[3] = 'Programa_';

		foreach ($this->nomes_arq_varredura as $key => $value) {
			$this->log .= "<br>VARRENDO SEÇÃO $value<br><hr>";

			/*
			if($key == 4){
				$local = getcwd().'\Arquivos\\'.$value.$this->revista;
			}
			else{
				$local = getcwd().'\Arquivos\\'.$value.$this->sufixo;
			}*/

			$local = getcwd() . '/tmp/Arquivos/' . $value . $this->sufixo;
			if (file_exists($local)) {
				if($key == 0){
					$this->pro_tag = 'processo-patente';	//Tag's de controle do .xml de cada seção
					$this->secao = 'Secao VI';
				} elseif ($key == 1) {
					$this->pro_tag = 'processo-patente';
					$this->secao = 'Secao III';
				} elseif($key == 2){
					$this->pro_tag = 'processo-contrato';
					$this->secao = 'Secao II';
				} elseif ($key == 3) {
					$this->pro_tag = 'processo-programa';
					$this->secao = 'Secao VII';
				}

				$this->compare($cods, $local);
				$this->log .= "<hr>";
				$this->gerarAlertas();
				unset($this->varridos);
			} else {
				$this->log .= "The file $local does not exist<br>";
			}
		}
	}

	public function compare($cods, $local){
		$this->xml = simplexml_load_file($local); //carrega o arquivo XML e retornando um Array
		$id = 0;

		if (is_array($this->xml->despacho) || is_object($this->xml->despacho)){
			foreach ($this->xml->despacho as $des) {	//Loop percorre todo o arquivo xml, captando cada tag 'despacho'
				foreach ($cods as $value) {
					if((string)$des->{$this->pro_tag}->numero === $value->codigo_do_pedido){		//Compara códigos do BD com os códigos submetidos na revista.
						$this->varridos[$id]['revista'] = $this->revista;
						$this->varridos[$id]['secao'] = $this->secao;
						$this->varridos[$id]['codigo'] = $des->codigo;
						$this->varridos[$id]['titulo'] = $des->titulo;
						$this->varridos[$id]['numero'] = (string) $des->{$this->pro_tag}->numero;
	
	
						if(isset($des->{$this->pro_tag}->titulo)){
							$this->varridos[$id]['projeto'] = (string) $des->{$this->pro_tag}->titulo;
						}else{
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

		if(!isset($this->varridos)){
			$this->varridos[0]['revista'] = $this->revista;
			$this->varridos[0]['secao'] = $this->secao;
		}	

	}

	public function createRegistro(Registro $r){	//Cria novo registro na TABLE_VERIFICA.
		$attr_revista = preg_split("/___/", $this->params->get('element_verificainpi_inpi_revista'));
		$attr_secao = preg_split("/___/", $this->params->get('element_verificainpi_inpi_secao'));
		$attr_patentes_citadas = preg_split("/___/", $this->params->get('element_verificainpi_inpi_patentes_citadas'));
		$attr_patentes_n_citadas = preg_split("/___/", $this->params->get('element_verificainpi_inpi_patentes_n_citadas'));
		$attr_data = preg_split("/___/", $this->params->get('element_verificainpi_inpi_data'));

		// Get a db connection.
		$db = JFactory::getDbo();

		// Create a new query object.
		$query = $db->getQuery(true);

		// Insert columns.
		$columns = array($attr_data[1], $attr_revista[1], $attr_secao[1], $attr_patentes_citadas[1], $attr_patentes_n_citadas[1]);

		// Insert values.
		$values = array('CURRENT_TIMESTAMP()', $r->getRevista(), $db->quote($r->getSecao()), $db->quote($r->getPatentesCitadas()), $db->quote($r->getPatentes_N_Citadas()));

		// Prepare the insert query.
		$query
				->insert($db->quoteName($this->table_inpi))
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
			$alerta = new Alerta();

			foreach ($this->varridos as $id => $value) {	//Preenche descrição de cada alerta
				$desc = 'O pedido '.$value['numero'].' (Projeto : '.$value['projeto'].') foi mencionado na revista '.$value['revista'].', na '.$this->secao.'. No intuito : '.$value['titulo'].' ('.$value['codigo'].')';
				
				$this->log .= "<br><br>";

				$alerta->setDescricao($desc);
				$alerta->setStatus('Nao tratado');

				$this->createAlerta($alerta);
			}
		}

		$cods = $this->getCodigoPatente();
		$verifica = new Registro($cods, $this->varridos);
		$this->createRegistro($verifica);
	}

	public function createAlerta(Alerta $a){	//Cria novo alerta na tabela TABLE_ALERTA.
		$attr_descricao = preg_split("/___/", $this->params->get('element_verificainpi_alerta_desc'));
		$attr_situacao = preg_split("/___/", $this->params->get('element_verificainpi_alerta_status'));
		$attr_data = preg_split("/___/", $this->params->get('element_verificainpi_alerta_data'));

		// Get a db connection.
		$db = JFactory::getDbo();

		// Create a new query object.
		$query = $db->getQuery(true);

		// Insert columns.
		$columns = array($attr_descricao[1], $attr_situacao[1], $attr_data[1]);

		// Insert values.
		$values = array($db->quote($a->getDescricao()), $db->quote($a->getStatus()), 'CURRENT_TIMESTAMP()');

		// Prepare the insert query.
		$query
				->insert($db->quoteName($this->table_alerta))
				->columns($db->quoteName($columns))
				->values(implode(',', $values));


		// Set the query using our newly populated query object and execute it.
		$db->setQuery($query);
		$db->execute();

		$this->log .= "Alerta Inserido com exito !<br>";
	}

}