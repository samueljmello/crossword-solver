<?php

/**
* 	Puzzle Solver Written for Hacker Rank
*
* 	@package 	crosswordSolver
* 	@author 	Samuel Mello
* 	@version 	1.0.0
*/

// load input from stdin
// un-comment this for hacker rank puzzle
/*
$stdin = '';
while($f = fgets(STDIN)){
    $stdin .= $f;
}
*/

// load test data
// remove this for hacker rank puzzle
$stdin = '+-++++++++
+-++++++++
+-------++
+-++++++++
+-++++++++
+------+++
+-+++-++++
+++++-++++
+++++-++++
++++++++++
AGRA;NORWAY;ENGLAND;GWALIOR';

final class crosswordSolver {

  // props for storage
  public $words = array();
  public $wordString = '';
  public $grid = array();
  public $spaces = array();

  public function __construct($input) {
    // check the input
    if (is_string($input)) {
      // run preparation
      $this->parseInput($input);
      $this->spaces = $this->getSpaces($this->grid);
      $this->results = $this->matchWordsToSpaces($this->spaces, $this->words);
      // check results and throw excption if failed
      if ($this->results['status'] === 0) {
        throw new Exception('Unable to match all words correctly: <pre>' . print_r($this->results) . '</pre>');
      }
      // ouput the results
      $this->output($this->grid, $this->results);
    }
    // error out if input isn't a string
    else {
      throw new Exception('Invalid input provided: ' . $input);
    }
  }

  // parse all the input
  private function parseInput($input) {
    // get the grid from input
    $grid = explode(PHP_EOL, trim($input));
    // pop off the words as the last element of the input
    $wordString = array_pop($grid);
    $this->wordString = $wordString;
    // separate words into values
    $words = explode(';', $wordString);
    $words = array_filter($words);
    $this->words = $words;
    // get the grid into an array
    for ($i = 0; $i < count($grid); $i++) {
      $grid[$i] = str_split(trim($grid[$i]));
    }
    $this->grid = $grid;
  }

  // gets all possible word spaces
  private function getSpaces($grid) {
    // create word spaces container
    $spaces = array();
    // loop through grid for each row
    for ($y = 0; $y < count($grid); $y++) {
      // set flags
      $isX = false;
      // for each column
      for ($x = 0; $x < count($grid[$y]); $x++) {
        // check if this is the start of a word spot
        if ($this->isSpace($grid[$y][$x])) {
          // check if there is another spot to the right to make it a horizontal word
          if (isset($grid[$y][($x + 1)]) && $this->isSpace($grid[$y][($x + 1)]) && !$isX) {
            // set flag to avoid recording of sequential spaces
            $isX = true;
            // set props
            $lengthX = 0;
            $intersects = array();
            // loop rest of the characters horizontally
            for ($pos = $x; $pos < count($grid[$y]); $pos++) {
              if ($this->isSpace($grid[$y][$pos])) {
                if ($this->isSpace($grid[($y+1)][$pos])) {
                  array_push($intersects, array('y' => $y, 'x' => $pos, 'direction' => 'y'));
                }
                $lengthX++;
              }
              // break when finding next gap
              else {
                break;
              }
            }
            // log word space
            array_push($spaces, $this->createSpace('x', $y, $x, $lengthX, $intersects));
          }
          // check if there is another spot below and above to make it a vertical word
          if (isset($grid[($y + 1)][$x]) && $this->isSpace($grid[($y + 1)][$x]) && (!isset($grid[($y - 1)][$x]) || !$this->isSpace($grid[($y - 1)][$x]))) {
            // set props
            $lengthY = 0;
            $intersects = array();
            // loop rest of the characters vertically
            for ($pos = $y; $pos < count($grid); $pos++) {
              if ($this->isSpace($grid[$pos][$x])) {
                if ($this->isSpace($grid[$pos][($x + 1)])) {
                  array_push($intersects, array('y' => $pos, 'x' => $x, 'direction' => 'x'));
                }
                $lengthY++;
              }
              // break when finding next gap
              else {
                break;
              }
            }
            // log word space
            array_push($spaces, $this->createSpace('y', $y, $x, $lengthY, $intersects));
          }
        }
        // for gaps between multiple words on a row
        // set x flag back to false
        elseif ($isX && !$this->isSpace($grid[$y][$x])) {
          $isX = false;
        }
      }
    }
    return $spaces;
  }

  // matches words to their appropriate spaces (heavy lifting)
  private function matchWordsToSpaces($spaces, $words) {
    // set flags to help control recursion
    $status = 0;
    $filledOut = 0;
    // loop through found word spaces
    for ($s = 0; $s < count($spaces); $s++) {
      // if the word has already been matched, move on
      if ($spaces[$s]['word'] !== '') {
        $filledOut++;
        continue;
      }
      // loop through words to find a match
      for ($w = 0; $w < count($words); $w++) {
        // if the length matches, let's continue
        if (strlen($words[$w]) === $spaces[$s]['length']) {
          // set the matches flag to true since they are the same length
          $matched = true;
          // if this word space has intersects, you need to compare them
          if (count($spaces[$s]['intersects']) > 0) {
            // set to false because we need to validate the intersects
            $matched = false;
            // set counts to compare found vs not found
            $foundIntersects = 0;
            $matchedIntersects = 0;
            // loop through intersects to see if they match
            foreach ($spaces[$s]['intersects'] as $int) {
              // set a noFill flag in case no words have been filled out yet
              $noFilled = true;
              // find interesecting word space
              foreach ($spaces as $space) {
                // abort if word isn't filled out
                if ($space['word'] === '') {
                  continue;
                }
                // set our flag since we have one filled in word
                $noFilled = false;
                // abort this word if coordinates match (same word);
                if ($space['x'] === $spaces[$s]['x'] && $space['y'] === $spaces[$s]['y']) {
                  continue;
                }
                // make sure this word space is the same direction as our intersect
                if ($space['direction'] === $int['direction']) {
                  // if the intersect is vertical, we know this is a horizontal word
                  // so we know that the intersection will have the same X axis
                  if ($int['direction'] === 'y' && $int['x'] === $space['x']) {
                    // increase intersects found
                    $foundIntersects++;
                    // parse the two words into arrays
                    $wordLetters = str_split($words[$w]);
                    $compareWordLetters = str_split($space['word']);
                    // subtract the coordinates from the interesect to get the poisition in the words
                    $intersectLetterX = $wordLetters[($int['x'] - $spaces[$s]['x'])];
                    $intersectLetterY = $compareWordLetters[($int['y'] - $space['y'])];
                    // compare to see if the letters match
                    if ($intersectLetterX === $intersectLetterY) {
                      $matchedIntersects++;
                    }
                    // break the word loop since this is the intersection we were looking for
                    break;
                  }
                  // otherwhise if the intersect is horizontal, we know that the X axis will match
                  elseif ($int['direction'] === 'x' && $int['y'] === $space['y']) {
                    // increase intersects found
                    $foundIntersects++;
                    // parse the two words into arrays
                    $wordLetters = str_split($words[$w]);
                    $compareWordLetters = str_split($space['word']);
                    // subtract the coordinates from the interesect to get the poisition in the words
                    $intersectLetterY = $wordLetters[($int['y'] - $spaces[$s]['y'])];
                    $intersectLetterX = $compareWordLetters[($int['x'] - $space['x'])];
                    // compare to see if the letters match
                    if ($intersectLetterX === $intersectLetterY) {
                      $matchedIntersects++;
                    }
                    // break the word loop since this is the intersection we were looking for
                    break;
                  }
                }
              }
              // if no words have been filled out, we need to start somewhere
              // so set the matched flag and let's move forward
              if ($noFilled) {
                $matched = true;
                break;
              }
            }
            // if the amount of intersects found match the matched intersects, set to true
            if ($foundIntersects === $matchedIntersects) {
              $matched = true;
            }
          }
          // detect if this is correct, set & unset values, then recurse
          if ($matched) {
            // make copies of arrays so the originals aren't effected
            $spacesCopy = $spaces;
            $wordsCopy = $words;
            // set / unset properties
            $spacesCopy[$s]['word'] = $words[$w];
            unset($wordsCopy[$w]);
            $wordsCopy = array_values($wordsCopy);
            // recurse
            $returned = $this->matchWordsToSpaces($spacesCopy, $wordsCopy);
            // set status from recursed
            $status = $returned['status'];
            // if the status is successful, overwrite the array with our results
            if ($status === 1) {
              $spaces = $returned['spaces'];
            }
          }
          // set status to 0 since no match was found (steps back up and continues with recursion)
          else {
            $status = 0;
          }
        }
        // break the word loop if successful
        if ($status === 1) {
          break;
        }
      }
      // break the space loop if successful
      if ($status === 1) {
        break;
      }
    }
    // if all spaces have been filled out, there's no reason to continue. 
    // set our status to completed and go back
    if (count($spaces) === $filledOut) {
      $status = 1;
    }
    // return the array at the end
    return array('status' => $status, 'spaces' => $spaces);
  }

  // outputs the results
  public function output($grid, $results) {
    // set output
    $output = '';
    // for each row
    for ($y = 0; $y < count($grid); $y++) {
      // for each column
      for ($x = 0; $x < count($grid[$y]); $x++) {
        // if this is a word space
        if ($this->isSpace($grid[$y][$x])) {
          // set found flag for printing gaps
          $found = false;
          // throw exception if invalid input is provided
          if (!isset($results['spaces']) || count($results['spaces']) <= 0) {
            throw new Exception('You must provide a valid results set from the method "matchWordsToSpaces". Provided: <pre>' . print_r($results) . '</pre>');
          }
          // loop through all of our results
          foreach ($results['spaces'] as $space) {
            // split up the word into letters
            $letters = str_split($space['word']);
            // if this is a horizontal word
            // and the y axis matches
            if ($space['direction'] === 'x' && $y === $space['y']) {
              // get ending index of this word
              $endingSpot = ($space['x'] + ($space['length'] - 1));
              // if the x asis is in range
              if ($x >= $space['x'] && $x <= $endingSpot) {
                // set our flags, get the letter for this position, and break
                $found = true;
                $output .= $letters[($x - $space['x'])];
                break;
              }
            }
            // if this is a vertical word
            // and the x axis matches
            elseif ($space['direction'] === 'y' && $x === $space['x']) {
              // get ending index of this word
              $endingSpot = ($space['y'] + ($space['length'] - 1));
              // if the y asis is in range
              if ($y >= $space['y'] && $y <= $endingSpot) {
                // set our flags, get the letter for this position, and break
                $found = true;
                $output .= $letters[($y - $space['y'])];
                break;
              }
            }
          }
          // print gap if nothing found (should fail solution)
          if (!$found) {
            $output .= '-';
          }
        }
        // print space for non-word spot
        else {
          $output .= "+";
        }
      }
      $output .= PHP_EOL;
    }
    echo trim($output);
  } 

  // detect if char is a letter space
  private function isSpace($char) {
    if ($char === '-') {
      return true;
    }
    return false;
  }

  // creates space associative array for storage
  private function createSpace($dir, $y, $x, $length, $intersects) {
    return array(
      'direction' => $dir,
      'y' => $y,
      'x' => $x,
      'length' => $length,
      'word' => '',
      'intersects' => $intersects
    );
  }

}

// process
new crosswordSolver($stdin);