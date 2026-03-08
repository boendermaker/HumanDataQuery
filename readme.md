# Human Data Query with a LLM (ollama) over PHP

This an example how toolcalling and systemprompting with PHP to an LLM-Instance (ollama) can be done.

In this example a two PHP Middlewares receives each Human Input by REST-Request from a HTML-Page, processes the Userinput and puts it into a LLM to transform the Userinput into Machine readable Data, or call Toolfunctions to receive the requested Information by the User, or to do things requested by the user.

Usage:

In any CLI go to the folder containing the docker-compose.yml and enter "docker compose up"

When the images got pulled and ollama also pulled the qwen3 model (5gb) and the environment is running then open your browser and enter

http://localhost:8080/humangquery

or 

http://localhost:8080/toolbridge

To run the two examples


![Flowers](https://www.boendermaker.de/github/humanquery.gif?)
