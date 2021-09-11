<?php
	
	use NitricWare\NWDatabase;
	use NitricWare\NWDBRecord;
	use NitricWare\NWDBSortOrder;
	
	require "../vendor/autoload.php";
	
	/*
	 * Declare a NWDBRecord for the records of this database.
	 * Since PHP does not support generics, this is a
	 * somewhat workaround.
	 */
	
	class CityCountryRiver extends NWDBRecord {
		public string $city;
		public string $country;
		public string $river;
	}
	
	// Instantiate NWDatabase object
	$database = new NWDatabase("cityCountryRiverDatabase", CityCountryRiver::class);
	
	/*
	 * Insert columns
	 */
	try {
		$database->NWDBInsertColumn(["city", "country", "river"]);
	} catch (Exception $e) {
		echo "Error: $e";
	}
	
	// Display an ASCII representation of the database
	try {
		echo $database->NWDBDraw();
	} catch (Exception $e) {
		echo "Error: $e";
	}
	
	/*
	 * Insert records
	 */
	try {
		$linz = new CityCountryRiver();
		$linz->city = "Linz";
		$linz->country = "Austria";
		$linz->river = "Danube";
		
		$prague = new CityCountryRiver();
		$prague->city = "Prague";
		$prague->country = "Czechia";
		$prague->river = "Moldova";
		
		$berlin = new CityCountryRiver();
		$berlin->city = "Berlin";
		$berlin->country = "Germany";
		$berlin->river = "Spree";
		
		$database->NWDBInsertRecord([$linz, $prague, $berlin]);
	} catch (Exception $e) {
		echo "Error: $e";
	}
	
	// Display an ASCII representation of the database
	try {
		echo $database->NWDBDraw();
	} catch (Exception $e) {
		echo "Error: $e";
	}
	
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
		echo "The river name is: ".$searchResult[0]->river."\n";
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
	
	try {
		/** @var CityCountryRiver[] $singleRecord */
		$manyRecords = $database->NWDBGetRecords(2,1);
		
		echo "<pre>";
		print_r($manyRecords);
		echo "</pre>";
		
		echo "<pre>";
		echo "Now NWDB sorts the array based on the city column.\n";
		echo "</pre>";
		
		/** @var CityCountryRiver[] $sortedRecords */
		$sortedRecords = $database->NWDBSortResult($manyRecords, "city");
		
		echo "<pre>";
		print_r($sortedRecords);
		echo "</pre>";
	} catch (Exception $e) {
		echo "Error: $e";
	}
	
	/*
	 * Delete a record
	 */
	try {
		$database->NWDBDeleteRecord(2);
	} catch (Exception $e) {
		echo "Error: $e";
	}
	
	// Display an ASCII representation of the database
	try {
		echo $database->NWDBDraw();
	} catch (Exception $e) {
		echo "Error: $e";
	}
	
	// Destroy/Delete the database
	try {
		$database->NWDBDestroy();
	} catch (Exception $e) {
		echo "Error: $e";
	}