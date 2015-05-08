<?php
	/*
	
		NitricWare presents
		NWDatabase
		
		2014
		
		XML based Database System
		
		Requires
		- NWFileOperations.NWFunction
		- NWLog.NWFunction
		
		Version 1.0 rev 3
		
	*/
	
	class NWDatabase {
		private $dbName;
		private $path;
		private $errorArray = array();
		private $dataBase;
		private $fileName;
		
		/*
			$dataBase
				Name of the database without file extension.
				Example
					Filename: Database.nwdb
					$dataBase: Database
			
			$location
				Full path to the Database. Default is ./ - same as script
			
			Class will return false if the path given creates an error.
		*/
		
		function __construct($database, $location = "./"){
			$this->dbName = $database;
			$this->path = $location;
			
			if (!$this->path = NWPathComplete($this->path)) return false;
			if (!$fileName = NWFileExists($this->dbName,"nwdb",$this->path)){
				if (!$this->NWDBCreate()){
					NWWriteLog("Creation of $this->dbName failed.");
					return false;
				}
			}
			
			$this->fileName = NWFileExists($this->dbName,"nwdb",$this->path);
			
			$this->dataBase = new DOMDocument();
			$this->dataBase->load($this->fileName);
		}
		
		/*
			This functions returns an array containing all the errors
			that happened so far. Returns false if no error happened.
		*/
		
		public function displayError(){
			if (count($this->errorArray) < 1){
				return false;
			}
			return $this->errorArray;
		}
		
		/*
			Allows functions of the class to add an occured error to
			the error array. Error array can be displayed using the
			displayError() function.
		*/
		
		protected function addError($number, $message){
			$this->errorArray[] = array($number => $message);
			NWWriteLog("$number $message");
			return true;
		}
		
		/*
			NWDBCreate
				Creates a blank database with no columns.
				This function returns an array, containing information
				about the database on success and false on failure.
			
			$deleteIfExists
				true: checks whether the database you tried to create
					is already in use or not and deletes it, if it
					is.
				flase: does not check whether the database you tried
					to create is already in use or not and the function
					will return false if it is.
		*/
		
		public function NWDBCreate($deleteIfExists=false){
			if (NWFileExists($this->dbName,"nwdb",$this->path)){ 
				if (!$deleteIfExists){
					if (DEBUG) $this->addError(1, "Database already exists. Pick another path or another filename.");
					return false;
				} else {
					if (!unlink("$this->path$this->dbName.nwdb")){
						if (DEBUG) $this->addError(20, "Database could not be deleted. Check permissions.");
						return false;
					}
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
		
		/*
			NWDBInfo
				Returns an array containing information about the
				Database or false if an error occured.
		*/
		
		public function NWDBInfo(){
			$infos = $this->dataBase->getElementsByTagName("Infos")->item(0);
			$returnArray = array();
			$returnArray["Name"] = $infos->getElementsByTagName("Name")->item(0)->nodeValue;
			$returnArray["CreatedTime"] = $infos->getElementsByTagName("CreatedTime")->item(0)->nodeValue;
			$returnArray["LastEditedTime"] = $infos->getElementsByTagName("LastEditedTime")->item(0)->nodeValue;
			$returnArray["LastRecordID"] = $infos->getElementsByTagName("LastRecordID")->item(0)->nodeValue;
			$returnArray["LastColumnID"] = $infos->getElementsByTagName("LastColumnID")->item(0)->nodeValue;
			
			return $returnArray;
		}
		
		/*
			NWDBUpdateEditTime
				Gives functions of the class the possibility to
				update the timestamp on last edit. Return false
				on failure and true on success.
		*/
		
		protected function NWDBUpdateEditTime(){
			$lastTime = $this->dataBase->getElementsByTagName("LastEditedTime")->item(0);
			$newTime = $this->dataBase->createElement("LastEditedTime",time());
			
			$lastTime->parentNode->replaceChild($newTime, $lastTime);
			
			$this->dataBase->formatOutput = true;
			
			$this->dataBase->save($this->fileName);
			
			return true;
		}
		
		/*
			checkColumnName
				Gives functions of the class the possibility to
				check whether the name of a column is useable or
				not. Returns false on failure and true on success.
			
			$columnName
				The name of the column to check.
		*/
		
		protected function checkColumnName($columnName){
			$nameCheck = preg_match("/^([a-zA-Z]){1}([a-zA-Z0-9]*)$/", $columnName);
			if ($nameCheck === false or $nameCheck == 0){
				if (DEBUG) $this->addError(5, "Your columnname is invalid. It must start with a letter and may only consist of letters and numbers.");
				return false;
			} else {
				return true;
			}
		}
		
		/*
			NWDBInsertColumn
				Adds columns to the document tree. Returns an array
				with the IDs of the added columns on success or
				false on failure.
			
			$columnArray
				This array must be 1D and can contain multiple
				column names.
		*/
		
		public function NWDBInsertColumn($columnArray){
			if (is_string($columnArray)){
				if (DEBUG) $this->addError(22, "$columnArray must be an array.");
				return false;
			}
			if (is_array($columnArray)){
				foreach ($columnArray as $column) {
					if ($this->checkColumnName($column)){
						$columns[] = strtolower($column);
					}
				}
			}
			
			$oldColumns = $this->NWDBGetColumns(false);
			$infos = $this->NWDBInfo();
			$records = $this->dataBase->getElementsByTagName("Record");
			$allColumns = $this->dataBase->getElementsByTagName("Columns")->item(0);
			
			$newID = $infos["LastColumnID"];
			
			foreach ($columns as $columnName){
				if (in_array($columnName,$oldColumns)){
					if (DEBUG) $this->addError(6, "Column $columnName already exists.");
					return false;
				}
				
				$newID = $newID+1;
				
				$return[] = $newID;
				
				$newColumn = $this->dataBase->createElement("column",$columnName);
				$newColumn->setAttribute("id", $newID);
				
				$allColumns->appendChild($newColumn);
				
				// update existing Entries
				
				foreach ($records as $record){
					$record->appendChild($this->dataBase->createElement($columnName));
				}
			}
			
			// update LastColumnID
				
			$infoNode = $this->dataBase->getElementsByTagName("LastColumnID")->item(0);
			$newColumnID = $this->dataBase->createElement("LastColumnID",$newID);
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
		
		public function NWDBGetColumns (){
			$columns = $this->dataBase->getElementsByTagName("column");
			
			$returnArray = array();
			
			foreach ($columns as $column){
				if (!$idIsKey){
					$returnArray[] = $column->nodeValue;
				} else {
					$returnArray[$column->getAttribute("id")] = $column->nodeValue;
				}
			}
			
			return $returnArray;
		}
		
		/*
			NWDBInsertRecord
				This function creates a new record and returns the ID
				of the last record on success or false on failure.
			
			$values
				This must ne an array and can be 1D or 2D.
				
			$firstRun
				Value is set by the function.
				true: Array is given and not yet recursively checked
				false: function detected a 2D array and inserts
					values recursively
		*/
		
		public function NWDBInsertRecord($values, $firstRun = true){
			if (is_array($values[0])){
				if (!firstRun){
					if (DEBUG) $this->addError(23, "Array may not have more than 2 dimensions.");
					return false;
				}
				// Multi Dimensional Array Detected
				foreach ($values as $record){
					$this->NWDBInsertRecord($record);
				}
			} else {
				$columns = $this->NWDBGetColumns(false);
				$nrV = count($values);
				$nrC = count($columns);
				if ($nrV != $nrC){
					if (DEBUG) $this->addError(9, "Number of values ($nrV) does not match number of columns ($nrC).");
					return false;
				}
				
				$infos = $this->NWDBInfo();
				$newID = $infos["LastRecordID"]+1;
				
				// update LastRecordID
				
				$lastID = $this->dataBase->getElementsByTagName("LastRecordID")->item(0);
				$newColumnID = $this->dataBase->createElement("LastRecordID",$newID);
				
				$lastID->parentNode->replaceChild($newColumnID, $lastID);
				
				// insert Record
				$records = $this->dataBase->getElementsByTagName("Records")->item(0);
				$record = $this->dataBase->createElement("Record");
				$record->setAttribute("id", $newID);
				for ($i = 0; $i < $nrC; $i++){
					if($i >= $nrV){
						$value = "";
					} else {
						$value = $values[$i];	
					}
					$record->appendChild($this->dataBase->createElement($columns[$i], $value));
				}
				
				$records->appendChild($record);		
				
				$this->dataBase->formatOutput = true;
				$this->dataBase->save($this->fileName);
			}
			
			$this->NWDBUpdateEditTime();
			
			return $newID;
		}
		
		/*
			NWDBGetRecords
				Returns all records from the database on success
				and false on failure.
		*/
		
		public function NWDBGetRecords(){
			$columns = $this->NWDBGetColumns();
			
			$records = $this->dataBase->getElementsByTagName("Record");
			
			$returnArray = array();
			
			foreach ($records as $record){
				foreach ($columns as $column){
					$columnLine = $record->getElementsByTagName($column);
					
					@$returnArray[$record->getAttribute("id")][$column] = $columnLine->item(0)->nodeValue;
				}
			}
			return $returnArray;
		}
		
		/*
			NWDBGetRecord
				Returns one single record on success and false
				on failure.
				
			$id
				Specifies the ID of the requested record.
		*/
		
		public function NWDBGetRecord ($id){
			$xpath = new DOMXPath($this->dataBase);
			
			$query = "//Record[@id='$id']";
			if (!$record = $xpath->query($query)){
				if (DEBUG) $this->addError(12, "Record $id not found.");
				return false;
			}
			
			$record = $record->item(0);
			
			$returnArray = array();
			
			foreach ($record->childNodes as $value){
				$returnArray[$value->localName] = $value->textContent;
			}
			
			return $returnArray;
		}
		
		/*
			NWDBDeleteRecord
				Deletes a record and returns information
				about the database on success and false
				on failure.
			$id
				Specifies the ID of the record you want to delete.
		*/
		
		public function NWDBDeleteRecord($id){
			$xpath = new DOMXPath($this->dataBase);
			
			$query = "//Record[@id='$id']";
			$search = $xpath->query($query);
			
			if($search->length == 0){
				if (DEBUG) $this->addError(14, "Record with ID $id does not exist.");
				return false;
			}
			
			$result = $search->item(0);
			$result->parentNode->removeChild($result);
			
			$this->dataBase->formatOutput = true;
			$this->dataBase->save($this->fileName);
			
			$this->NWDBUpdateEditTime();
			
			return $this->NWDBInfo();
		}
		
		/*
			NWDBUpdateRecord
				Updates a record and returns the updated record
				on success and false on failure.
				
			$id
				Specifies the ID of the record you want to update.
				
			$values
				(array, 1-dimensional)
				Contains the values with the columns as key.
				Example:
					array("column1" => "Value 1", "column2" => "Value 2");
		*/
		
		public function NWDBUpdateRecord($id, $valuesArray){
			$xpath = new DOMXPath($this->dataBase);
			
			$query = "//Record[@id='$id']";
			if (!$record = $xpath->query($query)){
				if (DEBUG) $this->addError(16, "Record $id not found.");
				return false;
			}
			
			$record = $record->item(0);
			
			foreach ($valuesArray as $key => $value){
				$columns[] = $key;
				$values[] = $value;
			}
			
			$nrV = count($values);
			$nrC = count($columns);
			
			for ($i = 0; $i < $nrC; $i++){
				if($i >= $nrV){
					$value = "";
				} else {
					$value = $values[$i];	
				}
				
				if ($value != false and $value != "false"){
					$oldChild = $record->getElementsByTagName($columns[$i])->item(0);
					$newChild = $this->dataBase->createElement($columns[$i],$value);
					
					$oldChild->parentNode->replaceChild($newChild, $oldChild);
				}
			}
			
			$this->dataBase->formatOutput = true;
			
			$this->dataBase->save($this->fileName);
			
			$this->NWDBUpdateEditTime();
			
			return $this->NWDBGetRecord($id);
		}
		
		/*
			NWDBSearch
				Searches the database for a matching string and returns
				matches on success and false on failure.
			
			$column
				Select the column you want to search in for a string. If
				$exact is false, $column can be "*" to search in all
				columns for $value.
			
			$value
				Specifies the string that you want to search for.
			
			$exact
				true: $colum must exactly contain $value.
			
				false: $column can contain $value and other strings.
					Search is case insensitive.
		*/
		
		public function NWDBSearch($column, $value, $exact = false){
			$xpath = new DOMXPath($this->dataBase);

			if ($exact){
				$query = "//Record[$column='$value']";
			} else {
				$upper = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
				$lower = "abcdefghijklmnopqrstuvwxyz";
				$contains = 'translate(text(),\''.$upper.'\',\''.$lower.'\')';
				$column = strtolower($column);
				$value = strtolower($value);
				$query = '//Record/'.$column.'[contains('.$contains.',\''.$value.'\')]/..';
			}
			
			if ($column == "id") {
				$query = "//Record[@id='$value']";
			}
			
			$record = $xpath->query($query);
			if ($record->length > 0){
				$returnArray = array();
				foreach ($record as $value){
					$dataArray = array();
					$returnArray[] = array("ID" => $value->attributes->item(0)->textContent, "DATA" => $this->NWDBGetRecord($value->getAttribute('id')));
				}
			} else {
				if (DEBUG) $this->addError(19, "No result found.");
				return false;
			}
			
			return $returnArray;
		}
		
		/*
			NWDBDestroy
				Deletes a database file from the server and returns
				true on success and false on failure.
		*/
		
		public function NWDBDestroy(){
			if (!unlink("$this->path$this->dbName.nwdb")){
				if (DEBUG) $this->addError(21, "Database $this->path.$this->dbName.nwdb could not be deleted.");
				return false;
			}
			return true;
		}
		
		/*
			placeholder
				Determins the difference between the lenght
				of a given string and the maximum lenght and
				returns the difference as a string containing
				the specified character.
			
			$string
				The string.
				
			$maxLenght
				An Integer that specifies the maximum lenght.
				
			$chars
				The character that shall be used for filling.
		*/
		
		protected function placeholder($string, $maxLenght, $chars = " "){
			$whiteSpace = "";
			
			if (strlen($chars) > 1){
				$chars = substr($chars, 0, 1);
			}
			
			$strLenString = strlen($string);
			$neededWhiteSpaces = $maxLenght-$strLenString;
			for ($i = 0; $i < $neededWhiteSpaces; $i++) {
				$whiteSpace .= $chars;
			}
			return $whiteSpace;
		}
		
		/*
			drawLine
				Draws a line containing the specified characters.
			
			$count
				The line shall constist of how many chars?
			
			$char
				Which chars shall be used?
		*/
		
		function drawLine($count, $char = "#"){
			for ($i = 0; $i < $count; $i++) {
				$line .= $char;
			}
			return "$line\n";
		}
		
		/*
			NWDBDraw
				Draws the database to an ASCII table. Returns the table
				on success and false on failure.
			
			$bound
				Determines whether the content of the table should be
				left (any string or integer) or right (1) bound.
		*/
		
		function NWDBDraw($bound = 1){		
			$records = $this->NWDBGetRecords();
			$columns = $this->NWDBGetColumns();
			$infos = $this->NWDBInfo();
			$countColumns = count($columns);
			if ($idLenght=strlen($infos["LastRecordID"]) == 1){
				$idLenght = 2;
			}
			$columnLenghts["ID"] = $idLenght;
			foreach($columns as $column){
				$columnLenghts[$column] = strlen($column);
			}
			
			foreach ($records as $record){
				foreach($record as $key => $value){
					$strLen = strlen($value);
					if ($columnLenghts[$key] < $strLen){
						$columnLenghts[$key] = $strLen;
					}
				}
			}
			
			//4 for left border and ID column. Right border accomplished with countColumns
			$tableWidth = 4;
			
			foreach($columnLenghts as $lenght){
				$tableWidth = $tableWidth+$lenght;
			}
			
			$tableWidth = $tableWidth+($countColumns*3);
			
			// Drawing Header
			
			$headerLenght = $tableWidth;
			foreach ($infos as $key => $info){
				if (strpos($key, "Time")){
					$info = date("d.m.y h:i", $info);
				}
				$headerLenghtCount=strlen("# $key: $info #");
				if ($headerLenghtCount > $headerLenght){
					$headerLenght = $headerLenghtCount;
				}
			}
			
			foreach ($infos as $key => $info){
				if (strpos($key, "Time")){
					$info = date("d.m.y h:i", $info);
				}
				$header .= "# $key: $info".$this->placeholder("# $key: $info#", $headerLenght)."#\n";
			}
			
			// Drawing Column Names = Headline
			
			$headLine .= "# ID #";
			foreach($columns as $value){
				$whiteSpace = $this->placeholder("$value", $columnLenghts[$value]);
				/*$whiteSpace = "";
				$strLenValue = strlen($value);
				$neededWhiteSpaces = $columnLenghts[$value]-$strLenValue;
				for ($i = 0; $i < $neededWhiteSpaces; $i++) {
					$whiteSpace .= " ";
				}*/
				if ($bound == 1){
					$headLine .= "$whiteSpace $value #";
				} else {
					$headLine .= " $value$whiteSpace #";
				}
			}
			$headLine .= "\n#";
			foreach ($columnLenghts as $lenght){
				$headLine .= "-";
				for ($i = 0; $i < $lenght; $i++) {
					$headLine .= "-";
				}
				$headLine .= "-#";
			}
			$headLine .= "\n";
			
			// Drawing Records
			
			foreach($records as $recordID => $record){
				$whiteSpaceID = $this->placeholder($recordID, $columnLenghts["ID"]);
				if ($bound == 1){
					$data .= "#$whiteSpaceID $recordID #";
				} else {
					$data .= "# $recordID$whiteSpaceID #";
				}
				foreach ($record as $key => $value){
					$whiteSpace = $this->placeholder($value, $columnLenghts[$key]);
					/*$whiteSpace = "";
					$strLenValue = strlen($value);
					$neededWhiteSpaces = $columnLenghts[$key]-$strLenValue;
					for ($i = 0; $i < $neededWhiteSpaces; $i++) {
						$whiteSpace .= " ";
					}*/
					if ($bound == 1){
						$data .= "$whiteSpace $value #";
					} else {
						$data .= " $value$whiteSpace #";
					}
				}
				$data .= "\n";
			}
			
			$line = $this->drawLine($tableWidth);
			$headerLine = $this->drawLine($headerLenght);
			
			$return = "<pre>$headerLine$header$headerLine\n$line$headLine$data$line</pre>";
			return $return;
		}
	}

?>