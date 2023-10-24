[German Version](README.md)

# MySQL / PHP csv data import

## The task

A fictitious customer administration software stores contact data in a MySQL database.
Each contact is given a time stamp when it was entered into the database and when the record was last modified.

The following contact data is also recorded:

* salutation
* first name
* last name
* date of birth 
* country
* email
* phone number
* language

In order to be able to synchronize and expand these contacts with external databases, a web-based tool for import of CSV files should be developed.

The tool should be able to process *arbitrarily structured* CSV files with up to 200,000 records per import.

A distinction must be made between new and existing contacts. While new contacts are merely added to the database, existing contacts are to be imported with the new
data are compared and updated if necessary.

Example data to be imported are already uploaded to the server. These files are treated as if they were supplied by different clients.
The file names are

* data_01
* data_02
* data_03
* data_1000
* data_50000

The file extension .csv is internally assumed and should not be entered into the input file field. 

## The implementation

For the backend, an object-oriented PHP approach was used. The Frontend is JavaScript. Communications via JSON / POST.

Focus on the following points:

* large files can be processed
* Duplicate detection is possible
* different data structures in the files can be processed
* Control takes place via a user interface 

