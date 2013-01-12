![""](http://ryanuber.github.com/veneer-swagger/veneer-swagger.png "")

What does it do?
================

veneer-swagger is an extension to [the veneer framework](https://github.com/ryanuber/veneer "")
that will massage the usual endpoint definitions into usable swagger
documentation format. This means you can have web documentation, developer
experimentation tooling, and example client code generation, without writing
any extra code!

What is Swagger?
================

Swagger, per its project page at
[http://swagger.worldnik.com](http://swagger.wordnik.com ""), is:

    a specification and complete framework implementation for describing, producing,
    consuming, and visualizing RESTful web services.

What you need to know is that it's a super-slick collection of HTML, CSS, and
Javascript that creates an explorable REST API experience without much effort.

How do I use veneer-swagger?
============================

A minimal implementation involves:

* Downloading [swagger-v1.php](https://raw.github.com/ryanuber/veneer-swagger/master/swagger-v1.php "")
* Including swagger-v1.php in your code, as you would any other API endpoint
* Installing [swagger-ui](https://github.com/wordnik/swagger-ui "") on a webserver
* Pointing swagger-ui at [your-api-url-here]/v1/swagger

If your API is publicly accessible, you could even "try before you buy" by
visiting [the online demo](http://petstore.swagger.wordnik.com ""), changing
the URL field to point to [your-api-url-here]/v1/swagger, and pressing the
"Explore" button. Instant documentation!

Caveats
=======

Since Swagger is run entirely in your browser, it is susceptible to the
[same origin policy](http://www.w3.org/Security/wiki/Same_Origin_Policy ""). To
work around this (and there are many ways), a few things you might do could include:

* If you are using Apache, add a header to every request within an Apache directory tag:
  `Header set Access-Control-Allow-Origin "*"`
* Within your endpoint code, set an access control header:
  `$this->response->set_header('Access-Control-Allow-Origin: *');`
* Run swagger and your API code within the same domain
