<?php
	
	namespace NitricWare;
	
	/**
	 * NWDBInfo is a struct that holds various information
	 * about the database.
	 */
	class NWDBInfo {
		/**
		 * The name of the database.
		 * @var string
		 */
		public string $name;
		/**
		 * A UNIX timestamp of the creation date of the database.
		 * @var int
		 */
		public int $createdTime;
		/**
		 * A UNIX timestamp of the creation date when the database
		 * was last edited.
		 * @var int
		 */
		public int $lastEditedTime;
		/**
		 * An integer representation of the last ID of the records.
		 * @var int
		 */
		public int $lastRecordID;
		/**
		 * An integer representation of the last ID of the columns.
		 * @var int
		 */
		public int $lastColumnID;
	}