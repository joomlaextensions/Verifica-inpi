<?php 

class Alerta {

	private $id, $descricao, $status, $data;

	public function getId(){
		return $this->id;
	}

	public function setId($id){
		$this->id = $id;
	}

	public function getDescricao(){
		return $this->descricao;
	}

	public function setDescricao($descricao){
		$this->descricao = $descricao;
	}

	public function getStatus(){
		return $this->status;
	}

	public function setStatus($status){
		$this->status = $status;
	}

	public function getData(){
		return $this->data;
	}

	public function setData(){
		$this->data = date('d/m/Y H:i:s');
	}
}

 ?>