# MQL to SQL
This is mql-to-sql, a project that provides software to run MQL queries against relational database systems (RDBMS).

## Using a JSON-based query language to query your RDBMS from your AJAX application
MQL is an abbreviation for the Metaweb Query Language. This is the JSON-based native query language of Freebase. SQL is an abbreviation of Structured Query Language, which is the de-facto standard query language for relational database systems (RDBMS). This project is mql-to-sql. It provides software to implement webservices that allow you to execute MQL queries against a relational database.

## Why MQL, and why for an RDBMS?
The main objective for developing mql-to-sql is to have a generic and securable data access solution that plays well with (AJAX-based) web applications. In short, the reasons to develop mql-to-sql are:

## MQL is a great, powerful database query language, especially for AJAX web applications
RDBMS-es are great solutions for storing and structuring data. They are ubiquitous and many AJAX applications need to deal with them.
SQL, the de-facto RDBMS query language may be great for some purposes, but is completely unsuitable and inappropriate for AJAX web applications (for many, too many reasons)
Traditional solutions to data access for AJAX web applications rely on special purpose webservices, which have to be reinvented for every single web application
A MQL to SQL bridge potentially provides a solution to all these issues. It would provide AJAX web applications instant access to the many RDBMS systems out there, without the drawbacks of using SQL, and without having to code a special purpose webservice for each new application.

All in all, these considerations are sufficient justification for creating a solution that allows MQL as a RDBMS query language. You can read more about the goal and purpose of mql-to-sql in this project's wiki pages.

## Learn More
[MQL-to-SQL: A JSON-based query language for your favorite RDBMS - Part I](http://rpbouman.blogspot.nl/2011/01/mql-to-sql-json-based-query-language.html)
[MQL-to-SQL: A JSON-based query language for your favorite RDBMS - Part II](http://rpbouman.blogspot.nl/2011/01/mql-to-sql-json-based-query-language_07.html)
[MQL-to-SQL: A JSON-based query language for your favorite RDBMS - Part III](http://rpbouman.blogspot.nl/2011/01/mql-to-sql-json-based-query-language_4061.html)
