<?php
	/*
	
		NitricWare presents
		NWDatabase
		
		2014
		
		XML based Database System
		
		Requires
		- NWFileOperations.NWFunction
		- NWLog.NWFunction
		
		Version 1.1
		
	*/
	
	namespace NitricWare;
	
	use DOMDocument;
	use DOMNode;
	use DOMNodeList;
	use DOMXPath;
	use Exception;
	
	class NWDatabase {
		private string $dbName;
		private string $path;
		private DOMDocument $dataBase;
		private string $fileName;
		private string $fullPath;
		
		private string $extension = "nwdb";
		
		/**
		 * NWDatabase constructor.
		 *
		 * @param string $database       Name of the database without file extension.
		 * @param string $location       Full path to the Database. Default is ./ - same as script
		 * @param bool   $deleteIfExists Deletes the database if it already exists
		 *
		 * @throws Exception
		 */
		function __construct (string $database, string $location = "./", bool $deleteIfExists = false) {
			if (!file_exists(realpath($location))) {
				throw new Exception("Location does not exist.");
			}
			
			$this->dbName = $database;
			$this->path = realpath($location) . "/";
			$this->fullPath = $this->path . $this->dbName . "." . $this->extension;
			
			if (!$this->NWDBCreate($deleteIfExists)) {
				throw new Exception("Couldn't create database.");
			}
			
			$this->fileName = $this->dbName . "." . $this->extension;
			
			$this->dataBase = new DOMDocument();
			$this->dataBase->preserveWhiteSpace = false;
			$this->dataBase->formatOutput = true;
			$this->dataBase->load($this->fileName);
		}
		
		/**
		 * Creates a blank database with no columns.
		 * This function returns an array, containing information
		 * about the database on success.
		 *
		 * @param bool $deleteIfExists
		 *
		 * @return array
		 * @throws Exception
		 */
		public function NWDBCreate (bool $deleteIfExists = false): array {
			if (file_exists($this->fullPath)) {
				if ($deleteIfExists) {
					$this->NWDBDestroy();
				}
			}
			$this->fileName = "$this->path$this->dbName.nwdb";
			
			// Creates a new document tree
			
			$dataBase = new DOMDocument("1.0", "UTF-8");
			$rootElement = $dataBase->createElement("Database");
			
			// Creates the information tree.
			
			$infos = $dataBase->createElement("Infos");
			$infos->appendChild($dataBase->createElement("Name", $this->dbName));
			$infos->appendChild($dataBase->createElement("CreatedTime", time()));
			$infos->appendChild($dataBase->createElement("LastEditedTime", time()));
			$infos->appendChild($dataBase->createElement("LastRecordID", 0));
			$infos->appendChild($dataBase->createElement("LastColumnID", 0));
			
			$rootElement->appendChild($infos);
			
			$rootElement->appendChild($dataBase->createElement("Columns"));
			$rootElement->appendChild($dataBase->createElement("Records"));
			
			$dataBase->appendChild($rootElement);
			
			$dataBase->formatOutput = true;
			
			$dataBase->save($this->fileName);
			
			$this->dataBase = new DOMDocument();
			$this->dataBase->load($this->fileName);
			
			return $this->NWDBInfo();
		}
		
		/**
		 * Returns an array containing information about the Database.
		 *
		 * @return array
		 */
		public function NWDBInfo (): array {
			$infos = $this->dataBase->getElementsByTagName("Infos")->item(0);
			$returnArray = array();
			$returnArray["Name"] = $infos->getElementsByTagName("Name")->item(0)->nodeValue;
			$returnArray["CreatedTime"] = $infos->getElementsByTagName("CreatedTime")->item(0)->nodeValue;
			$returnArray["LastEditedTime"] = $infos->getElementsByTagName("LastEditedTime")->item(0)->nodeValue;
			$returnArray["LastRecordID"] = $infos->getElementsByTagName("LastRecordID")->item(0)->nodeValue;
			$returnArray["LastColumnID"] = $infos->getElementsByTagName("LastColumnID")->item(0)->nodeValue;
			
			return $returnArray;
		}
		
		/**
		 * Gives functions of the class the possibility to
		 * update the timestamp on last edit.
		 *
		 * @return void
		 */
		protected function NWDBUpdateEditTime (): void {
			$lastTime = $this->dataBase->getElementsByTagName("LastEditedTime")->item(0);
			$newTime = $this->dataBase->createElement("LastEditedTime", time());
			
			$lastTime->parentNode->replaceChild($newTime, $lastTime);
			
			$this->dataBase->formatOutput = true;
			
			$this->dataBase->save($this->fileName);
		}
		
		/**
		 * Gives functions of the class the possibility to
		 * check whether the name of a column is useable or
		 * not.
		 *
		 * @param string $columnName
		 *
		 * @throws Exception
		 */
		protected function checkColumnName (string $columnName): void {
			$nameCheck = preg_match("/^([a-zA-Z]){1}([a-zA-Z0-9]*)$/", $columnName);
			if ($nameCheck === false or $nameCheck == 0) {
				throw new Exception("Your columnname is invalid. It must start with a letter and may only consist of letters and numbers.");
			}
		}
		
		/**
		 * Adds columns to the document tree. Returns an array
		 * with the IDs of the added columns on success or
		 * false on failure.
		 *
		 * @param array $columnArray This array must be 1D and can contain multiple column names.
		 *
		 * @return array
		 * @throws Exception
		 */
		public function NWDBInsertColumn (array $columnArray): array {
			$columns = array();
			$return = array();
			foreach ($columnArray as $column) {
				$this->checkColumnName($column);
				$columns[] = strtolower($column);
			}
			
			$oldColumns = $this->NWDBGetColumns(false);
			$infos = $this->NWDBInfo();
			$records = $this->dataBase->getElementsByTagName("Record");
			$allColumns = $this->dataBase->getElementsByTagName("Columns")->item(0);
			
			$newID = $infos["LastColumnID"];
			
			foreach ($columns as $columnName) {
				if (in_array($columnName, $oldColumns)) {
					throw new Exception("Column $columnName already exists.");
				}
				
				$newID = $newID + 1;
				
				$return[] = $newID;
				
				$newColumn = $this->dataBase->createElement("column", $columnName);
				$newColumn->setAttribute("id", $newID);
				
				$allColumns->appendChild($newColumn);
				
				// update existing Entries
				
				foreach ($records as $record) {
					$record->appendChild($this->dataBase->createElement($columnName));
				}
			}
			
			// update LastColumnID
			
			$infoNode = $this->dataBase->getElementsByTagName("LastColumnID")->item(0);
			$newColumnID = $this->dataBase->createElement("LastColumnID", $newID);
			$infoNode->parentNode->replaceChild($newColumnID, $infoNode);
			$this->dataBase->formatOutput = true;
			$this->dataBase->save($this->fileName);
			
			$this->NWDBUpdateEditTime();
			
			return $return;
		}
		
		/*
			NWDBGetColumns
				Returns alls column names on success or false on
				failure.
		*/
		
		/**
		 * Returns alls column names on success
		 *
		 * @param $idIsKey
		 *
		 * @return array
		 */
		public function NWDBGetColumns (bool $idIsKey = false): array {
			$columns = $this->dataBase->getElementsByTagName("column");
			
			$returnArray = array();
			
			foreach ($columns as $column) {
				if (!$idIsKey) {
					$returnArray[] = $column->nodeValue;
				} else {
					$returnArray[$column->getAttribute("id")] = $column->nodeValue;
				}
			}
			
			return $returnArray;
		}
		
		/**
		 * This function creates a new record and returns the ID
		 * of the last record on success or false on failure.
		 *
		 * @param array $values   This must be an array and can be 1D or 2D.
		 * @param bool  $firstRun Value is set by the function.
		 *
		 * @return int
		 * @throws Exception
		 */
		public function NWDBInsertRecord (array $values, bool $firstRun = true): int {
			$newID = 0;
			
			if (is_array($values[0])) {
				if (!$firstRun) {
					throw new Exception("Array may not have more than 2 dimensions.");
				}
				// Multi Dimensional Array Detected
				foreach ($values as $record) {
					$this->NWDBInsertRecord($record);
				}
			} else {
				$columns = $this->NWDBGetColumns(false);
				$nrV = count($values);
				$nrC = count($columns);
				if ($nrV != $nrC) {
					throw new Exception("Number of values ($nrV) does not match number of columns ($nrC).");
				}
				
				$infos = $this->NWDBInfo();
				$newID = $infos["LastRecordID"] + 1;
				
				// update LastRecordID
				
				$lastID = $this->dataBase->getElementsByTagName("LastRecordID")->item(0);
				$newColumnID = $this->dataBase->createElement("LastRecordID", $newID);
				
				$lastID->parentNode->replaceChild($newColumnID, $lastID);
				
				// insert Record
				$records = $this->dataBase->getElementsByTagName("Records")->item(0);
				$record = $this->dataBase->createElement("Record");
				$record->setAttribute("id", $newID);
				for ($i = 0; $i < $nrC; $i++) {
					if ($i >= $nrV) {
						$value = "";
					} else {
						$value = $values[$i];
					}
					
					if (is_array($value)) {
						throw new Exception("Value provided for " . $columns[$i] . " is an array. This is illegal.");
					}
					
					$record->appendChild($this->dataBase->createElement($columns[$i], $value));
				}
				
				$records->appendChild($record);
				$this->dataBase->save($this->fileName);
			}
			
			$this->NWDBUpdateEditTime();
			
			return $newID;
		}
		
		/*
			NWDBGetRecords
				Returns all records from the database on success
				and false on failure.
				
			$limit
				Limits the return array to a specific amount
				of entries.
				
				false: no limit
				
				any int: limit
		*/
		
		/**
		 * Returns all records from the database.
		 *
		 * @param bool|int $limit Limits the return array to a specific amount of entries.
		 * @param int      $start
		 *
		 * @return array
		 */
		public function NWDBGetRecords ($limit = false, int $start = 0) {
			$columns = $this->NWDBGetColumns();
			
			$records = $this->dataBase->getElementsByTagName("Record");
			
			$returnArray = array();
			
			foreach ($records as $record) {
				$columnArray = array();
				foreach ($columns as $column) {
					$columnLine = $record->getElementsByTagName($column);
					
					$columnArray[$column] = $columnLine->item(0)->nodeValue;
				}
				$returnArray[] = array("ID" => $record->getAttribute("id"), "DATA" => $columnArray);
			}
			
			if ($limit) {
				$returnArray = array_slice($returnArray, $start, $limit);
			}
			
			return $returnArray;
		}
		
		/**
		 * Returns one single record.
		 *
		 * @param int $id
		 *
		 * @return array
		 * @throws Exception
		 */
		public function NWDBGetRecord (int $id): array {
			$xpath = new DOMXPath($this->dataBase);
			
			$query = "//Record[@id='$id']";
			if (!$record = $xpath->query($query)) {
				throw new Exception("Record $id not found.");
			}
			
			$record = $record->item(0);
			
			$returnArray = array();
			
			foreach ($record->childNodes as $value) {
				$returnArray[$value->localName] = $value->textContent;
			}
			
			return $returnArray;
		}
		
		/**
		 * Deletes a record and returns information
		 * about the database.
		 *
		 * @param int $id
		 *
		 * @return array
		 * @throws Exception
		 */
		public function NWDBDeleteRecord (int $id): array {
			$xpath = new DOMXPath($this->dataBase);
			
			$query = "//Record[@id='$id']";
			$search = $xpath->query($query);
			
			if ($search->length == 0) {
				throw new Exception("Record with ID $id does not exist.");
			}
			
			$result = $search->item(0);
			$result->parentNode->removeChild($result);
			
			$this->dataBase->formatOutput = true;
			$this->dataBase->save($this->fileName);
			
			$this->NWDBUpdateEditTime();
			
			return $this->NWDBInfo();
		}
		
		/**
		 * Updates a record and returns the updated record
		 * on success and false on failure.
		 *
		 * @param int   $id
		 * @param array $valuesArray array("column1" => "Value 1", "column2" => "Value 2");
		 *
		 * @return array
		 * @throws Exception
		 */
		public function NWDBUpdateRecord (int $id, array $valuesArray) {
			$values = array();
			$columns = array();
			
			$xpath = new DOMXPath($this->dataBase);
			
			$query = "//Record[@id='$id']";
			if (!$record = $xpath->query($query)) {
				throw new Exception("Record $id not found.");
			}
			
			$record = $record->item(0);
			
			foreach ($valuesArray as $key => $value) {
				$columns[] = $key;
				$values[] = $value;
			}
			
			$nrV = count($values);
			$nrC = count($columns);
			
			for ($i = 0; $i < $nrC; $i++) {
				if ($i >= $nrV) {
					$value = "";
				} else {
					$value = $values[$i];
				}
				
				if ($value != false and $value != "false") {
					$oldChild = $record->getElementsByTagName($columns[$i])->item(0);
					$newChild = $this->dataBase->createElement($columns[$i], $value);
					
					$oldChild->parentNode->replaceChild($newChild, $oldChild);
				}
			}
			
			$this->dataBase->formatOutput = true;
			
			$this->dataBase->save($this->fileName);
			
			$this->NWDBUpdateEditTime();
			
			return $this->NWDBGetRecord($id);
		}
		
		/**
		 * Searches the database for a matching string and returns
		 * matches on success and false on failure.
		 *
		 * @param string   $column Select the column you want to search in for a string. If
		 *                         $exact is false, $column can be "*" to search in all
		 *                         columns for $value.
		 * @param string   $value  Specifies the string that you want to search for.
		 * @param bool     $exact  $column can contain $value and other strings.
		 *                         Search is case insensitive.
		 * @param bool|int $limit  Limits the return array to a specific amount
		 *                         of entries.
		 * @param int      $start
		 *
		 * @return array
		 * @throws Exception
		 */
		public function NWDBSearch (string $column, string $value, bool $exact = false, $limit = false, int $start = 0) {
			$xpath = new DOMXPath($this->dataBase);
			
			if ($exact) {
				$query = "//Record[$column='$value']";
			} else {
				$upper = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
				$lower = "abcdefghijklmnopqrstuvwxyz";
				$contains = 'translate(text(),\'' . $upper . '\',\'' . $lower . '\')';
				$column = strtolower($column);
				$value = strtolower($value);
				$query = '//Record/' . $column . '[contains(' . $contains . ',\'' . $value . '\')]/..';
			}
			
			if ($column == "id") {
				$query = "//Record[@id='$value']";
			}
			
			$record = $xpath->query($query);
			if ($record->length > 0) {
				$returnArray = array();
				foreach ($record as $value) {
					$returnArray[] = array("ID" => $value->attributes->item(0)->textContent, "DATA" => $this->NWDBGetRecord($value->getAttribute('id')));
				}
			} else {
				throw new Exception("No result found.");
			}
			if ($limit) {
				$returnArray = array_slice($returnArray, $start, $limit);
			}
			return $returnArray;
		}
		
		/**
		 * Sorts a given result by given column either ascending
		 * or descending.
		 *
		 * @param array  $array  The result obtained by either NWDBSearch()
		 *                       or NWDBGetRecords().
		 * @param string $column Select the column you want for ordering.
		 * @param string $order
		 *
		 * @return array
		 * @throws Exception
		 */
		public function NWDBSortResult (array $array, string $column = "id", string $order = "asc"): array {
			if ($column == "id") {
				foreach ($array as $value) {
					$columnValues[]["ID"] = $value["ID"];
				}
			} else {
				foreach ($array as $key => $value) {
					$columnValues[] = $value["DATA"][$column];
				}
			}
			
			if ($order == "asc") {
				array_multisort($columnValues, SORT_ASC, $array);
			} elseif ($order == "desc") {
				array_multisort($columnValues, SORT_DESC, $array);
			} else {
				throw new Exception("Unknown order parameter $order.");
			}
			
			return $array;
		}
		
		/**
		 * Deletes a database file from the server.
		 *
		 * @return void
		 * @throws Exception
		 */
		public function NWDBDestroy (): void {
			if (!unlink($this->fullPath)) {
				throw new Exception("Couldn't delete database");
			}
		}
		
		/**
		 * Determins the difference between the lenght
		 * of a given string and the maximum lenght and
		 * returns the difference as a string containing
		 * the specified character.
		 *
		 * @param string $string
		 * @param int    $maxLength
		 * @param string $chars The character used for filling.
		 *
		 * @return string
		 */
		protected function placeholder (string $string, int $maxLength, string $chars = " "): string {
			$whiteSpace = "";
			
			if (strlen($chars) > 1) {
				$chars = substr($chars, 0, 1);
			}
			
			$strLenString = strlen($string);
			$neededWhiteSpaces = $maxLength - $strLenString;
			for ($i = 0; $i < $neededWhiteSpaces; $i++) {
				$whiteSpace .= $chars;
			}
			return $whiteSpace;
		}
		
		/**
		 * Draws a line containing the specified characters.
		 *
		 * @param int    $count
		 * @param string $char
		 *
		 * @return string
		 */
		function drawLine (int $count, string $char = "#"): string {
			$line = "";
			for ($i = 0; $i < $count; $i++) {
				$line .= $char;
			}
			return "$line\n";
		}
		
		/**
		 * Draws the database to an ASCII table. Returns the table
		 * on success and false on failure.
		 *
		 * @param int $bound Determines whether the content of the table should be
		 *                   left (any string or integer) or right (1) bound.
		 *
		 * @return string
		 */
		function NWDBDraw (int $bound = 1) {
			$header = "";
			$headLine = "";
			$data = "";
			
			$records = $this->NWDBGetRecords();
			$columns = $this->NWDBGetColumns();
			$infos = $this->NWDBInfo();
			$countColumns = count($columns);
			if ($idLenght = strlen($infos["LastRecordID"]) == 1) {
				$idLenght = 2;
			}
			$columnLengths["ID"] = $idLenght;
			foreach ($columns as $column) {
				$columnLengths[$column] = strlen($column);
			}
			
			foreach ($records as $record) {
				foreach ($record["DATA"] as $key => $value) {
					$strLen = strlen($value);
					if ($columnLengths[$key] < $strLen) {
						$columnLengths[$key] = $strLen;
					}
				}
			}
			
			//4 for left border and ID column. Right border accomplished with countColumns
			$tableWidth = 4;
			
			foreach ($columnLengths as $length) {
				$tableWidth = $tableWidth + $length;
			}
			
			$tableWidth = $tableWidth + ($countColumns * 3);
			
			// Drawing Header
			
			$headerLength = $tableWidth;
			foreach ($infos as $key => $info) {
				if (strpos($key, "Time")) {
					$info = date("d.m.y h:i", $info);
				}
				$headerLenghtCount = strlen("# $key: $info #");
				if ($headerLenghtCount > $headerLength) {
					$headerLength = $headerLenghtCount;
				}
				if (strpos($key, "Time")) {
					$info = date("d.m.y h:i", $info);
				}
				$header .= "# $key: $info" . $this->placeholder("# $key: $info#", $headerLength) . "#\n";
			}
			
			// Drawing Column Names = Headline
			
			$headLine .= "# ID #";
			foreach ($columns as $value) {
				$whiteSpace = $this->placeholder("$value", $columnLengths[$value]);
				if ($bound == 1) {
					$headLine .= "$whiteSpace $value #";
				} else {
					$headLine .= " $value$whiteSpace #";
				}
			}
			$headLine .= "\n#";
			foreach ($columnLengths as $length) {
				$headLine .= "-";
				for ($i = 0; $i < $length; $i++) {
					$headLine .= "-";
				}
				$headLine .= "-#";
			}
			$headLine .= "\n";
			
			// Drawing Records
			
			foreach ($records as $recordID => $record) {
				$whiteSpaceID = $this->placeholder($recordID, $columnLengths["ID"]);
				if ($bound == 1) {
					$data .= "#$whiteSpaceID $recordID #";
				} else {
					$data .= "# $recordID$whiteSpaceID #";
				}
				foreach ($record["DATA"] as $key => $value) {
					$whiteSpace = $this->placeholder($value, $columnLengths[$key]);
					if ($bound == 1) {
						$data .= "$whiteSpace $value #";
					} else {
						$data .= " $value$whiteSpace #";
					}
				}
				$data .= "\n";
			}
			
			$line = $this->drawLine($tableWidth);
			$headerLine = $this->drawLine($headerLength);
			
			return "<pre>$headerLine$header$headerLine\n$line$headLine$data$line</pre>";
		}
	}