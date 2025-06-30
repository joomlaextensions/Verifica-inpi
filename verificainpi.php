<?php

/**
 *
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.email
 * @copyright   Copyright (C) PITT/UFG. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-cron.php';

/**
 * 
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.verificainpi
 * @since       1.0
 */

class PlgFabrik_CronVerificaInpi extends PlgFabrik_Cron {

	/**
	 * Whether cron should automagically load table data
	 *
	 * @return  bool
	 */
	public function requiresTableData()
	{
		return false;
	}

	/**
	 * Do the plugin action
	 *
	 * @param   array &$data data
	 * @param   object  &$listModel  List model
	 */
	public function process(&$data, &$listModel)
	{
		$params = $this->getParams();
		JModelLegacy::addIncludePath(JPATH_ROOT . '/plugins/fabrik_cron/verificainpi/models');
		$model = JModelLegacy::getInstance('VerificaInpi', 'FabrikModel', array('ignore_request' => true, 'params' => $params));
		$ult_revista = (int) $model->getRevista()[0]->revista; //Obtém número da última revista verificada
		$revista = isset($ult_revista) ? $ult_revista + 1 : '';	//Incrementa número da revista para a próxima ocorrênica 
		$logMsg = '';

		if(isset($revista) && !empty($revista)) {
			$model->downloadRevista($revista); //Realiza downloads das seções da revist
			if($model->statusDownload()){		//Verifica se downloads ocorreram com exito
				$model->descompactaRevista($revista);	//Descompacta as pastas .zip 
				if($model->statusUnzip()){
					$cods = $model->getCodigoPatente();		//Obtem todos os dados do campo 'codigo' da tabela patentes.
					$model->varrer($revista, $cods);	//Realiza varredura, e efetua os alertas e registros no BD.
				} else {
					$logMsg .= "Arquivos corrompidos.<br>";	//Erro na descompactação dos arquivos.
				}
			} else {
				$logMsg .= 'Revista numero ' . $revista . ' não encontrada.<br />';
			}
		} else {
			$logMsg .= 'Não foi possível obter o número da revista.<br />';
		}

		$logMsg .= $model->getLog();
		$this->log = $logMsg;
		FabrikWorker::log('plg.cron.verificainpi.information', $logMsg);

	}

}