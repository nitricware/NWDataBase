# NWDataBase
## Introduction
NWDataBase is an XML-based database system for small databases. It provides a light weight database solution for webspaces where no SQLite is available. The system provides simple functions, including a search function.

## License
The NWDataBase system is distributed under the **MIT License** which allows you to use it privately and commercially, to distribute, modify and sublicense it. You may not hold me liable and must include my name in the credits of your work.

NWDataBase was created by **Kurt Höblinger** as **NitricWare**.

## Dependencies
Unlike version 1.x, NWDatabase 2.x does not require any additional packages. PHP Extension ```ext-dom``` must be installed however.

## Usage
Use *Composer* to install NWDataBase to your project via the ```composer require nitricware/nwdatabase``` command or your ```composer.json```.

Alternatively you can also just include the NWDataBase.php-file.

```php
require "./path/to/NWDataBase.php";
use NitricWare\NWDataBase;
```

## Functions
For information about the functions of the class, please check the documentation inside the .php-file!

### With NWDataBase you can:
* Create a database
* Create columns
* Insert records
* Fetch records
* Update records
* Delete records
* Search for records
* Draw an ASCII table with the database content
* Delete a database
* Sort a result by a specified column ascending or descending
* Limit a result to a specified amount and start by a specified offset if desired

## Changelog
v2.0
- docker support
- examples added
- PHP 7.4 support
- eliminated the need for dependencies

v1.1.1
- fixed composer

v1.1
- added limit parameter to NWDBSearch and NWDBGetRecords
- the array structure of NWDBGetRecords has changed to match the structure of NWDBSearch
- added the possibility to sort a result
- made NWDataBase available to composer

v1.0.2
- bugfixes
- whitespace fixes

v1.0.1
- fixed XML layout
- using new NWLog 1.0.1

v1.0
- initial release