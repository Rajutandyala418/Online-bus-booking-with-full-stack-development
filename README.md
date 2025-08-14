# Online-bus-booking-with-full-stack-development
Online bus booking application by using php and html and css with the help of mysql as a database to save the customers login details along with booking details.


At first the customer creates a account in the page by using there respective details after creating a account it will go the booking page in that page select the date along with route and then 
it will go the next page then that page select the default bus with default prices and that select the seats based on you choice and then fill the neccessary details

After filling the details go to payment page to complete the booking.

In this i add a customer feedback about the trip and comfort and trip details etc.

and there is a customer support which there any issue occurs while booking a ticket.


Bus Ticket Booking System - Setup Guide
🧾 Requirements:
XAMPP or WAMP (for PHP, Apache, and MySQL)
Web browser
TCPDF library (for PDF generation)


Folder Structure:
arduino
Copy
Edit
BusBookingApp/
├── index.php
├── home.php
├── bus_select.php
├── passenger_info.php
├── payment.php
├── confirmation.php
├── generate_pdf.php
├── config.php
├── tcpdf/         ← download from https://tcpdf.org
└── bus_booking_schema.sql

Install and run XAMPP or WAMP.

Place all files and folders inside: htdocs/BusBookingApp/

Start Apache and MySQL from the XAMPP Control Panel.

Go to http://localhost/phpmyadmin

Create a database named bus_booking.

Import bus_booking_schema.sql into that database.

Open your app in browser: http://localhost/BusBookingApp/index.php

Create an account and start booking.

Apply coupon busparty for 10% off.

After booking, click “Download PDF” to get your ticket.



