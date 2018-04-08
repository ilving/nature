<?php
$file = $_SERVER['DOCUMENT_ROOT'] . "/data/storage/" . $_GET['name'] . ".json";
$templateDir = $_SERVER['DOCUMENT_ROOT'] . "/data/templates/kml/";

$cacheKey = sha1($file);
if (!file_exists($file)) {
	http_response_code(404);
	return;
}
header("Content-type: application/xml");

if (apc_exists($cacheKey)) {
	header("key-exists: ok");
	$content = apc_fetch($cacheKey);
	if (!empty($content)) {
		header("output: APC");
		echo $content;
		return;
	}
}

header("output: non-cached");


function formatFields($item) {
	$fieldNames = [
		"square" => "Площадь листовой поверхности, м.кв",
		"age" => "Возраст, лет",
		"perimetr" => "Периметр ствола, см",
		"height" => "Высота, м",
	];

	$output = "";
	foreach ($item as $name=>$value) {
		if (!empty($value) && !empty($fieldNames[$name])) {
			$output .= "<b>".$fieldNames[$name]."</b>: " . $value . "<br>";
		}
	}

	return empty($output) ? "" : "<hr>" . $output;
}

ob_start();
$content = json_decode(file_get_contents($file));
echo file_get_contents($templateDir . "header.xml");
if (!empty($content)) {
	$pointTemplate = file_get_contents($templateDir . "point.xml");
	foreach ($content as $point) {
		$description = "<hr>" . $point->address . "<br>" . $point->oblast . " обл. " . $point->rajon . " район";
		if (!empty($point->descriptio)) {
			$description .= "<hr>" . $point->descriptio;
		}
		$description .= formatFields($point);

		echo str_replace(
			['{name}', '{description}', '{style}', '{lon}', '{lat}'],
			[
				$point->vid,
				$description,
				"",
				$point->long, $point->lat
			],
			$pointTemplate);
	}
}
echo file_get_contents($templateDir . "footer.xml");
echo PHP_EOL;

$content = ob_get_clean();
apc_store($cacheKey, $content, 60);
echo $content;