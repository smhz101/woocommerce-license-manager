<?php

class WCLM_Loader {

	protected $actions = array();
	protected $filters = array();

	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions[] = array( $hook, $component, $callback, $priority, $accepted_args );
	}

	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters[] = array( $hook, $component, $callback, $priority, $accepted_args );
	}

	public function run() {
		foreach ( $this->filters as $hook ) {
			add_filter( $hook[0], array( $hook[1], $hook[2] ), $hook[3], $hook[4] );
		}
		foreach ( $this->actions as $hook ) {
			add_action( $hook[0], array( $hook[1], $hook[2] ), $hook[3], $hook[4] );
		}
	}
}
