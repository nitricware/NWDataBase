<?php
	/** @noinspection PhpPossiblePolymorphicInvocationInspection */
	
	namespace NitricWare;
	
	use DOMDocument;
	use DOMXPath;
	use Exception;
	
	/**
	 * NitricWare presents
	 * NWDatabase
	 *
	 * 2014
	 *
	 * XML based Database System
	 *
	 * Requires
	 * - NWFileOperations.NWFunction
	 * - NWLog.NWFunction
	 *
	 * Version 3.0
	 */
	class NWDatabase {
		private string $dbName;
		private string $path;
		private DOMDocument $dataBase;
		private string $fileName;
		private string $fullPath;
		private string $recordType;
		
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
		function __construct (string $database, string $recordType, string $location = "./", bool $deleteIfExists = false) {
			if (!class_exists($recordType)) {
				throw new Exception("Record type does not exist.");
			}
			
			$this->recordType = $recordType;
			
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
		 * @return NWDBInfo
		 * @throws Exception
		 */
		public function NWDBCreate (bool $deleteIfExists = false): NWDBInfo {
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
			$infos->appendChild($dataBase->createElement("LastRecordID", -1));
			$infos->appendChild($dataBase->createElement("LastColumnID", -1));
			
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
		 * @return NWDBInfo
		 */
		public function NWDBInfo (): NWDBInfo {
			$infos = $this->dataBase->getElementsByTagName("Infos")->item(0);
			
			$dbInfo = new NWDBInfo();
			$dbInfo->name = $infos->getElementsByTagName("Name")->item(0)->nodeValue;
			$dbInfo->createdTime = (int)$infos->getElementsByTagName("CreatedTime")->item(0)->nodeValue;
			$dbInfo->lastEditedTime = (int)$infos->getElementsByTagName("LastEditedTime")->item(0)->nodeValue;
			$dbInfo->lastRecordID = (int)$infos->getElementsByTagName("LastRecordID")->item(0)->nodeValue;
			$dbInfo->lastColumnID = (int)$infos->getElementsByTagName("LastColumnID")->item(0)->nodeValue;
			
			return $dbInfo;
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
			// $nameCheck = preg_match("/^([a-zA-Z]){1}([a-zA-Z0-9]*)$/", $columnName); is marked as "single repetition"
			$nameCheck = preg_match("/^([a-zA-Z])([a-zA-Z0-9]*)$/", $columnName);
			if ($nameCheck === false or $nameCheck == 0) {
				throw new Exception("Your column name is invalid. It must start with a letter and may only consist of letters and numbers.");
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
			
			$oldColumns = $this->NWDBGetColumns();
			$infos = $this->NWDBInfo();
			$records = $this->dataBase->getElementsByTagName("Record");
			$allColumns = $this->dataBase->getElementsByTagName("Columns")->item(0);
			
			$newID = $infos->lastColumnID;
			
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
				$this->NWDBIncrementLastColumnID();
			}
			
			$this->NWDBUpdateEditTime();
			
			return $return;
		}
		
		/**
		 * Returns all column names on success
		 *
		 * @param bool $idIsKey
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
		 * Increments the LastRecordID metadata by 1.
		 *
		 * @return int the updated (incremented) LastRecordID
		 */
		private function NWDBIncrementLastRecordID (): int {
			$infos = $this->NWDBInfo();
			$newID = $infos->lastRecordID + 1;
			
			$lastID = $this->dataBase->getElementsByTagName("LastRecordID")->item(0);
			$newRecordID = $this->dataBase->createElement("LastRecordID", $newID);
			
			$lastID->parentNode->replaceChild($newRecordID, $lastID);
			
			$this->dataBase->formatOutput = true;
			$this->dataBase->save($this->fileName);
			
			return $newID;
		}
		
		/**
		 * Increments the LastColumnID metadata by 1.
		 *
		 * @return void
		 */
		private function NWDBIncrementLastColumnID (): void {
			$infos = $this->NWDBInfo();
			$newID = $infos->lastColumnID + 1;
			
			$infoNode = $this->dataBase->getElementsByTagName("LastColumnID")->item(0);
			$newColumnID = $this->dataBase->createElement("LastColumnID", $newID);
			
			$infoNode->parentNode->replaceChild($newColumnID, $infoNode);
			
			$this->dataBase->formatOutput = true;
			$this->dataBase->save($this->fileName);
		}
		
		/**
		 * This function creates a new record and returns the ID
		 * of the last record on success.
		 *
		 * @param NWDBRecord[] $records
		 *
		 * @return int
		 * @throws Exception
		 */
		public function NWDBInsertRecord (array $records): int {
			foreach ($records as $newRecord) {
				if (get_class($newRecord) == $this->recordType) {
					$newRecord->id = $this->NWDBIncrementLastRecordID();
					// insert Record
					$records = $this->dataBase->getElementsByTagName("Records")->item(0);
					$record = $this->dataBase->createElement("Record");
					$record->setAttribute("id", $newRecord->id);
					
					foreach ($newRecord as $column => $value) {
						if ($column != "id") {
							$record->appendChild($this->dataBase->createElement($column, $value));
						}
					}
					
					$records->appendChild($record);
					$this->dataBase->save($this->fileName);
				} else {
					throw new Exception("Provided record is not of type " . $this->recordType);
				}
			}
			
			$this->NWDBUpdateEditTime();
			
			return $this->NWDBInfo()->lastRecordID;
		}
		
		/**
		 * Returns all records from the database.
		 *
		 * @param bool|int $limit Limits the return array to a specific amount of entries.
		 * @param int      $start
		 *
		 * @return NWDBRecord[]
		 * @throws Exception
		 */
		public function NWDBGetRecords (bool|int $limit, int $start = 0): array {
			$records = $this->dataBase->getElementsByTagName("Record")->count();
			if ($start > $records) {
				throw new Exception("Start position $start is too high.");
			}
			
			if ($limit) {
				$limit = $start + $limit;
				if ($records < $limit) {
					$limit = $records;
				}
			} else {
				$limit = $records;
			}
			
			$returnArray = array();
			
			for ($i = $start; $i < $limit; $i++) {
				$returnArray[] = $this->NWDBGetRecord($i);
			}
			
			return $returnArray;
		}
		
		/**
		 * Returns one single record.
		 *
		 * @param int $id
		 *
		 * @return NWDBRecord
		 * @throws Exception
		 */
		public function NWDBGetRecord (int $id): NWDBRecord {
			$xpath = new DOMXPath($this->dataBase);
			$query = "//Record[@id='$id']";
			if (!$record = $xpath->query($query)) {
				throw new Exception("Record $id not found.");
			}
			
			$record = $record->item(0);
			
			$dbRecord = new ($this->recordType)();
			foreach ($record->childNodes as $value) {
				$recordPropertyName = $value->localName;
				$dbRecord->id = $id;
				$dbRecord->$recordPropertyName = $value->textContent;
			}
			
			return $dbRecord;
		}
		
		/**
		 * Deletes a record and returns information
		 * about the database.
		 *
		 * @param int $id
		 *
		 * @return NWDBInfo
		 * @throws Exception
		 */
		public function NWDBDeleteRecord (int $id): NWDBInfo {
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
		 * @param NWDBRecord $updatedRecord
		 *
		 * @return NWDBRecord
		 * @throws Exception
		 */
		public function NWDBUpdateRecord (NWDBRecord $updatedRecord): NWDBRecord {
			$xpath = new DOMXPath($this->dataBase);
			
			$query = "//Record[@id='" . $updatedRecord->id . "']";
			if (!$record = $xpath->query($query)) {
				throw new Exception("Record " . $updatedRecord->id . " not found.");
			}
			
			$record = $record->item(0);
			
			foreach ($updatedRecord as $column => $value) {
				if ($column != "id") {
					$oldChild = $record->getElementsByTagName($column)->item(0);
					$newChild = $this->dataBase->createElement($column, $value);
					
					$oldChild->parentNode->replaceChild($newChild, $oldChild);
				}
			}
			
			$this->dataBase->formatOutput = true;
			
			$this->dataBase->save($this->fileName);
			
			$this->NWDBUpdateEditTime();
			
			return $this->NWDBGetRecord($updatedRecord->id);
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
		 *                         Search is case-insensitive.
		 *                         TODO: implement ?int (Nullable type)
		 * @param bool|int $limit  Limits the return array to a specific amount
		 *                         of entries.
		 * @param int      $start
		 *
		 * @return NWDBRecord[]
		 * @throws Exception
		 */
		public function NWDBSearch (string $column, string $value, bool $exact = false, bool|int $limit = false, int $start = 0): array {
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
					$returnArray[] = $this->NWDBGetRecord($value->getAttribute('id'));
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
		 * Further Reading: https://stackoverflow.com/questions/4282413/sort-array-of-objects-by-object-fields
		 *
		 * @param NWDBRecord[] $records
		 * @param string       $column Select the column you want for ordering.
		 *                             TODO: PHP 8.1 ENUMs
		 * @param string       $order
		 *
		 * @return NWDBRecord[]
		 * @throws Exception
		 */
		public function NWDBSortResult (array $records, string $column = "id", string $order = NWDBSortOrder::ASC): array {
			if (!property_exists($this->recordType, $column)) {
				throw new Exception("Column $column does not exist.");
			}
			
			if ($order == NWDBSortOrder::ASC) {
				usort($records, fn($a, $b) => strtolower($a->$column) > strtolower($b->$column) ? 1 : -1
				);
			} elseif ($order == NWDBSortOrder::DESC) {
				usort($records, fn($a, $b) => strtolower($a->$column) < strtolower($b->$column) ? 1 : -1
				);
			}
			
			return $records;
			
			/*
			if ($column == "id") {
				foreach ($array as $value) {
					$columnValues[]["ID"] = $value["ID"];
				}
			} else {
				foreach ($records as $record) {
					foreach ($record as $column => $value) {
						$columnValues[] = $record->$column;
					}
				}
			}
			
			if ($order == "asc") {
				array_multisort($columnValues, SORT_ASC, $records);
			} elseif ($order == "desc") {
				array_multisort($columnValues, SORT_DESC, $records);
			} else {
				throw new Exception("Unknown order parameter $order.");
			}
			
			return $records;
			*/
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
			if (strlen($chars) > 1) {
				$chars = substr($chars, 0, 1);
			}
			
			$strLenString = strlen($string);
			$neededWhiteSpaces = $maxLength - $strLenString;
			return str_repeat($chars, $neededWhiteSpaces);
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
			$line = str_repeat($char, $count);
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
		 * @throws Exception
		 */
		function NWDBDraw (int $bound = 1): string {
			$header = "";
			$headLine = "";
			$data = "";
			
			$records = $this->NWDBGetRecords(false);
			$columns = $this->NWDBGetColumns();
			$infos = $this->NWDBInfo();
			$countColumns = count($columns);
			
			if ($idLength = strlen($infos->lastRecordID) == 1) {
				$idLength = 2;
			}
			
			$columnLengths["ID"] = $idLength;
			foreach ($columns as $column) {
				$columnLengths[$column] = strlen($column);
			}
			
			foreach ($records as $record) {
				foreach ($record as $key => $value) {
					$strLen = strlen($value);
					if ($key != "id" && $columnLengths[$key] < $strLen) {
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
				$headerLengthCount = strlen("# $key: $info #");
				if ($headerLengthCount > $headerLength) {
					$headerLength = $headerLengthCount;
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
				$headLine .= str_repeat("-", $length);
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
				foreach ($record as $key => $value) {
					if ($key != "id") {
						$whiteSpace = $this->placeholder($value, $columnLengths[$key]);
						if ($bound == 1) {
							$data .= "$whiteSpace $value #";
						} else {
							$data .= " $value$whiteSpace #";
						}
					}
				}
				$data .= "\n";
			}
			
			$line = $this->drawLine($tableWidth);
			$headerLine = $this->drawLine($headerLength);
			
			return "<pre>$headerLine$header$headerLine\n$line$headLine$data$line</pre>";
		}
	}