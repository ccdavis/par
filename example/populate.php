<?php

require_once(dirname(__FILE__) . "/models.php");
$db = new DatabaseConnection('localhost:3307','user', 'movie%reviews', 'moviedb');

// In real life you might load from a CSV file, possibly exported from a spreadsheet
// or from HTML forms. 

$movie = new Movie();
$movie->title ="American History X"; 
$movie->released = 1998;
$movie->cast = "Ed Norton, etc.";
$movie->create();

$movie = new Movie();
$movie->title = "Office Space";
$movie->released = 1999;
$movie->create();

$movie = new Movie();
$movie->title = "Mystery Men";
$movie->released = 1997;
$movie->cast = "Ben Stiller, William H. Macy";
$movie->create();

$reviewer = new Reviewer();
$reviewer->first_name = "Colin";	
$reviewer->last_name = "Davis";
$reviewer->username= "ccd";
$reviewer->email="colin.c.davis@gmail.com";
$reviewer->create();

$review = new MovieReview();

$mystery_men = Movie::find_first_by_title("Mystery Men");
$review->movie_id = $mystery_men->id();
$review->main_text = "This is a fantastically funny spoof of  the comic book movie
  with lots of in-jokes.  Stands up to multiple viewings.";
$review->rating = 4;
$review->headline = "Great";

$reviewer->add_review($review);



?>