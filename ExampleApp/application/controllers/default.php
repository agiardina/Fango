<?php
class DefaultController  extends FangoController{

	function init() {
		//If no template specified, it will search automatically template/page.phtml
		$this->page = new FangoView('page');

		//Model for table messages
		$this->messages = $this->fango->db->model('messages','id');
	}

	function getForm() {
		$message = new FangoView('message');
		$name = new FangoView('name');

		$name->value($this->fango->request('name'));
		$message->value($this->fango->request('message'));

		$form = new FangoView();
		$form->template('templates/form.phtml');
		$form->name = $name;
		$form->message = $message;
		return $form;
	}

	function getAllMessages() {
		return $this->messages->order('id','desc')->getAll();
	}

	function addAction() {
		echo 'ad';
	}

	function indexAction() {
		$form = $this->getForm();
		$this->page->form = $form;
		$this->page->messages = $this->getAllMessages();
		echo $this->page;
	}

}