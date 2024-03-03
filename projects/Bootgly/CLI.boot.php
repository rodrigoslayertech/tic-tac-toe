<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright 2023-present
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace projects\Bootgly;


use Bootgly\CLI;
use projects\Bootgly\CLI\games\TicTacToe;


// $Commands, $Scripts, $Terminal availables...
// @ Bootgly
$Input = CLI::$Terminal->Input;
$Output = CLI::$Terminal->Output;


$Game = new TicTacToe($Input, $Output);
$Game->play();
