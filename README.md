# par
PHP Active-Record. Does the easy 80% of the OR-mapping. You wouldn't want that last 20% in PHP anyway, now would you? 


=== PHP  PAR (PHP Active-Record.)

Requirements:  PHP 5.x, MySQL.

This is a minimalist framework for PHP object persistence.    PAR offers:

*  Easy mapping of database tables to PHP classes.  

*  Dynamic creation of getter / setter methods for fields in a DB table, no need to write them or
    generate them and save the generated code.
    
*  Schema changes only need to be represented in one place rather than two, keeping your code 'DRY',
    (Don't Repeat Yourself.)  
  
*  Filtering of queries to prevent SQL injection attacks.

*  Does 80% of what you would expect of an ORM with 80% less code to do it, keeping your simple
    projects simple.
  
Look in the 'example' directory to see how you can use PAR to save  and load objects to a database.  
The 'index.php' file is a   tiny sample application demonstrating use of PAR.

Also see the test suite for more examples of usage.

My goals for this framework were to: 

*  Keep the amount of included and generated PHP to a minimum.
*  Avoid use of many / large configuration and mapping data files.
*  Maximize readability  of the classes  that use the PAR framework.

Ruby's Active-Record was  the main influence.  However I only imitated the core behaviour, because it was
easy to do, and skipped the parts that required a lot of PHP contortions or pre-generated code.  This is by no means 
a full version of Active-Record written in PHP.

I based the concept of column accessors on this article: http://www.ibm.com/developerworks/xml/library/os-php-flexobj/  The 
approach there reminded me of Active-Record, and I was inspired to take it further to the limits of what PHP
would easily allow.  With PHP 5.3.x the code could be further streamlined, but I'm done with PHP for the foreseeable
future and won't be working on this more.

What I have done is remove any requirement for ever declaring column names in PHP code or mapping files, by retrieving the
table metadata from the database.  Also I added standard finder methods that rely on the primary keys of the
tables, using the Rails convention of an 'id' column on every table (which you can over-ride.)  

The library currently requires MySql because of the method of table metadata retrieval  ('show fields from ...'.)  
You could certainly rewrite it a bit to do this in a more generic way.




== LICENSE:

(The MIT License)


Copyright (c) 2008 Colin C. Davis

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.




