<?

namespace iFixit\TokenBucket;

use \InvalidArgumentException;

/**
 * Defines a rate of tokens per second. Specify the tokens you want to
 * allow for a given number of seconds.
 */
class TokenRate {
   private $rate;

   public function __construct($tokens, $seconds) {
      if (!is_int($tokens)) {
         throw new InvalidArgumentException("Tokens must be an int");
      }
      if (!is_int($seconds)) {
         throw new InvalidArgumentException("Seconds must be an int");
      }

      $this->tokens = $tokens;
      $this->seconds = $seconds;

      if ($this->tokens == 0 || $this->seconds == 0) {
         $this->rate = 0;
      } else {
         $this->rate = (double)$this->tokens / (double)$this->seconds;
      }
   }

   /**
    * @return double rate of token regenerationo
    */
   public function getRate() {
      return $this->rate;
   }

   public function getTokens() {
      return $this->tokens;
   }

   public function getSeconds() {
      return $this->seconds();
   }
}
