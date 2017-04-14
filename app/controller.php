<?php

//! Base controller
class Controller 
{

	protected
		$db;

	//! HTTP route pre-processor
	function beforeroute($f3) 
	{
		$db=$this->db;
	
	}

	//! HTTP route post-processor
	function afterroute() {
		// Render HTML layout
		//echo Template::instance()->render('layout.htm');
	}

	//! Instantiate class
	function __construct() 
	{
		$f3=Base::instance();
		// Connect to the database
		$db=new DB\SQL($f3->get('DB'));

		if (file_exists('setup.sql')) {
			// Initialize database with default setup
			$db->exec(explode(';',$f3->read('setup.sql')));
			// Make default setup inaccessible
			rename('setup.sql','setop.$ql');
		}
		
		// Save frequently used variables
		$this->db=$db;
	}

}
