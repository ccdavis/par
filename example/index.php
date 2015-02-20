<?php

require_once(dirname(__FILE__) . "/models.php");
require_once(dirname(__FILE__) . "/db_config.php");

$movies = Movie::find_all();


?>

<html>
<body>
<h1> Movie Reviews</h1>
<h3>Movies:  <?= sizeof($movies); ?><br/></h3>
<?php foreach($movies as $movie){?>
<div>
  <span>
   <b>Title </b><?=$movie->title ?> 
  </span>
  </div>
  <div>
  <span>Released: <?=$movie->released ?></span>
  </div>
  <ul> Reviews <br/>
    <?php $reviews = $movie->reviews();?>
    
      <?php if (sizeof($reviews) <1){ ?>
         No reviews yet
      <?php } else
    foreach($reviews as $review){?>
    <li> <?= $review->main_text; ?>   <br/>
    -- <?= $review->reviewer()->first_name ?>
    </li>
    <?php } ?>
  </ul>
  
<?php }?>


</body>
</html>
