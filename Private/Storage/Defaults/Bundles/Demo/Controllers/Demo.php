<?php

use Polyfony as pf;

// new example class to realize tests
class DemoController extends pf\Controller {

	public function preAction() {

		// set some common metas and assets
		pf\Response::set([
			'metas'=>[
				'title'=>'Bundles/Demo',
				'description'=>'Demo bundle with example of most features'
			],
			'assets'=>[
				'css'=>['//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css']
			]
		]);
		
		// get the currently browsed tab or null if none
		// this doesn't sounds safe, using a get parameter, right ?
		// actually it is, since the route won't match types that are not in that route's restrictions
		$this->CurrentTab = Polyfony\Request::get('type', null);

		// share a header
		$this->view('Header');
		
	}

	public function welcomeAction() {


		// view the main index/welcome page
		$this->view('Index');
	}
	
	public function indexAction() {

		// view the main demo index
		$this->view('Demo');
	
	}

	public function routerAction() {

		// generating an url using its route
		$this->url = pf\Router::reverse('demo', ['type'=>'locales'], true, true);
		$this->view('Router');

	}
	
	public function vendorBootstrapAction() {

		/*

		bootstrap/alert replaces the old polyfony/notice
		
		$container = new pf\Element('div',['class'=>'col-10 offset-1']);
		echo $container->adopt(new Polyfony\Notice\Success('Description', 'Title'));

		*/

		// just a bootstrap container to align the alerts
		$container = new pf\Element('div', ['class'=>'col-10 offset-1']);

		$successAlert = new Bootstrap\Alert(
			'success','Lorem ipsum', 'Dolor sit amet sed ut perspicadis', 'Footer'
		);
		$warningAlert = new Bootstrap\Alert(
			'warning','Lorem ipsum', 'Dolor sit amet sed ut perspicadis', 'Footer'
		);
		$dangerAlert = new Bootstrap\Alert(
			'danger',null, 'Lorem ipsum, dolor sit amet'
		);

		$modal = new Bootstrap\Modal();
		$modal
			->setTitle([
				'text'=>'Which do you prefer ?'
			],'fa fa-car')
			->setBody([
				'html'=>'I mean <strong>good</strong> cars ?'
			])
			->addOption([
				'text'=>'Me likey Porsche',
				'class'=>'btn btn-secondary'
			],'fa fa-car')
			->addOption([
				'text'=>'Me likey Ferrari',
				'class'=>'btn btn-danger'
			],'fa fa-car')
			->addOption([
				'text'=>'Me likey McLaren',
				'class'=>'btn btn-warning'
			],'fa fa-car')
			->setTrigger([
				'text'=>' Do you like cars ?',
				'class'=>'btn btn-primary'
			], 'fa fa-car');

		// adopt the alerts and dump them
		echo $container
			->adopt($successAlert)
			->adopt($warningAlert)
			->adopt($dangerAlert)
			->adopt($modal);

	}

	public function vendorGoogleAction() {

		echo new pf\Element('h1', ['text'=>'Demo of Vendor/Google']);
		echo new pf\Element('p', ['text'=>'Please note that if you get errors, it is because you should provide a Google API key.']);
		// address geocoder
		$address = 'Arc de triomphe, Paris';
		echo new pf\Element('code', ['text'=>"Google\Position::address('{$address}')"]);
		var_dump(
			Google\Position::address($address)
		);
		
		echo new pf\Element('hr');

		// reverse geocoder
		echo new pf\Element('code', ['text'=>"Google\Position::reverse('48.873','2.292')"]);
		var_dump(
			Google\Position::reverse(48.873,2.292)
		);

		echo new pf\Element('hr');

		// google map
		echo new pf\Element('code', ['text'=>"new Google\Map('roadmap',400,12, 48.873, 2.292)"]);
		echo new pf\Element('br');

		$map = new Google\Map('roadmap',400,12, 48.873, 2.292);
		echo new pf\Element('a', [
			'href'	=>$map->url(),
			'target'=>'_blank',
			'text'	=>'Open map image',
			'class'	=>'btn btn-outline-primary'
		]);

		echo new pf\Element('hr');

		// google street view
		echo new pf\Element('code', ['text'=>"new Google\Photo(400)->position(48.873,2.292)"]);
		echo new pf\Element('br');

		$photo = (new Google\Photo(400, 90, 10))->position(48.873,2.292);
		echo new pf\Element('a', [
			'href'	=>$photo->url(),
			'target'=>'_blank',
			'text'	=>'Open streetview image',
			'class'	=>'btn btn-outline-primary'

		]);


	}

	public function loginAction() {	

		// add a notice
		$this->Notice = new Bootstrap\Alert('info','Only one account exists by default','That account is : root/toor');

		// build input field
		$this->LoginInput = pf\Form::input(pf\Config::get('security','login'), null, array(
			'class'			=>'form-control',
			'id'			=>'inputLogin',
			'placeholder'	=>'Email'
		));;
		
		// build input field
		$this->PasswordInput = pf\Form::input(pf\Config::get('security','password'), null, array(
			'class'			=>'form-control',
			'id'			=>'inputPassword',
			'placeholder'	=>'*************',
			'type'			=>'password'
		));;
		
		// cache the page for 24 hours
		pf\Response::enableOutputCache(24);

		// view
		$this->view('Login');
	}
	
	public function secureAction() {	

		// enforce security for this action
		pf\Security::enforce();

		// grab some informations
		$this->Id 		= pf\Security::get('id');
		$this->Login 	= pf\Security::get('login');
		$this->Level 	= pf\Security::get('id_level');
		$this->Modules 	= implode(', ',pf\Security::get('modules_array'));

		// normal view
		$this->view('Secure');
	}

	public function disconnectAction() {

		// close the opened session
		pf\Security::disconnect();

	}
	
	public function localesAction() {
		$this->view('Locales');		
	}
	
	public function databaseAction() {
		
		// retrieve specific account
		$this->RootAccount = new Models\Accounts(1);
		// change something
		$this->UpdateStatus = $this->RootAccount
			->set('last_login_date',rand(time()-1/10*time(),time()+1/10*time()))
			->set('password',pf\Security::getPassword('toor'))
			->save();
		
		// create a new account
		$this->NewAccount = new Models\Accounts();
		// set something
		$this->NewAccount
			->set('login','test')
			->set('id_level','5')
			->set('last_failure_date',time());
		// save the record
		$this->CreateStatus = $this->NewAccount->save();
		// delete the new account
		$this->DeleteStatus = $this->NewAccount->delete();
		
		// demo query
		$this->Accounts = pf\Database::query()
			->select()
			->from('Accounts')
			->where(array(
				'id_level'=>1
			))
			->limitTo(0,5)
			->execute();
		
		// demo query from a model
		$this->AnotherList = Models\Accounts::all();
		$this->AnotherList = Models\Accounts::recentlyCreated();
		$this->AnotherList = Models\Accounts::disabled();
		$this->AnotherList = Models\Accounts::withErrors();
		
		// simple view	
		$this->view('Database');		
	}
	
	public function responseAction() {
		// add the JS portion of bootstrap
		pf\Response::setAssets('js',array(
			'//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js',
			'//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js'
		));
		// add some metas
		pf\Response::setMetas(array('description'=>'An awesome page'));
		// manually change the status
		pf\Response::setStatus(202);
		// set manual header
		pf\Response::setHeaders(array('X-Knock-Knock'=>'Who\'s there ?'));
		// simply import the view
		$this->view('Response');
	}
	
	public function requestAction() {
		
		// check if something has been posted
		$this->Feedback = pf\Request::isPost() ? 
			new Bootstrap\Alert('Success','You posted the following string : ',pf\Request::post('test')) : null;
		
		// create a new input, using posted data if available
		$this->InputExample = pf\Form::input(
			'test',
			pf\Request::post('test',null),
			array(
				'placeholder'	=>'Type something in there',
				'class'			=>'form-control',
				'id'			=>'exampleField'
			)
		);
		
		// use the view
		$this->view('Request');		
	}
	
	public function exceptionAction() {
		
		// enhanced exceptions with automatic HTTP status code and formatting using the « exception » route declared in the tools bundle
		Throw new pf\Exception('This is a custom exception with proper status code',502);
		
	}

	public function jsonAction() {

		pf\Response::set([
			'type'		=>'json',
			'content'	=>[
				'edible'	=>['Hoummus','Mango','Peach','Cheese'],
				'not_edible'=>['Dog','Cow','Rabbit','Lizard']
			]
		]);

		/*
		Alternatively you can also use this longer syntax

		pf\Response::setType('json');
		pf\Response::setContent([
			'edible'	=>['Hoummus','Mango','Peach','Cheese'],
			'not_edible'=>['Dog','Cow','Rabbit','Lizard']
		]);
		pf\Response::render();
		*/

	}
	

}

?>