# Goal and Purpose #
The goal of this project is not to compete with freebase, or re-invent freebase on top of a RDBMS. The goal of this project is to provide a clean, powerful and secure solution for modern (AJAX) web-applications to communicate with relational database backends. MQL fits that bill quite nicely - much better than SQL, and much better than monolithic special-purpose data services. A few arguments in favor of MQL are listed below.

## MQL Advantages ##
The advantage over using MQL instead of a closed, special purpose web service, is that client application developers can freely access the data in a relational database without having to build a new server-side webservice for each function of their app.

At the same time, MQL has considerable advantages over SQL.

It is much easier to secure a MQL webservice than a SQL webservice. a webservice that would allow arbitrary SQL would be extremely difficult to secure against abuse. Checking access privileges against MQL queries is extremely easy.

MQL is by nature much more restricted than SQL. A small mistake in an SQL query (like, a wrong JOIN condition) can easily lead to a runaway query. In MQL, it is literally impossible to write such a query, because all possible relationships are fixed in advance. MQL also does not offer complex functions to format or transform the data. This leaves fewer opportunities for client applications to write heavy queries.

MQL queries offer an extra level of abstraction on top of SQL, which makes it easier to write. For example SQL queries need to explicitly formulate JOIN conditions and subquery correlation conditions to combine data from multiple tables. In MQL, relationships are mapped to properties. This allows an intuitive and declarative way to do the same thing without requiring intimate knowledge of the exact foreign keys that implement these relationships in the underlying database schema.

Finally, MQL offers a straightforward solution to the Object-relational impedance mismatch, but without requiring a generative object-relational mapping. Because MQL queries and results are represented in JSON, it is very easy to work with both queries and data in an object oriented language - in particular Javascript. As such, MQL relieves the need to use a separate Object-relational mapper. Altough some applications may need to augment the JSON data structures with methods to manipulate data, this can be implemented with a very thin layer on the client side.