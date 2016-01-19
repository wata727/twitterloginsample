<?php

class Controller_Test extends Controller
{
  public function action_ci($arg = NULL)
  {
    if ($arg !== NULL) {
      echo "Not NULL!!";
    } else {
      echo 'NULL!';
    }
  } 
}
