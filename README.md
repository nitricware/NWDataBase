# NWDataBase 1.0.1
## Introduction
NWDataBase is an XML-based database system for small databases. It provides a light weight database solution for webspaces where no SQLite is available. The system provides simple functions, including a search function.
## License
The NWDataBase system is distributed under the **MIT License** which allows you to use it privately and commercially, to distribute, modify and sublicense it. You may not hold me liable and must include my name in the credits of your work.
NWDataBase was created by **Kurt Höblinger** as **NitricWare**.
## Requirements
NWDataBase requires NWFileOperations, NWLog (both available on Github) and PHP 5.x.
## Usage
In order to use NWDataBase, you just need to include the .php-file and write the use command.
```php
require "./path/to/NWDataBase.php";
use NitricWare\NWDataBase;
```
Done. No installation required.
## Functions
For information about the functions of the class, please check the documentation inside the .php-file!
### With NWDataBase you can:
* Create a database
* Create columns
* Create rows
* Read rows
* Update rows
* Delete rows
* Search for rows
* Draw an ASCII table with the database content
* Delete a database

## Changelog
v1.0.1
- fixed XML layout
- using new NWLog 1.0.1

v1.0
- initial release