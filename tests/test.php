<?php
require('../src/Handlebars/Autoloader.php');
Handlebars_Autoloader::register();
$hdlbars = new Handlebars_Engine();

$hdlbars->getHelpers()->add('json_encode',
	function($template, $context, $args, $source) {
		return json_encode($context->get($args));
	}
);
$hdlbars->registerPartial('red', "\033[31m");
$hdlbars->registerPartial('green', "\033[32m");
$hdlbars->registerPartial('inv', "\033[7m");
$hdlbars->registerPartial('nrm', "\033[0m");
$hdlbars->registerPartial('drk', "\033[30m");

$json_tests = file_get_contents('tests.json');
$tests = json_decode($json_tests, true);

$format_templates = array(
	'unix' => "{{#categories}}\033[7m{{name}}\033[0m\n".
		"{{#tests}}".
			"{{#if result.success}}".
				"{{> green}}SUCCESS{{> nrm}}\t{{title}}\n".
			"{{else}}\n".
			"{{> red}}FAILED{{> nrm}}\t{{title}}\n".
				"{{#if description}}{{> drk}}{{> inv}}{{description}}\n{{/if}}".
				"{{> nrm}}{{> drk}}DATA:\n".
				"{{> inv}}{{#json_encode data}}{{/json_encode}}\n\n".
				"{{> nrm}}{{> drk}}TEMPLATE:\n".
				"{{> inv}}{{template}}\n".
				"{{> nrm}}{{> drk}}EXPECTED OUTPUT:\n".
				"{{> inv}}{{output}}{{> nrm}}\n".
				"{{> nrm}}{{> drk}}ACTUAL OUTPUT:\n".
				"{{> inv}}{{result.output}}{{> nrm}}{{> drk}}\n".
				"{{> nrm}}".
			"{{/if}}".
		"{{/tests}}".
		"{{/categories}}",
	'html' => file_get_contents('tests.handlebars.html')
);

if( isset( $_SERVER['SHELL']) ) {
	$format = $format_templates['unix'];
}
if( isset( $_SERVER['HTTP_HOST']) ) {
	$format = $format_templates['html'];
}


$result_data = array(
	'categories' => array()
);


if( isset($argv[1]) ) {
	$filter = $argv[1];
} elseif( isset($_GET['filter']) ) {
	$filter = $_GET['filter'];
} else {
	$filter = null;
}

foreach($tests as $category => $subtests) {
	$category_results = array('name' => $category, 'tests' => array());
	if( empty($filter) || $category == $filter ) {
		foreach( $subtests as $index => $test ) {

			$title			= $test['title'];
			$description	= $test['description'];
			$template		= $test['template'];
			$data			= $test['data'];
			$output			= $test['output'];

			$tests[] = $test;

			@$testoutput = $hdlbars->render($template, $data);
			$test['result'] = array(
				'output' => $testoutput,
				'success' => ($testoutput == $output)
			);

			$category_results['tests'][] = $test;
		}
	}
	$result_data['categories'][] = $category_results;
}
print $hdlbars->render($format, $result_data);
