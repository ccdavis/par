<?php
require_once(dirname(__FILE__) . "/../par.php");

$db = new DatabaseConnection('localhost:3307','user', 'movie%reviews', 'moviedb');

$create_movies = "create table movies(
  id int(11) not null auto_increment,
  title varchar(50) not null,
  released int(11),
  genre varchar(50),
  description varchar(255),
  `cast` varchar(255),
  rating int(11),
  imdb_key varchar(255),
  primary key(id)
)";

$create_movie_reviews = "create table movie_reviews(
  id int(11) not null auto_increment,
  movie_id int(11) not null,
  reviewer_id int(11) not null,
  main_text text not null,
  headline varchar(100),
  rating int(11) not null,
  primary key(id)
)";

$create_reviewers = "create table reviewers(
  id int(11) not null auto_increment,
  first_name varchar(50) not null,
  last_name varchar(50) not null,
  email varchar(255) not null,
  `username` varchar(50) not null,
  primary key(id)  
)";

$db->execute("drop table if exists movies");
$db->execute("drop table if exists movie_reviews");
$db->execute("drop table if exists reviewers");

$db->execute($create_movies);
$db->execute($create_movie_reviews);
$db->execute($create_reviewers);
echo "Schema created \n";




?>