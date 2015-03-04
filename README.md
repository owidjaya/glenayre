# Manure Order Manager
## Requirements 
To deploy this web application,  it requires the following things:
* Apache: 2.2 or later
* PHP: 5.4 or later
* Curl: curl lib and php curl binding
* Git
* it also needs to setup a project on **Google developer console**
  * Login to **Google developer console**
  * Create a project
  * On **API & auth** section on the left, hit the **Credentials** link
  * Setup OAuth key for **Web Application**
  * In the **Authorized Redirect Uris** box enter the callback to complete the oauth authentication. in this webapp http://<domain>/auth
  * Enter http://<domain> in **Authorized Javascript Origins**
  * Hit the **Download JSON** to save the **key.json** file.
## Installation
* Clone this repository to own local filesystem. once clone is done, this directory will be referenced as <web-app-root>
* Setup Virtual hosts to serve from the <web-app-root>/web
* Place the **key.json** above to <web-app-root>/config

