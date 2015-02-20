<?php
require_once(dirname(__FILE__) . "/../par.php");

class MovieReview extends Par{
  function __construct(){parent::__construct( 'movie_reviews'); $this->child_class = __CLASS__;}   
  
  // Has one reviewer
  public function reviewer(){
    $finder = new Reviewer();
    return $finder->find($this->reviewer_id);
  }
  
  // Has one movie
  public function movie(){
    $finder = new Movie();
    return $finder->find($this->movie_id);
  }
}

class Movie extends Par{
  function __construct(){parent::__construct( 'movies'); $this->child_class = __CLASS__;}   
  
  public static function find_first_by_title($title){
    $movie = new Movie();
    return $movie->find_first_by("title",$title);
  }
  
  public static function find_all(){
    $finder = new Movie();
    return $finder->find_all_in_order("title asc");
  }
  
  public function reviews(){
    $finder = new MovieReview();
    return $finder->find_by("movie_id", $this->id());
  }
  
  public function reviewers(){
    $finder = new Reviewers();
    return $finder->find_by("movie_id", $this->id());
  }
}

class Reviewer extends Par{
  function __construct(){parent::__construct( 'reviewers'); $this->child_class = __CLASS__;}   
  
  public function add_review($unsaved_review){
    $unsaved_review->reviewer_id = $this->id();
    
    // we assume the movie_id has been set as a prerequisite to getting here
    $unsaved_review->create();
  }
  
  public function reviews(){
    $finder = new MovieReview();
    return $finder->find_by("reviewer_id", $this->id());
  }
}



?>