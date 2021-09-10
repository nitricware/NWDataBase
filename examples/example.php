<?php
	
	use NitricWare\NWDatabase;
	
	require "../vendor/autoload.php";
	
	// Instantiate NWDatabase object
	$database = new NWDatabase("myDatabase");
	
	/*
	 * Insert columns
	 */
	try {
		$database->NWDBInsertColumn(["city", "country", "river"]);
	} catch (Exception $e) {
		echo "Error: $e";
	}
	
	// Display an ASCII representation of the database
	echo $database->NWDBDraw();
	
	/*
	 * Insert a single record
	 */
	try {
		$database->NWDBInsertRecord(["Linz", "Austria", "Danube"]);
	} catch (Exception $e) {
		echo "Error: $e";
	}
	
	/*
	 * Insert multiple records
	 */
	try {
		$database->NWDBInsertRecord([
				["Prague", "Czechia", "Moldova"],
				["Berlin", "Germany", "Spree"]
			]
		);
	} catch (Exception $e) {
		echo "Error: $e";
	}
	
	// Display an ASCII representation of the database
	echo $database->NWDBDraw();
	
	/*
	 * update a value of a record
	 */
	try {
		$database->NWDBUpdateRecord(2, ["river" => "Vlatava"]);
	} catch (Exception $e) {
		echo "Error: $e";
	}
	
	// Display an ASCII representation of the database
	echo $database->NWDBDraw();
	
	/*
	 * Delete a record
	 */
	try {
		$database->NWDBDeleteRecord(3);
	} catch (Exception $e) {
		echo "Error: $e";
	}
	
	/*
	 * Search for a record
	 */
	try {
		print_r($database->NWDBSearch("city", "Linz"));
	} catch (Exception $e) {
		echo "Error: $e";
	}
	
	// Display an ASCII representation of the database
	echo $database->NWDBDraw();
	
	// Destroy/Delete the database
	try {
		$database->NWDBDestroy();
	} catch (Exception $e) {
		echo "Error: $e";
	}