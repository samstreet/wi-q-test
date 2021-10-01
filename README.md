# wi-Q Backend Developer Test

The purpose of this test is to demonstrate your understanding of REST APIs and how you would orchestrate their consumption within an application. Please create a PHP application using whichever tools you feel would be the most suitable to get the job done, we would prefer no framework, but if you have a good reason to use something then feel free. We would encourage the use of other packages that you deem useful.

It is not necessary to create a functioning REST API for this task, we are simply trying to see how you would go about connecting and consuming information from one.

Please only spend 1 to 2 hours at most.

## How to provide your answers

Providing unit/feature tests as your answer is preferred, otherwise you will need to find another way to demonstrate that your solution works.

## Scenario 1

wi-Q is integrating with a fictitious company called 'Great Food Ltd'. 'Great Food Ltd' provide menu and item data from a REST API. Your job is to write some code to consume their API and parse the response into a readable format.

Using the API endpoints described below, write code that would be able to connect to this API and consume the product data for menu with name `Takeaway`. Once the product data has been obtained, print it out in a list, containing the product id and name, such as:


| ID | Name    |
| -- | ------- |
| 4  | Burger  |
| 5  | Chips   |
| 99 | Lasagna |

The available endpoints for their API are the following:
 ### /auth_token
 #### Arguments
 | Name          | Value              |
 | ------------- | ------------------ |
 | client_secret | 4j3g4gj304gj3      |
 | client_id     | 1337               |
 | grant_type    | client_credentials |
 #### Request Type
 `POST`
 #### Headers
 | Name         | Value                             |
 | -------------| --------------------------------- |
 | Content-Type | application/x-www-form-urlencoded |
 #### Response
 This has been provided in `responses/token.json`

 ### /menus
 #### Request Type
 `GET`
 #### Headers
 Authorization:
 | Name          | Value          |
 | ------------- | -------------- |
 | Authorization | Bearer {token} |
 #### Response
 This has been provided in `responses/menus.json`

 ### /menu/{menu_id}/products
 #### Request Type
 `GET`
 #### Headers
 Authorization:
 | Name          | Value          |
 | ------------- | -------------- |
 | Authorization | Bearer {token} |
 #### Response
 This has been provided in `responses/menu-products.json`

## Scenario 2

A customer has been in touch and advised you that product with id 84 in menu 7 has the wrong name. The product is currently named 'Chpis' but it should be named 'Chips'.

Using any of the API endpoints from Scenario 1 and the new method detailed below, write code to demonstrate this item being updated in the 'Great Food Ltd''s API.

 ### /menu/{menu_id}/product/{product_id}
 #### Arguments
 Product model as described in `GET` `/menu/{menu_id}/products` response
 #### Request Type
 `PUT`
 #### Headers
 | Name          | Value          |
 | ------------- | -------------- |
 | Authorization | Bearer {token} |

## When you're done

Please zip up your solution and email it to both sam@wi-q.com and matt@wi-q.com.
