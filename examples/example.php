<?php
	
	use NitricWare\NWDatabase;
	use NitricWare\NWDBRecord;
	
	require "../vendor/autoload.php";
	
	/*
	 * Declare a NWDBRecord for the records of this database.
	 * Since PHP does not support generics, this is a
	 * somewhat workaround.
	 */
	
	class CityCountryRiver implements NWDBRecord {
		public string $city;
		public string $country;
		public string $river;
	}
	
	// Instantiate NWDatabase object
	$database = new NWDatabase("myDatabase", CityCountryRiver::class);
	
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
		/** @var CityCountryRiver $updatedRecord */
		$updatedRecord = $database->NWDBGetRecord(1);
		$updatedRecord->river = "Vltava";
		$database->NWDBUpdateRecord($updatedRecord);
	} catch (Exception $e) {
		echo "Error: $e";
	}
	
	// Display an ASCII representation of the database
	echo $database->NWDBDraw();
	
	/*
	 * Delete a record
	 */
	try {
		$database->NWDBDeleteRecord(2);
	} catch (Exception $e) {
		echo "Error: $e";
	}
	
	/*
	 * Search for a record
	 */
	try {
		/** @var CityCountryRiver $searchResult */
		$searchResult = $database->NWDBSearch("city", "Linz");
		
		/*
		 * Since the result of this function is a NWDBSearchResult Object,
		 * the data property holds an object of the recordType specified
		 * when initializing the NWDatabase object.
		 *
		 * Which can then be access in a simple, OOP way. However, PHPDoc
		 * is required for this.
		 */
		
		echo "<pre>";
		echo "The river name is: ".$searchResult[0]->data->river."\n";
		print_r($searchResult);
		echo "</pre>";
	} catch (Exception $e) {
		echo "Error: $e";
	}
	
	try {
		/** @var CityCountryRiver $singleRecord */
		$singleRecord = $database->NWDBGetRecord(1);
		
		echo "<pre>";
		echo "The city name is: ".$singleRecord->city."\n";
		print_r($singleRecord);
		echo "</pre>";
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