<?php
class DefaultController  extends FangoController{

	function init() {
		$this->page = new FangoView('page');
		$this->messages = new FangoModel('messages','id');
	}

	function indexAction() {
		$this->page->form = $this->getForm();
		$this->page->messages = $this->getRandomMessages();
		echo $this->page;
	}

	function addAction() {
		$values = $this->getForm()->getValues();
		if ($this->valid($values)) {

			$this->messages->insert($values);
			$id = $this->messages->lastInsertID();

			$_REQUEST = array();
			$this->page->form = $this->getForm();
			$this->page->messages = $this->getLastMessage($id);
		} else {
			$this->page->error = $this->getError();
			$this->page->form = $this->getForm();
			$this->page->messages = $this->getRandomMessages();
		}
		echo $this->page;
		
	}

	function refreshAction() {
		echo $this->getRandomMessages();
	}

	function valid($row) {
		return strlen($row['author']) && strlen($row['message']);
	}

	function getError() {
		$error = new FangoView('error');
		$error->text = "Please fill out all fields";
		return $error;
	}
	
	function getForm() {
		$form = new FangoView('form');
		$form->author =  new FangoInput('author');
		$form->message = new FangoInput('message');
		$form->author->value($this->fango->request('author'));
		$form->message->value($this->fango->request('message'));
		return $form;
	}

	function getLastMessage($id) {
		$view = new FangoView('messages');
		$view->messages = $this->messages->where('id = ?',$id)->limit(1)->getAll();
		return $view;
	}

	function getRandomMessages() {
		$view = new FangoView('messages');
		$view->messages = $this->messages->order('rand()')->limit(3)->getAll();
		return $view;
	}
}