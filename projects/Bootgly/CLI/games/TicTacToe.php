<?php
namespace projects\Bootgly\CLI\games;


use Bootgly\CLI\Terminal\Input;
use Bootgly\CLI\Terminal\Input\Mousestrokes;
use Bootgly\CLI\Terminal\Output;
use Bootgly\CLI\Terminal\Reporting\Mouse;


class TicTacToe
{
   private Input $Input;
   private Output $Output;
   private Mouse $Mouse;


   // * Config
   public string $banner;

   // * Data
   // @ Display
   private array $board;
   // @ Player
   private string $player; // X or O
   // @ Mouse
   private array $hovered; // Mouse hovered coordinate

   // * Metadata
   // @ Game
   private bool $end; // End game state


   public function __construct (Input $Input, Output $Output)
   {
      $this->Input = $Input;
      $this->Output = $Output;
      $this->Mouse = new Mouse($Input, $Output);

      // * Config
      $this->banner = <<<OUTPUT
      /* @*:
      * @#green: TicTacToe - v1.0 @;
      * @#yellow: @ Powered by Bootgly CLI (from Bootgly PHP Framework) @;
      * by Rodrigo Vieira [rodrigo@bootly.com]
      * ---
      * @#cyan: Instructions: @;
      * @#cyan: Use the mouse to play! @;
      */\n
      OUTPUT;

      // * Data
      // @ Display
      $this->board = [
         [' ', ' ', ' '],
         [' ', ' ', ' '],
         [' ', ' ', ' ']
      ];
      // @ Player
      $this->player = 'X';
      // @ Mouse
      $this->hovered = [];

      // * Metadata
      // @ Game
      $this->end = false;
   }

   // Board
   private function display ()
   {
      $Output = $this->Output;

      $Output->clear();

      $Output->render($this->banner);

      // @ Print the top edge
      $Output->write("╔═══════════╗\n");

      foreach ($this->board as $row_key => $row) {
         $Output->write("║ ");

         foreach ($row as $col_key => $cell) {
            if ($col_key === 2) {
               $Output->write($cell . " ║ ");
            }
            else {
               $Output->write($cell . " │ ");
            }
         }

         $Output->write("\n");

         // @ Print the intermediate horizontal lines
         if ($row_key < 2) {
            $Output->write("╟───┼───┼───╢\n");
         }
      }

      // @ Print the bottom edge
      $Output->write("╚═══════════╝\n");
   }

   // Mouse
   public function hover (int $x, int $y) // Handle Mouse hover
   {
      // @ Convert mouse coordinates to game grid coordinates
      $col = ($x / 5); // Each cell is 5 characters wide
      $row = ($y - 7) / 3; // 7 is the number of rows in the banner (header)

      // @ Make the move if the cell is empty
      if (@$this->board[$row][$col] == ' ') {
         // @ Clear last hovered if any
         if ($this->hovered !== []) {
            [$row_, $col_] = $this->hovered;

            if ( \strpos($this->board[$row_][$col_], "1;30m") ) {
               $this->board[$row_][$col_] = ' ';
            }
         }

         $this->board[$row][$col] = "\033[1;30m" . $this->player . "\033[0m";
         $this->display();

         // @ Save row and column of the last hovered block
         $this->hovered = [$row, $col];
      }
   }
   public function click (int $x, int $y) // Handle Mouse click
   {
      // @ Convert mouse coordinates to game grid coordinates
      $col = ($x / 5); // Each cell is 5 characters wide
      $row = ($y - 7) / 3; // 7 is the number of rows in the banner (header)

      // @ Make the move if the cell is empty
      if (@$this->board[$row][$col] == "\033[1;30m" . $this->player . "\033[0m") {
         $this->board[$row][$col] = $this->player;
         $this->player = ($this->player == 'X') ? 'O' : 'X';

         $this->display();

         $winner = $this->win();

         if ($winner !== null) {
            $this->Output->render("@.;@#green:Player '$winner' wins!@;@.;" . PHP_EOL);
            $this->end = true;
         }
         else if ($this->tie() === true) {
            $this->Output->render("@.;@#blue:It's a tie!@;@.;" . PHP_EOL);
            $this->end = true;
         }
      }
   }

   // Game
   private function win () : ? string
   {
      for ($i = 0; $i < 3; $i++) {
         if ($this->board[$i][0] == $this->board[$i][1] && $this->board[$i][1] == $this->board[$i][2] && $this->board[$i][0] != ' ') {
            return $this->board[$i][0];
         }
         if ($this->board[0][$i] == $this->board[1][$i] && $this->board[1][$i] == $this->board[2][$i] && $this->board[0][$i] != ' ') {
            return $this->board[0][$i];
         }
      }

      // @ Check Diagonals
      if (($this->board[0][0] == $this->board[1][1] && $this->board[1][1] == $this->board[2][2] && $this->board[0][0] != ' ') ||
         ($this->board[0][2] == $this->board[1][1] && $this->board[1][1] == $this->board[2][0] && $this->board[0][2] != ' ')) {
         return $this->board[1][1];
      }

      return null;
   }
   private function tie () : bool
   {
      foreach ($this->board as $row) {
         if (\in_array(' ', $row)) {
            return false;
         }
      }

      return true;
   }
   public function play () : void
   {
      $Input = &$this->Input;
      $Output = &$this->Output;
      $Mouse = &$this->Mouse;

      $Input->configure(blocking: false, canonical: false, echo: false);

      \pcntl_signal(SIGINT, function (int $signal)
      use ($Input, $Output, $Mouse) {
         $Mouse->report(false);
         $Input->configure();
         $Output->Cursor->show();
         exit;
      });
      \register_shutdown_function(function ()
      use ($Input, $Output, $Mouse) {
         $Mouse->report(false);
         $Input->configure();
         $Output->Cursor->show();
      });

      $this->display();

      $Output->Cursor->hide();

      $Game = &$this;
      $this->Mouse->reporting(function (Mousestrokes $Action, array $coordinate)
         use ($Game) {
            [$col, $row] = $coordinate; // Mouse coordinate (x, y) in real time

            if ($Action === Mousestrokes::NONE_CLICK_WITH_MOVEMENT) {
               $Game->hover($col, $row);
            }

            if ($Action === Mousestrokes::LEFT_CLICK) {
               $Game->click($col, $row);
            }

            return $Game->end !== true;
      });

      $Mouse->report(false);
      $Input->configure();
      $Output->Cursor->show();
   }
}
