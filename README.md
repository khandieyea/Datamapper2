DataMapper 2 - An ORM for CodeIgniter v2.x
==========================================

DataMapper is an Object Relational Mapper written in PHP for CodeIgniter. It is designed to map your Database tables into easy to work with objects, fully aware of the relationships between each other.

> Note:
> this version is currently under development, and currently not useable in any shape or form!


Installation
------------

There are five steps you have to take to enable the use of DataMapper in your application:

1. Make sure you have at least version 2.0 of CodeIgniter (Core or Reactor)

2. Make sure your PHP version is at least 5.2

3. Install the DataMapper bootloader in your application's index.php file

			// add this, just before the CodeIgniter bootstrap is loaded
			require_once APPPATH.'third_party/datamapper/bootstrap.php';

4. Make sure the DataMapper package is added

			// add the datamapper package
			$this->load->add_package_path(APPPATH.'third_party/datamapper');

You can also autoload the package in your configuration file.

5. Load the DataMapper library to activate DataMapper

			// load datamapper
			$this->load->library('datamapper');


Test framework
--------------

You can run the test framework by creating a new controller, and add this to the index method:

		// add the datamapper package
		$this->load->add_package_path(APPPATH.'third_party/datamapper');

		// load the test framework
		$this->load->library('datamapper_tests');

		// and run the tests
		DataMapper_Tests::run();


The MIT License
---------------

Copyright (c) 2011 Harro "WanWizard" Verton

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
