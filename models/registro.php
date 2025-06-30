<?php 
/**
 * PIIT Verifica Inpi Registro Class
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.verificainpi
 * @copyright   Copyright (C) 2019-2020 Plataforma PITT. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;

class Registro {

	private $id, $revista, $secao, $patentesCitadas = '//', $patentes_N_Citadas = '//', $aux_pos;

	public function __construct($cods, $varridos){	//vetor $cods contem todas patentes do BD. vetor $varridos contém todos os dados das patentes citadas na revista, que também estão no BD.
		$this->revista = $varridos[0]['revista'];	//numero da revista
		$this->secao = $varridos[0]['secao'];
		
		if(isset($varridos[0]['numero'])){
			foreach ($varridos as $value) {	//Armazena todos os numeros/codigos das patentes citadas na revista.
				$this->patentesCitadas = $this->patentesCitadas . (string) $value['numero'] ."//";
			}
			
			foreach ($varridos as $key => $value) { 
				foreach ($cods as $idx => $codigo) {

					// Jeison - 21-11-22 - Begin
					$numeral =str_replace(array(' ','-'), '',(string)$value['numero']);
					$codigoPedido=str_replace(array(' ','-'), '',$codigo->codigoPedido);
					// End
					//if((string) $value['numero'] == $codigo->codigoPedido){  - Substituido  pelo if abaixo.

					 if($numeral == $codigoPedido){	

						$this->aux_pos[$key] = $idx;
	 					break;
					}
				}
			}

			foreach ($cods as $idx => $codigo) {
				$naoCitado = false;
				foreach ($this->aux_pos as $key => $value) {
					if($value == $idx){
						$naoCitado = true;
						break;
					}
				}

				if($naoCitado == false){
					$this->patentes_N_Citadas = $this->patentes_N_Citadas . $codigo->codigoPedido . "//";
				}
			}
		} else {
			foreach ($cods as $idx => $codigo) {
				$this->patentes_N_Citadas = $this->patentes_N_Citadas . $codigo->codigoPedido . '//';
			}
		}
	}

	public function getId(){
		return $this->id;
	}

	public function setId($id){
		$this->id = $id;
	}

	public function getRevista(){
		return $this->revista;
	}

	public function setRevista($revista){
		$this->revista = $revista;
	}

	public function getSecao(){
		return $this->secao;
	}

	public function setSecao($secao){
		$this->secao = $secao;
	}

	public function getPatentesCitadas(){
		return $this->patentesCitadas == '//' ? Text::_("PLG_FABRIK_CRON_VERIFICAINPI_NOT_FOUND") : $this->patentesCitadas; 
	}

	public function setPatentesCitadas($patentesCitadas){
		$this->patentesCitadas = $patentesCitadas;
	}

	public function getPatentes_N_Citadas(){
		return $this->patentes_N_Citadas;
	}

	public function setPatentes_N_Citadas($patentes_N_Citadas){
		$this->patentes_N_Citadas = $patentes_N_Citadas;
	}

}
 ?>