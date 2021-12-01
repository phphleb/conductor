 ### Use of mutexes in a project (including projects based on the HLEB micro framework)
 
The use of mutexes is worthwhile in cases, when access to any code is to be locked, until it is executed in the current process or the set locking time period expires. 
For example, repetitive simultaneous API requests can cause a parallel recording one and the same value into the data base. In order to avoid such event, a section of the code responsible for recording is to be transformed by mutex methods. There are only three such mutex methods: `acquire`, `release` and `unlock`.

  
 ### FileMutex
```php
use \Phphleb\Conductor\FileMutex;

$mutex = new FileMutex();
if ($mutex->acquire('mutex-name', 20)) { // Start blocking
       try {
       // Custom code.

       } catch (\Throwable $e) {
          $mutex->unlock('mutex-name'); // Force unlock
          throw $e;
       }
   } else {
       throw new \Exception('Custom error text.');
   }
if (!$mutex->release('mutex-name')) { // End of blocking
   // Rolling back transactions
}

```

When setting the time period for locking (the second argument `acquire` in seconds), it should be taken into account that, if an active process is unable to unlock the mutex on its own, other processes from the **non-sequential** queue that have addressed to this code will continue working only after this time period has expired. That is why they will be completed, in a case of a long delay, at the web server level by timeout waiting for a response from the script. 

#### Installation in a project based on the framework HLEB

 ```bash
 $ composer require phphleb/mutex
```
Create a console commands `php console mutex/mutex-db-stat-task` and `php console mutex/mutex-file-stat-task` to get statistics on active mutexes:
 ```bash
 $ php console phphleb/mutex --add

 $ composer dump-autoload
 ```
#### Installation in another project

Using Composer (or copy the files into the ** vendor ** folder of the project):
 ```bash
 $ composer require phphleb/mutex
```

Own configuration (to be installed once):

```php
use \Phphleb\Conductor\FileMutex;

$config = new MainConfig(); // implements FileConfigInterface, BaseConfigInterface
$mutex = new FileMutex($config);

```

Files of a mutex type are usually applied only for one backend server; otherwise, you can try to synchronize the folder with the tag files of mutexes.
However, if it is possible, it will be better to use mutexes based on storing the tags in the data base.


 ### DbMutex
 
The locks with the stored status in the data bases – similar implementation of mutexes. The same methods – `acquire`, `release` и `unlock` – are used, as well as connecting your own configuration. 
The difference lies in the class used for initializing the mutex.


```php
use \Phphleb\Conductor\DbMutex;

$mutex = new DbMutex();

```

 ### PredisMutex
 
 Redis is connected in the same way.
 
 ```php
 use \Phphleb\Conductor\PredisMutex;
 
 $mutex = new PredisMutex();
 
 ```
By default, the configuration settings are taken from the `HLEB_MUTEX_TYPE_REDIS` or `HLEB_TYPE_DB` constant (database/dbase.config.php).



 
-----------------------------------

[![License: MIT](https://img.shields.io/badge/License-MIT%20(Free)-brightgreen.svg)](https://github.com/phphleb/draft/blob/main/LICENSE) ![PHP](https://img.shields.io/badge/PHP-^7.4.0-blue) ![PHP](https://img.shields.io/badge/PHP-8-blue)
