<?php
// Simple PHP test page

function add($a, $b) {
	return $a + $b;
}

$tests = [];

$tests[] = [
	'name' => 'Addition of positives',
	'expected' => 5,
	'actual' => add(2,3)
];

$tests[] = [
	'name' => 'Addition with zero',
	'expected' => 7,
	'actual' => add(7,0)
];

$passed = 0;
foreach ($tests as $t) {
	if ($t['expected'] === $t['actual']) {
		$status = 'PASS';
		$passed++;
	} else {
		$status = 'FAIL';
	}
	echo sprintf("%s: %s (expected=%s, actual=%s)\n", $status, $t['name'], var_export($t['expected'], true), var_export($t['actual'], true));
}

echo "\nSummary: {$passed}/" . count($tests) . " tests passed.\n";

// Return HTTP 200 on success, 500 if any test failed
if ($passed === count($tests)) {
	http_response_code(200);
} else {
	http_response_code(500);
}

?>
