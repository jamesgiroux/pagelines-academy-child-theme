<?php
// Setup  -- Probably want to keep this stuff... 

/**
 * Hello and welcome to the PageLines Academy Child Theme! First, lets load the PageLines core so we have access to the functions 
 */	
require_once( dirname(__FILE__) . '/setup.php' );
	
// Chances are you probably won't have much to add in here but in case you're thinking about it or have a background in it, this is where you can add your hooks, actions and section calls.

// Posix check

add_filter( 'posix_bypass', '__return_true' );
add_filter( 'render_css_posix_', '__return_true' );
