# MQL to SQL #
This is mql-to-sql, a project that provides software to run MQL queries against relational database systems (RDBMS).
## Using a JSON-based query language to query your RDBMS from your AJAX application ##
MQL is an abbreviation for the [Metaweb Query Language](http://www.freebase.com/docs/mql/ch03.html). This is the [JSON](http://www.json.org/)-based native query language of [Freebase](http://www.freebase.com/). SQL is an abbreviation of [Structured Query Language](http://en.wikipedia.org/wiki/SQL), which is the de-facto standard query language for [relational database systems](http://en.wikipedia.org/wiki/Relational_database_management_system) (RDBMS). This project is mql-to-sql. It provides software to implement webservices that allow you to execute MQL queries against a relational database.

## Why MQL, and why for an RDBMS? ##
The main objective for developing mql-to-sql is to have a generic and securable data access solution that plays well with (AJAX-based) web applications. In short, the reasons to develop mql-to-sql are:
  * MQL is a great, powerful database query language, especially for AJAX web applications
  * RDBMS-es are great solutions for storing and structuring data. They are ubiquitous and many AJAX applications need to deal with them.
  * SQL, the de-facto RDBMS query language may be great for some purposes, but is completely unsuitable and inappropriate for AJAX web applications (for many, too many reasons)
  * Traditional solutions to data access for AJAX web applications rely on special purpose webservices, which have to be reinvented for every single web application

A MQL to SQL bridge potentially provides a solution to all these issues. It would provide AJAX web applications instant access to the many RDBMS systems out there, without the drawbacks of using SQL, and without having to code a special purpose webservice for each new application.

All in all, these considerations are sufficient justification for creating a solution that allows MQL as a RDBMS query language. You can read more about the goal and purpose of mql-to-sql in this project's [wiki pages](http://code.google.com/p/mql-to-sql/wiki/GoalAndPurpose).
# Learn More #

  * [Goal and Purpose of this project](http://code.google.com/p/mql-to-sql/wiki/GoalAndPurpose) - Explains in detail why MQL on a RDBMS would be good for developing web applications
  * [Getting Started](http://code.google.com/p/mql-to-sql/wiki/GettingStarted) - Explains how to run mql-to-sql on your own system, and how to get a quick start using the [online demo](http://mql.qbmetrix.com/mqlread/mql-to-sql-query-editor.php)
  * [How the MQL to SQL mapping works](http://code.google.com/p/mql-to-sql/wiki/MQLtoSQLMapping) - Explains how mql-to-sql translates MQL queries to SQL
  * [Sample MQL Queries and Online Demo](http://code.google.com/p/mql-to-sql/wiki/SampleMQLQueries) - A mini-tutorial of MQL, which discusses sample queries that you can run right away in the [online demo](http://mql.qbmetrix.com/mqlread/mql-to-sql-query-editor.php)
  * [Presentation](http://www.slideshare.net/rpbouman/mql-tosql-a-jsonbased-rdbms-query-language) - The [MySQL User conference presentation](http://en.oreilly.com/mysql2011/public/schedule/detail/17134) about MQL-to-SQL
